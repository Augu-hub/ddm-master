<template>
  <VerticalLayout>
    <Head title="Référentiel ARMP national" />

    <div class="rna-page">
      <!-- ══ HEADER ═══════════════════════════════════════════════ -->
      <div class="rna-header">
        <div class="rna-header-top">
          <div>
            <h1 class="rna-title">🏛️ Référentiel ARMP national</h1>
            <p class="rna-subtitle">ddmparam · pm_* — source unique, propagée à tous les tenants</p>
          </div>
          <div class="rna-header-actions">
            <span class="version-badge">v{{ version }}</span>
            <button @click="refreshData" class="btn-refresh" :class="{ 'is-spinning': refreshing }">
              <i class="ti ti-refresh"></i> Actualiser
            </button>
            <button @click="forceSyncAll" class="btn-sync-all" :disabled="syncingAll">
              <i class="ti ti-cloud-upload"></i> {{ syncingAll ? 'Synchronisation…' : 'Forcer la synchro (tous les tenants)' }}
            </button>
          </div>
        </div>

        <div class="tabs-row">
          <button v-for="t in tabs" :key="t.key" class="tab-btn" :class="{ active: activeTab === t.key }" @click="activeTab = t.key">
            <i :class="t.icon"></i> {{ t.label }}
          </button>
        </div>
      </div>

      <b-alert v-if="msg.text" :variant="msg.variant" dismissible @dismissed="msg.text=''" class="mx-3 mt-2 py-2">
        {{ msg.text }}
      </b-alert>

      <!-- ══ ONGLET SYNCHRONISATION ═══════════════════════════════ -->
      <div v-if="activeTab === 'sync'" class="rna-body-simple">
        <table class="sync-table">
          <thead>
            <tr><th>Tenant</th><th>Code</th><th>Version synchronisée</th><th>Dernière synchro</th><th>État</th><th></th></tr>
          </thead>
          <tbody>
            <tr v-for="t in tenantsSync" :key="t.id">
              <td>{{ t.name }}</td>
              <td><code>{{ t.code }}</code></td>
              <td>{{ t.pm_referentiel_version_synced }}</td>
              <td>{{ t.pm_referentiel_synced_at || '—' }}</td>
              <td>
                <span v-if="t.pm_referentiel_version_synced >= version" class="badge-uptodate">À jour</span>
                <span v-else class="badge-behind">En retard</span>
              </td>
              <td>
                <button class="btn-sync-one" @click="forceSyncOne(t.id)" :disabled="syncingTenant === t.id">
                  <i class="ti ti-refresh" :class="{ 'anim-spin': syncingTenant === t.id }"></i> Synchroniser
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- ══ ONGLET RÉFÉRENTIELS SIMPLES ══════════════════════════ -->
      <div v-else-if="activeTab === 'simples'" class="rna-body">
        <div class="types-panel">
          <div class="panel-head"><span>Référentiels</span></div>
          <div class="types-list">
            <div v-for="e in simpleEntities" :key="e.key" class="type-card" :class="{ active: activeSimple === e.key }" @click="activeSimple = e.key">
              <div class="type-icon"><i :class="e.icon"></i></div>
              <div class="type-info">
                <div class="type-label">{{ e.label }}</div>
                <div class="type-counts"><span class="badge-forms">{{ simpleData[e.key].length }} entrée(s)</span></div>
              </div>
            </div>
          </div>
        </div>

        <div class="forms-panel">
          <div class="forms-header">
            <div class="forms-title-row">
              <div class="forms-type-badge">{{ currentSimpleEntity?.label }}</div>
              <button @click="openSimpleModal()" class="btn-add-form"><i class="ti ti-plus"></i> Ajouter</button>
            </div>
          </div>
          <div class="phases-container">
            <table class="simple-table">
              <thead>
                <tr><th style="width:110px">Code</th><th>Libellé</th><th style="width:120px"></th></tr>
              </thead>
              <tbody>
                <tr v-for="row in simpleData[activeSimple]" :key="row.id">
                  <td><code>{{ row.code }}</code></td>
                  <td>{{ row.libelle }}</td>
                  <td class="text-end">
                    <button class="btn-icon-sm" @click="openSimpleModal(row)"><i class="ti ti-edit"></i></button>
                    <button class="btn-icon-sm danger" @click="destroySimple(row.id)"><i class="ti ti-trash"></i></button>
                  </td>
                </tr>
                <tr v-if="!simpleData[activeSimple]?.length"><td colspan="3" class="empty-forms">Aucune entrée</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ══ ONGLET GRILLES DE VÉRIFICATION ═══════════════════════ -->
      <div v-else-if="activeTab === 'grilles'" class="rna-grilles-wrap">

        <!-- ── Bandeau de stats (parité avec la vue tenant) ────────── -->
        <div class="rna-stats">
          <div v-for="s in statsCards" :key="s.label" class="stat-box">
            <div class="stat-icon" :style="{ background: s.color }"><i :class="s.icon"></i></div>
            <div>
              <div class="stat-count">{{ s.count }}</div>
              <div class="stat-label">{{ s.label }}</div>
            </div>
          </div>
        </div>

        <div class="rna-body rna-body-grilles">
          <div class="types-panel">
            <div class="panel-head"><span>Mode de passation</span></div>
            <div class="types-list">
              <div v-for="m in modeTabs" :key="m.code" class="type-card" :class="{ active: activeMode === m.code }" @click="activeMode = m.code; selectedGrille = null">
                <div class="type-info">
                  <div class="type-code">{{ m.code }}</div>
                  <div class="type-label">{{ m.libelle }}</div>
                  <div class="type-counts"><span class="badge-forms">{{ grillesForMode(m.code).length }} grille(s)</span></div>
                </div>
              </div>
            </div>

            <!-- ── Testeur d'affectation ────────────────────────────── -->
            <div class="coverage-tester">
              <div class="coverage-head"><i class="ti ti-flask"></i> Tester une affectation</div>
              <select v-model="tester.nature" class="finput-sm">
                <option value="">Nature de marché…</option>
                <option v-for="n in naturesMarche" :key="n.code" :value="n.code">{{ n.code }} — {{ n.libelle }}</option>
              </select>
              <select v-model="tester.mode" class="finput-sm">
                <option value="">Mode de passation…</option>
                <option v-for="m in modesPassation" :key="m.code" :value="m.code">{{ m.code }} — {{ m.libelle }}</option>
              </select>
              <select v-model="tester.pq" class="finput-sm">
                <option value="">Indifférent</option>
                <option value="1">Avec préqualification</option>
                <option value="0">Sans préqualification</option>
              </select>
              <button class="btn-save btn-sm" @click="testerAffectation" :disabled="!tester.nature || !tester.mode">Tester</button>

              <div v-if="tester.result" class="coverage-result">
                <div v-if="tester.result.phases_manquantes.length" class="coverage-warning">
                  <i class="ti ti-alert-triangle"></i> Phases sans grille : <strong>{{ tester.result.phases_manquantes.join(', ') }}</strong>
                </div>
                <div v-if="Object.keys(tester.result.phases_ambigues).length" class="coverage-warning">
                  <i class="ti ti-alert-circle"></i> Conflits :
                  <span v-for="(codes, phase) in tester.result.phases_ambigues" :key="phase">{{ phase }} ({{ codes.join(' vs ') }})</span>
                </div>
                <table class="simple-table">
                  <thead><tr><th>Phase</th><th>Grille</th><th>Pts</th></tr></thead>
                  <tbody>
                    <tr v-for="g in tester.result.grilles" :key="g.id">
                      <td><span class="badge-phase-mini">{{ g.phase_marche }}</span></td>
                      <td>{{ g.code }}</td>
                      <td>{{ g.nb_items }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="forms-panel">
            <div v-if="!activeMode" class="forms-empty-state"><i class="ti ti-folder-open"></i><p>Sélectionnez un mode de passation</p></div>
            <template v-else>
              <div class="forms-header">
                <div class="forms-title-row">
                  <div class="forms-type-badge">Mode {{ activeMode }}</div>
                  <button @click="openGrilleModal(null, activeMode)" class="btn-add-form"><i class="ti ti-plus"></i> Nouvelle grille</button>
                </div>
              </div>
              <div class="phases-container">
                <!-- ── Groupement par nature de marché, comme la vue tenant ── -->
                <div v-for="nat in naturesForActiveMode" :key="nat.key" class="nature-group">
                  <div class="nature-group-header">
                    <span class="badge-nature">{{ nat.label }}</span>
                  </div>

                  <div v-for="g in nat.grilles" :key="g.id" class="phase-block">
                    <div class="phase-header" style="cursor:pointer" @click="selectGrille(g)">
                      <div class="phase-badge">{{ g.code }}</div>
                      <span class="phase-label-text">{{ g.intitule }}</span>
                      <span v-if="g.avec_prequalification === 1" class="stat-chip">Avec PQ</span>
                      <span v-else-if="g.avec_prequalification === 0" class="stat-chip">Sans PQ</span>
                      <span class="phase-count">{{ itemCount(g.id) }} pt(s)</span>
                      <button class="btn-icon-sm" @click.stop="analyserGrilleIA(g)" :disabled="analysingGrille===g.id" title="Analyser toute la grille (IA)">
                        <i class="ti" :class="analysingGrille===g.id ? 'ti-loader-2 anim-spin' : 'ti-sparkles'"></i>
                      </button>
                      <div class="phase-actions">
                        <button class="btn-icon-sm" @click.stop="openGrilleModal(g)"><i class="ti ti-edit"></i></button>
                        <button class="btn-icon-sm danger" @click.stop="destroyGrille(g.id)"><i class="ti ti-trash"></i></button>
                      </div>
                    </div>

                    <!-- ── Organes compétents pour cette grille ─────────── -->
                    <div class="phase-organes" @click.stop>
                      <span v-for="oc in getGrilleOrganes(g.id)" :key="oc" class="badge-organe" @click="removeGrilleOrgane(g.id, oc)">
                        {{ oc }} <i class="ti ti-x"></i>
                      </span>
                      <select class="select-add-sm" @change="e => { if (e.target.value) addGrilleOrgane(g.id, e.target.value); e.target.value='' }">
                        <option value="">+ organe compétent</option>
                        <option v-for="o in organes.filter(og => !getGrilleOrganes(g.id).includes(og.code))" :key="o.code" :value="o.code">
                          {{ o.sigle || o.code }} — {{ o.libelle }}
                        </option>
                      </select>
                    </div>

                    <div v-if="selectedGrille?.id === g.id" class="phase-forms">
                      <div v-for="it in itemsOf(g.id)" :key="it.id" class="item-detail-card">
                        <div class="item-detail-head">
                          <span class="item-numero">{{ it.numero }}</span>
                          <span class="item-libelle">{{ it.libelle_controle }}</span>
                          <div class="item-actions">
                            <button class="btn-icon-sm" @click="analyserItemIA(it)" :disabled="analysingItem===it.id" title="Analyser avec l'IA">
                              <i class="ti" :class="analysingItem===it.id ? 'ti-loader-2 anim-spin' : 'ti-sparkles'"></i>
                            </button>
                            <button class="btn-icon-sm" @click="openItemModal(it)"><i class="ti ti-edit"></i></button>
                            <button class="btn-icon-sm danger" @click="destroyItem(it.id)"><i class="ti ti-trash"></i></button>
                          </div>
                        </div>

                        <div v-if="it.preuves" class="item-preuves">
                          <i class="ti ti-file-check"></i> <strong>Preuves attendues :</strong> {{ it.preuves }}
                        </div>

                        <!-- ── Opérations rattachées (filtre précis pour les délais) ── -->
                        <div class="item-subrow">
                          <span class="subrow-label"><i class="ti ti-list-check"></i> Opérations :</span>
                          <span v-for="o in getItemOperations(it.id)" :key="o.id" class="badge-operation" :title="o.libelle" @click="removeItemOperation(it.id, o.id)">
                            {{ o.code }} <i class="ti ti-x"></i>
                          </span>
                          <select class="select-add-sm" @change="e => { if (e.target.value) addItemOperation(it.id, Number(e.target.value)); e.target.value='' }">
                            <option value="">+ opération</option>
                            <option v-for="o in operations" :key="o.id" :value="o.id">{{ o.code }} — {{ o.libelle }}</option>
                          </select>
                        </div>

                        <!-- ── Délais rattachés (suggestions marquées ★) ── -->
                        <div class="item-subrow" v-if="it.depend_delai">
                          <span class="subrow-label"><i class="ti ti-clock"></i> Délais :</span>
                          <span v-for="d in getItemDelaisMulti(it)" :key="d.id" class="badge-delai" @click="removeItemDelaiMulti(it.id, d.id)">
                            {{ delaiSummary(d.id) }} <i class="ti ti-x"></i>
                          </span>
                          <select class="select-add-sm" @change="e => { if (e.target.value) addItemDelaiMulti(it.id, Number(e.target.value)); e.target.value='' }">
                            <option value="">+ délai</option>
                            <option v-if="!delaisPourItem(it).length" disabled>Aucun délai compatible</option>
                            <option v-for="d in delaisPourItem(it)" :key="d.id" :value="d.id">
                              {{ d.__suggere ? '★ ' : '' }}{{ delaiSummary(d.id) }}
                            </option>
                          </select>
                        </div>

                        <!-- ── Articles de loi rattachés ── -->
                        <div class="item-articles">
                          <span v-for="a in getItemArticles(it.id)" :key="a.id"
                                class="badge-article" :class="{ 'badge-article-ia': a.genere_par_ia }"
                                :title="a.titre || a.texte_reference">
                            <span @click="articleDetail = a">Art. {{ a.numero }}<span v-if="a.genere_par_ia"> ✨</span></span>
                            <i class="ti ti-x" @click.stop="removeItemArticle(it.id, a.id)"></i>
                          </span>
                          <select class="select-add-sm" @change="e => { if (e.target.value) addItemArticle(it.id, Number(e.target.value)); e.target.value='' }">
                            <option value="">+ article</option>
                            <option v-for="a in articlesLoi" :key="a.id" :value="a.id">Art. {{ a.numero }} — {{ a.titre || a.texte_reference }}</option>
                          </select>
                          <button class="link-btn-sm" @click="loadLiaisons(it.id)">
                            <i class="ti ti-refresh"></i> Recharger
                          </button>
                        </div>
                      </div>

                      <button class="link-btn" @click="openItemModal(null, g.id)"><i class="ti ti-plus"></i> Ajouter un point de contrôle</button>
                    </div>
                  </div>
                </div>
                <div v-if="!grillesForMode(activeMode).length" class="empty-forms">
                  <i class="ti ti-list-off"></i><p>Aucune grille pour ce mode</p>
                </div>
              </div>
            </template>
          </div>
        </div>
      </div>
    </div>

    <!-- ══ MODAL — Référentiel simple ═══════════════════════════ -->
    <Teleport to="body">
      <div v-if="simpleModal.show" class="modal-overlay" @click.self="simpleModal.show=false">
        <div class="modal-box">
          <div class="modal-head">
            <h3>{{ simpleModal.edit ? 'Modifier' : 'Nouveau' }} — {{ currentSimpleEntity?.label }}</h3>
            <button @click="simpleModal.show=false" class="btn-close"><i class="ti ti-x"></i></button>
          </div>
          <form @submit.prevent="submitSimple" class="modal-form">
            <div class="fg">
              <label>Code *</label>
              <input v-model.trim="simpleModal.form.code" required class="finput" :disabled="simpleModal.edit" />
            </div>
            <div class="fg">
              <label>Libellé *</label>
              <input v-model.trim="simpleModal.form.libelle" required class="finput" />
            </div>
            <div class="modal-footer">
              <button type="button" class="btn-cancel" @click="simpleModal.show=false">Annuler</button>
              <button type="submit" class="btn-save">Enregistrer</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- ══ MODAL — Grille ═══════════════════════════════════════ -->
    <Teleport to="body">
      <div v-if="grilleModal.show" class="modal-overlay" @click.self="grilleModal.show=false">
        <div class="modal-box modal-lg">
          <div class="modal-head">
            <h3>{{ grilleModal.edit ? 'Modifier' : 'Nouvelle' }} grille</h3>
            <button @click="grilleModal.show=false" class="btn-close"><i class="ti ti-x"></i></button>
          </div>
          <form @submit.prevent="submitGrille" class="modal-form">
            <div class="fg-row">
              <div class="fg"><label>Code *</label><input v-model.trim="grilleModal.form.code" required class="finput" :disabled="grilleModal.edit" /></div>
              <div class="fg"><label>Famille (regroupement)</label><input v-model.trim="grilleModal.form.code_parent" class="finput" placeholder="A6" /></div>
            </div>
            <div class="fg-row">
              <div class="fg"><label>Mode de passation</label>
                <select v-model="grilleModal.form.mode_passation_code" class="finput">
                  <option value="">— Tous modes —</option>
                  <option v-for="m in modesPassation" :key="m.code" :value="m.code">{{ m.code }} — {{ m.libelle }}</option>
                </select>
              </div>
              <div class="fg"><label>Préqualification</label>
                <select v-model="grilleModal.form.avec_prequalification" class="finput">
                  <option value="">— Indifférent —</option>
                  <option value="1">Avec préqualification</option>
                  <option value="0">Sans préqualification</option>
                </select>
              </div>
            </div>
            <div class="fg"><label>Intitulé *</label><textarea v-model.trim="grilleModal.form.intitule" required class="finput" rows="2"></textarea></div>
            <div class="fg"><label>Nature de marché</label>
              <select v-model="grilleModal.form.nature_marche_code" class="finput">
                <option value="">— Toutes natures —</option>
                <option v-for="n in naturesMarche" :key="n.code" :value="n.code">{{ n.code }} — {{ n.libelle }}</option>
              </select>
            </div>
            <div class="fg"><label>Phase de marché</label>
              <select v-model="grilleModal.form.phase_marche" class="finput">
                <option value="">—</option>
                <option v-for="p in ['PLA','DAO','ROO','EVA','SAN','EXE','REP','CAT']" :key="p" :value="p">{{ p }}</option>
              </select>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn-cancel" @click="grilleModal.show=false">Annuler</button>
              <button type="submit" class="btn-save">Enregistrer</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- ══ MODAL — Item ═══════════════════════════════════════════ -->
    <Teleport to="body">
      <div v-if="itemModal.show" class="modal-overlay" @click.self="itemModal.show=false">
        <div class="modal-box modal-lg">
          <div class="modal-head">
            <h3>{{ itemModal.edit ? 'Modifier' : 'Nouveau' }} point de contrôle</h3>
            <button @click="itemModal.show=false" class="btn-close"><i class="ti ti-x"></i></button>
          </div>
          <form @submit.prevent="submitItem" class="modal-form">
            <div class="fg-row">
              <div class="fg"><label>N° *</label><input v-model.trim="itemModal.form.numero" required class="finput" /></div>
              <div class="fg"><label>Obligatoire</label>
                <select v-model="itemModal.form.obligatoire" class="finput">
                  <option :value="1">Oui</option>
                  <option :value="0">Non</option>
                </select>
              </div>
            </div>
            <div class="fg"><label>Libellé du contrôle *</label><textarea v-model.trim="itemModal.form.libelle_controle" required class="finput" rows="4" placeholder="Conformité de... aux exigences de l'article ... de la loi n°2020-26..."></textarea></div>
            <div class="fg"><label>Preuves attendues</label><textarea v-model.trim="itemModal.form.preuves" class="finput" rows="2" placeholder="Documents/justificatifs attendus pour ce point de contrôle"></textarea></div>
            <p class="hint-text">
              <i class="ti ti-info-circle"></i> Si le libellé contient le mot « délai », le point sera automatiquement
              marqué comme lié à un délai — rattachez-le ensuite depuis la section « Délais » de la carte du point de contrôle.
            </p>
            <div class="modal-footer">
              <button type="button" class="btn-cancel" @click="itemModal.show=false">Annuler</button>
              <button type="submit" class="btn-save">Enregistrer</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- ══ MODAL — Détail article ═══════════════════════════════ -->
    <Teleport to="body">
      <div v-if="articleDetail" class="modal-overlay" @click.self="articleDetail=null">
        <div class="modal-box">
          <div class="modal-head">
            <h3>Article {{ articleDetail.numero }}</h3>
            <button @click="articleDetail=null" class="btn-close"><i class="ti ti-x"></i></button>
          </div>
          <div class="modal-form">
            <div>
              <span class="badge-article">{{ articleDetail.texte_reference }}</span>
              <span v-if="articleDetail.source_loi" class="stat-chip">{{ articleDetail.source_loi }}</span>
              <span v-if="articleDetail.genere_par_ia" class="stat-chip">✨ généré par IA</span>
            </div>
            <h4 v-if="articleDetail.titre">{{ articleDetail.titre }}</h4>
            <p v-if="articleDetail.contenu" style="white-space:pre-line;font-size:.85rem">{{ articleDetail.contenu }}</p>
            <p v-else class="text-muted fst-italic">Contenu non renseigné — relancez l'analyse IA.</p>
          </div>
        </div>
      </div>
    </Teleport>
  </VerticalLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import { ref, reactive, computed } from 'vue'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

const props = defineProps({
  activeTab:        { type: String, default: 'grilles' },
  version:          { type: Number, default: 1 },
  tenantsSync:      { type: Array,  default: () => [] },
  typesEntites:     { type: Array,  default: () => [] },
  sourcesFinance:   { type: Array,  default: () => [] },
  naturesMarche:    { type: Array,  default: () => [] },
  modesPassation:   { type: Array,  default: () => [] },
  organes:          { type: Array,  default: () => [] },
  operations:       { type: Array,  default: () => [] },
  datesReference:   { type: Array,  default: () => [] },
  grilles:          { type: Array,  default: () => [] },
  items:            { type: Array,  default: () => [] },
  articlesLoi:      { type: Array,  default: () => [] },
  itemsArticles:    { type: Array,  default: () => [] },
  // ── Ajouts pour parité avec la vue tenant GrillesVerification.vue ──
  grilleOrganes:    { type: Array,  default: () => [] }, // [{grille_id, organe_code}]
  delais:           { type: Array,  default: () => [] },
  delaiModes:       { type: Array,  default: () => [] }, // [{delai_id, mode_passation_code}]
  delaiOrganes:     { type: Array,  default: () => [] }, // [{delai_id, organe_code}]
  itemsDelaisMulti: { type: Array,  default: () => [] }, // [{item_id, delai_id, genere_par_ia}]
  itemsOperations:  { type: Array,  default: () => [] }, // [{item_id, operation_id, genere_par_ia}]
})

const version         = ref(props.version)
const tenantsSync     = ref([...props.tenantsSync])
const naturesMarche   = ref([...props.naturesMarche])
const modesPassation  = ref([...props.modesPassation])
const organes         = ref([...props.organes])
const operations      = ref([...props.operations])
const grilles         = ref([...props.grilles])
const items           = ref([...props.items])
const articlesLoi     = ref([...props.articlesLoi])
const itemsArticles   = ref([...props.itemsArticles])
const grilleOrganes    = ref([...props.grilleOrganes])
const delais            = ref([...props.delais])
const delaiModes        = ref([...props.delaiModes])
const delaiOrganes      = ref([...props.delaiOrganes])
const itemsDelaisMulti  = ref([...props.itemsDelaisMulti])
const itemsOperations   = ref([...props.itemsOperations])
const articleDetail   = ref(null)
const analysingItem   = ref(null)
const analysingGrille = ref(null)

const simpleData = reactive({
  'types-entites':       [...props.typesEntites],
  'sources-financement': [...props.sourcesFinance],
  'natures-marche':      [...props.naturesMarche],
  'modes-passation':     [...props.modesPassation],
  'organes':             [...props.organes],
  'operations':          [...props.operations],
  'dates-reference':     [...props.datesReference],
})

const simpleEntities = [
  { key: 'types-entites',       label: "Types d'entités",        icon: 'ti ti-building' },
  { key: 'sources-financement', label: 'Sources de financement', icon: 'ti ti-cash' },
  { key: 'natures-marche',      label: 'Natures de marché',      icon: 'ti ti-category' },
  { key: 'modes-passation',     label: 'Modes de passation',     icon: 'ti ti-route' },
  { key: 'organes',             label: 'Organes de contrôle',     icon: 'ti ti-gavel' },
  { key: 'operations',          label: 'Opérations',             icon: 'ti ti-list-check' },
  { key: 'dates-reference',     label: 'Dates de référence',     icon: 'ti ti-calendar' },
]

const tabs = [
  { key: 'grilles', label: 'Grilles de vérification', icon: 'ti ti-checklist' },
  { key: 'simples', label: 'Référentiels de base',     icon: 'ti ti-list-details' },
  { key: 'sync',    label: 'Synchronisation tenants',  icon: 'ti ti-cloud-check' },
]

const activeTab    = ref(props.activeTab)
const activeSimple = ref('types-entites')
const currentSimpleEntity = computed(() => simpleEntities.find(e => e.key === activeSimple.value))

const activeMode = ref(null)
const modeTabs = computed(() => modesPassation.value.map(m => ({ code: m.code, libelle: m.libelle })))
if (modeTabs.value.length && !activeMode.value) activeMode.value = modeTabs.value[0].code

const selectedGrille = ref(null)
const refreshing     = ref(false)
const syncingAll     = ref(false)
const syncingTenant  = ref(null)
const msg = reactive({ text: '', variant: 'success' })
const tester = reactive({ nature: '', mode: '', pq: '', result: null })

function grillesForMode(code) { return grilles.value.filter(g => g.mode_passation_code === code || !g.mode_passation_code) }
function itemsOf(grilleId)    { return items.value.filter(i => i.grille_id === grilleId).sort((a,b) => a.sort - b.sort) }
function itemCount(grilleId)  { return itemsOf(grilleId).length }
function selectGrille(g)      { selectedGrille.value = selectedGrille.value?.id === g.id ? null : g }

// ── Grilles du mode actif, regroupées par nature de marché (parité tenant) ──
const natureLibelle = code => naturesMarche.value.find(n => n.code === code)?.libelle || code
const naturesForActiveMode = computed(() => {
  if (!activeMode.value) return []
  const list = grillesForMode(activeMode.value)
  const map = {}
  list.forEach(g => {
    const key = g.nature_marche_code || '__TOUTES__'
    if (!map[key]) map[key] = { key, label: g.nature_marche_code ? natureLibelle(g.nature_marche_code) : 'Toutes natures', grilles: [] }
    map[key].grilles.push(g)
  })
  return Object.values(map).sort((a, b) => a.label.localeCompare(b.label))
})

// ── Stats (parité tenant) ────────────────────────────────────────────
const statsCards = computed(() => [
  { label: 'Grilles',            count: grilles.value.length,                              icon: 'ti ti-folder',          color: '#667eea' },
  { label: 'Modes couverts',     count: modeTabs.value.filter(m => grillesForMode(m.code).length).length, icon: 'ti ti-arrows-shuffle', color: '#38b2ac' },
  { label: 'Points de contrôle', count: items.value.length,                                icon: 'ti ti-checklist',       color: '#48bb78' },
  { label: 'Liés à un délai',    count: items.value.filter(i => i.depend_delai).length,     icon: 'ti ti-clock',           color: '#ed8936' },
])

function getItemArticles(itemId) {
  const ids = itemsArticles.value.filter(a => a.item_id === itemId).map(a => a.article_id)
  return articlesLoi.value.filter(a => ids.includes(a.id))
}

// ── fetch helper ──────────────────────────────────────────────────
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? ''
async function api(url, method, body) {
  const r = await fetch(url, {
    method, headers: { 'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN': csrf() },
    body: body ? JSON.stringify(body) : undefined,
  })
  return r.json()
}

async function refreshData() {
  refreshing.value = true
  try {
    const res = await api('/admin/referentiel-armp/api/all', 'GET')
    version.value        = res.version
    tenantsSync.value     = res.tenantsSync
    naturesMarche.value   = res.naturesMarche
    modesPassation.value  = res.modesPassation
    organes.value          = res.organes
    operations.value       = res.operations
    grilles.value         = res.grilles
    items.value           = res.items
    articlesLoi.value     = res.articlesLoi
    itemsArticles.value   = res.itemsArticles
    grilleOrganes.value    = res.grilleOrganes    ?? []
    delais.value            = res.delais            ?? []
    delaiModes.value        = res.delaiModes        ?? []
    delaiOrganes.value      = res.delaiOrganes      ?? []
    itemsDelaisMulti.value  = res.itemsDelaisMulti  ?? []
    itemsOperations.value   = res.itemsOperations   ?? []
    simpleData['types-entites']       = res.typesEntites
    simpleData['sources-financement'] = res.sourcesFinance
    simpleData['natures-marche']      = res.naturesMarche
    simpleData['modes-passation']     = res.modesPassation
    simpleData['organes']             = res.organes
    simpleData['operations']          = res.operations
    simpleData['dates-reference']     = res.datesReference
  } finally { refreshing.value = false }
}

async function forceSyncAll() {
  if (!confirm('Forcer la synchronisation de TOUS les tenants maintenant ?')) return
  syncingAll.value = true
  try {
    const res = await api('/admin/referentiel-armp/sync/all', 'POST')
    if (res.success) { msg.variant='success'; msg.text='Synchronisation lancée sur tous les tenants.'; await refreshData() }
  } finally { syncingAll.value = false }
}

async function forceSyncOne(tenantId) {
  syncingTenant.value = tenantId
  try {
    const res = await api(`/admin/referentiel-armp/sync/${tenantId}`, 'POST')
    if (res.success) { msg.variant='success'; msg.text='Tenant synchronisé.'; await refreshData() }
  } finally { syncingTenant.value = null }
}

async function testerAffectation() {
  const params = new URLSearchParams({ nature: tester.nature, mode: tester.mode })
  if (tester.pq !== '') params.set('pq', tester.pq)
  const res = await api(`/admin/referentiel-armp/preview-affectation?${params}`, 'GET')
  tester.result = res.success ? res : null
}

// ── Référentiels simples ───────────────────────────────────────────
const simpleModal = reactive({ show: false, edit: false, form: { id:null, code:'', libelle:'' } })
function openSimpleModal(row = null) {
  simpleModal.show = true; simpleModal.edit = !!row
  simpleModal.form = row ? { id: row.id, code: row.code, libelle: row.libelle } : { id:null, code:'', libelle:'' }
}
async function submitSimple() {
  const entity = activeSimple.value
  if (simpleModal.edit) {
    const res = await api(`/admin/referentiel-armp/${entity}/${simpleModal.form.id}`, 'PUT', { libelle: simpleModal.form.libelle })
    if (res.success) { const row = simpleData[entity].find(r=>r.id===simpleModal.form.id); if (row) row.libelle = simpleModal.form.libelle }
  } else {
    const res = await api(`/admin/referentiel-armp/${entity}`, 'POST', simpleModal.form)
    if (res.success) simpleData[entity].push({ id: res.id, ...simpleModal.form })
  }
  simpleModal.show = false
}
async function destroySimple(id) {
  if (!confirm('Supprimer cette entrée ?')) return
  const entity = activeSimple.value
  const res = await api(`/admin/referentiel-armp/${entity}/${id}`, 'DELETE')
  if (res.success) simpleData[entity] = simpleData[entity].filter(r => r.id !== id)
}

// ── Grilles ─────────────────────────────────────────────────────────
const grilleModal = reactive({ show:false, edit:false, form:{} })
function openGrilleModal(g=null, presetMode=null) {
  grilleModal.show = true; grilleModal.edit = !!g
  grilleModal.form = g
    ? {
        id: g.id, code: g.code, code_parent: g.code_parent || '', intitule: g.intitule,
        nature_marche_code: g.nature_marche_code || '', mode_passation_code: g.mode_passation_code || '',
        avec_prequalification: g.avec_prequalification === null || g.avec_prequalification === undefined ? '' : String(g.avec_prequalification),
        phase_marche: g.phase_marche || '',
      }
    : { id:null, code:'', code_parent:'', intitule:'', nature_marche_code:'', mode_passation_code: presetMode || '', avec_prequalification: '', phase_marche:'' }
}
async function submitGrille() {
  const payload = {
    code_parent: grilleModal.form.code_parent || null,
    intitule: grilleModal.form.intitule,
    nature_marche_code: grilleModal.form.nature_marche_code || null,
    mode_passation_code: grilleModal.form.mode_passation_code || null,
    avec_prequalification: grilleModal.form.avec_prequalification === '' ? null : Number(grilleModal.form.avec_prequalification),
    phase_marche: grilleModal.form.phase_marche || null,
  }
  if (grilleModal.edit) {
    const res = await api(`/admin/referentiel-armp/grilles/${grilleModal.form.id}`, 'PUT', payload)
    if (res.success) Object.assign(grilles.value.find(g=>g.id===grilleModal.form.id), payload)
  } else {
    const res = await api('/admin/referentiel-armp/grilles', 'POST', { ...payload, code: grilleModal.form.code })
    if (res.success) grilles.value.push({ id: res.id, code: grilleModal.form.code, ...payload, actif:1, sort:999 })
  }
  grilleModal.show = false
}
async function destroyGrille(id) {
  if (!confirm('Supprimer cette grille et tous ses points de contrôle ?')) return
  const res = await api(`/admin/referentiel-armp/grilles/${id}`, 'DELETE')
  if (res.success) {
    grilles.value = grilles.value.filter(g=>g.id!==id)
    items.value = items.value.filter(i=>i.grille_id!==id)
    grilleOrganes.value = grilleOrganes.value.filter(o => o.grille_id !== id)
    if (selectedGrille.value?.id === id) selectedGrille.value = null
  }
}

async function analyserGrilleIA(g) {
  if (!confirm(`Analyser les ${itemCount(g.id)} points de contrôle de cette grille avec l'IA ?`)) return
  analysingGrille.value = g.id
  try {
    for (const it of itemsOf(g.id)) await analyserItemIA(it, true)
    msg.variant = 'success'; msg.text = 'Analyse IA terminée pour toute la grille.'
  } finally { analysingGrille.value = null }
}

// ── Organes <-> Grille ────────────────────────────────────────────────
function getGrilleOrganes(grilleId) {
  return grilleOrganes.value.filter(o => o.grille_id === grilleId).map(o => o.organe_code)
}
async function addGrilleOrgane(grilleId, organeCode) {
  const res = await api('/admin/referentiel-armp/grille-organes', 'POST', { grille_id: grilleId, organe_code: organeCode })
  if (res.success) grilleOrganes.value.push({ grille_id: grilleId, organe_code: organeCode })
}
async function removeGrilleOrgane(grilleId, organeCode) {
  const res = await api('/admin/referentiel-armp/grille-organes', 'DELETE', { grille_id: grilleId, organe_code: organeCode })
  if (res.success) {
    const idx = grilleOrganes.value.findIndex(o => o.grille_id === grilleId && o.organe_code === organeCode)
    if (idx !== -1) grilleOrganes.value.splice(idx, 1)
  }
}

// ── Opérations <-> Item (filtre précis pour les délais) ────────────────
function getItemOperations(itemId) {
  const ids = itemsOperations.value.filter(o => o.item_id === itemId).map(o => o.operation_id)
  return operations.value.filter(o => ids.includes(o.id))
}
async function addItemOperation(itemId, operationId) {
  const res = await api(`/admin/referentiel-armp/items/${itemId}/link-operation`, 'POST', { operation_id: operationId })
  if (!res.success) return
  itemsOperations.value.push({ item_id: itemId, operation_id: operationId, genere_par_ia: 0 })

  // Auto-rattachement des délais compatibles avec cette opération (mode de la grille), comme côté tenant
  const item = items.value.find(i => i.id === itemId)
  const grille = item ? grilles.value.find(g => g.id === item.grille_id) : null
  const modeCode = grille?.mode_passation_code
  const delaisCompatibles = delais.value.filter(d => {
    if (d.operation_id !== operationId) return false
    const modes = getDelaiModes(d.id)
    return !modes.length || !modeCode || modes.includes(modeCode)
  })
  for (const d of delaisCompatibles) {
    const dejaLie = itemsDelaisMulti.value.some(x => x.item_id === itemId && x.delai_id === d.id)
    if (!dejaLie) await addItemDelaiMulti(itemId, d.id)
  }
}
async function removeItemOperation(itemId, operationId) {
  const res = await api(`/admin/referentiel-armp/items/${itemId}/unlink-operation`, 'DELETE', { operation_id: operationId })
  if (res.success) {
    const idx = itemsOperations.value.findIndex(o => o.item_id === itemId && o.operation_id === operationId)
    if (idx !== -1) itemsOperations.value.splice(idx, 1)
  }
}

// ── Délais multiples <-> Item ───────────────────────────────────────────
function getItemDelaisMulti(item) {
  const idsM2M = itemsDelaisMulti.value.filter(d => d.item_id === item.id).map(d => d.delai_id)
  const ids = new Set(idsM2M)
  if (item.delai_id) ids.add(item.delai_id)
  return delais.value.filter(d => ids.has(d.id))
}
async function addItemDelaiMulti(itemId, delaiId) {
  const res = await api(`/admin/referentiel-armp/items/${itemId}/link-delai-multi`, 'POST', { delai_id: delaiId })
  if (res.success) {
    itemsDelaisMulti.value.push({ item_id: itemId, delai_id: delaiId, genere_par_ia: 0 })
    const idx = items.value.findIndex(i => i.id === itemId)
    if (idx !== -1) items.value[idx].depend_delai = 1
  }
}
async function removeItemDelaiMulti(itemId, delaiId) {
  const res = await api(`/admin/referentiel-armp/items/${itemId}/unlink-delai-multi`, 'DELETE', { delai_id: delaiId })
  if (res.success) {
    const idx = itemsDelaisMulti.value.findIndex(d => d.item_id === itemId && d.delai_id === delaiId)
    if (idx !== -1) itemsDelaisMulti.value.splice(idx, 1)
  }
}
async function linkItemDelai(itemId, delaiId) {
  const res = await api(`/admin/referentiel-armp/items/${itemId}/link-delai`, 'PUT', { delai_id: delaiId })
  if (res.success) {
    const idx = items.value.findIndex(i => i.id === itemId)
    if (idx !== -1) items.value[idx].delai_id = delaiId
  }
}

function delaiSummary(delaiId) {
  const d = delais.value.find(x => x.id === delaiId)
  if (!d) return '—'
  const op = operations.value.find(o => o.id === d.operation_id)
  const opLabel = op ? `${op.code} — ${op.libelle}` : ''
  if (d.delai_type === 'sans-delai') return `Sans délai — ${opLabel}`
  if (d.delai_type === 'non-defini') return `Délai non défini — ${opLabel}`
  return `${d.delai_valeur ?? '?'} ${d.delai_unite ?? ''} — ${opLabel}`
}

function getDelaiModes(delaiId) {
  return delaiModes.value.filter(m => m.delai_id === delaiId).map(m => m.mode_passation_code)
}

// ── Suggestion de délais par mots-clés (parité tenant) ──────────────────
const STOPWORDS = new Set(['de','du','des','la','le','les','et','en','au','aux','à','a','d','l','un','une','pour','par','sur','dans','ou','qui','que','ne','se','ce','ces','son','sa','ses'])
function keywords(text) {
  return (text || '')
    .toLowerCase()
    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9\s]/g, ' ')
    .split(/\s+/)
    .filter(w => w.length >= 4 && !STOPWORDS.has(w))
}
function scoreMatch(itemLibelle, delai) {
  const op = operations.value.find(o => o.id === delai.operation_id)
  if (!op) return 0
  const a = new Set(keywords(itemLibelle))
  const b = keywords(op.libelle)
  let score = 0
  b.forEach(w => { if (a.has(w)) score++ })
  return score
}
function delaisPourItem(item) {
  const grille = grilles.value.find(g => g.id === item.grille_id)
  const modeCode = grille?.mode_passation_code
  const operationIds = getItemOperations(item.id).map(o => o.id)

  if (operationIds.length) {
    return delais.value
      .filter(d => operationIds.includes(d.operation_id))
      .filter(d => {
        const modes = getDelaiModes(d.id)
        return !modes.length || !modeCode || modes.includes(modeCode)
      })
      .map(d => ({ ...d, __suggere: true }))
  }

  let candidats = delais.value.filter(d => {
    const modes = getDelaiModes(d.id)
    return !modes.length || !modeCode || modes.includes(modeCode)
  })
  candidats = candidats.map(d => ({ ...d, __score: scoreMatch(item.libelle_controle, d) }))
  candidats.sort((x, y) => y.__score - x.__score)
  if (candidats.length && candidats[0].__score > 0 && (candidats.length === 1 || candidats[0].__score > (candidats[1]?.__score ?? 0))) {
    candidats[0].__suggere = true
  }
  return candidats
}
function autoSuggestDelai(libelle, modeCode) {
  let candidats = delais.value.filter(d => {
    const modes = getDelaiModes(d.id)
    return !modes.length || !modeCode || modes.includes(modeCode)
  })
  candidats = candidats.map(d => ({ ...d, __score: scoreMatch(libelle, d) }))
  candidats.sort((x, y) => y.__score - x.__score)
  if (candidats.length && candidats[0].__score >= 2 && candidats[0].__score > (candidats[1]?.__score ?? 0)) {
    return candidats[0].id
  }
  return null
}

