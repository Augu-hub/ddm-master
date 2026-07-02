<template>
  <VerticalLayout>
    <Head title="Paramètres ADM — Marchés Publics" />

    <!-- ── HEADER ─────────────────────────────────────────────────── -->
    <b-row class="mb-0 align-items-center">
      <b-col>
        <div class="d-flex align-items-center gap-2">
          <i class="ti ti-building-store text-primary fs-5"></i>
          <h4 class="m-0 fw-semibold">Paramètres — Marchés Publics</h4>
          <small class="text-muted ms-2">Décrets N°2020-599 &amp; 600 — Bénin</small>
        </div>
      </b-col>
      <b-col cols="auto" class="d-flex gap-2">
        <b-button size="sm" variant="outline-danger" @click="resetData" :disabled="seeding">
          <i class="ti ti-trash me-1"></i>Reset
        </b-button>
        <b-button size="sm" variant="outline-warning" @click="seedData" :disabled="seeding">
          <i class="ti ti-plant me-1"></i>{{ seeding ? 'Initialisation…' : 'Initialiser' }}
        </b-button>
      </b-col>
    </b-row>

    <b-alert v-if="seedMsg.text" :variant="seedMsg.variant" dismissible @dismissed="seedMsg.text=''" class="py-2 px-3 mt-2" style="font-size:.78rem">
      {{ seedMsg.text }}
    </b-alert>

    <!-- ── STATS ──────────────────────────────────────────────────── -->
    <b-row class="g-2 mb-2 mt-1">
      <b-col lg="2" v-for="s in statsCards" :key="s.label">
        <b-card no-body class="shadow-sm stat-card">
          <b-card-body class="p-2">
            <div class="d-flex align-items-center gap-2">
              <div class="stat-icon" :class="'bg-'+s.color"><i :class="s.icon"></i></div>
              <div>
                <small class="text-muted d-block" style="font-size:.65rem;line-height:1.1">{{ s.label }}</small>
                <span class="fw-bold">{{ s.count }}</span>
              </div>
            </div>
          </b-card-body>
        </b-card>
      </b-col>
    </b-row>

    <!-- ── TABS ───────────────────────────────────────────────────── -->
    <b-card no-body class="mb-2 shadow-none border-0">
      <b-card-body class="p-1">
        <b-button-group size="sm">
          <b-button v-for="t in tabs" :key="t.key" @click="activeTab=t.key"
            :variant="activeTab===t.key?'primary':'outline-primary'">
            <i :class="t.icon+' me-1'"></i>{{ t.label }}
          </b-button>
        </b-button-group>
      </b-card-body>
    </b-card>

    <!-- ══════════════════════════════════════════════════════════════
         TAB 1 : RÉFÉRENTIELS
    ══════════════════════════════════════════════════════════════ -->
    <b-row v-if="activeTab==='referentiels'" class="g-2">

      <!-- Types AC -->
      <b-col lg="4">
        <b-card no-body class="shadow-sm">
          <b-card-header class="py-2 px-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="ti ti-building me-1 text-primary"></i>Types d'AC</h6>
            <b-button size="sm" variant="primary" @click="openForm('typesEntites')"><i class="ti ti-plus"></i></b-button>
          </b-card-header>
          <b-card-body class="p-0">
            <DataTable :value="typesEntites" size="small" class="pv-table flat">
              <Column header="Code" style="width:70px"><template #body="{data}"><Tag :value="data.code" severity="info"/></template></Column>
              <Column header="Libellé" field="libelle"/>
              <Column header="" style="width:55px" bodyClass="text-end">
                <template #body="{data}">
                  <div class="d-flex gap-1 justify-content-end">
                    <b-button size="sm" variant="light" @click="editItem('typesEntites',data)"><i class="ti ti-pencil"></i></b-button>
                    <b-button size="sm" variant="light" @click="destroyItem('typesEntites',data.id,'/m/audit.core/param-marches/types-entites')"><i class="ti ti-trash text-danger"></i></b-button>
                  </div>
                </template>
              </Column>
              <template #empty><div class="text-muted py-2 px-3 small">Aucun — initialiser</div></template>
            </DataTable>
          </b-card-body>
        </b-card>
      </b-col>

      <!-- Natures marché -->
      <b-col lg="4">
        <b-card no-body class="shadow-sm">
          <b-card-header class="py-2 px-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="ti ti-file-description me-1 text-warning"></i>Natures de marché</h6>
            <b-button size="sm" variant="warning" @click="openForm('naturesMarche')"><i class="ti ti-plus"></i></b-button>
          </b-card-header>
          <b-card-body class="p-0">
            <DataTable :value="naturesMarche" size="small" class="pv-table flat">
              <Column header="Code" style="width:65px"><template #body="{data}"><Tag :value="data.code" severity="warning"/></template></Column>
              <Column header="Libellé" field="libelle"/>
              <Column header="Sous-type" style="width:120px"><template #body="{data}"><small class="text-muted">{{ data.sous_type||'—' }}</small></template></Column>
              <Column header="" style="width:55px" bodyClass="text-end">
                <template #body="{data}">
                  <div class="d-flex gap-1 justify-content-end">
                    <b-button size="sm" variant="light" @click="editItem('naturesMarche',data)"><i class="ti ti-pencil"></i></b-button>
                    <b-button size="sm" variant="light" @click="destroyItem('naturesMarche',data.id,'/m/audit.core/param-marches/natures-marche')"><i class="ti ti-trash text-danger"></i></b-button>
                  </div>
                </template>
              </Column>
              <template #empty><div class="text-muted py-2 px-3 small">Aucun — initialiser</div></template>
            </DataTable>
          </b-card-body>
        </b-card>
      </b-col>

      <!-- Organes -->
      <b-col lg="4">
        <b-card no-body class="shadow-sm">
          <b-card-header class="py-2 px-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="ti ti-building-bank me-1 text-success"></i>Organes de contrôle</h6>
            <b-button size="sm" variant="success" @click="openForm('organes')"><i class="ti ti-plus"></i></b-button>
          </b-card-header>
          <b-card-body class="p-0">
            <DataTable :value="organes" size="small" class="pv-table flat">
              <Column header="Sigle" style="width:75px">
                <template #body="{data}"><span class="badge bg-success font-monospace" style="font-size:.67rem">{{ data.sigle||data.code }}</span></template>
              </Column>
              <Column header="Libellé" field="libelle"/>
              <Column header="Niv." style="width:80px" bodyClass="text-center">
                <template #body="{data}"><Tag v-if="data.niveau" :value="data.niveau" :severity="niveauSeverity(data.niveau)" style="font-size:.61rem"/></template>
              </Column>
              <Column header="" style="width:55px" bodyClass="text-end">
                <template #body="{data}">
                  <div class="d-flex gap-1 justify-content-end">
                    <b-button size="sm" variant="light" @click="editItem('organes',data)"><i class="ti ti-pencil"></i></b-button>
                    <b-button size="sm" variant="light" @click="destroyItem('organes',data.id,'/m/audit.core/param-marches/organes')"><i class="ti ti-trash text-danger"></i></b-button>
                  </div>
                </template>
              </Column>
              <template #empty><div class="text-muted py-2 px-3 small">Aucun — initialiser</div></template>
            </DataTable>
          </b-card-body>
        </b-card>
      </b-col>

      <!-- Modes passation — avec organes PM inline -->
      <b-col cols="12">
        <b-card no-body class="shadow-sm">
          <b-card-header class="py-2 px-3 d-flex justify-content-between align-items-center">
            <div>
              <h6 class="mb-0"><i class="ti ti-arrows-shuffle me-1 text-info"></i>Modes de passation</h6>
              <small class="text-muted">Famille PM → associez plusieurs organes de contrôle (cliquez + pour ajouter, × pour retirer)</small>
            </div>
            <b-button size="sm" variant="info" @click="openForm('modesPassation')"><i class="ti ti-plus me-1"></i>Ajouter</b-button>
          </b-card-header>
          <b-card-body class="p-0">
            <DataTable :value="modesPassation" size="small" class="pv-table flat">
              <Column header="Code" style="width:65px"><template #body="{data}"><Tag :value="data.code" severity="info"/></template></Column>
              <Column header="Libellé" field="libelle"/>
              <Column header="Fam." style="width:60px" bodyClass="text-center">
                <template #body="{data}"><span class="badge" :class="familleBadge(data.code_famille)">{{ data.code_famille||'—' }}</span></template>
              </Column>
              <Column header="Organes de contrôle (PM)" style="width:300px">
                <template #body="{data}">
                  <div v-if="data.code_famille==='PM'" class="d-flex flex-wrap gap-1 align-items-center">
                    <span v-for="oc in getModeOrganes(data.code)" :key="oc"
                          class="badge bg-primary d-flex align-items-center gap-1" style="font-size:.62rem;padding:.25rem .45rem;cursor:pointer"
                          @click="removeModeOrgane(data.code, oc)">
                      {{ oc }} <i class="ti ti-x" style="font-size:.6rem"></i>
                    </span>
                    <b-dropdown size="sm" variant="outline-primary" no-caret toggle-class="py-0 px-1" boundary="viewport">
                      <template #button-content><i class="ti ti-plus" style="font-size:.7rem"></i></template>
                      <b-dropdown-item
                        v-for="o in organes.filter(og=>!getModeOrganes(data.code).includes(og.code))"
                        :key="o.code" @click="addModeOrgane(data.code, o.code)" style="font-size:.75rem">
                        <span class="badge bg-success me-1" style="font-size:.6rem">{{ o.sigle||o.code }}</span>{{ o.libelle }}
                      </b-dropdown-item>
                    </b-dropdown>
                  </div>
                  <span v-else class="text-muted fst-italic" style="font-size:.68rem">Non applicable ({{ data.code_famille }})</span>
                </template>
              </Column>
              <Column header="" style="width:55px" bodyClass="text-end">
                <template #body="{data}">
                  <div class="d-flex gap-1 justify-content-end">
                    <b-button size="sm" variant="light" @click="editItem('modesPassation',data)"><i class="ti ti-pencil"></i></b-button>
                    <b-button size="sm" variant="light" @click="destroyItem('modesPassation',data.id,'/m/audit.core/param-marches/modes-passation')"><i class="ti ti-trash text-danger"></i></b-button>
                  </div>
                </template>
              </Column>
              <template #empty><div class="text-muted py-2 px-3 small">Aucun — initialiser</div></template>
            </DataTable>
          </b-card-body>
        </b-card>
      </b-col>

      <!-- Sources financement -->
      <b-col lg="4">
        <b-card no-body class="shadow-sm">
          <b-card-header class="py-2 px-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="ti ti-coin me-1"></i>Sources de financement</h6>
            <b-button size="sm" variant="secondary" @click="openForm('sourcesFinance')"><i class="ti ti-plus"></i></b-button>
          </b-card-header>
          <b-card-body class="p-0">
            <DataTable :value="sourcesFinance" size="small" class="pv-table flat">
              <Column header="Code" style="width:65px"><template #body="{data}"><Tag :value="data.code" severity="secondary"/></template></Column>
              <Column header="Libellé" field="libelle"/>
              <Column header="" style="width:55px" bodyClass="text-end">
                <template #body="{data}">
                  <div class="d-flex gap-1 justify-content-end">
                    <b-button size="sm" variant="light" @click="editItem('sourcesFinance',data)"><i class="ti ti-pencil"></i></b-button>
                    <b-button size="sm" variant="light" @click="destroyItem('sourcesFinance',data.id,'/m/audit.core/param-marches/sources-financement')"><i class="ti ti-trash text-danger"></i></b-button>
                  </div>
                </template>
              </Column>
              <template #empty><div class="text-muted py-2 px-3 small">Aucun — initialiser</div></template>
            </DataTable>
          </b-card-body>
        </b-card>
      </b-col>
    </b-row>

    <!-- ══════════════════════════════════════════════════════════════
         TAB 2 : SEUILS GÉNÉRAUX
    ══════════════════════════════════════════════════════════════ -->
    <b-row v-if="activeTab==='seuils'" class="g-2">
      <b-col cols="12">
        <b-alert variant="info" class="py-2 px-3 mb-0" style="font-size:.78rem">
          <i class="ti ti-info-circle me-1"></i>
          Seuils <strong>indépendants</strong> de la catégorie AC — règles de routing automatique par montant.
          Les montants exacts et organes compétents par AC sont dans <strong>Seuils par AC</strong>.
        </b-alert>
      </b-col>
      <b-col cols="12">
        <b-card no-body class="shadow-sm">
          <b-card-header class="py-2 px-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="ti ti-coin me-1 text-warning"></i>Seuils généraux — Décret N°2020-599</h6>
            <b-button size="sm" variant="warning" @click="openForm('seuilsGeneraux')"><i class="ti ti-plus me-1"></i>Ajouter</b-button>
          </b-card-header>
          <b-card-body class="p-0">
            <DataTable :value="seuilsGeneraux" size="small" class="pv-table flat">
              <Column header="#" style="width:35px" bodyClass="text-center"><template #body="{index}"><small class="text-muted">{{ index+1 }}</small></template></Column>
              <Column header="Type de seuil" style="width:230px">
                <template #body="{data}">
                  <div class="d-flex align-items-center gap-2">
                    <span class="seuil-dot" :class="'dot-'+(data.couleur||'gray')"></span>
                    <span class="fw-semibold" style="font-size:.75rem">{{ data.type_seuil }}</span>
                  </div>
                </template>
              </Column>
              <Column header="Valeur (FCFA HT)" style="width:220px">
                <template #body="{data}"><span class="font-monospace fw-semibold" style="font-size:.72rem">{{ formatSeuil(data) }}</span></template>
              </Column>
              <Column header="Mode auto" style="width:85px" bodyClass="text-center">
                <template #body="{data}">
                  <Tag v-if="data.code_mode_passation" :value="data.code_mode_passation" :severity="modeSeverity(data.code_mode_passation)"/>
                  <span v-else class="text-muted">—</span>
                </template>
              </Column>
              <Column header="Description"><template #body="{data}"><small class="text-muted">{{ data.description }}</small></template></Column>
              <Column header="" style="width:55px" bodyClass="text-end">
                <template #body="{data}">
                  <div class="d-flex gap-1 justify-content-end">
                    <b-button size="sm" variant="light" @click="editItem('seuilsGeneraux',data)"><i class="ti ti-pencil"></i></b-button>
                    <b-button size="sm" variant="light" @click="destroyItem('seuilsGeneraux',data.id,'/m/audit.core/param-marches/seuils-generaux')"><i class="ti ti-trash text-danger"></i></b-button>
                  </div>
                </template>
              </Column>
              <template #empty><div class="text-muted py-2 px-3 small">Aucun seuil — initialiser</div></template>
            </DataTable>
          </b-card-body>
        </b-card>
      </b-col>
      <b-col lg="3" v-for="s in seuilsGeneraux" :key="s.id">
        <b-card no-body class="shadow-sm seuil-visual-card" :class="'seuil-card-'+(s.couleur||'gray')">
          <b-card-body class="p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <span class="fw-bold" style="font-size:.75rem;line-height:1.2">{{ s.type_seuil }}</span>
              <span v-if="s.code_mode_passation" class="badge ms-1" :class="modeBadgeClass(s.code_mode_passation)" style="font-size:.62rem">{{ s.code_mode_passation }}</span>
            </div>
            <div class="seuil-valeur">{{ formatSeuil(s) }}</div>
            <small style="font-size:.65rem;opacity:.75">{{ s.description }}</small>
          </b-card-body>
        </b-card>
      </b-col>
    </b-row>

    <!-- ══════════════════════════════════════════════════════════════
         TAB 3 : SEUILS PAR AC
         Tableau : AC sélectionné → Natures (colonnes) × Modes (lignes)
         Pour PM : clic sur cellule → sous-tableau organes × plages montants
    ══════════════════════════════════════════════════════════════ -->
    <div v-if="activeTab==='seuils-ac'">
      <b-card no-body class="shadow-sm mb-2">
        <b-card-header class="py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
          <h6 class="mb-0"><i class="ti ti-chart-bar me-1 text-info"></i>Seuils par catégorie d'AC</h6>
          <div class="d-flex gap-2 align-items-center flex-wrap">
            <b-button-group size="sm">
              <b-button v-for="te in typesEntites" :key="te.code"
                @click="selectAC(te.code)"
                :variant="activeAC===te.code?'primary':'outline-primary'">
                {{ te.code }}
              </b-button>
            </b-button-group>
          </div>
        </b-card-header>

        <b-card-body v-if="activeAC" class="p-2">
          <div class="text-muted small mb-3 px-1">
            <strong>{{ typesEntites.find(t=>t.code===activeAC)?.libelle }}</strong>
          </div>

          <!-- Pas de cellule PM ouverte : tableau principal Natures × Modes -->
          <div v-if="!activePMCell">
            <b-alert variant="light" class="py-1 px-2 mb-2" style="font-size:.72rem;border-left:3px solid #17a2b8">
              <i class="ti ti-info-circle me-1 text-info"></i>
              Cliquez sur une cellule <span class="badge bg-primary" style="font-size:.62rem">PM</span>
              pour saisir les plages de montants par organe.
              Les modes <span class="badge bg-success" style="font-size:.62rem">SP</span>
              <span class="badge bg-warning text-dark" style="font-size:.62rem">PD</span>
              s'activent sans montant (seuils généraux).
            </b-alert>
            <div class="table-responsive">
              <table class="table table-bordered table-sm seuils-cross-table mb-0">
                <thead>
                  <tr class="table-light">
                    <th class="mode-header-cell text-center align-middle">
                      <small class="text-muted fw-normal">Mode ↓ / Nature →</small>
                    </th>
                    <th v-for="nat in naturesMarche" :key="nat.code"
                        class="nature-header text-center align-middle">
                      <div class="fw-bold" style="font-size:.73rem">{{ nat.code }}</div>
                      <div class="text-muted" style="font-size:.63rem;line-height:1.1">{{ nat.libelle }}</div>
                      <div v-if="nat.sous_type" style="font-size:.6rem;color:#999;font-style:italic">{{ nat.sous_type }}</div>
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="mode in modesPassation" :key="mode.code">
                    <td class="mode-label-cell align-middle">
                      <div class="d-flex align-items-center gap-1">
                        <span class="badge" :class="familleBadge(mode.code_famille)" style="font-size:.6rem;min-width:28px">{{ mode.code_famille }}</span>
                        <span class="fw-bold" style="font-size:.73rem">{{ mode.code }}</span>
                      </div>
                      <small class="text-muted" style="font-size:.61rem;line-height:1.1;display:block;max-width:110px">{{ mode.libelle }}</small>
                    </td>
                    <td v-for="nat in naturesMarche" :key="nat.code"
                        class="seuil-cell align-middle"
                        :class="getCellClass(activeAC, nat.code, mode.code)"
                        @click="mode.code_famille==='PM' ? openPMCell(activeAC, nat.code, mode.code) : null"
                        :style="mode.code_famille==='PM' ? 'cursor:pointer' : ''">

                      <!-- Cellule PM — résumé cliquable -->
                      <template v-if="mode.code_famille==='PM'">
                        <div v-if="getPMRules(activeAC, nat.code, mode.code).length > 0" class="pm-cell-summary">
                          <div v-for="rule in getPMRules(activeAC, nat.code, mode.code)" :key="rule.id"
                               class="pm-rule-line">
                            <!-- Organe badge -->
                            <span v-for="oc in getRuleOrganes(rule.id)" :key="oc"
                                  class="badge bg-primary me-1" style="font-size:.58rem">{{ oc }}</span>
                            <!-- Montant -->
                            <span class="font-monospace" style="font-size:.65rem;color:#1a56db">
                              {{ formatCellSeuil(rule) }}
                            </span>
                          </div>
                          <div class="text-center mt-1">
                            <small class="text-info" style="font-size:.6rem"><i class="ti ti-pencil"></i> éditer</small>
                          </div>
                        </div>
                        <div v-else class="text-center py-1">
                          <div class="pm-add-hint">
                            <i class="ti ti-plus text-primary" style="font-size:.9rem"></i>
                            <small class="d-block text-muted" style="font-size:.6rem">Saisir montants</small>
                          </div>
                        </div>
                      </template>

                      <!-- Cellule SP/PD/GG — toggle simple -->
                      <template v-else>
                        <div class="text-center">
                          <div v-if="getCellRules(activeAC, nat.code, mode.code).length > 0">
                            <Tag :value="mode.code" :severity="modeSeverity(mode.code)" style="font-size:.62rem"/>
                            <div class="mt-1">
                              <b-button size="sm" variant="light" class="py-0 px-1" style="font-size:.58rem"
                                @click.stop="removeCellRule(activeAC, nat.code, mode.code)">
                                <i class="ti ti-x text-danger"></i>
                              </b-button>
                            </div>
                          </div>
                          <div v-else>
                            <b-button size="sm" variant="light" class="py-0 px-2 cell-add-btn"
                              @click.stop="quickAddSeuil(activeAC, nat.code, mode.code)"
                              style="font-size:.62rem;opacity:.35">
                              <i class="ti ti-plus"></i>
                            </b-button>
                          </div>
                        </div>
                      </template>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Vue PM ouverte : sous-tableau organes × plages montants -->
          <div v-else>
            <div class="d-flex align-items-center gap-2 mb-3">
              <b-button size="sm" variant="outline-secondary" @click="closePMCell">
                <i class="ti ti-arrow-left me-1"></i>Retour au tableau
              </b-button>
              <div>
                <span class="fw-bold" style="font-size:.82rem">
                  {{ activeAC }} / {{ activePMCell.natCode }} / {{ activePMCell.modeCode }}
                </span>
                <small class="text-muted ms-2">— Plages de montants par organe compétent</small>
              </div>
              <b-button size="sm" variant="primary" class="ms-auto" @click="openAddPMRule">
                <i class="ti ti-plus me-1"></i>Ajouter plage
              </b-button>
            </div>

            <!-- Description AC + Nature -->
            <b-alert variant="light" class="py-1 px-2 mb-2" style="font-size:.72rem;border-left:3px solid #007bff">
              <strong>{{ typesEntites.find(t=>t.code===activeAC)?.libelle }}</strong> —
              Nature : <strong>{{ naturesMarche.find(n=>n.code===activePMCell.natCode)?.libelle }}</strong>
              <span v-if="naturesMarche.find(n=>n.code===activePMCell.natCode)?.sous_type">
                ({{ naturesMarche.find(n=>n.code===activePMCell.natCode)?.sous_type }})
              </span>
              — Mode : <strong>{{ modesPassation.find(m=>m.code===activePMCell.modeCode)?.libelle }}</strong>
            </b-alert>

            <!-- Sous-tableau : lignes = règles, colonnes = organes + montants -->
            <div class="table-responsive">
              <table class="table table-bordered table-sm pm-detail-table mb-0">
                <thead>
                  <tr class="table-primary">
                    <th style="width:40px" class="text-center">#</th>
                    <th>Plage de montants (FCFA HT)</th>
                    <th class="text-center" v-for="oc in pmCellOrganes" :key="oc"
                        style="min-width:90px">
                      <span class="badge bg-white text-primary border border-primary" style="font-size:.68rem">{{ oc }}</span>
                    </th>
                    <th style="width:60px" class="text-center align-middle">
                      <small class="text-white opacity-75" style="font-size:.6rem">Gérer dans<br>Référentiels</small>
                    </th>
                    <th style="width:60px"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(rule, idx) in pmCellRules" :key="rule.id">
                    <td class="text-center align-middle">
                      <small class="text-muted">{{ idx+1 }}</small>
                    </td>
                    <td class="align-middle">
                      <!-- Éditable inline ou affichage -->
                      <span v-if="editingPMRule !== rule.id" class="font-monospace fw-semibold" style="font-size:.75rem;color:#1a56db">
                        {{ formatCellSeuil(rule) }}
                      </span>
                      <!-- Formulaire inline édition montants -->
                      <div v-else class="d-flex gap-1 align-items-center">
                        <b-form-select class="form-select-sm" v-model="editPMForm.operateur_min" :options="operateurs" style="width:55px"/>
                        <b-form-input class="form-control-sm" type="number" v-model.number="editPMForm.valeur_min" placeholder="100 000 000" style="width:120px"/>
                        <span class="text-muted small">et</span>
                        <b-form-select class="form-select-sm" v-model="editPMForm.operateur_max" :options="operateurs" style="width:55px"/>
                        <b-form-input class="form-control-sm" type="number" v-model.number="editPMForm.valeur_max" placeholder="500 000 000" style="width:120px"/>
                        <b-button size="sm" variant="success" class="py-0 px-2" @click="savePMRule(rule.id)"><i class="ti ti-check"></i></b-button>
                        <b-button size="sm" variant="light" class="py-0 px-2" @click="editingPMRule=null"><i class="ti ti-x"></i></b-button>
                      </div>
                    </td>
                    <!-- Case cochée par organe -->
                    <td v-for="oc in pmCellOrganes" :key="oc" class="text-center align-middle pm-organe-cell">
                      <div class="d-flex justify-content-center">
                        <div class="pm-check-wrapper"
                             :class="getRuleOrganes(rule.id).includes(oc) ? 'checked' : 'unchecked'"
                             @click="toggleRuleOrgane(rule.id, oc)">
                          <i v-if="getRuleOrganes(rule.id).includes(oc)" class="ti ti-check"></i>
                          <i v-else class="ti ti-plus"></i>
                        </div>
                      </div>
                    </td>
                    <td class="text-center align-middle">
                      <b-button size="sm" variant="light" class="py-0 px-1 me-1" @click="startEditPMRule(rule)"><i class="ti ti-pencil" style="font-size:.7rem"></i></b-button>
                      <b-button size="sm" variant="light" class="py-0 px-1"
                        @click="destroyItem('seuilsAC', rule.id, '/m/audit.core/param-marches/seuils-ac')">
                        <i class="ti ti-trash text-danger" style="font-size:.7rem"></i>
                      </b-button>
                    </td>
                  </tr>
                  <!-- Ligne vide si pas de règle -->
                  <tr v-if="!pmCellRules.length">
                    <td :colspan="3 + pmCellOrganes.length" class="text-center text-muted py-3 small">
                      Aucune plage définie — cliquez <strong>Ajouter plage</strong>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Formulaire ajout rapide plage -->
            <div v-if="showAddPMRule" class="mt-2 p-2 rounded border" style="background:#f0f4ff">
              <small class="fw-semibold text-primary d-block mb-2"><i class="ti ti-plus me-1"></i>Nouvelle plage de montants</small>
              <div class="d-flex gap-2 align-items-end flex-wrap">
                <div>
                  <label class="form-label mb-1" style="font-size:.7rem">Op. min</label>
                  <b-form-select class="form-select-sm" v-model="newPMForm.operateur_min" :options="operateurs" style="width:60px"/>
                </div>
                <div>
                  <label class="form-label mb-1" style="font-size:.7rem">Valeur min (F HT)</label>
                  <b-form-input class="form-control-sm" type="number" v-model.number="newPMForm.valeur_min" placeholder="100 000 000" style="width:150px"/>
                </div>
                <div>
                  <label class="form-label mb-1" style="font-size:.7rem">Op. max</label>
                  <b-form-select class="form-select-sm" v-model="newPMForm.operateur_max" :options="operateurs" style="width:60px"/>
                </div>
                <div>
                  <label class="form-label mb-1" style="font-size:.7rem">Valeur max (F HT)</label>
                  <b-form-input class="form-control-sm" type="number" v-model.number="newPMForm.valeur_max" placeholder="500 000 000" style="width:150px"/>
                </div>
                <div>
                  <b-button size="sm" variant="primary" @click="savePMNewRule" class="py-1">
                    <i class="ti ti-check me-1"></i>Ajouter
                  </b-button>
                  <b-button size="sm" variant="light" @click="showAddPMRule=false" class="ms-1 py-1">Annuler</b-button>
                </div>
              </div>
            </div>
          </div>
        </b-card-body>

        <b-card-body v-else class="text-center text-muted py-4 small">
          Sélectionnez une catégorie AC ci-dessus
        </b-card-body>
      </b-card>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         TAB 4 : DÉLAIS
    ══════════════════════════════════════════════════════════════ -->
    <div v-if="activeTab==='delais'">
      <b-card no-body class="mb-2 shadow-none border-0">
        <b-card-body class="p-1">
          <b-button-group size="sm">
            <b-button @click="delaisSubTab='operations'" :variant="delaisSubTab==='operations'?'secondary':'outline-secondary'">
              <i class="ti ti-tool me-1"></i>Opérations
            </b-button>
            <b-button @click="delaisSubTab='dates'" :variant="delaisSubTab==='dates'?'secondary':'outline-secondary'">
              <i class="ti ti-calendar me-1"></i>Dates de référence
            </b-button>
            <b-button @click="delaisSubTab='delais'" :variant="delaisSubTab==='delais'?'secondary':'outline-secondary'">
              <i class="ti ti-clock me-1"></i>Délais (associations)
            </b-button>
          </b-button-group>
        </b-card-body>
      </b-card>

      <!-- Opérations -->
      <b-card v-if="delaisSubTab==='operations'" no-body class="shadow-sm">
        <b-card-header class="py-2 px-3 d-flex justify-content-between align-items-center">
          <div>
            <h6 class="mb-0"><i class="ti ti-tool me-1 text-secondary"></i>Référentiel des opérations</h6>
            <small class="text-muted">Étapes du processus de passation (Décret 2020-600)</small>
          </div>
          <b-button size="sm" variant="secondary" @click="openForm('operations')"><i class="ti ti-plus me-1"></i>Ajouter</b-button>
        </b-card-header>
        <b-card-body class="p-0" style="max-height:500px;overflow-y:auto">
          <DataTable :value="operations" size="small" class="pv-table flat">
            <Column header="#" style="width:35px" bodyClass="text-center"><template #body="{data}"><small class="text-muted">{{ data.sort }}</small></template></Column>
            <Column header="Code" style="width:110px"><template #body="{data}"><code style="font-size:.67rem">{{ data.code }}</code></template></Column>
            <Column header="Libellé"><template #body="{data}"><span style="font-size:.73rem">{{ data.libelle }}</span></template></Column>
            <Column header="" style="width:55px" bodyClass="text-end">
              <template #body="{data}">
                <div class="d-flex gap-1 justify-content-end">
                  <b-button size="sm" variant="light" @click="editItem('operations',data)"><i class="ti ti-pencil"></i></b-button>
                  <b-button size="sm" variant="light" @click="destroyItem('operations',data.id,'/m/audit.core/param-marches/operations')"><i class="ti ti-trash text-danger"></i></b-button>
                </div>
              </template>
            </Column>
            <template #empty><div class="text-muted py-2 px-3 small">Aucune opération — initialiser</div></template>
          </DataTable>
        </b-card-body>
      </b-card>

      <!-- ── DATES DE RÉFÉRENCE ─────────────────────────────────────────
           Principe :
           1. On saisit un libellé libre + une date calendaire réelle
           2. Dans le formulaire délai, on choisit la date via un select
              qui affiche : libellé + date formatée
      ────────────────────────────────────────────────────────────────── -->
      <b-card v-if="delaisSubTab==='dates'" no-body class="shadow-sm">
        <b-card-header class="py-2 px-3 d-flex justify-content-between align-items-center">
          <div>
            <h6 class="mb-0"><i class="ti ti-calendar me-1 text-secondary"></i>Dates de référence</h6>
            <small class="text-muted">Saisissez un libellé + choisissez la date réelle — utilisées comme point de départ des délais</small>
          </div>
          <b-button size="sm" variant="secondary" @click="openForm('datesReference')"><i class="ti ti-plus me-1"></i>Ajouter</b-button>
        </b-card-header>
        <b-card-body class="p-0" style="max-height:500px;overflow-y:auto">
          <DataTable :value="datesReference" size="small" class="pv-table flat">
            <Column header="#" style="width:35px" bodyClass="text-center"><template #body="{data}"><small class="text-muted">{{ data.sort }}</small></template></Column>
            <Column header="Code" style="width:150px"><template #body="{data}"><code style="font-size:.65rem">{{ data.code }}</code></template></Column>
            <Column header="Libellé"><template #body="{data}"><span style="font-size:.73rem">{{ data.libelle }}</span></template></Column>
            <!-- Date calendaire réelle -->
            <Column header="Date réelle" style="width:130px" bodyClass="text-center">
              <template #body="{data}">
                <span v-if="data.date_valeur" class="badge bg-info text-dark" style="font-size:.68rem">
                  {{ formatDate(data.date_valeur) }}
                </span>
                <span v-else class="text-muted fst-italic" style="font-size:.68rem">Non définie</span>
              </template>
            </Column>
            <Column header="" style="width:55px" bodyClass="text-end">
              <template #body="{data}">
                <div class="d-flex gap-1 justify-content-end">
                  <b-button size="sm" variant="light" @click="editItem('datesReference',data)"><i class="ti ti-pencil"></i></b-button>
                  <b-button size="sm" variant="light" @click="destroyItem('datesReference',data.id,'/m/audit.core/param-marches/dates-reference')"><i class="ti ti-trash text-danger"></i></b-button>
                </div>
              </template>
            </Column>
            <template #empty><div class="text-muted py-2 px-3 small">Aucune date — initialiser</div></template>
          </DataTable>
        </b-card-body>
      </b-card>

      <!-- Délais — associations -->
      <b-row v-if="delaisSubTab==='delais'" class="g-2">
        <b-col lg="9">
          <b-card no-body class="shadow-sm">
            <b-card-header class="py-2 px-3 d-flex justify-content-between align-items-center">
              <div>
                <h6 class="mb-0"><i class="ti ti-clock me-1 text-info"></i>Délais réglementaires — Associations</h6>
                <small class="text-muted">Décret 2020-600 — {{ delais.length }} règles</small>
              </div>
              <b-button size="sm" variant="info" @click="openForm('delais')"><i class="ti ti-plus me-1"></i>Ajouter</b-button>
            </b-card-header>
            <b-card-body class="p-0" style="max-height:560px;overflow-y:auto">
              <DataTable :value="delaisFormatted" size="small" class="pv-table flat">
                <Column header="#" style="width:35px" bodyClass="text-center"><template #body="{data}"><small class="text-muted">{{ data.sort }}</small></template></Column>
                <!-- PLUSIEURS ORGANES par délai -->
                <Column header="Organe(s)" style="width:140px">
                  <template #body="{data}">
                    <div class="d-flex flex-wrap gap-1">
                      <span v-for="oc in getDelaiOrganes(data.id)" :key="oc"
                            class="badge bg-primary font-monospace" style="font-size:.62rem">{{ oc }}</span>
                      <b-dropdown v-if="data.id" size="sm" variant="outline-primary" no-caret toggle-class="py-0 px-1" boundary="viewport">
                        <template #button-content><i class="ti ti-plus" style="font-size:.65rem"></i></template>
                        <b-dropdown-item v-for="o in organes.filter(og=>!getDelaiOrganes(data.id).includes(og.code))"
                          :key="o.code" @click="addDelaiOrgane(data.id, o.code)" style="font-size:.75rem">
                          {{ o.sigle||o.code }} — {{ o.libelle }}
                        </b-dropdown-item>
                      </b-dropdown>
                    </div>
                  </template>
                </Column>
                <Column header="Opération" style="width:260px">
                  <template #body="{data}"><span style="font-size:.72rem">{{ data.operation_libelle }}</span></template>
                </Column>
                <Column header="Délai" style="width:110px" bodyClass="text-center">
                  <template #body="{data}">
                    <span v-if="data.delai_type==='sans-delai'" class="text-muted fst-italic" style="font-size:.7rem">Sans délai</span>
                    <span v-else-if="data.delai_type==='non-defini'" class="text-muted" style="font-size:.7rem">Non défini</span>
                    <span v-else-if="data.delai_valeur">
                      <span class="fw-bold" :class="data.delai_type==='ouvrable'?'text-success':'text-primary'" style="font-size:.85rem">{{ data.delai_valeur }}</span>
                      <small class="text-muted d-block" style="font-size:.6rem;line-height:1">{{ data.delai_unite }}</small>
                    </span>
                    <span v-else class="text-muted">—</span>
                  </template>
                </Column>
                <Column header="Phrase / Référence">
                  <template #body="{data}">
                    <small style="font-size:.68rem;color:#555">{{ buildDelaiPhrase(data) }}</small>
                    <!-- Date réelle si définie -->
                    <div v-if="data.date_valeur" class="mt-1">
                      <span class="badge bg-info text-dark" style="font-size:.6rem">
                        <i class="ti ti-calendar me-1"></i>{{ formatDate(data.date_valeur) }}
                      </span>
                    </div>
                  </template>
                </Column>
                <Column header="Mode" style="width:65px" bodyClass="text-center">
                  <template #body="{data}">
                    <Tag v-if="data.condition_mode" :value="data.condition_mode" :severity="modeSeverity(data.condition_mode)" style="font-size:.6rem"/>
                    <span v-else class="text-muted" style="font-size:.6rem">Tous</span>
                  </template>
                </Column>
                <Column header="" style="width:55px" bodyClass="text-end">
                  <template #body="{data}">
                    <div class="d-flex gap-1 justify-content-end">
                      <b-button size="sm" variant="light" @click="editItem('delais',data)"><i class="ti ti-pencil"></i></b-button>
                      <b-button size="sm" variant="light" @click="destroyItem('delais',data.id,'/m/audit.core/param-marches/delais')"><i class="ti ti-trash text-danger"></i></b-button>
                    </div>
                  </template>
                </Column>
                <template #empty><div class="text-muted py-2 px-3 small">Aucun délai — initialiser</div></template>
              </DataTable>
            </b-card-body>
          </b-card>
        </b-col>
        <b-col lg="3">
          <b-card no-body class="shadow-sm mb-2">
            <b-card-header class="py-2 px-3"><h6 class="mb-0">Répartition</h6></b-card-header>
            <b-card-body class="p-3">
              <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded" style="background:#e3f2fd">
                <span class="small fw-semibold">Calendaires</span><b-badge bg="primary">{{ delaisCalendaires }}</b-badge>
              </div>
              <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded" style="background:#e8f5e9">
                <span class="small fw-semibold">Ouvrables</span><b-badge bg="success">{{ delaisOuvrables }}</b-badge>
              </div>
              <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded" style="background:#f5f5f5">
                <span class="small fw-semibold">Sans délai</span><b-badge bg="secondary">{{ delaisSansDelai }}</b-badge>
              </div>
              <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background:#fff3cd">
                <span class="small fw-semibold">Non défini</span><b-badge bg="warning">{{ delaisNonDefini }}</b-badge>
              </div>
            </b-card-body>
          </b-card>
        </b-col>
      </b-row>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         MODALES
    ══════════════════════════════════════════════════════════════ -->

    <!-- Types entités -->
    <b-modal v-model="modal.typesEntites" title="Type d'AC" hide-footer>
      <b-form @submit.prevent="submitForm('typesEntites','/m/audit.core/param-marches/types-entites')">
        <b-row class="g-2">
          <b-col cols="4"><label class="form-label mb-1">Code *</label><b-form-input class="form-control-sm" v-model.trim="form.code" required/></b-col>
          <b-col cols="8"><label class="form-label mb-1">Libellé *</label><b-form-input class="form-control-sm" v-model.trim="form.libelle" required/></b-col>
          <b-col cols="12"><label class="form-label mb-1">Description</label><b-form-textarea class="form-control-sm" rows="3" v-model.trim="form.description"/></b-col>
        </b-row>
        <div class="text-end mt-3"><b-button variant="light" class="me-2" @click="modal.typesEntites=false">Annuler</b-button><b-button variant="primary" type="submit">Enregistrer</b-button></div>
      </b-form>
    </b-modal>

    <!-- Sources financement -->
    <b-modal v-model="modal.sourcesFinance" title="Source de financement" hide-footer>
      <b-form @submit.prevent="submitForm('sourcesFinance','/m/audit.core/param-marches/sources-financement')">
        <b-row class="g-2">
          <b-col cols="4"><label class="form-label mb-1">Code *</label><b-form-input class="form-control-sm" v-model.trim="form.code" required/></b-col>
          <b-col cols="8"><label class="form-label mb-1">Libellé *</label><b-form-input class="form-control-sm" v-model.trim="form.libelle" required/></b-col>
        </b-row>
        <div class="text-end mt-3"><b-button variant="light" class="me-2" @click="modal.sourcesFinance=false">Annuler</b-button><b-button variant="primary" type="submit">Enregistrer</b-button></div>
      </b-form>
    </b-modal>

    <!-- Natures marché -->
    <b-modal v-model="modal.naturesMarche" title="Nature de marché" hide-footer>
      <b-form @submit.prevent="submitForm('naturesMarche','/m/audit.core/param-marches/natures-marche')">
        <b-row class="g-2">
          <b-col cols="4"><label class="form-label mb-1">Code *</label><b-form-input class="form-control-sm" v-model.trim="form.code" required/></b-col>
          <b-col cols="8"><label class="form-label mb-1">Libellé *</label><b-form-input class="form-control-sm" v-model.trim="form.libelle" required/></b-col>
          <b-col cols="12"><label class="form-label mb-1">Sous-type</label><b-form-input class="form-control-sm" v-model.trim="form.sous_type"/></b-col>
        </b-row>
        <div class="text-end mt-3"><b-button variant="light" class="me-2" @click="modal.naturesMarche=false">Annuler</b-button><b-button variant="primary" type="submit">Enregistrer</b-button></div>
      </b-form>
    </b-modal>

    <!-- Modes passation -->
    <b-modal v-model="modal.modesPassation" title="Mode de passation" hide-footer>
      <b-form @submit.prevent="submitForm('modesPassation','/m/audit.core/param-marches/modes-passation')">
        <b-row class="g-2">
          <b-col cols="4"><label class="form-label mb-1">Code *</label><b-form-input class="form-control-sm" v-model.trim="form.code" required/></b-col>
          <b-col cols="8"><label class="form-label mb-1">Libellé *</label><b-form-input class="form-control-sm" v-model.trim="form.libelle" required/></b-col>
          <b-col cols="8"><label class="form-label mb-1">Famille</label><b-form-input class="form-control-sm" v-model.trim="form.famille"/></b-col>
          <b-col cols="4"><label class="form-label mb-1">Code famille</label>
            <b-form-select class="form-select-sm" v-model="form.code_famille" :options="[{value:'',text:'—'},'PM','SP','PD','GG','PCD']"/>
          </b-col>
        </b-row>
        <div class="text-end mt-3"><b-button variant="light" class="me-2" @click="modal.modesPassation=false">Annuler</b-button><b-button variant="primary" type="submit">Enregistrer</b-button></div>
      </b-form>
    </b-modal>

    <!-- Organes -->
    <b-modal v-model="modal.organes" title="Organe de contrôle" hide-footer>
      <b-form @submit.prevent="submitForm('organes','/m/audit.core/param-marches/organes')">
        <b-row class="g-2">
          <b-col cols="4"><label class="form-label mb-1">Code *</label><b-form-input class="form-control-sm" v-model.trim="form.code" required/></b-col>
          <b-col cols="4"><label class="form-label mb-1">Sigle</label><b-form-input class="form-control-sm" v-model.trim="form.sigle"/></b-col>
          <b-col cols="4"><label class="form-label mb-1">Niveau</label>
            <b-form-select class="form-select-sm" v-model="form.niveau" :options="[{value:'',text:'—'},'national','departemental','local','entite']"/>
          </b-col>
          <b-col cols="12"><label class="form-label mb-1">Libellé *</label><b-form-input class="form-control-sm" v-model.trim="form.libelle" required/></b-col>
        </b-row>
        <div class="text-end mt-3"><b-button variant="light" class="me-2" @click="modal.organes=false">Annuler</b-button><b-button variant="primary" type="submit">Enregistrer</b-button></div>
      </b-form>
    </b-modal>

    <!-- Seuils généraux -->
    <b-modal v-model="modal.seuilsGeneraux" title="Seuil général" hide-footer size="lg">
      <b-form @submit.prevent="submitForm('seuilsGeneraux','/m/audit.core/param-marches/seuils-generaux')">
        <b-row class="g-2">
          <b-col cols="12"><label class="form-label mb-1">Type de seuil *</label><b-form-input class="form-control-sm" v-model.trim="form.type_seuil" required/></b-col>
          <b-col cols="3"><label class="form-label mb-1">Op. min</label><b-form-select class="form-select-sm" v-model="form.operateur_min" :options="operateurs"/></b-col>
          <b-col cols="3"><label class="form-label mb-1">Valeur min (F HT)</label><b-form-input class="form-control-sm" type="number" v-model.number="form.valeur_min"/></b-col>
          <b-col cols="3"><label class="form-label mb-1">Op. max</label><b-form-select class="form-select-sm" v-model="form.operateur_max" :options="operateurs"/></b-col>
          <b-col cols="3"><label class="form-label mb-1">Valeur max (F HT)</label><b-form-input class="form-control-sm" type="number" v-model.number="form.valeur_max"/></b-col>
          <b-col cols="12">
            <label class="form-label mb-1">Mode automatique</label>
            <b-form-select class="form-select-sm" v-model="form.code_mode_passation"
              :options="[{value:'',text:'— Aucun —'},...modesPassation.map(m=>({value:m.code,text:m.code+' — '+m.libelle}))]"/>
          </b-col>
          <b-col cols="12"><label class="form-label mb-1">Description</label><b-form-textarea class="form-control-sm" rows="2" v-model.trim="form.description"/></b-col>
          <b-col cols="12">
            <label class="form-label mb-1">Couleur</label>
            <div class="d-flex gap-2">
              <span v-for="c in couleurs" :key="c.val" class="couleur-swatch" :class="['swatch-'+c.val, form.couleur===c.val?'swatch-selected':'']" @click="form.couleur=c.val" :title="c.label"></span>
            </div>
          </b-col>
        </b-row>
        <div class="text-end mt-3"><b-button variant="light" class="me-2" @click="modal.seuilsGeneraux=false">Annuler</b-button><b-button variant="primary" type="submit">Enregistrer</b-button></div>
      </b-form>
    </b-modal>

    <!-- Opérations -->
    <b-modal v-model="modal.operations" title="Opération" hide-footer size="lg">
      <b-form @submit.prevent="submitForm('operations','/m/audit.core/param-marches/operations')">
        <b-row class="g-2">
          <b-col cols="4"><label class="form-label mb-1">Code *</label><b-form-input class="form-control-sm" v-model.trim="form.code" required placeholder="PPMP"/></b-col>
          <b-col cols="12"><label class="form-label mb-1">Libellé *</label><b-form-textarea class="form-control-sm" rows="3" v-model.trim="form.libelle" required/></b-col>
          <b-col cols="12"><label class="form-label mb-1">Description</label><b-form-textarea class="form-control-sm" rows="2" v-model.trim="form.description"/></b-col>
        </b-row>
        <div class="text-end mt-3"><b-button variant="light" class="me-2" @click="modal.operations=false">Annuler</b-button><b-button variant="primary" type="submit">Enregistrer</b-button></div>
      </b-form>
    </b-modal>

    <!-- ── DATES DE RÉFÉRENCE
         Libellé libre + datepicker pour la date réelle
         Dans le délai, on choisit via un select qui affiche libellé + date
    ── -->
    <b-modal v-model="modal.datesReference" title="Date de référence" hide-footer size="lg">
      <b-form @submit.prevent="submitForm('datesReference','/m/audit.core/param-marches/dates-reference')">
        <b-row class="g-2">
          <b-col cols="5">
            <label class="form-label mb-1">Code *</label>
            <b-form-input class="form-control-sm" v-model.trim="form.code" required placeholder="APPRO_BUDGET"/>
          </b-col>
          <b-col cols="7">
            <!-- Datepicker natif HTML5 — date réelle -->
            <label class="form-label mb-1">
              <i class="ti ti-calendar me-1 text-info"></i>Date réelle (choisissez dans le calendrier)
            </label>
            <b-form-input class="form-control-sm" type="date" v-model="form.date_valeur"/>
          </b-col>
          <b-col cols="12">
            <label class="form-label mb-1">Libellé *</label>
            <b-form-textarea class="form-control-sm" rows="2" v-model.trim="form.libelle" required
              placeholder="ex : l'approbation du budget par l'autorité compétente"/>
          </b-col>
          <b-col cols="12">
            <label class="form-label mb-1">Description</label>
            <b-form-textarea class="form-control-sm" rows="2" v-model.trim="form.description"/>
          </b-col>
        </b-row>
        <!-- Aperçu de la phrase dans le délai -->
        <b-alert v-if="form.libelle || form.date_valeur" variant="light" class="py-2 px-3 mt-3 mb-0" style="font-size:.78rem;border-left:3px solid #17a2b8">
          <strong>Aperçu dans un délai :</strong>
          <span class="ms-2 text-muted">
            10 jours calendaires <em>à compter de</em>
            <strong>{{ form.libelle || '…' }}</strong>
            <span v-if="form.date_valeur" class="badge bg-info text-dark ms-1" style="font-size:.68rem">{{ formatDate(form.date_valeur) }}</span>
          </span>
        </b-alert>
        <div class="text-end mt-3"><b-button variant="light" class="me-2" @click="modal.datesReference=false">Annuler</b-button><b-button variant="primary" type="submit">Enregistrer</b-button></div>
      </b-form>
    </b-modal>

    <!-- Délai — association avec PLUSIEURS organes + date de référence choisie -->
    <b-modal v-model="modal.delais" title="Délai réglementaire" hide-footer size="xl">
      <b-form @submit.prevent="submitDelai">
        <b-row class="g-2">
          <!-- PLUSIEURS ORGANES : sélection multiple inline -->
          <b-col cols="12">
            <label class="form-label mb-1"><i class="ti ti-building-bank me-1 text-success"></i>Organe(s) responsable(s) *</label>
            <div class="d-flex flex-wrap gap-1 mb-1 p-2 rounded border" style="min-height:34px;background:#f8fffe">
              <span v-for="oc in form.organes_codes" :key="oc"
                    class="badge bg-primary d-flex align-items-center gap-1" style="font-size:.72rem;padding:.3rem .5rem">
                {{ oc }}
                <i class="ti ti-x" style="cursor:pointer" @click="removeFormOrgane(oc)"></i>
              </span>
              <b-dropdown size="sm" variant="outline-primary" no-caret toggle-class="py-0 px-2" boundary="viewport" v-if="organes.filter(o=>!form.organes_codes.includes(o.code)).length">
                <template #button-content><i class="ti ti-plus me-1" style="font-size:.7rem"></i><small style="font-size:.7rem">Ajouter organe</small></template>
                <b-dropdown-item v-for="o in organes.filter(og=>!form.organes_codes.includes(og.code))"
                  :key="o.code" @click="form.organes_codes.push(o.code)" style="font-size:.75rem">
                  <span class="badge bg-success me-1" style="font-size:.62rem">{{ o.sigle||o.code }}</span>{{ o.libelle }}
                </b-dropdown-item>
              </b-dropdown>
            </div>
          </b-col>
          <b-col cols="12">
            <label class="form-label mb-1">Opération *</label>
            <b-form-select class="form-select-sm" v-model="form.operation_id" required
              :options="[{value:'',text:'— Choisir l\'opération —'},...operations.map(o=>({value:o.id,text:o.code+' — '+o.libelle.substring(0,70)+'…'}))]"/>
          </b-col>
          <b-col cols="3">
            <label class="form-label mb-1">Type délai *</label>
            <b-form-select class="form-select-sm" v-model="form.delai_type" :options="typesDelai"/>
          </b-col>
          <b-col cols="2">
            <label class="form-label mb-1">Valeur</label>
            <b-form-input class="form-control-sm" type="number" v-model.number="form.delai_valeur"
              :disabled="['sans-delai','non-defini'].includes(form.delai_type)"/>
          </b-col>
          <b-col cols="3">
            <label class="form-label mb-1">Unité</label>
            <b-form-select class="form-select-sm" v-model="form.delai_unite" :options="unitesDelai"
              :disabled="['sans-delai','non-defini'].includes(form.delai_type)"/>
          </b-col>
          <b-col cols="4">
            <label class="form-label mb-1">Condition mode</label>
            <b-form-select class="form-select-sm" v-model="form.condition_mode"
              :options="[{value:'',text:'Tous modes'},...modesPassation.map(m=>({value:m.code,text:m.code+' — '+m.libelle}))]"/>
          </b-col>
          <!-- Mot liaison + Date de référence (choisie dans le select) -->
          <template v-if="!['sans-delai','non-defini'].includes(form.delai_type)">
            <b-col cols="3">
              <label class="form-label mb-1">Mot de liaison</label>
              <b-form-select class="form-select-sm" v-model="form.mot_liaison"
                :options="[{value:'',text:'—'},'à compter de','avant','après']"/>
            </b-col>
            <b-col cols="9">
              <label class="form-label mb-1">
                <i class="ti ti-calendar me-1 text-info"></i>Opération de référence
                <small class="text-muted ms-1">(créées dans l'onglet Dates de référence)</small>
              </label>
              <b-form-select class="form-select-sm" v-model="form.date_reference_id"
                :options="[{value:'',text:'— Aucune —'},...datesReference.map(d=>({
                  value: d.id,
                  text: d.libelle + (d.date_valeur ? ' — ' + formatDate(d.date_valeur) : '')
                }))]"/>
            </b-col>
          </template>
          <b-col cols="12">
            <label class="form-label mb-1">Note / contexte</label>
            <b-form-input class="form-control-sm" v-model.trim="form.note"/>
          </b-col>
        </b-row>
        <!-- Aperçu phrase complète -->
        <b-alert v-if="form.operation_id" variant="light" class="py-2 px-3 mt-3 mb-0" style="font-size:.78rem;border-left:3px solid #007bff">
          <strong>Aperçu :</strong>
          <span class="ms-2 text-dark">{{ previewDelaiPhrase }}</span>
        </b-alert>
        <div class="text-end mt-3">
          <b-button variant="light" class="me-2" @click="modal.delais=false">Annuler</b-button>
          <b-button variant="primary" type="submit" :disabled="!form.organes_codes.length">Enregistrer</b-button>
        </div>
      </b-form>
    </b-modal>

  </VerticalLayout>
