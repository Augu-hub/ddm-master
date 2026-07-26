# Branchement dans config/tenancy.php

Je ne t'ai PAS envoyé un `config/tenancy.php` complet exprès : je ne connais pas
le reste de ta config, et l'écraser risquerait de te faire perdre des events
déjà en place. Ouvre ton fichier et ajoute le job dans le pipeline de
`TenantCreated`, APRÈS `MigrateDatabase` :

```php
use Stancl\Tenancy\Events;
use Stancl\Tenancy\Jobs;
use Stancl\Tenancy\Listeners\JobPipeline;
use App\Jobs\Tenancy\SyncMissionReferentialsJob;

'events' => [
    Events\TenantCreated::class => [
        JobPipeline::make([
            Jobs\CreateDatabase::class,
            Jobs\MigrateDatabase::class,
            // Jobs\SeedDatabase::class,     // si tu en as un, garde-le AVANT le sync
            SyncMissionReferentialsJob::class, // ← à ajouter, toujours en dernier
        ])->send(function (Events\TenantCreated $event) {
            return $event->tenant;
        })->shouldBeQueued(false), // false = exécuté en synchrone pendant le provisioning
    ],

    // … garde tous tes autres events existants tels quels
],
```

## Points à vérifier avant de lancer

1. **Colonnes réelles côté ddmparam** — j'ai supposé :
   - `mission_types(id, code, label, is_active)`
   - `mission_phases(id, code, label, order, is_active)`
   Si tes vraies colonnes diffèrent, ajuste le tableau `$tables` dans
   `app/Services/Tenancy/ReferentialSyncService.php`.

2. **Colonnes `created_at` / `updated_at`** — le service les ajoute
   automatiquement à l'insert. Si tes tables tenant n'ont pas ces colonnes
   (`timestamps()` absent), retire les deux lignes correspondantes dans
   `syncTable()`, sinon l'upsert plantera.

3. **Nom de la connexion tenant** — j'ai repris `'tenant'`, comme dans ton
   `MissionController::tenant()` actuel. Si `config('tenancy.database
   .central_connection')` ne pointe pas vers `ddmparam` chez toi, précise-le
   explicitement dans `ReferentialSyncService::syncTable()` au lieu de
   passer par la config.

4. **Tenants déjà existants** — le hook automatique ne joue qu'à la
   création. Pour pousser une modif faite après coup dans ddmparam vers des
   tenants déjà provisionnés :
   ```bash
   php artisan tenants:sync-referentials        # tous les tenants
   php artisan tenants:sync-referentials 3       # un seul tenant (id=3)
   ```

## Fichiers à copier

```
app/Services/Tenancy/ReferentialSyncService.php
app/Jobs/Tenancy/SyncMissionReferentialsJob.php
app/Console/Commands/SyncTenantReferentials.php
```
Rien à faire pour `MissionController@create` — il lit déjà `mission_types`
depuis la connexion `tenant`, donc une fois les données synchronisées il
n'y a rien à changer côté contrôleur ou côté Vue.