// ── Articles <-> Item : ajout / retrait manuel (parité tenant) ─────────
async function addItemArticle(itemId, articleId) {
  const res = await api(`/admin/referentiel-armp/items/${itemId}/link-article`, 'POST', { article_id: articleId })
  if (res.success) itemsArticles.value.push({ item_id: itemId, article_id: articleId, genere_par_ia: 0 })
}
async function removeItemArticle(itemId, articleId) {
  const res = await api(`/admin/referentiel-armp/items/${itemId}/unlink-article`, 'DELETE', { article_id: articleId })
  if (res.success) {
    const idx = itemsArticles.value.findIndex(a => a.item_id === itemId && a.article_id === articleId)
    if (idx !== -1) itemsArticles.value.splice(idx, 1)
  }
}

// ── Items ───────────────────────────────────────────────────────────
const itemModal = reactive({ show:false, edit:false, form:{} })
function openItemModal(it=null, grilleId=null) {
  itemModal.show = true; itemModal.edit = !!it
  itemModal.form = it
    ? { id:it.id, numero:it.numero, libelle_controle:it.libelle_controle, preuves: it.preuves || '', obligatoire: it.obligatoire ?? 1 }
    : { id:null, grille_id:grilleId, numero:'', libelle_controle:'', preuves:'', obligatoire: 1 }
}
async function submitItem() {
  if (itemModal.edit) {
    const payload = { numero: itemModal.form.numero, libelle_controle: itemModal.form.libelle_controle, preuves: itemModal.form.preuves, obligatoire: itemModal.form.obligatoire }
    const res = await api(`/admin/referentiel-armp/items/${itemModal.form.id}`, 'PUT', payload)
    if (res.success) Object.assign(items.value.find(i=>i.id===itemModal.form.id), payload)
  } else {
    const payload = { grille_id: itemModal.form.grille_id, numero: itemModal.form.numero, libelle_controle: itemModal.form.libelle_controle, preuves: itemModal.form.preuves, obligatoire: itemModal.form.obligatoire }
    const res = await api('/admin/referentiel-armp/items', 'POST', payload)
    if (res.success) {
      const dependDelai = /d[ée]lai/i.test(itemModal.form.libelle_controle) ? 1 : 0
      items.value.push({ id: res.id, ...payload, depend_delai: dependDelai, delai_id: null, sort: 999 })

      if (dependDelai) {
        const grille = grilles.value.find(g => g.id === itemModal.form.grille_id)
        const suggestedId = autoSuggestDelai(itemModal.form.libelle_controle, grille?.mode_passation_code)
        if (suggestedId) await linkItemDelai(res.id, suggestedId)
      }
    }
  }
  itemModal.show = false
}
async function destroyItem(id) {
  if (!confirm('Supprimer ce point de contrôle ?')) return
  const res = await api(`/admin/referentiel-armp/items/${id}`, 'DELETE')
  if (res.success) {
    items.value = items.value.filter(i=>i.id!==id)
    itemsOperations.value = itemsOperations.value.filter(o => o.item_id !== id)
    itemsDelaisMulti.value = itemsDelaisMulti.value.filter(d => d.item_id !== id)
    itemsArticles.value = itemsArticles.value.filter(a => a.item_id !== id)
  }
}