</template>

<script setup>
import { Head } from "@inertiajs/vue3"
import { ref, reactive, computed } from "vue"
import VerticalLayout from "@/layoutsparam/VerticalLayout.vue"
import DataTable from "primevue/datatable"
import Column from "primevue/column"
import Tag from "primevue/tag"

// ── Props ──────────────────────────────────────────────────────────────────
const props = defineProps({
  typesEntites:    { type: Array,  default: () => [] },
  sourcesFinance:  { type: Array,  default: () => [] },
  naturesMarche:   { type: Array,  default: () => [] },
  modesPassation:  { type: Array,  default: () => [] },
  organes:         { type: Array,  default: () => [] },
  modeOrganes:     { type: Array,  default: () => [] },
  seuilsGeneraux:  { type: Array,  default: () => [] },
  seuilsAC:        { type: Array,  default: () => [] },
  seuilsAcOrganes: { type: Array,  default: () => [] },
  operations:      { type: Array,  default: () => [] },
  datesReference:  { type: Array,  default: () => [] },
  delais:          { type: Array,  default: () => [] },
  delaiOrganes:    { type: Array,  default: () => [] }, // [{delai_id, organe_code}]
  activeTab:       { type: String, default: 'referentiels' },
})

// ── État réactif ───────────────────────────────────────────────────────────
const typesEntites    = ref([...props.typesEntites])
const sourcesFinance  = ref([...props.sourcesFinance])
const naturesMarche   = ref([...props.naturesMarche])
const modesPassation  = ref([...props.modesPassation])
const organes         = ref([...props.organes])
const modeOrganes     = ref([...props.modeOrganes])
const seuilsGeneraux  = ref([...props.seuilsGeneraux])
const seuilsAC        = ref([...props.seuilsAC])
const seuilsAcOrganes = ref([...props.seuilsAcOrganes])
const operations      = ref([...props.operations])
const datesReference  = ref([...props.datesReference])
const delais          = ref([...props.delais])
const delaiOrganes    = ref([...props.delaiOrganes])

