<template>
  <VerticalLayout>
    <div class="page">
      <div class="page-hdr">
        <div class="page-hdr-left">
          <div class="hdr-icon hdr-icon--tgt"><i class="ti ti-target"></i></div>
          <div>
            <h1>Risque cible</h1>
            <p>Définissez l'objectif de risque et la décision de traitement — avec appétence et tolérance</p>
          </div>
        </div>
        <div class="page-hdr-right">
          <div class="stat-row">
            <div class="stat"><span>{{ stats.with_residual||0 }}</span><small>Résiduels</small></div>
            <div class="stat stat-ok"><span>{{ stats.with_target||0 }}</span><small>Cibles</small></div>
            <div class="stat stat-nd"><span>{{ (stats.with_residual||0)-(stats.with_target||0) }}</span><small>En attente</small></div>
          </div>
          <Link :href="route('risk.core.evaluation.residuelle')" class="btn-nav"><i class="ti ti-arrow-left"></i> Résiduel</Link>
          <Link :href="route('risk.core.evaluation.residuelle')" class="btn-nav btn-nav-next"><i class="ti ti-list-check"></i> Plans d'action</Link>
        </div>
      </div>

      <div class="table-wrap">
        <div class="table-toolbar">
          <div class="tt-left">
            <div class="search-box"><i class="ti ti-search"></i><input v-model="searchQ" placeholder="Rechercher…"/></div>
            <div class="filter-tabs">
              <button :class="['ftab',filter==='all'?'ftab--on':'']" @click="filter='all'">Tous <span>{{ withResidual.length }}</span></button>
              <button :class="['ftab',filter==='done'?'ftab--on':'']" @click="filter='done'"><i class="ti ti-check"></i> Cibles définis <span>{{ withTarget.length }}</span></button>
              <button :class="['ftab',filter==='todo'?'ftab--on':'']" @click="filter='todo'"><i class="ti ti-clock"></i> En attente <span>{{ withoutTarget.length }}</span></button>
            </div>
          </div>
        </div>

        <div class="table-scroll">
          <table class="risk-table">
            <thead>
              <tr>
                <th>Code</th>
                <th>Risque</th>
                <th>Processus</th>
                <th class="th-c">Inhérent</th>
                <th class="th-c">Résiduel</th>
                <th class="th-c">Impact cible</th>
                <th class="th-c">Fréq. cible</th>
                <th class="th-c">Score C.</th>
                <th class="th-c">Zone C.</th>
                <th>Appétence I.</th>
                <th>Appétence F.</th>
                <th>Tolérance</th>
                <th>Décision</th>
                <th>Statut</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="macro in displayTree" :key="macro.id">
                <tr class="tr-macro"><td colspan="15"><span class="macro-badge" :style="{background:macroColor(macro.kind)}">{{ macroKindLabel(macro.kind) }}</span>{{ macro.name }}</td></tr>
                <template v-for="proc in macro.processes" :key="proc.id">
                  <template v-for="act in proc.activities" :key="act.id">
                    <tr v-for="risk in act.risks" :key="risk.id"
                        :class="['tr-risk',selectedRisk?.id===risk.id?'tr-risk--sel':'',risk.target_criticality?'tr-risk--done':'']">
                      <td><span class="code-badge">{{ risk.code_risk }}</span></td>
                      <td class="td-name"><div class="risk-name">{{ risk.libelle }}</div></td>
                      <td>
                        <div class="proc-line"><span class="proc-code">{{ proc.code }}</span> {{ proc.name }}</div>
                        <div class="act-line"><span class="act-code">{{ act.code }}</span> {{ act.name }}</div>
                      </td>
                      <td class="td-c">
                        <span v-if="risk.criticality_score" class="big-score" :style="{background:risk.zone_color||'#6b7280'}">{{ risk.criticality_score }}</span>
                        <span v-else class="nd">—</span>
                      </td>
                      <td class="td-c">
                        <span v-if="risk.residual_criticality" class="big-score" :style="{background:risk.residual_zone_color||'#6b7280'}">{{ risk.residual_criticality }}</span>
                        <span v-else class="nd">—</span>
                      </td>
                      <td class="td-c">
                        <div v-if="risk.target_impact_score" class="lvl-mini" :style="{background:impactColor(risk.target_impact_score)+'18',borderColor:impactColor(risk.target_impact_score)+'66',color:impactColor(risk.target_impact_score)}">
                          {{ risk.target_impact_score }}
                        </div>
                        <span v-else class="nd">—</span>
                      </td>
                      <td class="td-c">
                        <div v-if="risk.target_frequency_score" class="lvl-mini" :style="{background:freqColor(risk.target_frequency_score)+'18',borderColor:freqColor(risk.target_frequency_score)+'66',color:freqColor(risk.target_frequency_score)}">
                          {{ risk.target_frequency_score }}
                        </div>
                        <span v-else class="nd">—</span>
                      </td>
                      <td class="td-c">
                        <span v-if="risk.target_criticality" class="big-score" :style="{background:risk.target_zone_color||'#6b7280'}">{{ risk.target_criticality }}</span>
                        <span v-else class="nd">—</span>
                      </td>
                      <td class="td-c">
                        <span v-if="risk.target_zone_label" class="zone-pill" :style="{background:(risk.target_zone_color||'#6b7280')+'18',color:risk.target_zone_color||'#6b7280',border:'1px solid '+(risk.target_zone_color||'#6b7280')+'44'}">{{ risk.target_zone_label }}</span>
                      </td>
                      <!-- Appétence Impact -->
                      <td>
                        <div class="appe-col">
                          <div v-for="f in (risk.factors||[]).filter(f=>f.factor_code&&f.factor_code.toLowerCase().includes('imp'))" :key="f.factor_id"
                               class="appe-tag" :style="{background:(f.appetite_color||'#6b7280')+'18',color:f.appetite_color||'#6b7280',border:'1px solid '+(f.appetite_color||'#6b7280')+'44'}">
                            <i class="ti ti-shield-check"></i> {{ f.appetite_label }}<br>
                            <span class="appe-range">{{ f.score_min }}-{{ f.score_max }}</span>
                          </div>
                          <div v-if="!(risk.factors||[]).filter(f=>f.factor_code&&f.factor_code.toLowerCase().includes('imp')).length && (risk.factors||[]).length" class="appe-tag" :style="{background:(risk.factors[0].appetite_color||'#6b7280')+'18',color:risk.factors[0].appetite_color||'#6b7280',border:'1px solid '+(risk.factors[0].appetite_color||'#6b7280')+'44'}">
                            <i class="ti ti-shield-check"></i> {{ risk.factors[0].appetite_label }}
                          </div>
                          <span v-else-if="!(risk.factors||[]).length" class="nd">—</span>
                        </div>
                      </td>
                      <!-- Appétence Fréquence -->
                      <td>
                        <div class="appe-col">
                          <div v-for="f in (risk.factors||[]).filter((f,i)=>i===1)" :key="f.factor_id"
                               class="appe-tag" :style="{background:(f.appetite_color||'#6b7280')+'18',color:f.appetite_color||'#6b7280',border:'1px solid '+(f.appetite_color||'#6b7280')+'44'}">
                            <i class="ti ti-shield-check"></i> {{ f.appetite_label }}<br>
                            <span class="appe-range">{{ f.score_min }}-{{ f.score_max }}</span>
                          </div>
                          <span v-if="!(risk.factors||[]).filter((f,i)=>i===1).length" class="nd">—</span>
                        </div>
                      </td>
                      <!-- Tolérance -->
                      <td>
                        <div v-if="risk.target_criticality && (risk.factors||[]).length" class="tol-col">
                          <span v-for="f in (risk.factors||[])" :key="f.factor_id" :class="['tol-chip',risk.target_criticality<=(f.score_max||99)?'tol-ok':'tol-ko']">
                            <i :class="risk.target_criticality<=(f.score_max||99)?'ti ti-check':'ti ti-alert-triangle'"></i>
                            {{ risk.target_criticality<=(f.score_max||99)?'OK':'≤'+f.score_max }}
                          </span>
                        </div>
                        <span v-else class="nd">—</span>
                      </td>
                      <!-- Décision -->
                      <td>
                        <span v-if="risk.decision" :class="['dec-badge','dec-'+risk.decision]">{{ DECISION_LABELS[risk.decision]||risk.decision }}</span>
                        <span v-else class="nd">—</span>
                      </td>
                      <td>
                        <span :class="['status-pill',risk.target_criticality?'sp-done':'sp-todo']">
                          <i :class="risk.target_criticality?'ti ti-target':'ti ti-clock'"></i>
                          {{ risk.target_criticality?'Défini':'À définir' }}
                        </span>
                      </td>
                      <td>
                        <button :class="['eval-btn',!risk.residual_criticality?'eval-btn--locked':'']" :disabled="!risk.residual_criticality" @click="openModal(risk)">
                          <i :class="risk.target_criticality?'ti ti-pencil':'ti ti-plus'"></i>
                          {{ !risk.residual_criticality?'Résiduel requis': risk.target_criticality?'Modifier':'Définir' }}
                        </button>
                      </td>
                    </tr>
                  </template>
                </template>
              </template>
              <tr v-if="!filteredRisks.length"><td colspan="15" class="empty-row"><i class="ti ti-inbox"></i> Aucun risque</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- MODAL CIBLE -->
      <Teleport to="body">
        <Transition name="mfade">
          <div v-if="modal" class="modal-ov" @click.self="closeModal">
            <div class="modal-box" @click.stop>
              <div class="modal-hdr">
                <span class="modal-code">{{ selectedRisk?.code_risk }}</span>
                <div>
                  <div class="modal-rname">{{ selectedRisk?.libelle }}</div>
                  <div class="modal-ctx">{{ selectedRisk?.process_name }} › {{ selectedRisk?.activity_name }}</div>
                </div>
                <div class="modal-prev-scores">
                  <div class="mps" :style="{background:(selectedRisk?.zone_color||'#6b7280')+'22',color:selectedRisk?.zone_color||'#6b7280'}">
                    <i class="ti ti-shield-bolt"></i> I: {{ selectedRisk?.criticality_score }} — {{ selectedRisk?.zone_label }}
                  </div>
                  <div class="mps" :style="{background:(selectedRisk?.residual_zone_color||'#6b7280')+'22',color:selectedRisk?.residual_zone_color||'#6b7280'}">
                    <i class="ti ti-shield-half"></i> R: {{ selectedRisk?.residual_criticality }} — {{ selectedRisk?.residual_zone_label }}
                  </div>
                </div>
                <button class="modal-x" @click="closeModal"><i class="ti ti-x"></i></button>
              </div>

              <div class="constraint-banner">
                <i class="ti ti-lock"></i>
                <strong>Contrainte :</strong> Cible ≤ {{ refLabel }} —
                Impact max : <span :style="{color:impactColor(refImpact)}">{{ refImpact }}</span>
                · Fréquence max : <span :style="{color:freqColor(refFreq)}">{{ refFreq }}</span>
              </div>

              <!-- Appétences -->
              <div v-if="(selectedRisk?.factors||[]).length" class="appe-banner">
                <span class="ab-lbl"><i class="ti ti-shield-check"></i> Appétences & Tolérances :</span>
                <div v-for="f in (selectedRisk?.factors||[])" :key="f.factor_id" class="ab-item" :style="{background:(f.appetite_color||'#6b7280')+'18',border:'1px solid '+(f.appetite_color||'#6b7280')+'44',color:f.appetite_color||'#6b7280'}">
                  <strong>{{ f.factor_label }}</strong> — {{ f.appetite_label }}
                  <span v-if="f.score_min" class="ab-range">Tolérance : {{ f.score_min }}–{{ f.score_max }}</span>
                </div>
              </div>

              <div class="modal-body">
                <!-- IMPACT CIBLE -->
                <div class="crit-section">
                  <div class="crit-hdr">
                    <i class="ti ti-arrow-up-circle" style="color:#ef4444"></i>
                    <strong>Impact cible</strong>
                    <div v-if="form.impact_id" class="sel-badge" :style="{background:findLvl('impact',form.impact_id)?.color_code}">{{ findLvl('impact',form.impact_id)?.label }} — S{{ form.impact_score }}</div>
                    <span v-else class="sel-hint">Cliquez une cellule</span>
                    <div v-if="scTgt && (selectedRisk?.factors||[]).length" class="tol-row">
                      <span v-for="f in (selectedRisk?.factors||[])" :key="f.factor_id" :class="['tol-chip',scTgt<=(f.score_max||99)?'tol-ok':'tol-ko']">
                        <i :class="scTgt<=(f.score_max||99)?'ti ti-check':'ti ti-alert-triangle'"></i>
                        {{ f.appetite_label }}: {{ scTgt<=(f.score_max||99)?'✓ Tolérance OK':'⚠ Hors tolérance (max '+f.score_max+')' }}
                      </span>
                    </div>
                  </div>
                  <div class="ct-wrap">
                    <table class="ct">
                      <thead><tr>
                        <th class="ct-c-hd">Critère / Appétence</th>
                        <th v-for="lvl in impactDesc" :key="lvl.id" class="ct-l-hd" :class="[form.impact_id===lvl.id?'ct-l-hd--sel':'',lvl.score>(refImpact||99)?'ct-l-hd--dis':'']" :style="{borderTop:'4px solid '+(lvl.color_code||'#94a3b8'),opacity:lvl.score>(refImpact||99)?0.3:1}">
                          <div class="lhd-pill" :style="{background:lvl.color_code||'#94a3b8'}">{{ lvl.label }}</div>
                          <div class="lhd-s">S{{ lvl.score }}</div>
                          <i v-if="lvl.score>(refImpact||99)" class="ti ti-lock lhd-lock"></i>
                        </th>
                      </tr></thead>
                      <tbody>
                        <tr v-for="tpl in impactCritTpls" :key="tpl.id">
                          <td class="ct-c">
                            <div class="ct-cname">{{ tpl.designation }}</div>
                            <div v-if="tpl.appetite_label" class="ct-apt" :style="{background:(tpl.appetite_color||'#6b7280')+'18',color:tpl.appetite_color||'#6b7280'}">
                              <i class="ti ti-shield-check"></i> {{ tpl.appetite_label }} <span v-if="tpl.appetite_score_max">≤ {{ tpl.appetite_score_max }}</span>
                            </div>
                          </td>
                          <td v-for="lvl in impactDesc" :key="lvl.id" :class="['ct-d',lvl.score<=(refImpact||99)?'ct-d--click':'ct-d--lock',form.impact_id===lvl.id?'ct-d--sel':'']" :style="{opacity:lvl.score>(refImpact||99)?0.3:1}" @click="lvl.score<=(refImpact||99)&&(form.impact_id=lvl.id,form.impact_score=lvl.score)">{{ getCritDesc(lvl,tpl.id)||'—' }}</td>
                        </tr>
                        <tr v-if="!impactCritTpls.length"><td class="ct-c"><div class="ct-cname">Description</div></td><td v-for="lvl in impactDesc" :key="lvl.id" :class="['ct-d',lvl.score<=(refImpact||99)?'ct-d--click':'ct-d--lock',form.impact_id===lvl.id?'ct-d--sel':'']" :style="{opacity:lvl.score>(refImpact||99)?0.3:1}" @click="lvl.score<=(refImpact||99)&&(form.impact_id=lvl.id,form.impact_score=lvl.score)">{{ lvl.description||'—' }}</td></tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <!-- FRÉQUENCE CIBLE -->
                <div class="crit-section" style="margin-top:14px">
                  <div class="crit-hdr">
                    <i class="ti ti-clock" style="color:#3b82f6"></i>
                    <strong>Fréquence cible</strong>
                    <div v-if="form.frequency_id" class="sel-badge" :style="{background:findLvl('frequency',form.frequency_id)?.color_code}">{{ findLvl('frequency',form.frequency_id)?.label }} — S{{ form.frequency_score }}</div>
                    <span v-else class="sel-hint">Cliquez une cellule</span>
                  </div>
                  <div class="ct-wrap">
                    <table class="ct">
                      <thead><tr>
                        <th class="ct-c-hd">Critère</th>
                        <th v-for="lvl in freqAsc" :key="lvl.id" class="ct-l-hd" :class="[form.frequency_id===lvl.id?'ct-l-hd--sel':'',lvl.score>(refFreq||99)?'ct-l-hd--dis':'']" :style="{borderTop:'4px solid '+(lvl.color_code||'#94a3b8'),opacity:lvl.score>(refFreq||99)?0.3:1}">
                          <div class="lhd-pill" :style="{background:lvl.color_code||'#94a3b8'}">{{ lvl.label }}</div>
                          <div class="lhd-s">S{{ lvl.score }}</div>
                          <i v-if="lvl.score>(refFreq||99)" class="ti ti-lock lhd-lock"></i>
                        </th>
                      </tr></thead>
                      <tbody>
                        <tr v-for="tpl in freqCritTpls" :key="tpl.id"><td class="ct-c"><div class="ct-cname">{{ tpl.designation }}</div></td><td v-for="lvl in freqAsc" :key="lvl.id" :class="['ct-d',lvl.score<=(refFreq||99)?'ct-d--click':'ct-d--lock',form.frequency_id===lvl.id?'ct-d--sel':'']" :style="{opacity:lvl.score>(refFreq||99)?0.3:1}" @click="lvl.score<=(refFreq||99)&&(form.frequency_id=lvl.id,form.frequency_score=lvl.score)">{{ getFreqDesc(lvl,tpl.id)||'—' }}</td></tr>
                        <tr v-if="!freqCritTpls.length"><td class="ct-c"><div class="ct-cname">Récurrence</div></td><td v-for="lvl in freqAsc" :key="lvl.id" :class="['ct-d',lvl.score<=(refFreq||99)?'ct-d--click':'ct-d--lock',form.frequency_id===lvl.id?'ct-d--sel':'']" :style="{opacity:lvl.score>(refFreq||99)?0.3:1}" @click="lvl.score<=(refFreq||99)&&(form.frequency_id=lvl.id,form.frequency_score=lvl.score)">{{ lvl.recurrence||lvl.description||'—' }}</td></tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <!-- PLAN & DÉCISION -->
                <div class="form-section" style="margin-top:14px">
                  <div class="form-sec-hdr"><span class="sec-num">3</span><i class="ti ti-list-check"></i><strong>Plan d'action & Décision de traitement</strong></div>
                  <div class="plan-grid">
                    <div>
                      <label class="flbl"><i class="ti ti-notes"></i> Plan d'action</label>
                      <textarea v-model="form.action_plan" rows="4" class="finp" placeholder="Décrivez les actions pour atteindre le niveau cible…"></textarea>
                      <label class="flbl" style="margin-top:10px"><i class="ti ti-calendar"></i> Date cible</label>
                      <input v-model="form.target_date" type="date" class="finp"/>
                    </div>
                    <div>
                      <label class="flbl"><i class="ti ti-arrows-decision"></i> Décision de traitement</label>
                      <div class="dec-cards">
                        <div v-for="d in DECISIONS" :key="d.code"
                             :class="['dec-card',form.decision===d.code?'dec-card--sel':'']"
                             :style="form.decision===d.code?{borderColor:d.color,background:d.color+'10'}:{}"
                             @click="form.decision=form.decision===d.code?null:d.code">
                          <div class="dc-icon" :style="{background:d.color+'20',color:d.color}"><i :class="d.icon"></i></div>
                          <div>
                            <div class="dc-lbl" :style="form.decision===d.code?{color:d.color}:{}">{{ d.label }}</div>
                            <div class="dc-desc">{{ d.desc }}</div>
                          </div>
                          <div v-if="form.decision===d.code" class="dc-chk" :style="{background:d.color}"><i class="ti ti-check"></i></div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="modal-footer">
                <div v-if="form.impact_score&&form.frequency_score" class="score-preview" :style="{borderColor:zoneFor(form.impact_score*form.frequency_score)?.color_code||'#94a3b8',background:(zoneFor(form.impact_score*form.frequency_score)?.color_code||'#94a3b8')+'10'}">
                  <span class="sp-num" :style="{color:zoneFor(form.impact_score*form.frequency_score)?.color_code}">{{ form.impact_score*form.frequency_score }}</span>
                  <div>
                    <div class="sp-zone" :style="{color:zoneFor(form.impact_score*form.frequency_score)?.color_code}">{{ zoneFor(form.impact_score*form.frequency_score)?.label||'—' }}</div>
                    <div class="sp-calc">{{ form.impact_score }} × {{ form.frequency_score }}</div>
                  </div>
                  <div v-if="(selectedRisk?.factors||[]).length" class="sp-tols">
                    <span v-for="f in (selectedRisk?.factors||[])" :key="f.factor_id" :class="['tol-chip',scTgt<=(f.score_max||99)?'tol-ok':'tol-ko']">
                      <i :class="scTgt<=(f.score_max||99)?'ti ti-check':'ti ti-alert-triangle'"></i>
                      {{ f.appetite_label }}: {{ scTgt<=(f.score_max||99)?'✓ Tolérance OK':'⚠ Hors tolérance' }}
                    </span>
                  </div>
                </div>
                <div class="mf-btns">
                  <button class="btn-cancel" @click="closeModal"><i class="ti ti-x"></i> Annuler</button>
                  <button class="btn-save" :disabled="!form.impact_score||!form.frequency_score||saving" @click="save">
                    <i v-if="saving" class="ti ti-loader-2 ti-spin"></i><i v-else class="ti ti-target"></i>
                    {{ saving?'Enregistrement…':'Valider le risque cible' }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>

      <Transition name="fl">
        <div v-if="flashMsg" :class="['flash',flashOk?'flash-ok':'flash-err']"><i :class="flashOk?'ti ti-check-circle':'ti ti-alert-circle'"></i> {{ flashMsg }}</div>
      </Transition>
    </div>
  </VerticalLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

const props = defineProps({
  risks: { type: Array, default: () => [] },
  tree:  { type: Array, default: () => [] },
  stats: { type: Object, default: () => ({}) },
  matrixConfigs: { type: Array, default: () => [] },
  matrixData:    { type: Object, default: () => null },
  selectedConfigId: { type: Number, default: null },
  criteriaTemplates: { type: Array, default: () => [] },
  frequencyCriteriaTemplates: { type: Array, default: () => [] },
  appetites: { type: Array, default: () => [] },
})

const DECISIONS = [
  { code:'accepter',   label:'Accepter',   icon:'ti ti-check-circle',       color:'#22c55e', desc:'Le risque est dans la tolérance' },
  { code:'reduire',    label:'Réduire',    icon:'ti ti-trending-down',       color:'#f59e0b', desc:'Actions pour réduire le risque' },
  { code:'transferer', label:'Transférer', icon:'ti ti-arrows-transfer-up',  color:'#3b82f6', desc:'Assurance ou externalisation' },
  { code:'refuser',    label:'Refuser',    icon:'ti ti-ban',                 color:'#ef4444', desc:'Abandonner l\'activité' },
]
const DECISION_LABELS = { accepter:'Accepté', reduire:'À réduire', transferer:'Transféré', refuser:'Refusé', ACCEPTE:'Accepté', REDUIT:'À réduire', TRANSFERE:'Transféré', REFUSE:'Refusé' }

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || ''
const searchQ = ref(''); const filter = ref('all'); const modal = ref(false)
const saving = ref(false); const selectedRisk = ref(null)
const flashMsg = ref(''); const flashOk = ref(true); let flashTimer = null
const form = ref({ impact_id:null, impact_score:null, frequency_id:null, frequency_score:null, target_date:'', action_plan:'', decision:null })

const impactDesc  = computed(() => [...(props.matrixData?.impacts??[])].sort((a,b)=>b.score-a.score))
const freqAsc     = computed(() => [...(props.matrixData?.frequencies??[])].sort((a,b)=>a.score-b.score))
const sortedZones = computed(() => [...(props.matrixData?.zones??[])].sort((a,b)=>(a.sort_order??0)-(b.sort_order??0)))
const impactMap   = computed(() => { const m={}; (props.matrixData?.impacts??[]).forEach(l=>m[l.score]=l); return m })
const freqMap     = computed(() => { const m={}; (props.matrixData?.frequencies??[]).forEach(l=>m[l.score]=l); return m })
const impactColor = s => impactMap.value[s]?.color_code||'#6b7280'
const freqColor   = s => freqMap.value[s]?.color_code||'#6b7280'
const findLvl     = (type,id) => (type==='impact'?props.matrixData?.impacts:props.matrixData?.frequencies)?.find(l=>l.id===id)??null
const zoneFor     = score => sortedZones.value.find(z=>score>=z.min_score&&score<=z.max_score)??null
const scTgt       = computed(() => form.value.impact_score&&form.value.frequency_score ? form.value.impact_score*form.value.frequency_score : null)
const refImpact   = computed(() => selectedRisk.value?.residual_impact_score??selectedRisk.value?.impact_score??null)
const refFreq     = computed(() => selectedRisk.value?.residual_frequency_score??selectedRisk.value?.frequency_score??null)
const refLabel    = computed(() => selectedRisk.value?.residual_criticality?'résiduel':'inhérent')

const impactCritTpls = computed(() => (props.criteriaTemplates??[]).map(t=>{
  const apt=props.appetites.find(a=>a.id===t.appetite_id)
  return {...t,appetite_label:apt?.label||t.appetite_label||null,appetite_color:apt?.color||null,appetite_score_max:apt?.score_max||null}
}))
const freqCritTpls = computed(() => props.frequencyCriteriaTemplates??[])
const getCritDesc  = (lvl,tplId) => (lvl.criteria??[]).find(c=>c.template_id===tplId)?.description??''
const getFreqDesc  = (lvl,tplId) => { const t=freqCritTpls.value.find(t=>t.id===tplId); return (t?.levels??[]).find(l=>l.frequency_level_id===lvl.id)?.description??''  }
const macroColor     = k => ({Direction:'#7c3aed',Réalisation:'#0d9488',Support:'#2563eb'})[k]||'#64748b'
const macroKindLabel = k => ({Direction:'DIR',Réalisation:'OP',Support:'SUP'})[k]||(k||'?')

const withResidual   = computed(() => props.risks.filter(r=>r.residual_criticality))
const withTarget     = computed(() => props.risks.filter(r=>r.target_criticality))
const withoutTarget  = computed(() => withResidual.value.filter(r=>!r.target_criticality))
const filteredRisks  = computed(() => {
  let r=filter.value==='done'?withTarget.value:filter.value==='todo'?withoutTarget.value:withResidual.value
  if(searchQ.value){const q=searchQ.value.toLowerCase();r=r.filter(x=>x.libelle?.toLowerCase().includes(q)||x.code_risk?.toLowerCase().includes(q))}
  return r
})
const displayTree = computed(() => {
  const ids=new Set(filteredRisks.value.map(r=>r.id))
  return props.tree.map(m=>({...m,processes:m.processes.map(p=>({...p,activities:p.activities.map(a=>({...a,risks:a.risks.filter(r=>ids.has(r.id))})).filter(a=>a.risks.length)})).filter(p=>p.activities.length)})).filter(m=>m.processes.length)
})
const openModal = risk => {
  selectedRisk.value = risk
  form.value = { impact_id:impactDesc.value.find(l=>l.score===risk.target_impact_score)?.id??null, impact_score:risk.target_impact_score??null, frequency_id:freqAsc.value.find(l=>l.score===risk.target_frequency_score)?.id??null, frequency_score:risk.target_frequency_score??null, target_date:risk.target_date||'', action_plan:risk.action_plan||risk.plan_traitement||'', decision:risk.decision||null }
  modal.value = true
}
const closeModal = () => { modal.value=false }
const showFlash = (msg,ok) => { if(flashTimer)clearTimeout(flashTimer); flashMsg.value=msg; flashOk.value=ok; flashTimer=setTimeout(()=>{flashMsg.value=''},3500) }
const patchRisk = (id,upd) => { const idx=props.risks.findIndex(r=>r.id===id); if(idx!==-1){Object.assign(props.risks[idx],upd);selectedRisk.value=props.risks[idx]} }
const save = async () => {
  if(!form.value.impact_score||!form.value.frequency_score||!selectedRisk.value) return
  saving.value=true
  try {
    const r=await fetch(route('risk.core.evaluation.cible.store'),{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},body:JSON.stringify({risk_id:selectedRisk.value.id,impact_score:form.value.impact_score,frequency_score:form.value.frequency_score,target_date:form.value.target_date||null,action_plan:form.value.action_plan||null})})
    const d=await r.json()
    if(r.ok&&d.success){
      showFlash('Cible enregistrée ✓',true)
      patchRisk(selectedRisk.value.id,{target_impact_score:d.risk.target_impact_score,target_frequency_score:d.risk.target_frequency_score,target_criticality:d.risk.target_criticality,target_zone_label:d.risk.target_zone_label,target_zone_color:d.risk.target_zone_color,target_date:form.value.target_date,action_plan:form.value.action_plan})
      // Enregistrer décision si choisie
      if(form.value.decision) {
        await fetch(route('risk.core.evaluation.decision.store'),{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf()},body:JSON.stringify({risk_id:selectedRisk.value.id,decision:form.value.decision})})
        patchRisk(selectedRisk.value.id,{decision:form.value.decision})
      }
      closeModal()
    } else showFlash(d.message||'Erreur',false)
  } catch { showFlash('Erreur réseau',false) }
  finally { saving.value=false }
}
</script>