async function loadLiaisons(itemId) {
  const res = await api(`/admin/referentiel-armp/items/${itemId}/liaisons`, 'GET')
  if (res.success) {
    res.articles.forEach(a => {
      if (!articlesLoi.value.find(x => x.id === a.id)) articlesLoi.value.push(a)
      if (!itemsArticles.value.find(x => x.item_id === itemId && x.article_id === a.id)) {
        itemsArticles.value.push({ item_id: itemId, article_id: a.id, genere_par_ia: a.genere_par_ia })
      }
    })
    res.delais.forEach(d => {
      if (!itemsDelaisMulti.value.find(x => x.item_id === itemId && x.delai_id === d.id)) {
        itemsDelaisMulti.value.push({ item_id: itemId, delai_id: d.id, genere_par_ia: d.genere_par_ia })
      }
    })
  }
}

async function analyserItemIA(item, silent = false) {
  analysingItem.value = item.id
  try {
    const res = await api(`/admin/referentiel-armp/items/${item.id}/ia-analyser`, 'POST')
    if (res.success) {
      (res.articles || []).forEach(a => {
        const idx = articlesLoi.value.findIndex(x => x.id === a.id)
        if (idx === -1) articlesLoi.value.push(a); else articlesLoi.value[idx] = a
        if (!itemsArticles.value.find(x => x.item_id === item.id && x.article_id === a.id)) {
          itemsArticles.value.push({ item_id: item.id, article_id: a.id, genere_par_ia: 1 })
        }
      })
      if (!silent) { msg.variant = 'success'; msg.text = `Analyse IA terminée pour le point ${item.numero}.` }
    }
  } finally {
    analysingItem.value = null
  }
}
</script>