const activeTab    = ref(props.activeTab)
const activeAC     = ref('')
const delaisSubTab = ref('delais')
const seeding      = ref(false)
const seedMsg      = reactive({ text: '', variant: 'success' })

// ── Vue PM Cell ────────────────────────────────────────────────────────────
const activePMCell   = ref(null)   // { acCode, natCode, modeCode }
const showAddPMRule  = ref(false)
const editingPMRule  = ref(null)
const editPMForm     = reactive({ operateur_min:'>=', valeur_min:null, operateur_max:'<', valeur_max:null })
const newPMForm      = reactive({ operateur_min:'>=', valeur_min:null, operateur_max:'<', valeur_max:null })

// Organes présents dans la vue PM (union de tous les organes des règles de cette cellule)
const pmCellRules = computed(() => {
  if (!activePMCell.value) return []
  const { acCode, natCode, modeCode } = activePMCell.value
  return seuilsAC.value.filter(r =>
    r.type_entite_code===acCode && r.nature_marche_code===natCode && r.mode_passation_code===modeCode
  )
})

// pmCellOrganes = organes du mode PM depuis pm_mode_organes (chargés au seed, pas de sélection)
const pmCellOrganes = computed(() => {
  if (!activePMCell.value) return []
  return getModeOrganes(activePMCell.value.modeCode)
})

