# Synchro ddmparam ↔ tenants + auto-génération de la programmation de mission

## Ce que j'ai compris de tes deux dumps

- **`ddmparam`** (base centrale) : `audit_types` (AC/AF/AP/AM/RP/ES), `mission_types`
  (Préparation/Réalisation/Conclusion/Suivi/Recommandation), `mission_type_audit_types`
  (pivot), `audit_type_forms` (formulaires/phases par type d'audit × phase_num 1‑5),
  `mission_type_forms`, `tenants` (liste des bases clientes).
- **Chaque tenant** (ex. `fruitiva`) a son **propre** `mission_types` : ce n'est PAS
  une copie 1:1 des 5 types centraux, c'est la liste des **programmes d'audit**
  propres au client (ex. "AGC", "AMP", "AM"...), **chacun rattaché à UN
  `audit_type` central** via les colonnes dénormalisées `audit_type_code /
  audit_type_label / audit_color / audit_icon` (pas de FK cross-DB possible).
- `mission_phases` (tenant) = l'arbre des phases (P1, P2...) pour chaque type
  de mission du tenant, avec `form_code` qui pointe vers
  `ddmparam.audit_type_forms.code`.

**Bug constaté dans ton dump `fruitiva`** : le type de mission `AMP` ("Audit
des Marchés Publics") est rattaché à `audit_type_code = AF` (Fraude) au lieu de
`AM` (Marchés) — preuve que la copie manuelle des champs dénormalisés part en
vrille sans synchro automatique. C'est exactement ce que ce système corrige.

## Ce qui a été livré

| Fichier | Rôle |
|---|---|
| `app/Services/Tenant/TenantReferenceSyncService.php` | Sync `audit_types`→`mission_types` et `audit_type_forms`→`mission_phases`, pour un tenant ou tous |
| `app/Jobs/SyncTenantReferenceDataJob.php` | Job asynchrone (1 par tenant), déclenché par les Observers |
| `app/Observers/AuditTypeObserver.php`, `AuditTypeFormObserver.php`, `MissionTypeObserver.php` | Déclenchent la synchro auto à chaque `save()` côté `ddmparam` |
| `app/Observers/TenantObserver.php` | Synchro initiale complète à la création d'un nouveau tenant |
| `app/Observers/Concerns/DispatchesTenantSync.php` | Trait commun |
| `app/Console/Commands/SyncTenantsReferenceData.php` | `php artisan tenants:sync-reference` (manuel/bootstrap) + `--diagnose` (lecture seule) |
| `app/Services/Mission/MissionProgrammationService.php` | Génère automatiquement `mission_programmation` + `mission_phase_assignments` à la création d'une mission, en proposition modifiable |
| `app/Http/Controllers/Risk/MissionController.php` | `store()` appelle désormais le service ci-dessus ; 2 nouvelles actions `updateProgrammation()` / `confirmProgrammation()` |
| `routes_snippet.php` | 2 routes à ajouter |
| `database/sql/diagnostic_pre_sync.sql` | Requêtes de lecture seule pour repérer les incohérences existantes AVANT le premier sync |

## Flux "création de mission", comme demandé

1. `POST .../missions` → crée `missions`, puis **automatiquement** :
   - `mission_programmation` (dossier d'exécution)
   - `mission_programmation_entity` pour **toutes** les entités sélectionnées
     (correctif au passage : avant, seule la 1ʳᵉ entité était vraiment exploitée
     au-delà de `missions.entity_id`)
   - `mission_phase_assignments` : une ligne par (phase active du type de
     mission × entité). Les phases **obligatoires** (`is_mandatory=1`) sont
     pré-activées (`pending`), les optionnelles sont créées mais `skipped`
     par défaut.
2. La réponse contient une **proposition** (`programmation_proposal`) que le
   front peut afficher pour revue.
3. L'utilisateur ajuste via `PATCH .../programmation/{id}` (activer une phase
   optionnelle, changer le responsable, les dates...).
4. `POST .../programmation/{id}/confirm` bascule la mission en `planifiée`.

*(Étape 2/3 : je n'ai pas reconstruit l'écran de revue Vue — seuls les
endpoints backend sont prêts. Dis-moi si tu veux que je fasse aussi l'écran.)*

## À adapter avant mise en prod

- **`TenantObserver::created()`** : j'ai laissé un commentaire — branche ici
  ta commande de migration existante si le provisioning d'une base tenant se
  fait ailleurs dans le code (je n'ai pas ce fichier).
- **Enregistrer les Observers**, dans `AppServiceProvider::boot()` :
  ```php
  AuditType::observe(AuditTypeObserver::class);
  AuditTypeForm::observe(AuditTypeFormObserver::class);
  MissionType::observe(MissionTypeObserver::class);
  Tenant::observe(TenantObserver::class);
  ```
- **Pivots `mission_type_audit_types` / `mission_type_form_audit_types`** :
  Eloquent ne déclenche pas d'event sur `sync()`/`attach()`. Si ces pivots
  sont modifiés quelque part, ajoute juste après :
  ```php
  app(\App\Services\Tenant\TenantReferenceSyncService::class)->syncAll();
  ```
- **Queue** : les jobs de synchro passent par `ShouldQueue` → assure-toi
  qu'un worker tourne (`php artisan queue:work`), sinon rien ne se passera.
- **Premier lancement** : avant de laisser les Observers tourner seuls,
  exécute une fois `php artisan tenants:sync-reference --diagnose` puis
  `php artisan tenants:sync-reference` pour remettre tous les tenants
  existants à niveau (ça corrigera au passage le bug AMP/AF).
- Adapte les noms de connexion/config tenant (`db_host`, `db_name`...) au
  driver réel que tu utilises si ce n'est pas du MySQL pur.

## Mise à jour : correctif du crash `Database connection [default] not configured`

Ton `.env` et `AuthenticatedSessionController` (que tu as fourni) confirment
l'architecture réelle du projet, différente de ce que j'avais supposé :

- **Une seule connexion Laravel existe : `mysql`** (`DB_CONNECTION=mysql`,
  `DB_DATABASE=ddmparam`). Il n'y a **pas** de connexion nommée `default`.
- La connexion `tenant` que `MissionController` utilise (`DB::connection('tenant')`)
  est configurée **dynamiquement par requête** par un `TenantManager` (visible
  dans tes logs : `TenantManager: Connection established`), pour le tenant de
  l'utilisateur **actuellement connecté**.
- Pour atteindre un tenant **arbitraire** (ex: boucler sur tous les tenants
  pour la synchro, ou consulter un tenant qui n'est pas celui de la session
  en cours), le code existant utilise des **requêtes cross-DB sur la
  connexion `mysql`** avec le nom de la base en dur :
  ```php
  DB::connection('mysql')->table(DB::raw("`{$dbName}`.`table`"))
  ```
  exactement comme le fait déjà `buildUserMenus()`.

**Correctifs appliqués :**
- `TenantReferenceSyncService` : entièrement réécrit pour utiliser ce pattern
  (`central()` = connexion `mysql`, `tenantTable($dbName, $table)` = requête
  cross-DB), au lieu de reconfigurer/purger une connexion `tenant` à chaque
  itération (fragile, et incompatible avec ton architecture réelle).
- `SyncTenantsReferenceData` (commande artisan `--diagnose`) : même correctif.
- `MissionController@create` : `DB::connection('default')` → 
  `DB::connection('mysql')->table('ddmparam.audit_types')`. C'est cette ligne
  précise qui provoquait le crash de ton log.
- `MissionProgrammationService` et le reste de `MissionController` restent
  inchangés : ils s'exécutent dans le contexte d'une requête normale, où
  `DB::connection('tenant')` est déjà correctement configurée par ton
  `TenantManager` pour le tenant de l'utilisateur connecté — pas besoin d'y
  toucher.

## Mise à jour : `getPhasesForType()` ne fait plus confiance à `fruitiva.mission_phases`

Dans `MissionPhaseAffectationController`, la méthode qui charge les phases
pour l'étape 2 (sélection des phases) lisait `label`, `phase_type`,
`sort_order`, `parent_id` **depuis la base tenant** — exactement le genre de
données qui dérive sans synchro (même famille de bug que `AMP`/`AF`).

**Correctif** : `getPhasesForType()` charge maintenant l'**intégralité** de
l'arbre (libellé, type de phase, ordre, hiérarchie parent/enfant) **en direct
depuis `ddmparam.audit_type_forms`**. La table tenant `mission_phases` ne sert
plus que pour deux choses, par `form_code` :
- l'**id** stable, nécessaire à la FK `mission_phase_assignments.mission_phase_id` ;
- les champs réellement **opérationnels** du tenant : `is_mandatory`, `status`.

Une phase qui existe dans `ddmparam` mais pas encore côté tenant (synchro pas
encore passée) s'affiche quand même, avec un id virtuel négatif et
`status = 'not_provisioned'` — mais elle ne pourra être réellement cochée /
sauvegardée qu'une fois provisionnée (lance `php artisan tenants:sync-reference`
si tu en vois apparaître).

## Mise à jour : redesign complet de la vue d'affectation des phases

`Affectation.vue` a été entièrement repris (mêmes props, mêmes endpoints,
même logique métier — rien ne casse côté backend) :

- **Toutes les classes CSS renommées et préfixées `pa-`**, avec un vrai
  système de tokens (couleurs, rayons, ombres) posé une fois en haut du
  `<style>` — plus de couleurs ni d'espacements à deviner en dur un peu
  partout.
- **Icônes cohérentes** : les icônes emoji (⚙ 🔍 📋 📊) des groupes de phase
  sont remplacées par des icônes `ti` alignées avec le reste de l'app.
  Couleur `RECOMMANDATIONS` ajoutée (elle manquait, retombait sur le gris
  par défaut).
- **Phases "non provisionnées" explicites** : une phase renvoyée par
  `ddmparam` mais pas encore créée côté tenant (id virtuel négatif, cf. le
  correctif précédent de `getPhasesForType()`) est maintenant clairement
  badgée *« Non provisionnée »* et **non cochable**, au lieu de pouvoir être
  sélectionnée puis silencieusement rejetée à la sauvegarde.
- **Correctif** : la logique qui force les phases obligatoires était
  dupliquée (une fois au montage, une fois dans `goStep3()`), avec un risque
  de désynchronisation si l'une des deux copies évoluait sans l'autre.
  Fusionnée en une seule fonction `applyMandatoryPhases()`.
- Stepper, panneau de synchro ddmparam, tableau d'affectation, onglets
  d'entités, modale de note : tous redessinés avec une hiérarchie visuelle
  plus nette (titres, badges, espacements) mais un comportement identique.

L'ancien fichier est remplacé — pas de fichier à supprimer côté serveur,
il suffit d'écraser `resources/js/pages/dashboards/Audit/Mission/Phases/Affectation.vue`
avec cette version.

## Mise à jour majeure : `mission_phases` (tenant) pointe directement sur les IDs de `ddmparam`

Demande : charger toutes les phases depuis la base principale, supprimer ce
qui est dupliqué dans le tenant, et faire pointer chaque tenant sur les IDs
de la base principale. C'est un changement de schéma, pas juste de requête :

**Avant** : `fruitiva.mission_phases` avait son propre `id` auto-incrémenté,
et dupliquait tout le contenu (`code`, `label`, `phase_type`, `parent_id`,
`form_code`...) recopié depuis `ddmparam.audit_type_forms` — la source des
bugs de désynchronisation rencontrés depuis le début de cette conversation.

**Après** : `fruitiva.mission_phases.id` **=** `ddmparam.audit_type_forms.id`
directement (même valeur, plus d'auto_increment séparé). La table ne garde
plus que les réglages réellement propres au tenant :

```
mission_phases
  id              -- = ddmparam.audit_type_forms.id
  mission_type_id -- FK tenant
  is_mandatory
  status
  weight
  created_at / updated_at
```

Tout le contenu (libellé, type de phase, hiérarchie, code de formulaire, url
du formulaire...) est désormais lu **en direct** par un simple `JOIN` sur cet
id, partout où c'était nécessaire :

| Fichier | Ce qui a changé |
|---|---|
| `database/sql/migrate_phases_to_central_ids.sql` | Migration à lancer une fois par tenant : remappe les `mission_phase_assignments.mission_phase_id` existants vers les ids centraux (via l'ancien `form_code`), sauvegarde les réglages tenant, reconstruit `mission_phases` avec le nouveau schéma. **Fais un backup avant.** |
| `TenantReferenceSyncService::syncMissionPhasesForTenant()` | Réécrit : provisionne une ligne `mission_phases(id = id ddmparam, mission_type_id, is_mandatory, status, weight)` pour chaque formulaire actif, sans plus jamais toucher de contenu. |
| `MissionPhaseAffectationController::getPhasesForType()` | Réécrit, bien plus simple : un seul `LEFT JOIN` entre `ddmparam.audit_type_forms` et `mission_phases` (par id) — plus de correspondance par `form_code`, plus d'ids virtuels négatifs. Un flag explicite `provisioned` remplace la déduction par signe de l'id. |
| `MissionPhaseAffectationController::syncPhasesLabelsInternal()` | Simplifié : ne fait plus que provisionner les phases manquantes (il n'y a plus de libellés à comparer/mettre à jour). |
| `AuditorMissionsController::buildPhasesByType()` et `startPhase()` | Le `JOIN mission_phases as ph` pour le contenu est remplacé par un `JOIN ddmparam.audit_type_forms as atf` sur `mpa.mission_phase_id = atf.id`. `mission_phases` n'est plus jointe que pour `weight` (réglage tenant). |
| `Affectation.vue` | `isProvisioned()` se fie désormais au flag explicite `provisioned` renvoyé par le backend, plus à `id > 0` (les ids sont maintenant toujours des ids centraux réels, jamais négatifs). |

**Ordre d'exécution recommandé, par tenant :**
1. `mysqldump fruitiva > backup.sql`
2. Repérer le nom de la contrainte FK (requête fournie en haut du script SQL)
3. Exécuter `database/sql/migrate_phases_to_central_ids.sql` (en remplaçant `<FK_NAME>`)
4. `php artisan tenants:sync-reference --tenant=fruitiva` (provisionne les phases manquantes)
5. Vérifier la requête de contrôle en fin de script (doit renvoyer 0 ligne)

## Mise à jour : les affectations ne passaient plus ("phase #X invalide")

Deux causes cumulées, corrigées :

1. **`Affectation.vue` que tu utilisais avait encore l'ancien `isProvisioned`**
   (`Number(p.id) > 0`). Comme les ids sont désormais toujours ceux de
   `ddmparam` (donc toujours positifs), cette condition est **tout le temps
   vraie** : plus aucune phase n'était réellement bloquée à l'écran, même
   celles pas encore provisionnées côté tenant. → Redistribué avec le bon
   `isProvisioned` (`p.provisioned !== false`), déjà présent dans le zip
   précédent mais apparemment pas encore appliqué chez toi — **remplace bien
   le fichier entier**, ne garde pas d'ancienne copie à côté.
2. **`saveAffectation()` rejetait sèchement** toute phase absente de
   `mission_phases` (tenant) au lieu de la provisionner — ce qui arrive
   systématiquement tant que la migration SQL / la synchro n'ont pas
   (encore) tourné pour ce type de mission. → **Auto-provisioning à la
   volée** : si une phase demandée existe bien dans `ddmparam` pour l'
   `audit_type_code` du type de mission mais n'a pas encore de ligne locale,
   elle est créée immédiatement (réglages par défaut : `is_mandatory=1`,
   `status='active'`) au lieu de faire échouer la sauvegarde. La sauvegarde
   n'est donc plus dépendante du bon timing de `tenants:sync-reference` /
   de la migration.

**Recommandation** malgré ce correctif : lance quand même
`php artisan tenants:sync-reference --tenant=fruitiva` (ou la migration SQL
si pas encore fait) — l'auto-provisioning à la sauvegarde est un filet de
sécurité, pas un remplacement de la synchro normale.

## Mise à jour : erreur `Duplicate entry '2' for key 'uq_code_type'` + phases mélangées entre missions

Ton erreur confirme que **la migration SQL n'a pas encore été appliquée** sur
`fruitiva` : la table `mission_phases` a toujours l'ancienne colonne `code`
et son ancienne contrainte `uq_code_type`. Mon auto-provisioning (ajouté au
tour précédent) essayait d'y insérer des lignes au nouveau format
(`id`, `mission_type_id`, `is_mandatory`, `status`, `weight`), ce qui viole
les anciennes contraintes — et c'est aussi la cause du mélange de phases
entre missions : sur l'ancien schéma, un `id` local (auto-incrémenté, sans
rapport avec ddmparam) peut coïncider par pur hasard avec l'id ddmparam d'une
phase d'un **autre** type d'audit, et les deux se retrouvent associées.

**Correctif** : ajout d'un garde-fou `assertPhasesSchemaMigrated()`, appelé
en tout début de `getPhasesForType()`, `saveAffectation()` (contrôleur) et
`syncMissionPhasesForTenant()` (service de synchro). Il détecte la présence
de l'ancienne colonne `code` et refuse immédiatement d'agir, avec un message
clair ("lance la migration avant de continuer") au lieu de laisser MySQL
renvoyer une erreur de contrainte cryptique ou, pire, mélanger des données.

**Il n'y a pas de contournement côté code à ce stade** : la seule vraie
solution est d'exécuter `database/sql/migrate_phases_to_central_ids.sql`
sur `fruitiva` (backup avant, comme indiqué dans le script). Une fois fait,
les phases ne se mélangeront plus entre missions et les affectations
passeront normalement.

## Mise à jour : script de migration rendu plus sûr (RENAME au lieu de DROP+CREATE)

Ton log montrait une erreur (`mission_phase_notifications` inexistante) et un
timeout d'appli à 30s juste après — signe d'une transaction restée ouverte
avec des verrous. Deux corrections dans `migrate_phases_to_central_ids.sql` :

- Le remap optionnel vers `mission_phase_notifications` est maintenant
  **conditionnel** (vérifie si la table existe avant d'agir) — il ne fait
  plus échouer tout le script si tu n'as pas cette table.
- **Le script ne fait plus `DROP TABLE` puis `CREATE TABLE`.** En MySQL,
  chaque DDL (`CREATE`/`ALTER`/`DROP`) déclenche un **commit implicite**,
  jamais annulable par `ROLLBACK` — donc un souci juste après le `DROP TABLE`
  aurait pu te laisser sans table `mission_phases` du tout. Le script
  construit maintenant la nouvelle table sous un nom provisoire
  (`mission_phases_new`), et bascule les deux tables d'un coup avec
  `RENAME TABLE` (atomique). L'ancienne table est gardée sous
  `mission_phases_old` — rien n'est supprimé tant que tu n'as pas toi-même
  confirmé que l'appli fonctionne bien et lancé le `DROP TABLE` final (étape
  8 du script). Un chemin de retour en arrière manuel est aussi documenté en
  bas du script si besoin.
- Note ajoutée en tête du script sur le réflexe **`ROLLBACK;` immédiat** si
  jamais une erreur interrompt l'exécution avant la fin, pour éviter que
  l'appli se bloque en attente de verrous.



## Mise à jour : correction des SQL envoyés + vue/contrôleur alimentés par la base centrale

- **`database/sql/fix_existing_tenant_data.sql`** : script correctif à lancer
  une fois sur chaque tenant existant (`fruitiva` inclus). Il corrige le bug
  concret trouvé dans ton dump (`AMP` rattaché à `AF` au lieu de `AM`), puis
  réaligne génériquement tous les `mission_types.audit_type_*` sur
  `ddmparam.audit_types` (sans jamais toucher `code`/`label`/`description`
  propres au tenant). Il liste aussi (sans les modifier) les `mission_types`
  sans `audit_type_code` et les `mission_phases.form_code` orphelins, à
  traiter manuellement. **Fais un `mysqldump` de sauvegarde avant.**
- **`MissionController@create`** : va désormais chercher `audit_types`
  directement dans `ddmparam` (connexion `mysql`, table `ddmparam.audit_types`)
  et enrichit `missionTypes` à l'affichage avec ces valeurs **live** (jamais
  la copie dénormalisée du tenant qui peut être périmée). Un flag `is_synced`
  par type indique si la donnée tenant est à jour ou pas encore repassée par
  le job de synchro. Nouvelle prop Inertia : `auditTypes`.
- **`create.vue`** : le select "Type de mission" affiche désormais le
  libellé du type d'audit rattaché ; un aperçu coloré (pastille + icône,
  valeurs venant de `ddmparam`) apparaît sous le select et dans le
  bandeau du haut (`type-chip`), avec un badge "Non synchronisé" si le
  type de mission tenant n'a pas encore été repassé par la synchro.