<style scoped>
* { box-sizing: border-box; }
.rna-page { min-height: 100vh; background: #f0f2f5; display: flex; flex-direction: column; }
.rna-header { background: #fff; padding: 1.5rem 2rem; border-bottom: 1px solid #e2e8f0; }
.rna-header-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 1rem; }
.rna-title { font-size: 1.4rem; font-weight: 800; color: #1a202c; }
.rna-subtitle { font-size: .8rem; color: #a0aec0; font-family: monospace; }
.rna-header-actions { display: flex; gap: .6rem; align-items: center; }
.version-badge { background: #edf2f7; color: #4a5568; font-weight: 800; padding: .3rem .7rem; border-radius: 6px; font-size: .8rem; }
.btn-refresh, .btn-sync-all, .btn-sync-one {
  border: 2px solid #e2e8f0; background: #f7fafc; color: #4a5568; padding: .5rem 1rem; border-radius: 8px;
  font-weight: 700; font-size: .82rem; cursor: pointer; display: flex; align-items: center; gap: .4rem;
}
.btn-sync-all { background: #667eea; border-color: #667eea; color: #fff; }
.btn-sync-all:disabled { opacity: .6; }
.is-spinning i, .anim-spin { animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.tabs-row { display: flex; gap: .5rem; }
.tab-btn { background: #f7fafc; border: 2px solid transparent; padding: .5rem 1rem; border-radius: 8px; font-weight: 700; font-size: .85rem; color: #718096; cursor: pointer; display:flex; align-items:center; gap:.4rem; }
.tab-btn.active { background: #ebf4ff; border-color: #667eea; color: #667eea; }

.rna-body-simple { padding: 1.5rem; }
.sync-table { width: 100%; background: #fff; border-collapse: collapse; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.05); }
.sync-table th, .sync-table td { padding: .6rem .9rem; border-bottom: 1px solid #f0f2f5; font-size: .85rem; text-align: left; }
.badge-uptodate { background: #c6f6d5; color: #276749; padding: .2rem .5rem; border-radius: 4px; font-size: .72rem; font-weight: 700; }
.badge-behind   { background: #fed7d7; color: #9b2c2c; padding: .2rem .5rem; border-radius: 4px; font-size: .72rem; font-weight: 700; }

/* ── Onglet Grilles : bandeau stats + corps ─────────────────────── */
.rna-grilles-wrap { display: flex; flex-direction: column; height: calc(100vh - 165px); overflow: hidden; }
.rna-stats { display: flex; gap: 1.5rem; padding: .9rem 1.5rem; background: #fff; border-bottom: 1px solid #e2e8f0; flex-shrink: 0; }
.stat-box { display: flex; align-items: center; gap: .6rem; }
.stat-icon { width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 16px; flex-shrink: 0; }
.stat-count { font-weight: 800; font-size: 1.15rem; color: #2d3748; line-height: 1.1; }
.stat-label { font-size: .68rem; color: #a0aec0; }

.rna-body { display: flex; flex: 1; overflow: hidden; }
.rna-body-grilles { height: auto; }
.types-panel { width: 300px; background: #fff; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; overflow-y: auto; }
.panel-head { padding: 1rem; border-bottom: 1px solid #e2e8f0; font-weight: 700; font-size: .9rem; background: #f7fafc; }
.types-list { padding: .5rem; }
.type-card { display: flex; align-items: center; gap: .75rem; padding: .75rem; border-radius: 8px; cursor: pointer; margin-bottom: .4rem; border: 2px solid transparent; }
.type-card:hover { background: #f7fafc; }
.type-card.active { border-color: #667eea; background: #ebf4ff; }
.type-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: #f7fafc; }
.type-info { flex: 1; min-width: 0; }
.type-code { font-size: .7rem; font-weight: 800; color: #667eea; }
.type-label { font-size: .85rem; font-weight: 600; color: #2d3748; }
.type-counts { margin-top: .2rem; }
.badge-forms { font-size: .68rem; background: #edf2f7; padding: .1rem .4rem; border-radius: 4px; color: #4a5568; }

.coverage-tester { border-top: 1px solid #e2e8f0; padding: 1rem; }
.coverage-head { font-weight:700; font-size:.8rem; color:#2d3748; margin-bottom:.5rem; display:flex; align-items:center; gap:.4rem; }
.finput-sm { width: 100%; padding: .4rem .6rem; border: 1px solid #e2e8f0; border-radius: 6px; font-size: .78rem; margin-bottom: .4rem; }
.btn-sm { width: 100%; padding: .4rem; font-size: .78rem; }
.coverage-result { margin-top: .5rem; }
.coverage-warning { background:#fff5f5; border:1px solid #fed7d7; color:#9b2c2c; border-radius:6px; padding:.5rem .6rem; font-size:.72rem; margin-bottom:.4rem; }
.badge-phase-mini { background:#667eea; color:#fff; font-size:.6rem; padding:.1rem .35rem; border-radius:4px; font-weight:700; }

.forms-panel { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
.forms-empty-state { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; color:#a0aec0; gap:1rem; }
.forms-header { background:#fff; padding: 1rem 1.5rem; border-bottom: 1px solid #e2e8f0; }
.forms-title-row { display:flex; justify-content:space-between; align-items:center; }
.forms-type-badge { background:#667eea; color:#fff; padding:.4rem 1rem; border-radius:20px; font-weight:700; font-size:.85rem; }
.btn-add-form { background:#667eea; color:#fff; border:none; padding:.4rem .9rem; border-radius:7px; font-weight:700; font-size:.8rem; cursor:pointer; }
.phases-container { flex:1; overflow-y:auto; padding:1rem 1.5rem; display:flex; flex-direction:column; gap:1rem; }

.nature-group { display: flex; flex-direction: column; gap: .6rem; }
.nature-group-header { padding: .2rem .1rem; }
.badge-nature { background: #fff8ec; color: #b7791f; border: 1px solid #f6e05e; font-size: .72rem; font-weight: 800; padding: .25rem .6rem; border-radius: 5px; }

.phase-block { background:#fff; border-radius:10px; box-shadow:0 1px 4px rgba(0,0,0,.05); overflow:hidden; }
.phase-header { display:flex; align-items:center; gap:.75rem; padding:.75rem 1rem; background:#f7fafc; border-bottom:1px solid #e2e8f0; }
.phase-badge { background:#667eea; color:#fff; padding:.2rem .65rem; border-radius:12px; font-size:.75rem; font-weight:800; }
.phase-label-text { flex:1; font-weight:700; color:#2d3748; font-size:.9rem; }
.phase-count { font-size:.78rem; color:#a0aec0; }
.phase-actions { display:flex; gap:.25rem; }
.phase-forms { padding: .75rem 1rem; }
.empty-forms { text-align:center; color:#a0aec0; padding: 2rem; }
.link-btn { background:none; border:none; color:#667eea; cursor:pointer; font-weight:600; }
.link-btn-sm { background:none; border:none; color:#667eea; font-size:.7rem; cursor:pointer; font-weight:600; }

/* ── Organes rattachés à une grille ──────────────────────────────── */
.phase-organes { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem; padding: .55rem 1rem; border-bottom: 1px solid #f0f2f5; background: #fafcff; }
.badge-organe { background: #276749; color: #fff; font-size: .68rem; font-weight: 700; padding: .18rem .5rem; border-radius: 4px; cursor: pointer; display: inline-flex; align-items: center; gap: .25rem; }
.select-add-sm { font-size: .7rem; padding: .18rem .4rem; border: 1px dashed #cbd5e0; border-radius: 4px; color: #718096; background: #fff; max-width: 220px; cursor: pointer; }

.item-detail-card { background:#fff; border:1px solid #f0f2f5; border-radius:8px; padding:.75rem .9rem; margin-bottom:.6rem; }
.item-detail-head { display:flex; align-items:flex-start; gap:.6rem; }
.item-numero { background:#667eea; color:#fff; font-size:.7rem; font-weight:800; padding:.15rem .45rem; border-radius:4px; flex-shrink:0; }
.item-libelle { flex:1; font-size:.82rem; color:#2d3748; }
.item-actions { display:flex; gap:.2rem; flex-shrink:0; }
.item-preuves { margin-top:.5rem; font-size:.75rem; color:#4a5568; background:#f7fafc; padding:.4rem .6rem; border-radius:6px; }

/* ── Opérations / Délais rattachés à un point de contrôle ────────── */
.item-subrow { margin-top:.5rem; display:flex; gap:.35rem; flex-wrap:wrap; align-items:center; }
.subrow-label { font-size:.68rem; font-weight:700; color:#a0aec0; display:flex; align-items:center; gap:.2rem; margin-right:.15rem; }
.badge-operation { background:#4a5568; color:#fff; font-size:.68rem; padding:.15rem .45rem; border-radius:4px; cursor:pointer; display:inline-flex; align-items:center; gap:.25rem; }
.badge-delai { background:#38a169; color:#fff; font-size:.68rem; padding:.15rem .45rem; border-radius:4px; cursor:pointer; display:inline-flex; align-items:center; gap:.25rem; }

.item-articles { margin-top:.5rem; display:flex; gap:.35rem; flex-wrap:wrap; align-items:center; }
.badge-article { background:#2d3748; color:#fff; font-size:.68rem; padding:.15rem .45rem; border-radius:4px; cursor:pointer; display:inline-flex; align-items:center; gap:.3rem; }
.badge-article-ia { background:#3182ce; }
.stat-chip { font-size: .7rem; background: #edf2f7; padding: .2rem .5rem; border-radius: 4px; color: #4a5568; margin-left:.3rem; }
.hint-text { font-size: .72rem; color: #718096; background: #f7fafc; border-left: 3px solid #17a2b8; padding: .5rem .7rem; border-radius: 4px; display: flex; gap: .35rem; align-items: flex-start; }

.simple-table { width:100%; border-collapse: collapse; }
.simple-table th, .simple-table td { padding:.5rem .6rem; border-bottom:1px solid #f0f2f5; font-size:.82rem; text-align:left; }
.text-end { text-align:right; }
.btn-icon-sm { width:28px; height:28px; border:none; background:transparent; cursor:pointer; border-radius:5px; color:#718096; }
.btn-icon-sm:hover { background:#e2e8f0; }
.btn-icon-sm.danger:hover { background:#fed7d7; color:#c53030; }
.btn-icon-sm:disabled { opacity:.5; cursor:not-allowed; }

.modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.5); display:flex; align-items:center; justify-content:center; z-index:9999; padding:1rem; }
.modal-box { background:#fff; border-radius:12px; width:100%; max-width:480px; max-height:92vh; overflow-y:auto; }
.modal-lg { max-width:640px; }
.modal-head { display:flex; justify-content:space-between; align-items:center; padding:1.25rem 1.5rem; border-bottom:2px solid #e2e8f0; }
.btn-close { background:none; border:none; font-size:1.25rem; cursor:pointer; color:#718096; }
.modal-form { padding:1.25rem 1.5rem; display:flex; flex-direction:column; gap:1rem; }
.modal-footer { display:flex; gap:.75rem; justify-content:flex-end; padding-top:1rem; border-top:2px solid #e2e8f0; }
.fg { display:flex; flex-direction:column; gap:.35rem; }
.fg label { font-weight:600; font-size:.85rem; color:#2d3748; }
.fg-row { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
.finput { padding:.6rem .8rem; border:2px solid #e2e8f0; border-radius:8px; font-size:.9rem; width:100%; }
.finput:focus { outline:none; border-color:#667eea; }
.finput:disabled { background:#f7fafc; color:#a0aec0; }
.btn-cancel { padding:.6rem 1.25rem; background:#edf2f7; border:none; border-radius:8px; font-weight:600; cursor:pointer; }
.btn-save { padding:.6rem 1.5rem; background:#667eea; color:#fff; border:none; border-radius:8px; font-weight:700; cursor:pointer; }
</style>