// ── Tabs ───────────────────────────────────────────────────────────────────
const tabs = [
  { key: 'referentiels', label: 'Référentiels',    icon: 'ti ti-adjustments' },
  { key: 'seuils',       label: 'Seuils généraux', icon: 'ti ti-coin' },
  { key: 'seuils-ac',    label: 'Seuils par AC',   icon: 'ti ti-chart-bar' },
  { key: 'delais',       label: 'Délais',           icon: 'ti ti-clock' },
]

// ── Stats ──────────────────────────────────────────────────────────────────
const statsCards = computed(() => [
  { label: 'Types AC',    count: typesEntites.value.length,   color: 'primary',   icon: 'ti ti-building' },
  { label: 'Natures',     count: naturesMarche.value.length,  color: 'warning',   icon: 'ti ti-file-description' },
  { label: 'Modes',       count: modesPassation.value.length, color: 'info',      icon: 'ti ti-arrows-shuffle' },
  { label: 'Organes',     count: organes.value.length,        color: 'success',   icon: 'ti ti-building-bank' },
  { label: 'Seuils gén.', count: seuilsGeneraux.value.length, color: 'danger',    icon: 'ti ti-coin' },
  { label: 'Opérations',  count: operations.value.length,     color: 'secondary', icon: 'ti ti-tool' },
])