<style scoped>
.page{display:flex;flex-direction:column;height:calc(100vh - 60px);background:#f0f4f8;overflow:hidden;font-family:'Inter',system-ui,sans-serif;font-size:13px;}
.page-hdr{display:flex;align-items:center;justify-content:space-between;padding:10px 22px;background:#0f172a;flex-shrink:0;flex-wrap:wrap;gap:10px;}
.page-hdr-left{display:flex;align-items:center;gap:12px;}
.hdr-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;}
.hdr-icon--tgt{background:linear-gradient(135deg,#0f766e,#0d9488);}
.page-hdr-left h1{font-size:16px;font-weight:800;color:#f1f5f9;margin:0;}
.page-hdr-left p{font-size:11px;color:#64748b;margin:0;}
.page-hdr-right{display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
.stat-row{display:flex;gap:6px;}
.stat{display:flex;flex-direction:column;align-items:center;padding:4px 12px;border-radius:8px;background:rgba(255,255,255,.07);}
.stat span{font-size:16px;font-weight:800;color:#f1f5f9;line-height:1;}
.stat small{font-size:9px;color:#64748b;font-weight:600;}
.stat-ok span{color:#2dd4bf;}.stat-nd span{color:#fbbf24;}
.btn-nav{display:flex;align-items:center;gap:5px;padding:7px 14px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.07);color:#c8d6e5;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;text-decoration:none;}
.btn-nav:hover{background:rgba(255,255,255,.13);}
.btn-nav-next{background:#0d9488!important;color:#fff!important;border-color:#0d9488!important;}
.btn-nav-next:hover{background:#0f766e!important;}
.table-wrap{flex:1;display:flex;flex-direction:column;overflow:hidden;background:#fff;}
.table-toolbar{display:flex;align-items:center;padding:10px 16px;border-bottom:1px solid #e2e8f0;flex-shrink:0;flex-wrap:wrap;gap:8px;}
.tt-left{display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
.search-box{position:relative;}.search-box i{position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;}
.search-box input{padding:7px 10px 7px 32px;border:1px solid #e2e8f0;border-radius:8px;font-size:12px;background:#f8fafc;width:220px;}
.filter-tabs{display:flex;gap:4px;}
.ftab{display:flex;align-items:center;gap:5px;padding:5px 12px;border:1.5px solid #e2e8f0;border-radius:20px;font-size:11px;font-weight:600;cursor:pointer;background:#fff;color:#64748b;transition:all .12s;}
.ftab:hover{border-color:#93c5fd;}.ftab--on{border-color:#0d9488;background:#ccfbf1;color:#0f766e;}
.ftab span{background:#e2e8f0;border-radius:10px;padding:0 6px;font-size:9px;font-weight:700;}
.ftab--on span{background:#99f6e4;color:#0f766e;}
.table-scroll{flex:1;overflow:auto;}
.risk-table{width:100%;border-collapse:collapse;font-size:11px;}
.risk-table thead th{position:sticky;top:0;z-index:4;text-align:left;padding:8px 10px;background:#f8fafc;border-bottom:2px solid #e2e8f0;font-size:9px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;}
.th-c{text-align:center;}
.risk-table td{padding:7px 10px;border-bottom:1px solid #f1f5f9;vertical-align:middle;}
.tr-macro td{background:#0f172a;padding:7px 10px;font-size:11px;font-weight:700;color:#94a3b8;}
.macro-badge{display:inline-block;font-size:9px;font-weight:800;padding:2px 8px;border-radius:4px;color:#fff;margin-right:8px;}
.tr-risk{transition:background .1s;cursor:pointer;}.tr-risk:hover{background:#f0fdfa;}.tr-risk--sel{background:#ccfbf1!important;}.tr-risk--done{border-left:3px solid #0d9488;}
.code-badge{font-family:monospace;font-size:10px;font-weight:700;color:#4338ca;background:#ede9fe;padding:2px 7px;border-radius:5px;}
.td-name{max-width:160px;}.risk-name{font-size:11px;font-weight:600;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.proc-line{font-size:10px;color:#475569;display:flex;align-items:center;gap:4px;}
.act-line{font-size:9px;color:#94a3b8;display:flex;align-items:center;gap:4px;margin-top:2px;}
.proc-code{font-family:monospace;font-size:9px;background:#e2e8f0;color:#475569;padding:0 4px;border-radius:3px;}
.act-code{font-family:monospace;font-size:8px;background:#dcfce7;color:#15803d;padding:0 4px;border-radius:3px;}
.big-score{display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:9px;font-size:13px;font-weight:900;color:#fff;}
.nd{color:#cbd5e1;font-size:11px;}
.zone-pill{font-size:9px;font-weight:700;padding:2px 8px;border-radius:10px;display:inline-block;white-space:nowrap;}
.lvl-mini{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:7px;border:1.5px solid;font-size:11px;font-weight:800;}
.appe-col{display:flex;flex-direction:column;gap:2px;}
.appe-tag{display:inline-flex;align-items:center;gap:4px;font-size:9px;font-weight:700;padding:2px 7px;border-radius:7px;width:fit-content;}
.appe-range{font-size:8px;opacity:.7;}
.tol-col{display:flex;flex-direction:column;gap:2px;}
.tol-chip{display:inline-flex;align-items:center;gap:3px;font-size:9px;font-weight:700;padding:2px 7px;border-radius:7px;}
.tol-ok{background:#dcfce7;color:#16a34a;}.tol-ko{background:#fee2e2;color:#dc2626;}
.dec-badge{font-size:9px;font-weight:700;padding:2px 8px;border-radius:6px;}
.dec-accepter,.dec-ACCEPTE{background:#dcfce7;color:#15803d;}
.dec-reduire,.dec-REDUIT{background:#fef3c7;color:#d97706;}
.dec-transferer,.dec-TRANSFERE{background:#dbeafe;color:#1d4ed8;}
.dec-refuser,.dec-REFUSE{background:#fee2e2;color:#dc2626;}
.status-pill{display:inline-flex;align-items:center;gap:4px;font-size:10px;font-weight:700;padding:4px 9px;border-radius:12px;white-space:nowrap;}
.sp-done{background:#ccfbf1;color:#0f766e;}.sp-todo{background:#fef3c7;color:#d97706;}
.eval-btn{display:flex;align-items:center;gap:5px;padding:5px 11px;border:1.5px solid #0d9488;border-radius:8px;font-size:11px;font-weight:700;cursor:pointer;background:#ccfbf1;color:#0f766e;white-space:nowrap;}
.eval-btn:hover:not(:disabled){background:#99f6e4;}
.eval-btn--locked{border-color:#e2e8f0!important;background:#f8fafc!important;color:#94a3b8!important;cursor:not-allowed!important;}
.empty-row{text-align:center;padding:40px!important;color:#94a3b8;}
/* Modal */
.modal-ov{position:fixed;inset:0;background:rgba(2,6,23,.7);backdrop-filter:blur(5px);display:flex;align-items:center;justify-content:center;z-index:2000;padding:20px;}
.modal-box{background:#fff;border-radius:18px;width:min(960px,100%);max-height:92vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 24px 60px rgba(0,0,0,.3);}
.modal-hdr{display:flex;align-items:center;gap:12px;padding:14px 20px;background:#0f172a;flex-shrink:0;flex-wrap:wrap;}
.modal-code{font-family:monospace;font-size:12px;font-weight:800;background:#0d9488;color:#fff;padding:4px 10px;border-radius:7px;flex-shrink:0;}
.modal-rname{font-size:14px;font-weight:700;color:#f1f5f9;}.modal-ctx{font-size:10px;color:#64748b;margin-top:1px;}
.modal-prev-scores{display:flex;gap:6px;margin-left:auto;flex-wrap:wrap;}
.mps{display:flex;align-items:center;gap:5px;font-size:11px;font-weight:700;padding:4px 12px;border-radius:8px;}
.modal-x{width:30px;height:30px;border:1px solid rgba(255,255,255,.1);background:rgba(255,255,255,.06);color:#94a3b8;border-radius:7px;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:16px;}
.modal-x:hover{background:rgba(255,0,0,.2);color:#f87171;}
.constraint-banner{display:flex;align-items:center;gap:8px;padding:9px 18px;background:#f0fdfa;border-bottom:1px solid #99f6e4;font-size:12px;font-weight:600;color:#134e4a;flex-shrink:0;}
.appe-banner{display:flex;align-items:center;gap:8px;padding:8px 18px;background:#f8fafc;border-bottom:1px solid #e2e8f0;flex-shrink:0;flex-wrap:wrap;}
.ab-lbl{font-size:11px;font-weight:700;color:#475569;white-space:nowrap;display:flex;align-items:center;gap:4px;}
.ab-item{display:inline-flex;align-items:center;flex-wrap:wrap;gap:4px;padding:4px 10px;border-radius:8px;font-size:10px;font-weight:600;}
.ab-range{font-size:9px;opacity:.75;margin-left:4px;}
.modal-body{flex:1;overflow-y:auto;padding:18px 20px;}
.crit-section{border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;}
.crit-hdr{display:flex;align-items:center;gap:8px;padding:10px 14px;background:#f8fafc;border-bottom:1px solid #e2e8f0;font-size:12px;font-weight:700;color:#1e293b;flex-wrap:wrap;}
.sel-badge{margin-left:auto;font-size:10px;font-weight:800;padding:3px 12px;border-radius:10px;color:#fff;}
.sel-hint{margin-left:auto;font-size:10px;color:#94a3b8;font-style:italic;}
.tol-row{display:flex;flex-wrap:wrap;gap:4px;width:100%;margin-top:4px;}
.ct-wrap{overflow-x:auto;max-height:210px;overflow-y:auto;}
.ct{width:100%;border-collapse:collapse;font-size:11px;}
.ct-c-hd{position:sticky;top:0;left:0;z-index:4;background:#1e293b;color:#cbd5e1;padding:8px 12px;font-size:10px;font-weight:700;text-align:left;min-width:175px;border-right:1px solid #334155;}
.ct-l-hd{position:sticky;top:0;z-index:3;background:#1e293b;color:#cbd5e1;padding:7px 10px;text-align:center;min-width:140px;border-right:1px solid #334155;}
.ct-l-hd--sel{background:#064e3b!important;}.ct-l-hd--dis{opacity:.35!important;}
.lhd-pill{font-size:10px;font-weight:800;padding:2px 9px;border-radius:6px;color:#fff;display:inline-block;margin-bottom:3px;}
.lhd-s{font-size:10px;color:#94a3b8;font-family:monospace;}.lhd-lock{font-size:10px;color:#ef4444;display:block;margin-top:2px;}
.ct-c{position:sticky;left:0;z-index:2;background:#f8fafc;padding:8px 12px;border-right:1px solid #e2e8f0;border-bottom:1px solid #f1f5f9;min-width:175px;vertical-align:top;}
.ct-cname{font-size:11px;font-weight:700;color:#1e293b;margin-bottom:3px;}
.ct-apt{display:inline-flex;align-items:center;gap:4px;font-size:9px;font-weight:700;padding:2px 7px;border-radius:8px;margin-bottom:2px;}
.ct-d{padding:8px 12px;border-right:1px solid #f1f5f9;border-bottom:1px solid #f1f5f9;font-size:11px;line-height:1.5;color:#374151;vertical-align:top;}
.ct-d--click{cursor:pointer;transition:background .1s;}.ct-d--click:hover{background:#f0fdfa!important;}.ct-d--sel{background:#ccfbf1!important;}.ct-d--lock{cursor:not-allowed;}
.form-section{border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;}
.form-sec-hdr{display:flex;align-items:center;gap:8px;padding:10px 14px;background:#f8fafc;border-bottom:1px solid #e2e8f0;font-size:12px;font-weight:700;color:#1e293b;}
.sec-num{width:22px;height:22px;border-radius:50%;background:#0d9488;color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;flex-shrink:0;}
.plan-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;padding:16px;}
.flbl{font-size:10px;font-weight:700;color:#475569;display:block;margin-bottom:4px;}
.finp{width:100%;padding:8px 10px;border:1px solid #e2e8f0;border-radius:8px;font-size:12px;color:#0f172a;background:#fff;box-sizing:border-box;font-family:inherit;}
.finp:focus{outline:none;border-color:#5eead4;}
textarea.finp{resize:vertical;min-height:70px;}
.dec-cards{display:flex;flex-direction:column;gap:8px;margin-top:8px;}
.dec-card{display:flex;align-items:center;gap:10px;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:10px;cursor:pointer;transition:all .12s;position:relative;}
.dec-card:hover{border-color:#5eead4;background:#f0fdfa;}
.dc-icon{width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;}
.dc-lbl{font-size:12px;font-weight:700;color:#1e293b;}.dc-desc{font-size:10px;color:#64748b;margin-top:1px;}
.dc-chk{position:absolute;top:8px;right:8px;width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:10px;}
.modal-footer{display:flex;align-items:center;justify-content:space-between;padding:12px 20px;border-top:1px solid #e2e8f0;background:#f8fafc;flex-shrink:0;flex-wrap:wrap;gap:10px;}
.score-preview{display:flex;align-items:center;flex-wrap:wrap;gap:12px;padding:10px 16px;border-left:4px solid;border-radius:10px;}
.sp-num{font-size:30px;font-weight:900;}.sp-zone{font-size:13px;font-weight:700;}.sp-calc{font-size:10px;color:#94a3b8;}
.sp-tols{display:flex;flex-wrap:wrap;gap:4px;width:100%;margin-top:4px;}
.mf-btns{display:flex;gap:8px;}
.btn-cancel{display:flex;align-items:center;gap:5px;padding:9px 16px;border:1.5px solid #e2e8f0;border-radius:9px;background:#fff;color:#475569;font-size:12px;font-weight:600;cursor:pointer;}
.btn-cancel:hover{background:#f1f5f9;}
.btn-save{display:flex;align-items:center;gap:6px;padding:9px 20px;border:none;border-radius:9px;background:#0d9488;color:#fff;font-size:12px;font-weight:800;cursor:pointer;}
.btn-save:hover:not(:disabled){background:#0f766e;}.btn-save:disabled{opacity:.4;cursor:not-allowed;}
.flash{position:fixed;bottom:20px;right:20px;z-index:9999;display:flex;align-items:center;gap:8px;padding:11px 18px;border-radius:12px;font-size:12px;font-weight:700;box-shadow:0 4px 16px rgba(0,0,0,.12);}
.flash-ok{background:#dcfce7;color:#15803d;border:1px solid #86efac;}.flash-err{background:#fee2e2;color:#dc2626;border:1px solid #fca5a5;}
.fl-enter-active,.fl-leave-active{transition:opacity .2s,transform .2s;}.fl-enter-from,.fl-leave-to{opacity:0;transform:translateX(20px);}
.mfade-enter-active{transition:opacity .18s,transform .18s;}.mfade-leave-active{transition:opacity .14s,transform .14s;}.mfade-enter-from,.mfade-leave-to{opacity:0;transform:scale(.97);}
::-webkit-scrollbar{width:4px;height:4px;}::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:2px;}
@keyframes ti-spin{to{transform:rotate(360deg);}}.ti-spin{animation:ti-spin .7s linear infinite;display:inline-block;}
</style>