// ── Form ───────────────────────────────────────────────────────────────────
const currentEntity = ref('')
const currentEditId = ref(null)
const form = reactive({
  code: '', libelle: '', description: '', sous_type: '',
  famille: '', code_famille: '', sigle: '', niveau: '',
  type_seuil: '', valeur_min: null, valeur_max: null,
  operateur_min: '>=', operateur_max: '<=',
  code_mode_passation: '', couleur: 'gray',
  date_valeur: '',   // date réelle pour pm_dates_reference
  operation_id: '',
  delai_valeur: null, delai_unite: 'jours calendaires', delai_type: 'calendaire',
  mot_liaison: 'à compter de', date_reference_id: '',
  condition_mode: '', note: '',
  organes_codes: [],  // PLUSIEURS organes pour un délai
})

const modal = reactive({
  typesEntites: false, sourcesFinance: false, naturesMarche: false,
  modesPassation: false, organes: false, seuilsGeneraux: false,
  operations: false, datesReference: false, delais: false,
})

// ── Constantes ─────────────────────────────────────────────────────────────
const operateurs  = [{ value:'', text:'—' }, '>','>=','<','<=','=']
const couleurs    = [{ val:'green',label:'Vert' },{ val:'blue',label:'Bleu' },{ val:'orange',label:'Orange' },{ val:'red',label:'Rouge' },{ val:'gray',label:'Gris' }]
const unitesDelai = ['jours calendaires','jours ouvrables','jour ouvrable','jours ouvrés','jour']
const typesDelai  = [
  { value:'calendaire',  text:'Jours calendaires' },
  { value:'ouvrable',    text:'Jours ouvrables / ouvrés' },
  { value:'sans-delai',  text:'Sans délai' },
  { value:'non-defini',  text:'Délai non défini' },
]

// ── Fields map ─────────────────────────────────────────────────────────────
const fieldsMap = {
  typesEntites:   ['code','libelle','description'],
  sourcesFinance: ['code','libelle','description'],
  naturesMarche:  ['code','libelle','sous_type','description'],
  modesPassation: ['code','libelle','famille','code_famille','description'],
  organes:        ['code','libelle','sigle','niveau','description'],
  seuilsGeneraux: ['type_seuil','valeur_min','valeur_max','operateur_min','operateur_max','code_mode_passation','description','couleur'],
  seuilsAC:       ['type_entite_code','nature_marche_code','mode_passation_code','valeur_min','valeur_max','operateur_min','operateur_max'],
  operations:     ['code','libelle','description'],
  datesReference: ['code','libelle','date_valeur','description'],
}

// ── Helpers affichage ──────────────────────────────────────────────────────
const fmtM = v => v != null ? (Number(v)/1000000).toLocaleString('fr-FR') + ' M F' : ''

const formatSeuil = s => {
  const p = []
  if (s.operateur_min != null && s.valeur_min != null) p.push(`${s.operateur_min} ${fmtM(s.valeur_min)}`)
  if (s.operateur_max != null && s.valeur_max != null) p.push(`${s.operateur_max} ${fmtM(s.valeur_max)}`)
  return p.length ? p.join(' et ') : '≥ Seuil passation (par AC)'
}

const formatCellSeuil = r => {
  const p = []
  if (r.operateur_min && r.valeur_min != null) p.push(`${r.operateur_min} ${fmtM(r.valeur_min)}`)
  if (r.operateur_max && r.valeur_max != null) p.push(`${r.operateur_max} ${fmtM(r.valeur_max)}`)
  return p.length ? p.join(' et ') : '≥ Seuil de passation'
}

const formatDate = v => {
  if (!v) return ''
  const d = new Date(v)
  return d.toLocaleDateString('fr-FR', { day:'2-digit', month:'short', year:'numeric' })
}

const familleBadge   = c => ({ PM:'bg-primary', SP:'bg-info text-dark', PD:'bg-success', GG:'bg-warning text-dark', PCD:'bg-danger' }[c]||'bg-secondary')
const niveauSeverity = n => ({ national:'info', departemental:'warning', local:'success', entite:'secondary' }[n]||'secondary')
const modeSeverity   = c => ({ SD:'success', DC:'info', DRP:'warning', AOO:'danger', AOR:'danger', GAG:'secondary', ACC:'secondary' }[c]||'secondary')
const modeBadgeClass = c => ({ SD:'bg-success', DC:'bg-info text-dark', DRP:'bg-warning text-dark', AOO:'bg-danger', AOR:'bg-danger', GAG:'bg-secondary', ACC:'bg-secondary' }[c]||'bg-secondary')

const getModeOrganes  = code => modeOrganes.value.filter(mo=>mo.mode_passation_code===code).map(mo=>mo.organe_code)
const getRuleOrganes  = id   => seuilsAcOrganes.value.filter(o=>o.seuil_ac_id===id).map(o=>o.organe_code)
const getDelaiOrganes = id   => delaiOrganes.value.filter(o=>o.delai_id===id).map(o=>o.organe_code)

// Tableau croisé helpers
const getCellRules   = (acCode, natCode, modeCode) => seuilsAC.value.filter(r => r.type_entite_code===acCode && r.nature_marche_code===natCode && r.mode_passation_code===modeCode)
const getPMRules     = getCellRules

const getCellClass = (acCode, natCode, modeCode) => {
  const rules = getCellRules(acCode, natCode, modeCode)
  if (!rules.length) return 'cell-empty'
  const mode = modesPassation.value.find(m=>m.code===modeCode)
  return `cell-${(mode?.code_famille||'other').toLowerCase()}`
}

// Délais formatés avec jointure opération + date
const delaisFormatted = computed(() =>
  delais.value.map(d => ({
    ...d,
    operation_libelle:       operations.value.find(o=>o.id===d.operation_id)?.libelle || '?',
    date_reference_libelle:  datesReference.value.find(dr=>dr.id===d.date_reference_id)?.libelle || '',
    date_valeur:             datesReference.value.find(dr=>dr.id===d.date_reference_id)?.date_valeur || '',
  }))
)

const buildDelaiPhrase = d => {
  if (d.delai_type==='sans-delai') return 'Sans délai' + (d.date_reference_libelle ? ` — après ${d.date_reference_libelle}` : '')
  if (d.delai_type==='non-defini') return 'Délai non défini'
  const duree   = d.delai_valeur ? `${d.delai_valeur} ${d.delai_unite}` : ''
  const liaison = d.mot_liaison  || ''
  const dateRef = d.date_reference_libelle || ''
  return [duree, liaison, dateRef].filter(Boolean).join(' ')
}

// Aperçu en temps réel dans le modal délai
const previewDelaiPhrase = computed(() => {
  const op      = operations.value.find(o=>o.id==form.operation_id)
  const dateRef = datesReference.value.find(d=>d.id==form.date_reference_id)
  if (!op) return ''
  const orgs = form.organes_codes.join(' / ') || '?'
  if (form.delai_type==='sans-delai') return `${orgs} — ${op.libelle} : Sans délai`
  if (form.delai_type==='non-defini') return `${orgs} — ${op.libelle} : Délai non défini`
  const duree    = form.delai_valeur ? `${form.delai_valeur} ${form.delai_unite}` : '…'
  const liaison  = form.mot_liaison  || '…'
  const dateLabel= dateRef ? dateRef.libelle + (dateRef.date_valeur ? ` (${formatDate(dateRef.date_valeur)})` : '') : '…'
  return `${orgs} — ${duree} ${liaison} ${dateLabel}`
})

const delaisCalendaires = computed(() => delais.value.filter(d=>d.delai_type==='calendaire').length)
const delaisOuvrables   = computed(() => delais.value.filter(d=>d.delai_type==='ouvrable').length)
const delaisSansDelai   = computed(() => delais.value.filter(d=>d.delai_type==='sans-delai').length)
const delaisNonDefini   = computed(() => delais.value.filter(d=>d.delai_type==='non-defini').length)

// ── CSRF ───────────────────────────────────────────────────────────────────
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? ''

async function apiFetch(url, method, body) {
  const r = await fetch(url, {
    method,
    headers: { 'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN': csrf() },
    body: body ? JSON.stringify(body) : undefined,
  })
  return r.json()
}

// ── Form helpers ───────────────────────────────────────────────────────────
function resetForm() {
  Object.keys(form).forEach(k => {
    if (Array.isArray(form[k])) { form[k] = [] }
    else if (typeof form[k]==='number') { form[k] = null }
    else { form[k] = '' }
  })
  form.operateur_min = '>='; form.operateur_max = '<='
  form.couleur       = 'gray'
  form.delai_unite   = 'jours calendaires'; form.delai_type = 'calendaire'
  form.mot_liaison   = 'à compter de'
}

function openForm(entity) {
  currentEntity.value = entity
  currentEditId.value = null
  resetForm()
  modal[entity] = true
}

function editItem(entity, data) {
  currentEntity.value = entity
  currentEditId.value = data.id
  resetForm()
  Object.keys(form).forEach(k => {
    if (k === 'organes_codes') { form.organes_codes = [...(getDelaiOrganes(data.id) || [])] }
    else { form[k] = data[k] ?? (Array.isArray(form[k]) ? [] : (typeof form[k]==='number' ? null : '')) }
  })
  modal[entity] = true
}

async function submitForm(entity, url) {
  const payload = {}
  ;(fieldsMap[entity]||[]).forEach(k => { payload[k] = form[k] ?? null })
  try {
    if (currentEditId.value) {
      const res = await apiFetch(`${url}/${currentEditId.value}`, 'PUT', payload)
      if (res.success) {
        const arr = stateMap[entity].value
        const idx = arr.findIndex(x=>x.id===currentEditId.value)
        if (idx!==-1) arr[idx] = { ...arr[idx], ...payload }
      }
    } else {
      const res = await apiFetch(url, 'POST', payload)
      if (res.success) stateMap[entity].value.push({ id:res.id, ...payload })
    }
    modal[entity] = false
  } catch(e) { console.error(e) }
}

// Submit délai spécial (plusieurs organes)
async function submitDelai() {
  if (!form.organes_codes.length) return
  const payload = {
    operation_id:      form.operation_id,
    delai_valeur:      ['sans-delai','non-defini'].includes(form.delai_type) ? null : form.delai_valeur,
    delai_unite:       ['sans-delai','non-defini'].includes(form.delai_type) ? null : form.delai_unite,
    delai_type:        form.delai_type,
    mot_liaison:       form.mot_liaison    || null,
    date_reference_id: form.date_reference_id || null,
    condition_mode:    form.condition_mode || null,
    note:              form.note           || null,
    organes_codes:     form.organes_codes,   // tableau → contrôleur crée N lignes dans pm_delai_organes
  }
  try {
    if (currentEditId.value) {
      const res = await apiFetch(`/m/audit.core/param-marches/delais/${currentEditId.value}`, 'PUT', payload)
      if (res.success) {
        const idx = delais.value.findIndex(x=>x.id===currentEditId.value)
        if (idx!==-1) delais.value[idx] = { ...delais.value[idx], ...payload }
        // Resync organes du délai
        delaiOrganes.value = delaiOrganes.value.filter(o=>o.delai_id!==currentEditId.value)
        form.organes_codes.forEach(oc => delaiOrganes.value.push({ delai_id:currentEditId.value, organe_code:oc }))
      }
    } else {
      const res = await apiFetch('/m/audit.core/param-marches/delais', 'POST', payload)
      if (res.success) {
        delais.value.push({ id:res.id, ...payload })
        form.organes_codes.forEach(oc => delaiOrganes.value.push({ delai_id:res.id, organe_code:oc }))
      }
    }
    modal.delais = false
  } catch(e) { console.error(e) }
}

async function destroyItem(entity, id, url) {
  if (!confirm('Supprimer ?')) return
  try {
    const res = await apiFetch(`${url}/${id}`, 'DELETE')
    if (res.success) {
      const arr = stateMap[entity]?.value
      if (arr) { const idx=arr.findIndex(x=>x.id===id); if(idx!==-1) arr.splice(idx,1) }
      // Si seuil AC : fermer la vue PM si on supprime la règle active
      if (entity==='seuilsAC' && activePMCell.value) { /* reste ouvert, la règle disparaît de pmCellRules */ }
    }
  } catch(e) { console.error(e) }
}

const stateMap = {
  typesEntites, sourcesFinance, naturesMarche, modesPassation, organes,
  seuilsGeneraux, seuilsAC, operations, datesReference, delais
}

// ── Organes form délai ─────────────────────────────────────────────────────
function removeFormOrgane(oc) {
  const idx = form.organes_codes.indexOf(oc)
  if (idx!==-1) form.organes_codes.splice(idx,1)
}

// ── Organes délais (ajout depuis le tableau) ───────────────────────────────
async function addDelaiOrgane(delaiId, organeCode) {
  const res = await apiFetch('/m/audit.core/param-marches/delai-organes', 'POST', { delai_id:delaiId, organe_code:organeCode })
  if (res.success) delaiOrganes.value.push({ delai_id:delaiId, organe_code:organeCode })
}

async function removeDelaiOrgane(delaiId, organeCode) {
  const res = await apiFetch('/m/audit.core/param-marches/delai-organes', 'DELETE', { delai_id:delaiId, organe_code:organeCode })
  if (res.success) {
    const idx = delaiOrganes.value.findIndex(o=>o.delai_id===delaiId && o.organe_code===organeCode)
    if (idx!==-1) delaiOrganes.value.splice(idx,1)
  }
}

// ── Mode Organes PM ────────────────────────────────────────────────────────
async function addModeOrgane(modeCode, organeCode) {
  const res = await apiFetch('/m/audit.core/param-marches/mode-organes', 'POST', { mode_passation_code:modeCode, organe_code:organeCode })
  if (res.success) modeOrganes.value.push({ mode_passation_code:modeCode, organe_code:organeCode })
}
async function removeModeOrgane(modeCode, organeCode) {
  const res = await apiFetch('/m/audit.core/param-marches/mode-organes', 'DELETE', { mode_passation_code:modeCode, organe_code:organeCode })
  if (res.success) {
    const idx = modeOrganes.value.findIndex(mo=>mo.mode_passation_code===modeCode && mo.organe_code===organeCode)
    if (idx!==-1) modeOrganes.value.splice(idx,1)
  }
}

// ── Seuils AC — Vue PM Cell ────────────────────────────────────────────────
function selectAC(code) { activeAC.value = code; closePMCell() }
function openPMCell(acCode, natCode, modeCode) { activePMCell.value = { acCode, natCode, modeCode }; showAddPMRule.value = false; editingPMRule.value = null }
function closePMCell() { activePMCell.value = null; showAddPMRule.value = false }
function openAddPMRule() { Object.assign(newPMForm, { operateur_min:'>=', valeur_min:null, operateur_max:'<', valeur_max:null }); showAddPMRule.value = true }

async function savePMNewRule() {
  if (!activePMCell.value) return
  const { acCode, natCode, modeCode } = activePMCell.value
  const payload = { type_entite_code:acCode, nature_marche_code:natCode, mode_passation_code:modeCode, ...newPMForm }
  const res = await apiFetch('/m/audit.core/param-marches/seuils-ac', 'POST', payload)
  if (res.success) { seuilsAC.value.push({ id:res.id, sort:999, ...payload }); showAddPMRule.value = false }
}

function startEditPMRule(rule) {
  editingPMRule.value = rule.id
  Object.assign(editPMForm, { operateur_min:rule.operateur_min||'>=', valeur_min:rule.valeur_min, operateur_max:rule.operateur_max||'<', valeur_max:rule.valeur_max })
}

async function savePMRule(ruleId) {
  const res = await apiFetch(`/m/audit.core/param-marches/seuils-ac/${ruleId}`, 'PUT', editPMForm)
  if (res.success) {
    const idx = seuilsAC.value.findIndex(r=>r.id===ruleId)
    if (idx!==-1) Object.assign(seuilsAC.value[idx], editPMForm)
    editingPMRule.value = null
  }
}

// Toggle organe dans une règle de cellule PM
async function toggleRuleOrgane(ruleId, organeCode) {
  if (getRuleOrganes(ruleId).includes(organeCode)) {
    const res = await apiFetch('/m/audit.core/param-marches/seuils-ac-organes', 'DELETE', { seuil_ac_id:ruleId, organe_code:organeCode })
    if (res.success) { const idx=seuilsAcOrganes.value.findIndex(o=>o.seuil_ac_id===ruleId && o.organe_code===organeCode); if(idx!==-1) seuilsAcOrganes.value.splice(idx,1) }
  } else {
    const res = await apiFetch('/m/audit.core/param-marches/seuils-ac-organes', 'POST', { seuil_ac_id:ruleId, organe_code:organeCode })
    if (res.success) seuilsAcOrganes.value.push({ seuil_ac_id:ruleId, organe_code:organeCode })
  }
}

async function addPMCellOrgane(organeCode) {
  // Associer cet organe à toutes les règles existantes de la cellule (ou juste l'ajouter à la liste des colonnes)
  for (const rule of pmCellRules.value) {
    if (!getRuleOrganes(rule.id).includes(organeCode)) {
      await apiFetch('/m/audit.core/param-marches/seuils-ac-organes', 'POST', { seuil_ac_id:rule.id, organe_code:organeCode })
      seuilsAcOrganes.value.push({ seuil_ac_id:rule.id, organe_code:organeCode })
    }
  }
}

async function quickAddSeuil(acCode, natCode, modeCode) {
  const res = await apiFetch('/m/audit.core/param-marches/seuils-ac', 'POST', {
    type_entite_code: acCode, nature_marche_code: natCode, mode_passation_code: modeCode,
    valeur_min: null, valeur_max: null, operateur_min: null, operateur_max: null,
  })
  if (res.success) seuilsAC.value.push({ id:res.id, type_entite_code:acCode, nature_marche_code:natCode, mode_passation_code:modeCode, sort:999 })
}

async function removeCellRule(acCode, natCode, modeCode) {
  const rules = getCellRules(acCode, natCode, modeCode)
  for (const rule of rules) {
    await apiFetch(`/m/audit.core/param-marches/seuils-ac/${rule.id}`, 'DELETE')
    const idx = seuilsAC.value.findIndex(r=>r.id===rule.id)
    if (idx!==-1) seuilsAC.value.splice(idx,1)
  }
}

// ── Reload complet ─────────────────────────────────────────────────────────
async function reloadAll() {
  const res = await apiFetch('/m/audit.core/param-marches/api/all', 'GET')
  if (!res) return
  typesEntites.value    = res.typesEntites    ?? []
  sourcesFinance.value  = res.sourcesFinance  ?? []
  naturesMarche.value   = res.naturesMarche   ?? []
  modesPassation.value  = res.modesPassation  ?? []
  organes.value         = res.organes         ?? []
  modeOrganes.value     = res.modeOrganes     ?? []
  seuilsGeneraux.value  = res.seuilsGeneraux  ?? []
  seuilsAC.value        = res.seuilsAC        ?? []
  seuilsAcOrganes.value = res.seuilsAcOrganes ?? []
  operations.value      = res.operations      ?? []
  datesReference.value  = res.datesReference  ?? []
  delais.value          = res.delais          ?? []
  delaiOrganes.value    = res.delaiOrganes    ?? []
}

async function seedData() {
  if (!confirm('Initialiser les données depuis pm_seed_reference_v2.sql ?')) return
  seeding.value = true
  try {
    const res = await apiFetch('/m/audit.core/param-marches/seed', 'POST')
    if (res.success) { await reloadAll(); seedMsg.variant='success'; seedMsg.text=res.message }
    else             { seedMsg.variant='warning'; seedMsg.text=res.message }
  } catch(e) { seedMsg.variant='danger'; seedMsg.text='Erreur seed.' }
  finally { seeding.value=false }
}

async function resetData() {
  if (!confirm('⚠️ Vider TOUTES les tables de paramétrage ?')) return
  seeding.value = true
  try {
    const res = await apiFetch('/m/audit.core/param-marches/reset', 'POST')
    if (res.success) { await reloadAll(); seedMsg.variant='info'; seedMsg.text=res.message }
  } catch(e) { seedMsg.variant='danger'; seedMsg.text='Erreur reset.' }
  finally { seeding.value=false }
}
</script>

<style scoped>
.form-control-sm,.form-select-sm { font-size:.75rem; height:26px; padding:.15rem .45rem }
.btn-sm { padding:.15rem .45rem; font-size:.72rem }
.stat-card { border-left:3px solid transparent; transition:all .2s }
.stat-card:hover { box-shadow:0 4px 12px rgba(0,0,0,.1); transform:translateY(-2px) }
.stat-icon { width:32px; height:32px; border-radius:6px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:15px }
.pv-table :deep(.p-datatable-thead>tr>th) { background:#f8fafc; border:1px solid #e5e7eb; padding:.25rem .35rem; font-size:.74rem }
.pv-table :deep(.p-datatable-tbody>tr>td) { border:1px solid #eef2f7; padding:.25rem .35rem; font-size:.72rem }
/* Seuils visuels */
.seuil-visual-card { border-width:2px }
.seuil-card-green{border-color:#28a745;background:linear-gradient(135deg,#d4edda,#c3e6cb)}
.seuil-card-blue{border-color:#007bff;background:linear-gradient(135deg,#cce5ff,#b8daff)}
.seuil-card-orange{border-color:#fd7e14;background:linear-gradient(135deg,#fff3cd,#ffeeba)}
.seuil-card-red{border-color:#dc3545;background:linear-gradient(135deg,#f8d7da,#f5c6cb)}
.seuil-card-gray{border-color:#6c757d;background:linear-gradient(135deg,#e2e3e5,#d6d8db)}
.seuil-valeur{font-size:.78rem;font-weight:bold;margin-bottom:.25rem}
.seuil-dot{width:10px;height:10px;border-radius:50%;display:inline-block;flex-shrink:0}
.dot-green{background:#28a745}.dot-blue{background:#007bff}.dot-orange{background:#fd7e14}.dot-red{background:#dc3545}.dot-gray{background:#6c757d}
.couleur-swatch{width:22px;height:22px;border-radius:50%;cursor:pointer;border:2px solid transparent;transition:all .15s}
.couleur-swatch:hover{transform:scale(1.15)}.swatch-selected{border-color:#333!important;transform:scale(1.2)}
.swatch-green{background:#28a745}.swatch-blue{background:#007bff}.swatch-orange{background:#fd7e14}.swatch-red{background:#dc3545}.swatch-gray{background:#6c757d}
/* Tableau croisé seuils */
.seuils-cross-table{font-size:.72rem;border-collapse:separate}
.mode-header-cell{width:130px;min-width:130px;background:#f0f0f0}
.nature-header{background:#e8f4ff!important;font-size:.72rem;min-width:150px}
.mode-label-cell{background:#fafafa;padding:.3rem .4rem!important;min-width:120px;vertical-align:middle}
.seuil-cell{padding:.3rem .4rem!important;vertical-align:top;min-width:140px;min-height:40px}
.cell-empty{background:#fafafa}
.cell-pm{background:#e8f4fd}
.cell-sp{background:#e8f8f0}
.cell-pd{background:#fef9e7}
.cell-gg{background:#fdf2fb}
.pm-cell-summary{font-size:.68rem}
.pm-rule-line{margin-bottom:.2rem;padding:.15rem .2rem;background:rgba(255,255,255,.7);border-radius:3px;border:1px solid rgba(0,0,0,.06)}
.pm-add-hint{padding:.3rem;border:1px dashed #a0c4ff;border-radius:4px;background:rgba(255,255,255,.5)}
.cell-add-btn:hover{opacity:1!important;background:#e3f2fd!important}
/* Vue PM détail */
.pm-detail-table{font-size:.72rem}
.pm-detail-table th{font-size:.72rem;background:#1a56db;color:#fff;vertical-align:middle;padding:.3rem .4rem}
.pm-detail-table td{vertical-align:middle;padding:.3rem .5rem}
.pm-organe-cell{background:#f8f9fa}
.pm-check-wrapper{width:26px;height:26px;border-radius:5px;border:2px solid #dee2e6;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all .15s;font-size:.75rem}
.pm-check-wrapper.checked{background:#1a56db;border-color:#1a56db;color:#fff}
.pm-check-wrapper.unchecked:hover{border-color:#1a56db;background:#e8f4fd;color:#1a56db}
</style>