<template>
  <VerticalLayoutAudit>
    <div class="qci-shell">

      <!-- ══ HEADER ══════════════════════════════════════════════════ -->
      <header class="qci-header">
        <div class="qci-hrow">
          <a :href="props.backUrl" class="qci-back"><i class="ti ti-arrow-left"></i></a>
          <div class="qci-hinfo">
            <div class="qci-chips">
              <code class="qci-code">{{ mission?.code_mission ?? '—' }}</code>
              <span class="qci-chip" :class="`chip-${form.validation_status || 'draft'}`">
                <i :class="vstIcon(form.validation_status || 'draft')"></i>
                {{ vstLbl(form.validation_status || 'draft') }}
              </span>
              <span class="qci-chip chip-type">QCI</span>
              <span v-if="props.auditorRole" class="qci-chip" :class="`chip-role-${props.auditorRole}`">
                {{ props.auditorRole }}
              </span>
            </div>
            <h1 class="qci-title">Questionnaire de Contrôle Interne</h1>
            <div class="qci-meta">
              <span v-if="assignment?.phase_label"><i class="ti ti-git-branch"></i>{{ assignment.phase_label }}</span>
              <span v-if="mission?.entity_name"><i class="ti ti-building"></i>{{ mission.entity_name }}</span>
              <span v-if="props.riskCount"><i class="ti ti-alert-triangle" style="color:#f59e0b"></i>{{ props.riskCount }} risques</span>
            </div>
          </div>
          <div class="qci-hactions">
            <button class="qci-btn-chat" :class="{ unread: unreadCount > 0 }" @click="openChat" title="Chat">
              <i class="ti ti-message-circle"></i>
              <span v-if="unreadCount > 0" class="qci-chatbadge">{{ unreadCount }}</span>
            </button>
          </div>
        </div>

        <!-- Bannières -->
        <div v-if="form.validation_status === 'validated'" class="qci-banner banner-lock">
          <i class="ti ti-lock"></i> QCI <strong>validé</strong> — lecture seule
        </div>
        <div v-else-if="form.validation_status === 'in_review'" class="qci-banner banner-review">
          <i class="ti ti-clock"></i> Soumis pour validation<span v-if="canManage"> · Vous pouvez valider ou rejeter.</span>
        </div>
        <div v-else-if="form.validation_status === 'draft' && form.validation_note" class="qci-banner banner-reject">
          <i class="ti ti-circle-x"></i> Rejeté — <em>{{ form.validation_note }}</em>
        </div>
      </header>

      <!-- ══ BODY ════════════════════════════════════════════════════ -->
      <div class="qci-body">
        <div class="qci-grid">

          <!-- ── COL GAUCHE ── -->
          <div class="qci-col-left">

            <!-- Info mission -->
            <section class="card">
              <div class="card-label"><i class="ti ti-briefcase"></i> Mission</div>
              <div class="card-body">
                <div class="form-row">
                  <div class="fg"><label class="flbl">Code</label>
                    <input class="inp inp-ro" :value="mission?.code_mission" readonly /></div>
                  <div class="fg"><label class="flbl">Phase</label>
                    <input class="inp inp-ro" :value="assignment?.phase_label || '—'" readonly /></div>
                </div>
                <div class="fg"><label class="flbl">Entité</label>
                  <input class="inp inp-ro" :value="mission?.entity_name || '—'" readonly /></div>
              </div>
            </section>

            <!-- Info QCI -->
            <section class="card">
              <div class="card-label"><i class="ti ti-clipboard-check"></i> Questionnaire</div>
              <div class="card-body">
                <div class="fg"><label class="flbl">Code QCI</label>
                  <input class="inp inp-ro" :value="form.code || 'QCI-AUTO'" readonly /></div>
                <div class="fg"><label class="flbl">Intitulé <span class="req">*</span></label>
                  <input class="inp" v-model="form.intitule_qci" :disabled="isLocked"
                    placeholder="Ex: Contrôle des achats…" /></div>
                <div class="form-row">
                  <div class="fg"><label class="flbl">Fait par</label>
                    <input class="inp" v-model="form.fait_par" :disabled="isLocked" /></div>
                  <div class="fg"><label class="flbl">Date</label>
                    <input type="date" class="inp" v-model="form.date_fait" :disabled="isLocked" /></div>
                </div>
                <div class="form-row">
                  <div class="fg"><label class="flbl">Revu par</label>
                    <input class="inp" v-model="form.revue_par" :disabled="isLocked" /></div>
                  <div class="fg"><label class="flbl">Date revue</label>
                    <input type="date" class="inp" v-model="form.date_revue" :disabled="isLocked" /></div>
                </div>
                <div class="fg"><label class="flbl">Synthèse</label>
                  <textarea class="inp inp-ta" v-model="form.synthese" :disabled="isLocked"
                    rows="3" placeholder="Observations générales…"></textarea></div>
              </div>
            </section>

            <!-- Pièces jointes -->
            <section class="card">
              <div class="card-label"><i class="ti ti-paperclip"></i> Pièces jointes
                <span class="card-count">{{ fichiers.length }}</span>
              </div>
              <div class="card-body" style="padding:10px">
                <!-- Drop zone -->
                <div v-if="!isLocked"
                  class="dropzone"
                  :class="{ 'dz-over': isDragOver }"
                  @dragover.prevent="isDragOver=true"
                  @dragleave="isDragOver=false"
                  @drop.prevent="onDrop"
                  @click="$refs.fileInput.click()">
                  <i class="ti ti-cloud-upload dz-ico"></i>
                  <span>Glisser ou <strong>cliquer pour parcourir</strong></span>
                  <small>PDF, Excel, Word, Image — max 10 Mo</small>
                  <input ref="fileInput" type="file" multiple class="hidden"
                    @change="onFileSelect" :disabled="isLocked" />
                </div>
                <!-- Liste fichiers -->
                <div v-for="(f, i) in fichiers" :key="i" class="file-item">
                  <i class="ti" :class="fileIcon(f.name)"></i>
                  <span class="file-name">{{ f.name }}</span>
                  <span class="file-badge" :class="f.saved ? 'badge-saved' : 'badge-new'">
                    {{ f.saved ? 'Enregistré' : 'Nouveau' }}
                  </span>
                  <span class="file-size">{{ f.size_label }}</span>
                  <button v-if="!isLocked" class="file-del" @click="fichiers.splice(i,1)">
                    <i class="ti ti-trash"></i>
                  </button>
                </div>
                <div v-if="!fichiers.length" class="file-empty">
                  <i class="ti ti-file-off"></i> Aucun fichier joint
                </div>
              </div>
            </section>

            <!-- Liste QCI enregistrés -->
            <section class="card">
              <div class="card-label">
                <i class="ti ti-list"></i> QCI enregistrés
                <span class="card-count">{{ props.qciList?.length ?? 0 }}</span>
                <button v-if="!isLocked" class="btn-new-inline" @click="newQci" title="Nouveau QCI">
                  <i class="ti ti-plus"></i>
                </button>
              </div>
              <div class="card-body" style="padding:0">
                <div v-if="!props.qciList?.length" class="td-empty">Aucun questionnaire</div>
                <div v-for="q in props.qciList" :key="q.id"
                  class="qci-list-row"
                  :class="{ active: form.id === q.id }"
                  @click="loadQci(q)">
                  <div class="qlr-l">
                    <code class="qlr-code">{{ q.code }}</code>
                    <span class="qlr-title">{{ q.intitule_qci || '—' }}</span>
                  </div>
                  <span class="qci-chip" :class="`chip-${q.validation_status || 'draft'}`" style="font-size:.58rem">
                    {{ vstLbl(q.validation_status || 'draft') }}
                  </span>
                </div>
              </div>
            </section>

          </div><!-- /col-left -->

          <!-- ── COL DROITE — TABLEAU QCI ── -->
          <div class="qci-col-right">

            <!-- Toolbar -->
            <div class="qci-toolbar">
              <div class="qtb-l">
                <i class="ti ti-table" style="color:#1565C0"></i>
                <span class="qtb-title">{{ form.intitule_qci || 'Questionnaire sans titre' }}</span>
                <span class="qtb-count">{{ controlItems.length }} point(s)</span>
              </div>
              <div class="qtb-r">
                <!-- Import Excel -->
                <label v-if="!isLocked" class="btn btn-import" title="Importer depuis Excel">
                  <span v-if="importing" class="spin-dot spin-sm" style="border-top-color:#0369a1"></span>
                  <i v-else class="ti ti-upload"></i>
                  {{ importing ? 'Import…' : 'Importer Excel' }}
                  <input
                    ref="xlsRef"
                    type="file"
                    accept=".xlsx,.xls"
                    class="hidden"
                    @change="importExcel"
                    :disabled="isLocked || importing"
                  />
                </label>
                <!-- Télécharger le template -->
                <a href="/templates/QCI_Template.xlsx" download class="btn btn-tpl" title="Télécharger le template">
                  <i class="ti ti-download"></i> Template
                </a>
                <button v-if="!isLocked" class="btn btn-cat" @click="addLine('cat')">
                  <i class="ti ti-folder-plus"></i> Section
                </button>
                <button v-if="!isLocked" class="btn btn-item" @click="addLine('item')">
                  <i class="ti ti-plus"></i> Ligne
                </button>
              </div>
            </div>

            <!-- Tableau -->
            <div class="qci-table-wrap">
              <table class="qci-table">
                <thead>
                  <tr>
                    <th class="th-num">N°</th>
                    <th class="th-question">Intitulé / Question de contrôle</th>
                    <th class="th-rep">O / N / SO</th>
                    <th class="th-forces">Forces</th>
                    <th class="th-faibl">Faiblesses</th>
                    <th class="th-obj">Objectif de contrôle</th>
                    <th v-if="!isLocked" class="th-act"></th>
                  </tr>
                </thead>
                <tbody>

                  <!-- Empty -->
                  <tr v-if="!items.length">
                    <td :colspan="isLocked ? 6 : 7" class="qci-empty">
                      <i class="ti ti-clipboard-off"></i>
                      <p>Aucune ligne. Ajoutez des sections et points de contrôle.</p>
                      <div v-if="!isLocked" class="empty-actions">
                        <button class="btn btn-cat" @click="addLine('cat')">
                          <i class="ti ti-folder-plus"></i> Ajouter une section
                        </button>
                        <button class="btn btn-item" @click="addLine('item')">
                          <i class="ti ti-plus"></i> Ajouter une ligne
                        </button>
                      </div>
                    </td>
                  </tr>

                  <template v-for="(item, idx) in items" :key="item._id">

                    <!-- SECTION / CATÉGORIE -->
                    <tr v-if="item.type === 'cat'" class="row-cat" @click="toggleCat(item._id)">
                      <td class="td-num">
                        <i class="ti" :class="collapsed.has(item._id) ? 'ti-chevron-right' : 'ti-chevron-down'"
                          style="font-size:.7rem;color:#6b7280"></i>
                      </td>
                      <td :colspan="isLocked ? 5 : 4" class="td-cat-label">
                        <input v-if="!isLocked" class="inp-cat" v-model="item.label"
                          placeholder="Nom de la section…" @click.stop />
                        <strong v-else>{{ item.label || '—' }}</strong>
                      </td>
                      <td v-if="!isLocked" class="td-act" @click.stop>
                        <button class="act-btn act-add" @click="addLineAfter(idx)" title="Ajouter une ligne">
                          <i class="ti ti-row-insert-bottom"></i>
                        </button>
                        <button class="act-btn act-del" @click="removeLine(idx)">
                          <i class="ti ti-trash"></i>
                        </button>
                      </td>
                    </tr>

                    <!-- LIGNE DE CONTRÔLE -->
                    <tr v-else-if="item.type === 'item'"
                      class="row-item"
                      :class="{
                        'row-hidden': isHidden(idx),
                        'row-oui':    item.reponse === 'Oui',
                        'row-vrai':    item.reponse === 'vrai',
                        'row-faux':    item.reponse === 'faux',
                        'row-non':    item.reponse === 'Non',
                        'row-so':     item.reponse === 'Sans objet',
                      }">

                      <!-- N° -->
                      <td class="td-num">
                        <span class="item-num">{{ itemNumber(idx) }}</span>
                      </td>

                      <!-- Intitulé / Question -->
                      <td class="td-question">
                        <textarea v-if="!isLocked" class="inp-q" v-model="item.label"
                          rows="2" placeholder="Point de contrôle…"></textarea>
                        <span v-else class="q-ro">{{ item.label || '—' }}</span>
                      </td>

                      <!-- SELECT O / N / SO -->
                      <td class="td-rep">
                        <select v-if="!isLocked"
                          class="sel-rep"
                          :class="`sel-${item.reponse || 'empty'}`"
                          v-model="item.reponse">
                          <option value="">—</option>
                          <option value="Oui">Oui</option>
                          <option value="Non">Non</option>
                          <option value="Sans objet">Sans objet</option>
                        </select>
                        <span v-else class="rep-ro" :class="`rep-ro-${item.reponse || 'empty'}`">
                          {{ item.reponse || '—' }}
                        </span>
                      </td>

                      <!-- Forces -->
                      <td class="td-forces">
                        <textarea v-if="!isLocked" class="inp-obs" v-model="item.forces"
                          rows="2" placeholder="Forces…"></textarea>
                        <span v-else class="obs-ro">{{ item.forces || '—' }}</span>
                      </td>

                      <!-- Faiblesses -->
                      <td class="td-faibl">
                        <textarea v-if="!isLocked" class="inp-obs" v-model="item.faiblesses"
                          rows="2" placeholder="Faiblesses…"></textarea>
                        <span v-else class="obs-ro">{{ item.faiblesses || '—' }}</span>
                      </td>

                      <!-- Objectif de contrôle -->
                      <td class="td-obj">
                        <input v-if="!isLocked" class="inp-obj" v-model="item.objectif"
                          placeholder="Objectif…" />
                        <span v-else class="obs-ro">{{ item.objectif || '—' }}</span>
                      </td>

                      <!-- Actions -->
                      <td v-if="!isLocked" class="td-act">
                        <button class="act-btn act-up" :disabled="idx===0" @click="moveUp(idx)">
                          <i class="ti ti-arrow-up"></i>
                        </button>
                        <button class="act-btn act-down" :disabled="idx===items.length-1" @click="moveDown(idx)">
                          <i class="ti ti-arrow-down"></i>
                        </button>
                        <button class="act-btn act-del" @click="removeLine(idx)">
                          <i class="ti ti-trash"></i>
                        </button>
                      </td>
                    </tr>

                  </template>
                </tbody>
              </table>
            </div><!-- /qci-table-wrap -->

          </div><!-- /col-right -->
        </div><!-- /qci-grid -->

        <!-- ══ FOOTER ══════════════════════════════════════════════ -->
        <footer class="qci-footer">
          <div>
            <button v-if="!isLocked" type="button" class="btn btn-ghost"
              :disabled="processing" @click="annuler">
              <i class="ti ti-x"></i> Annuler
            </button>
            <button v-if="!isLocked" type="button" class="btn btn-save"
              :disabled="processing" @click="submit">
              <span v-if="processing" class="spin-dot"></span>
              <i v-else class="ti ti-device-floppy"></i>
              {{ form.id ? 'Mettre à jour' : 'Enregistrer' }}
            </button>
          </div>
          <div>
            <button v-if="form.id && form.validation_status === 'draft'"
              type="button" class="btn btn-sub" :disabled="processing" @click="soumettre">
              <i class="ti ti-send"></i> Soumettre
            </button>
            <template v-if="canManage && form.validation_status === 'in_review'">
              <button type="button" class="btn btn-ok" :disabled="processing" @click="valider('validate')">
                <i class="ti ti-circle-check"></i> Valider
              </button>
              <button type="button" class="btn btn-rej" :disabled="processing" @click="promptReject">
                <i class="ti ti-circle-x"></i> Rejeter
              </button>
            </template>
          </div>
        </footer>
      </div><!-- /qci-body -->
    </div><!-- /qci-shell -->

    <!-- ══ PANEL CHAT ═══════════════════════════════════════════════ -->
    <Teleport to="body">
      <transition name="slide-right">
        <div v-if="chatPanel.show" class="qci-chat-panel">
          <div class="qci-chat-hd">
            <div class="qci-chat-hdinfo">
              <div class="qci-chat-av"><i class="ti ti-message-circle"></i></div>
              <div>
                <span class="qci-chat-title">Chat — QCI</span>
                <span class="qci-chat-sub">{{ mission?.code_mission }} · {{ form.intitule_qci || 'QCI' }}</span>
              </div>
            </div>
            <button class="qci-chat-close" @click="chatPanel.show = false"><i class="ti ti-x"></i></button>
          </div>

          <div class="qci-chat-msgs" ref="chatMsgEl">
            <div v-if="!localMsgs.length" class="qci-chat-empty">
              <i class="ti ti-messages"></i>
              <p>Aucun message pour ce formulaire.</p>
            </div>
            <div v-for="msg in localMsgs" :key="msg.id"
              class="qci-cmsg"
              :class="[`ft-${msg.type}`, `fp-${msg.priority}`, { mine: msg.is_mine }]">
              <div class="qci-cav" :class="`cav-${msg.author_role}`">{{ msg.author_initials }}</div>
              <div class="qci-cbody2">
                <div class="qci-cmeta">
                  <span class="qci-cwho" :class="`cr-${msg.author_role}`">{{ msg.author_name }}</span>
                  <span class="qci-crole">{{ msg.author_role }}</span>
                  <span v-if="msg.type !== 'message'" class="qci-ctypetag">{{ chatTypeLbl(msg.type) }}</span>
                  <span v-if="msg.priority !== 'normal'" class="qci-cpritag" :class="`pp-${msg.priority}`">{{ msg.priority }}</span>
                  <span class="qci-cdate">{{ msg.created_at_fr }}</span>
                </div>
                <p class="qci-ctxt">{{ msg.content }}</p>
              </div>
            </div>
          </div>

          <div class="qci-chat-compose">
            <div class="qci-chat-opts">
              <select v-model="chatPanel.type" class="qci-chat-sel">
                <option value="message">💬 Message</option>
                <option v-if="canManage" value="instruction">📋 Instruction</option>
                <option v-if="canManage" value="correction">✏️ Correction</option>
                <option v-if="canManage" value="validation">✅ Validation</option>
                <option v-if="canManage" value="rejet">❌ Rejet</option>
                <option value="info">ℹ️ Info</option>
              </select>
              <div class="qci-prios">
                <button v-for="p in PRIOS" :key="p.v" type="button"
                  class="qci-priobtn" :class="[{ active: chatPanel.priority === p.v }, `ppb-${p.v}`]"
                  @click="chatPanel.priority = p.v">
                  <i :class="p.icon"></i> {{ p.l }}
                </button>
              </div>
            </div>
            <div class="qci-chat-row">
              <textarea v-model="chatPanel.draft" class="qci-chat-ta" rows="2"
                placeholder="Votre message…" @keydown.ctrl.enter="sendMsg"></textarea>
              <button type="button" class="qci-chat-send"
                :disabled="!chatPanel.draft.trim() || chatPanel.sending" @click="sendMsg">
                <span v-if="chatPanel.sending" class="spin-dot spin-sm"></span>
                <i v-else class="ti ti-send"></i>
              </button>
            </div>
            <div class="qci-chat-hint">Ctrl+Entrée pour envoyer</div>
          </div>
        </div>
      </transition>
      <div v-if="chatPanel.show" class="qci-chat-overlay" @click="chatPanel.show = false"></div>
    </Teleport>

    <!-- ══ TOAST ═══════════════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition name="toast-t">
        <div v-if="toast.show" class="qci-toast" :class="`toast-${toast.type}`">
          <i :class="toast.type === 'success' ? 'ti ti-circle-check' : 'ti ti-circle-x'"></i>
          {{ toast.msg }}
        </div>
      </Transition>
    </Teleport>

  </VerticalLayoutAudit>
</template>

<script setup lang="ts">
import { ref, computed, reactive, onMounted, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import VerticalLayoutAudit from '@/layouts/VerticalLayoutAudit.vue'

// ── Props ─────────────────────────────────────────────────────────
const props = withDefaults(defineProps<{
  mission?:        any
  assignment?:     any
  auditeurs?:      any[]
  auditorRole?:    string
  missionId?:      number
  assignmentId?:   number
  form?:           any
  qciList?:        any[]
  riskCount?:      number
  currentAuditor?: any
  formUrl?:        string
  backUrl?:        string
  chatBaseUrl?:    string   // /m/audit.core/missions/{realMissionId}/chat
  chatMessages?:   any[]
  noMission?:      boolean
}>(), {
  auditeurs:    () => [],
  qciList:      () => [],
  riskCount:    0,
  chatMessages: () => [],
})

// ── Form state ────────────────────────────────────────────────────
const form = reactive<any>({
  id:                null,
  code:              '',
  validation_status: 'draft',
  validation_note:   '',
  intitule_qci:      '',
  fait_par:          '',
  revue_par:         '',
  date_fait:         '',
  date_revue:        '',
  synthese:          '',
  ...(props.form ?? {}),
})

// ── Items du tableau ──────────────────────────────────────────────
// Structure item : { _id, type:'cat'|'item', label, reponse:'Oui'|'Non'|'Sans objet'|'',
//                   forces, faiblesses, objectif }
let _uid = 0
function uid() { return `_${++_uid}` }

function parseItems(raw: any): any[] {
  if (!raw) return []
  const arr = typeof raw === 'string' ? (JSON.parse(raw) ?? []) : raw
  return (arr as any[]).map((it: any) => ({ ...it, _id: uid() }))
}

const items = ref<any[]>(parseItems(props.form?.questions))

onMounted(() => {
  // Restaurer les réponses depuis reponses JSON
  if (props.form?.reponses) {
    const reps = typeof props.form.reponses === 'string'
      ? (JSON.parse(props.form.reponses) ?? [])
      : props.form.reponses
    for (const r of (reps as any[])) {
      const found = items.value.find(it => it.id === r.question_id)
      if (found) {
        found.reponse   = r.reponse   ?? found.reponse
        found.forces    = r.forces    ?? found.forces
        found.faiblesses= r.faiblesses ?? found.faiblesses
        found.objectif  = r.objectif  ?? found.objectif
      }
    }
  }
  // Auto-remplir fait_par
  if (!form.fait_par && props.currentAuditor) {
    form.fait_par = `${props.currentAuditor.last_name ?? ''} ${props.currentAuditor.first_name ?? ''}`.trim()
  }
})

// ── Catégories repliées ───────────────────────────────────────────
const collapsed = ref<Set<string>>(new Set())
function toggleCat(id: string) {
  collapsed.value.has(id) ? collapsed.value.delete(id) : collapsed.value.add(id)
}
function getCatOf(idx: number): string | null {
  for (let i = idx - 1; i >= 0; i--) {
    if (items.value[i].type === 'cat') return items.value[i]._id
  }
  return null
}
function isHidden(idx: number): boolean {
  const cat = getCatOf(idx)
  return cat ? collapsed.value.has(cat) : false
}

// ── Numérotation ──────────────────────────────────────────────────
function itemNumber(idx: number): string {
  let n = 0
  for (let i = 0; i <= idx; i++) {
    if (items.value[i].type === 'item') n++
  }
  return String(n)
}

// ── CRUD lignes ───────────────────────────────────────────────────
function addLine(type: 'cat' | 'item') {
  items.value.push({ _id: uid(), type, label: '', reponse: '', forces: '', faiblesses: '', objectif: '' })
}
function addLineAfter(idx: number) {
  items.value.splice(idx + 1, 0, { _id: uid(), type: 'item', label: '', reponse: '', forces: '', faiblesses: '', objectif: '' })
}
function removeLine(idx: number) { items.value.splice(idx, 1) }
function moveUp(idx: number) {
  if (idx === 0) return
  ;[items.value[idx - 1], items.value[idx]] = [items.value[idx], items.value[idx - 1]]
}
function moveDown(idx: number) {
  if (idx >= items.value.length - 1) return
  ;[items.value[idx + 1], items.value[idx]] = [items.value[idx], items.value[idx + 1]]
}

// ── Computed ──────────────────────────────────────────────────────
const controlItems = computed(() => items.value.filter(it => it.type === 'item'))
const canManage    = computed(() => ['DM', 'CM'].includes(props.auditorRole ?? ''))
const isLocked     = computed(() =>
  form.validation_status === 'validated' ||
  (form.validation_status === 'in_review' && !canManage.value)
)

// ── Fichiers joints ───────────────────────────────────────────────
const fichiers   = ref<any[]>([])
const isDragOver = ref(false)

function fileIcon(name: string): string {
  const ext = name.split('.').pop()?.toLowerCase() ?? ''
  if (['pdf'].includes(ext)) return 'ti-file-type-pdf'
  if (['xlsx','xls'].includes(ext)) return 'ti-file-spreadsheet'
  if (['doc','docx'].includes(ext)) return 'ti-file-word'
  if (['png','jpg','jpeg'].includes(ext)) return 'ti-photo'
  return 'ti-file'
}
function fileSize(bytes: number): string {
  if (bytes < 1024) return bytes + ' o'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(0) + ' Ko'
  return (bytes / 1024 / 1024).toFixed(1) + ' Mo'
}
function onFileSelect(e: Event) {
  const input = e.target as HTMLInputElement
  if (input.files) addFiles(Array.from(input.files))
  input.value = ''
}
function onDrop(e: DragEvent) {
  isDragOver.value = false
  if (e.dataTransfer?.files) addFiles(Array.from(e.dataTransfer.files))
}
function addFiles(files: File[]) {
  for (const f of files) {
    fichiers.value.push({ name: f.name, size_label: fileSize(f.size), saved: false, file: f })
  }
}

// ── Nouveau QCI / Charger QCI ─────────────────────────────────────
function newQci() {
  form.id = null; form.code = ''; form.validation_status = 'draft'
  form.validation_note = ''; form.intitule_qci = ''
  form.fait_par = props.currentAuditor
    ? `${props.currentAuditor.last_name ?? ''} ${props.currentAuditor.first_name ?? ''}`.trim() : ''
  form.revue_par = ''; form.date_fait = ''; form.date_revue = ''; form.synthese = ''
  items.value = []; fichiers.value = []
}
function loadQci(q: any) {
  Object.assign(form, {
    id: q.id, code: q.code,
    validation_status: q.validation_status || 'draft',
    validation_note:   q.validation_note   ?? '',
    intitule_qci:      q.intitule_qci      ?? '',
    fait_par:          q.fait_par          ?? '',
    revue_par:         q.revue_par         ?? '',
    date_fait:         q.date_fait         ?? '',
    date_revue:        q.date_revue        ?? '',
    synthese:          q.synthese          ?? '',
  })
  items.value = parseItems(q.questions)
  if (q.reponses) {
    const reps = typeof q.reponses === 'string' ? (JSON.parse(q.reponses) ?? []) : q.reponses
    for (const r of (reps as any[])) {
      const found = items.value.find(it => it.id === r.question_id)
      if (found) {
        found.reponse    = r.reponse    ?? ''
        found.forces     = r.forces     ?? ''
        found.faiblesses = r.faiblesses ?? ''
        found.objectif   = r.objectif   ?? ''
      }
    }
  }
  fichiers.value = []
}

// ── Sérialisation ─────────────────────────────────────────────────
function buildQuestions() {
  return JSON.stringify(items.value.map((it, i) => ({
    id:      it.id ?? `q${i}`,
    type:    it.type,
    label:   it.label,
    objectif: it.objectif ?? '',
  })))
}
function buildReponses() {
  return JSON.stringify(
    items.value.filter(it => it.type === 'item').map((it, i) => ({
      question_id: it.id ?? `q${i}`,
      reponse:     it.reponse     ?? '',
      forces:      it.forces      ?? '',
      faiblesses:  it.faiblesses  ?? '',
      objectif:    it.objectif    ?? '',
    }))
  )
}

// ── CSRF ──────────────────────────────────────────────────────────
function csrf() {
  return (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? ''
}

// ── Toast ─────────────────────────────────────────────────────────
const toast = ref({ show: false, type: 'success', msg: '' })
function showToast(type: string, msg: string) {
  toast.value = { show: true, type, msg }
  setTimeout(() => (toast.value.show = false), 4000)
}

// ── Import Excel ──────────────────────────────────────────────────
const xlsRef   = ref<HTMLInputElement | null>(null)
const importing = ref(false)

async function importExcel(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (!file) return
  importing.value = true
  const fd = new FormData()
  fd.append('file', file)
  fd.append('_token', csrf())
  try {
    const res = await fetch(`${props.formUrl}/import-excel`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrf() },
      body: fd,
    })
    const data = await res.json()
    if (!res.ok || !data.success) throw new Error(data.error ?? 'Erreur import')

    const mapped = (data.items as any[]).map((it: any) => ({
      _id:         uid(),
      type:        it.type,
      label:       it.label       ?? '',
      reponse:     '',               // vide — auditeur remplit dans la Vue
      forces:      it.forces      ?? '',
      faiblesses:  it.faiblesses  ?? '',
      objectif:    it.objectif    ?? '',
    }))

    // Remplacer ou ajouter les lignes
    if (items.value.length > 0) {
      const confirm = window.confirm(
        `Le tableau contient déjà ${items.value.length} ligne(s).\nRemplacer tout le contenu par les ${mapped.length} lignes importées ?`
      )
      if (!confirm) { importing.value = false; if (xlsRef.value) xlsRef.value.value = ''; return }
    }

    items.value = mapped
    showToast('success', `${mapped.length} ligne(s) importée(s) depuis Excel.`)
  } catch (err: any) {
    showToast('error', err.message ?? 'Erreur lors de l\'import Excel.')
  } finally {
    importing.value = false
    if (xlsRef.value) xlsRef.value.value = ''
  }
}

// ── Submit ────────────────────────────────────────────────────────
const processing = ref(false)

async function submit() {
  if (!form.intitule_qci?.trim()) {
    showToast('error', 'L\'intitulé est obligatoire.')
    return
  }
  processing.value = true
  try {
    const payload = {
      mission_id:    props.missionId,
      assignment_id: props.assignmentId,
      intitule_qci:  form.intitule_qci,
      fait_par:      form.fait_par,
      revue_par:     form.revue_par,
      date_fait:     form.date_fait,
      date_revue:    form.date_revue,
      synthese:      form.synthese,
      questions:     buildQuestions(),
      reponses:      buildReponses(),
    }
    const method = form.id ? 'PUT' : 'POST'
    const url    = form.id ? `${props.formUrl}/${form.id}` : props.formUrl
    const res    = await fetch(url!, {
      method,
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify(payload),
    })
    const data = await res.json()
    if (data.success || res.ok) {
      showToast('success', form.id ? 'QCI mis à jour.' : 'QCI créé.')
      if (!form.id && data.form?.id) { form.id = data.form.id; form.code = data.form.code }
    } else {
      showToast('error', data.message ?? 'Erreur.')
    }
  } catch { showToast('error', 'Erreur réseau.') }
  finally { processing.value = false }
}

function annuler() { if (props.backUrl) router.visit(props.backUrl) }

async function soumettre() {
  processing.value = true
  try {
    const res  = await fetch(`${props.formUrl}/${form.id}/soumettre`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({ mission_id: props.missionId, assignment_id: props.assignmentId }),
    })
    const data = await res.json()
    if (data.success) { form.validation_status = 'in_review'; showToast('success', 'QCI soumis.') }
    else showToast('error', data.error ?? 'Erreur')
  } catch { showToast('error', 'Erreur réseau') }
  finally { processing.value = false }
}

async function valider(action: string, note?: string) {
  processing.value = true
  try {
    const res  = await fetch(`${props.formUrl}/${form.id}/valider`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({ mission_id: props.missionId, assignment_id: props.assignmentId, action, note }),
    })
    const data = await res.json()
    if (data.success) {
      form.validation_status = data.status
      showToast('success', action === 'validate' ? 'QCI validé ✓' : 'QCI rejeté.')
    } else showToast('error', data.error ?? 'Erreur')
  } catch { showToast('error', 'Erreur réseau') }
  finally { processing.value = false }
}

function promptReject() {
  const n = prompt('Motif du rejet (obligatoire) :')
  if (n?.trim()) valider('reject', n.trim())
}

// ── Chat ──────────────────────────────────────────────────────────
// URL correcte : chatBaseUrl + '/PREPARATION'  (ex: /m/audit.core/missions/5/chat/PREPARATION)
const chatMsgEl   = ref<HTMLElement | null>(null)
const localMsgs   = ref<any[]>([...(props.chatMessages ?? [])])
const chatPanel   = ref({ show: false, draft: '', type: 'message', priority: 'normal', sending: false })
const unreadCount = computed(() => localMsgs.value.filter((m: any) => !m.is_read && !m.is_mine).length)

const PRIOS = [
  { v: 'normal',   l: 'Normal',   icon: 'ti ti-info-circle' },
  { v: 'urgent',   l: 'Urgent',   icon: 'ti ti-alert-triangle' },
  { v: 'bloquant', l: 'Bloquant', icon: 'ti ti-alert-octagon' },
]

function openChat() {
  chatPanel.value.show = true
  nextTick(() => { if (chatMsgEl.value) chatMsgEl.value.scrollTop = chatMsgEl.value.scrollHeight })
}

async function sendMsg() {
  const { draft, type, priority } = chatPanel.value
  if (!draft.trim()) return
  chatPanel.value.sending = true
  try {
    // URL : chatBaseUrl/PREPARATION  → /m/audit.core/missions/{id}/chat/PREPARATION
    const chatUrl = (props.chatBaseUrl ?? '') + '/PREPARATION'
    const res = await fetch(chatUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
      body: JSON.stringify({
        assignment_id: props.assignmentId,
        mission_id:    props.missionId,
        form_code:     'analyse-ci-qci',
        content: draft, type, priority,
      }),
    })
    const json = await res.json()
    if (!res.ok) throw new Error(json?.message ?? 'Erreur')
    localMsgs.value.push({ ...json.message, is_mine: true })
    chatPanel.value.draft = ''
    nextTick(() => { if (chatMsgEl.value) chatMsgEl.value.scrollTop = chatMsgEl.value.scrollHeight })
  } catch (e: any) {
    showToast('error', 'Erreur chat : ' + e.message)
  } finally {
    chatPanel.value.sending = false
  }
}

function chatTypeLbl(t: string) {
  return ({ instruction:'Instruction', correction:'Correction', validation:'Validation', rejet:'Rejet', info:'Info' } as any)[t] ?? t
}

function vstLbl(s: string) {
  return ({ draft:'Brouillon', in_review:'En attente', validated:'Validé ✓', rejected:'Rejeté' } as any)[s] ?? s
}
function vstIcon(s: string) {
  return ({ draft:'ti ti-pencil', in_review:'ti ti-clock', validated:'ti ti-circle-check', rejected:'ti ti-circle-x' } as any)[s] ?? 'ti ti-circle'
}
</script>

<style scoped>
*, *::before, *::after { box-sizing: border-box; }
.qci-shell { display:flex; flex-direction:column; min-height:100vh; background:#f4f6f8; font-family:'Segoe UI',system-ui,sans-serif; }

/* ══ HEADER ══════════════════════════════════════════════════════ */
.qci-header { position:sticky; top:0; z-index:100; background:#fff; border-bottom:1px solid #e2e8f0; box-shadow:0 1px 4px rgba(0,0,0,.06); padding:0 20px; }
.qci-hrow   { display:flex; align-items:center; gap:12px; min-height:60px; padding:8px 0; flex-wrap:wrap; }
.qci-back   { display:flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:8px; background:#f1f5f9; border:1px solid #e2e8f0; color:#64748b; text-decoration:none; flex-shrink:0; font-size:.9rem; transition:all .15s; }
.qci-back:hover { background:#1565C0; color:#fff; border-color:#1565C0; }
.qci-hinfo  { flex:1; min-width:0; }
.qci-chips  { display:flex; align-items:center; gap:5px; flex-wrap:wrap; margin-bottom:3px; }
.qci-code   { font-family:ui-monospace,monospace; font-size:.68rem; font-weight:700; background:#1e293b; color:#fff; padding:2px 8px; border-radius:5px; }
.qci-chip   { display:inline-flex; align-items:center; gap:3px; font-size:.62rem; font-weight:700; padding:2px 8px; border-radius:10px; text-transform:uppercase; letter-spacing:.04em; }
.chip-draft     { background:rgba(100,116,139,.1); color:#64748b; }
.chip-in_review { background:#e3f2fd; color:#1565C0; border:1px solid rgba(21,101,192,.2); }
.chip-validated { background:#d1e7dd; color:#0f5132; border:1px solid rgba(15,81,50,.2); }
.chip-rejected  { background:#f8d7da; color:#842029; border:1px solid rgba(132,32,41,.2); }
.chip-type  { background:#ede9fe; color:#7c3aed; border:1px solid #ddd6fe; }
.chip-role-DM { background:rgba(251,191,36,.18); color:#d97706; }
.chip-role-CM { background:rgba(21,101,192,.12); color:#1565C0; }
.chip-role-AS { background:rgba(22,163,74,.12); color:#15803d; }
.chip-role-AJ { background:rgba(124,58,237,.12); color:#6d28d9; }
.qci-title  { font-size:.92rem; font-weight:700; color:#1a1a2e; }
.qci-meta   { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-top:2px; }
.qci-meta span { display:inline-flex; align-items:center; gap:4px; font-size:.69rem; color:#64748b; }
.qci-hactions { display:flex; align-items:center; gap:8px; margin-left:auto; }
.qci-btn-chat { position:relative; width:34px; height:34px; border-radius:8px; border:1px solid #bbdefb; background:#e3f2fd; color:#1565C0; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:.85rem; transition:all .15s; }
.qci-btn-chat:hover, .qci-btn-chat.unread { background:#1565C0; color:#fff; border-color:#1565C0; }
.qci-chatbadge { position:absolute; top:-5px; right:-5px; width:15px; height:15px; background:#dc3545; border-radius:50%; font-size:.48rem; font-weight:700; color:#fff; display:flex; align-items:center; justify-content:center; border:2px solid #fff; }
.qci-banner { display:flex; align-items:center; gap:8px; padding:7px 0 10px; font-size:.77rem; border-top:1px solid #f1f5f9; }
.banner-lock   { color:#0f5132; }
.banner-review { color:#1565C0; }
.banner-reject { color:#842029; }

/* ══ BODY ════════════════════════════════════════════════════════ */
.qci-body { padding:20px; flex:1; }
.qci-grid { display:grid; grid-template-columns:270px 1fr; gap:18px; }
@media(max-width:900px){ .qci-grid{ grid-template-columns:1fr; } }

/* ══ CARDS ═══════════════════════════════════════════════════════ */
.card { background:#fff; border:1px solid #e2e8f0; border-radius:10px; margin-bottom:14px; overflow:hidden; }
.card-label { display:flex; align-items:center; gap:6px; font-size:.71rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#64748b; padding:9px 13px; background:#f8fafc; border-bottom:1px solid #e2e8f0; }
.card-count { margin-left:auto; font-size:.64rem; font-weight:800; background:#e5e7eb; color:#6b7280; padding:1px 6px; border-radius:8px; }
.btn-new-inline { width:20px; height:20px; border:1px solid #e5e7eb; border-radius:4px; background:transparent; color:#6b7280; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:.65rem; transition:all .12s; }
.btn-new-inline:hover { background:#eff6ff; color:#1565C0; border-color:#bbdefb; }
.card-body { padding:12px 13px; }

/* ══ FORM INPUTS ═════════════════════════════════════════════════ */
.form-row { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
.fg  { display:flex; flex-direction:column; gap:3px; margin-bottom:10px; }
.fg:last-child { margin-bottom:0; }
.flbl { font-size:.64rem; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.05em; }
.req { color:#dc2626; }
.inp  { background:#fff; border:1px solid #e2e8f0; color:#1e293b; padding:6px 9px; border-radius:6px; font-size:.78rem; outline:none; transition:border-color .15s; font-family:inherit; width:100%; }
.inp:focus { border-color:#1565C0; box-shadow:0 0 0 3px rgba(21,101,192,.1); }
.inp:disabled, .inp-ro { background:#f8fafc; color:#94a3b8; cursor:default; }
.inp-ta { resize:vertical; min-height:70px; }

/* ══ PIÈCES JOINTES ══════════════════════════════════════════════ */
.dropzone { border:2px dashed #e2e8f0; border-radius:8px; padding:16px; text-align:center; cursor:pointer; color:#94a3b8; display:flex; flex-direction:column; align-items:center; gap:4px; transition:all .15s; }
.dropzone:hover, .dz-over { border-color:#1565C0; background:#e3f2fd; color:#1565C0; }
.dz-ico { font-size:1.4rem; }
.dropzone span { font-size:.76rem; font-weight:500; color:#374151; }
.dropzone small { font-size:.66rem; }
.file-item { display:flex; align-items:center; gap:7px; padding:6px 8px; border:1px solid #f1f5f9; border-radius:6px; margin-top:5px; font-size:.74rem; }
.file-item .ti { font-size:.9rem; color:#6b7280; }
.file-name { flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:#1e293b; }
.file-badge { font-size:.6rem; font-weight:700; padding:1px 6px; border-radius:8px; flex-shrink:0; }
.badge-new   { background:#fef3c7; color:#d97706; }
.badge-saved { background:#d1e7dd; color:#0f5132; }
.file-size { font-size:.62rem; color:#9ca3af; flex-shrink:0; }
.file-del  { width:18px; height:18px; border:none; background:transparent; color:#d1d5db; cursor:pointer; border-radius:3px; display:flex; align-items:center; justify-content:center; font-size:.6rem; flex-shrink:0; }
.file-del:hover { color:#dc2626; background:#fef2f2; }
.file-empty { display:flex; align-items:center; gap:6px; font-size:.74rem; color:#d1d5db; padding:10px 0; }
.hidden { display:none; }

/* ══ QCI LIST ════════════════════════════════════════════════════ */
.qci-list-row { display:flex; align-items:center; justify-content:space-between; padding:8px 12px; border-bottom:1px solid #f3f4f6; cursor:pointer; transition:background .1s; gap:8px; }
.qci-list-row:last-child { border-bottom:none; }
.qci-list-row:hover { background:#f8fafc; }
.qci-list-row.active { background:#e3f2fd; border-left:3px solid #1565C0; }
.qlr-l { flex:1; min-width:0; }
.qlr-code  { font-size:.6rem; font-family:ui-monospace,monospace; color:#94a3b8; display:block; }
.qlr-title { font-size:.74rem; font-weight:500; color:#1e293b; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; display:block; }
.td-empty  { text-align:center; color:#d1d5db; padding:14px; font-size:.76rem; }

/* ══ TOOLBAR ═════════════════════════════════════════════════════ */
.qci-toolbar { display:flex; align-items:center; justify-content:space-between; background:#fff; border:1px solid #e2e8f0; border-radius:10px 10px 0 0; padding:10px 14px; gap:12px; flex-wrap:wrap; }
.qtb-l { display:flex; align-items:center; gap:8px; }
.qtb-title { font-size:.8rem; font-weight:700; color:#1e293b; }
.qtb-count { font-size:.67rem; color:#94a3b8; background:#f3f4f6; padding:2px 7px; border-radius:6px; }
.qtb-r { display:flex; gap:6px; }

/* ══ BOUTONS ═════════════════════════════════════════════════════ */
.btn { display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border-radius:7px; font-size:.76rem; font-weight:600; border:none; cursor:pointer; font-family:inherit; transition:all .15s; white-space:nowrap; }
.btn-save  { background:#1e293b; color:#fff; }
.btn-save:hover:not(:disabled)  { background:#0f172a; }
.btn-ghost { background:#fff; color:#374151; border:1px solid #e5e7eb; }
.btn-ghost:hover:not(:disabled) { background:#f9fafb; }
.btn-sub   { background:#eff6ff; color:#1565C0; border:1px solid #bbdefb; }
.btn-ok    { background:#d1e7dd; color:#0f5132; border:1px solid rgba(15,81,50,.2); }
.btn-rej   { background:#f8d7da; color:#842029; border:1px solid rgba(132,32,41,.2); }
.btn-cat   { background:#f5f3ff; color:#7c3aed; border:1px solid #ddd6fe; font-size:.74rem; padding:5px 10px; }
.btn-item  { background:#eff6ff; color:#1565C0; border:1px solid #bbdefb; font-size:.74rem; padding:5px 10px; }
.btn-import { background:#f0f9ff; color:#0369a1; border:1px solid #bae6fd; font-size:.74rem; padding:5px 10px; cursor:pointer; }
.btn-import:hover { background:#e0f2fe; }
.btn-tpl   { background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; font-size:.74rem; padding:5px 10px; text-decoration:none; }
.btn-tpl:hover { background:#dcfce7; }
.btn:disabled { opacity:.45; cursor:not-allowed; }

/* ══ TABLEAU QCI ═════════════════════════════════════════════════ */
.qci-table-wrap { overflow-x:auto; background:#fff; border:1px solid #e2e8f0; border-top:none; border-radius:0 0 10px 10px; }
.qci-table { width:100%; border-collapse:collapse; font-size:.76rem; min-width:800px; }
.qci-table thead tr { background:#1565C0; }
.qci-table th { padding:8px 10px; text-align:left; font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#fff; white-space:nowrap; }
.th-num  { width:40px; text-align:center; }
.th-question { min-width:200px; }
.th-rep  { width:100px; text-align:center; }
.th-forces { min-width:140px; }
.th-faibl  { min-width:140px; }
.th-obj    { min-width:140px; }
.th-act    { width:80px; }

/* Ligne catégorie */
.row-cat { background:#e8f0fe; cursor:pointer; border-bottom:1px solid #bfdbfe; }
.row-cat:hover { background:#dbeafe; }
.td-cat-label { padding:7px 10px; }
.inp-cat { width:100%; background:transparent; border:none; font-size:.8rem; font-weight:700; color:#1e3a5f; outline:none; font-family:inherit; }
.inp-cat:focus { border-bottom:1px solid #1565C0; }

/* Ligne item */
.row-item { border-bottom:1px solid #f1f5f9; transition:background .1s; }
.row-item:hover { background:#fafafa; }
.row-hidden { display:none; }
.row-oui { background:#f0fdf4; }
.row-non { background:#fef2f2; }
.row-so  { background:#f8fafc; opacity:.75; }
.td-num  { text-align:center; padding:4px; }
.item-num { font-size:.65rem; font-weight:700; color:#94a3b8; font-family:ui-monospace,monospace; }
.td-question { padding:6px 8px; }
.inp-q  { width:100%; border:none; background:transparent; resize:none; font-size:.76rem; color:#1e293b; font-family:inherit; outline:none; min-height:38px; padding:2px 0; line-height:1.5; }
.inp-q:focus { border-bottom:1px dashed #1565C0; }
.q-ro { font-size:.76rem; color:#374151; line-height:1.5; display:block; }

/* SELECT O/N/SO */
.td-rep { text-align:center; padding:4px 6px; }
.sel-rep { width:100%; border:1px solid #d1d5db; border-radius:6px; padding:5px 6px; font-size:.78rem; font-weight:600; cursor:pointer; outline:none; font-family:inherit; transition:all .15s; text-align:center; }
.sel-rep:focus { border-color:#1565C0; box-shadow:0 0 0 2px rgba(21,101,192,.1); }
.sel-Oui        { background:#ecfdf5; color:#059669; border-color:#a7f3d0; }
.sel-Non        { background:#fef2f2; color:#dc2626; border-color:#fecaca; }
.sel-Sans\ objet { background:#f3f4f6; color:#6b7280; border-color:#d1d5db; }
.sel-empty      { background:#fffbeb; color:#92400e; border-color:#fde68a; }
.rep-ro { display:inline-block; font-size:.76rem; font-weight:700; padding:2px 8px; border-radius:5px; }
.rep-ro-Oui        { background:#ecfdf5; color:#059669; }
.rep-ro-Non        { background:#fef2f2; color:#dc2626; }
.rep-ro-Sans\ objet { background:#f3f4f6; color:#6b7280; }
.rep-ro-empty      { color:#d1d5db; }

/* Forces / Faiblesses / Objectif */
.td-forces, .td-faibl, .td-obj { padding:5px 7px; vertical-align:top; }
.inp-obs { width:100%; border:none; background:transparent; font-size:.72rem; color:#374151; font-family:inherit; outline:none; resize:none; min-height:34px; line-height:1.5; padding:2px 0; }
.inp-obs:focus { border-bottom:1px dashed #1565C0; }
.inp-obj { width:100%; border:none; background:transparent; font-size:.72rem; color:#374151; font-family:inherit; outline:none; padding:4px 0; line-height:1.5; }
.inp-obj:focus { border-bottom:1px dashed #1565C0; }
.obs-ro { font-size:.72rem; color:#64748b; line-height:1.5; display:block; }

/* Actions */
.td-act { padding:4px 6px; white-space:nowrap; text-align:right; }
.act-btn { width:22px; height:22px; border:1px solid transparent; border-radius:4px; background:transparent; cursor:pointer; color:#d1d5db; font-size:.68rem; display:inline-flex; align-items:center; justify-content:center; transition:all .12s; margin-left:2px; }
.act-btn:disabled { opacity:.3; cursor:not-allowed; }
.act-add:hover { color:#059669; border-color:#a7f3d0; background:#ecfdf5; }
.act-del:hover { color:#dc2626; border-color:#fecaca; background:#fef2f2; }
.act-up:hover, .act-down:hover { color:#1565C0; border-color:#bbdefb; background:#eff6ff; }

/* Empty state */
.qci-empty { text-align:center; padding:40px 20px; color:#94a3b8; }
.qci-empty i { font-size:1.8rem; display:block; margin-bottom:10px; }
.qci-empty p { font-size:.78rem; margin-bottom:14px; }
.empty-actions { display:flex; gap:8px; justify-content:center; flex-wrap:wrap; }

/* ══ FOOTER ══════════════════════════════════════════════════════ */
.qci-footer { display:flex; align-items:center; justify-content:space-between; padding:12px 20px; background:#fff; border-top:1px solid #e2e8f0; position:sticky; bottom:0; z-index:50; flex-wrap:wrap; gap:8px; }
.qci-footer > div { display:flex; gap:8px; flex-wrap:wrap; }

/* ══ CHAT ════════════════════════════════════════════════════════ */
.qci-chat-overlay { position:fixed; inset:0; background:rgba(0,0,0,.28); z-index:400; }
.qci-chat-panel { position:fixed; top:0; right:0; bottom:0; width:360px; max-width:95vw; background:#fff; border-left:1px solid #e2e8f0; box-shadow:-4px 0 24px rgba(0,0,0,.12); display:flex; flex-direction:column; z-index:500; }
.slide-right-enter-active, .slide-right-leave-active { transition:transform .22s ease; }
.slide-right-enter-from, .slide-right-leave-to { transform:translateX(100%); }
.qci-chat-hd { display:flex; align-items:center; justify-content:space-between; padding:12px 14px; border-bottom:1px solid #e2e8f0; background:#f8fafc; gap:10px; }
.qci-chat-hdinfo { display:flex; align-items:center; gap:10px; min-width:0; }
.qci-chat-av { width:34px; height:34px; border-radius:9px; background:#e3f2fd; color:#1565C0; display:flex; align-items:center; justify-content:center; font-size:.95rem; flex-shrink:0; }
.qci-chat-title { display:block; font-size:.8rem; font-weight:700; color:#1a1a2e; }
.qci-chat-sub   { display:block; font-size:.6rem; color:#94a3b8; font-family:monospace; }
.qci-chat-close { width:26px; height:26px; border-radius:6px; background:#f1f5f9; border:1px solid #e2e8f0; color:#64748b; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:.72rem; }
.qci-chat-msgs  { flex:1; overflow-y:auto; padding:12px; display:flex; flex-direction:column; gap:8px; }
.qci-chat-msgs::-webkit-scrollbar { width:3px; }
.qci-chat-empty { display:flex; flex-direction:column; align-items:center; gap:8px; padding:40px 20px; color:#cbd5e1; }
.qci-chat-empty i { font-size:1.8rem; }
.qci-chat-empty p { font-size:.76rem; }
.qci-cmsg { display:flex; gap:8px; }
.qci-cmsg.mine { flex-direction:row-reverse; }
.qci-cav { width:26px; height:26px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.5rem; font-weight:700; flex-shrink:0; background:#e5e7eb; color:#6b7280; }
.cav-DM { background:#f5f3ff; color:#7c3aed; } .cav-CM { background:#e3f2fd; color:#1565C0; }
.cav-AS { background:#f0fdf4; color:#059669; } .cav-AJ { background:#fffbeb; color:#d97706; }
.qci-cbody2 { flex:1; min-width:0; }
.qci-cmsg.mine .qci-cbody2 { align-items:flex-end; display:flex; flex-direction:column; }
.qci-cmeta { display:flex; align-items:center; gap:5px; flex-wrap:wrap; margin-bottom:3px; }
.qci-cmsg.mine .qci-cmeta { flex-direction:row-reverse; }
.qci-cwho { font-size:.65rem; font-weight:700; }
.cr-DM { color:#7c3aed; } .cr-CM { color:#1565C0; } .cr-AS { color:#059669; } .cr-AJ { color:#d97706; }
.qci-crole { font-size:.58rem; font-weight:600; background:#f3f4f6; color:#6b7280; padding:1px 5px; border-radius:4px; }
.qci-ctypetag { font-size:.6rem; font-weight:600; padding:1px 6px; border-radius:4px; background:#e3f2fd; color:#1565C0; }
.qci-cpritag  { font-size:.58rem; font-weight:700; padding:1px 5px; border-radius:4px; }
.pp-urgent   { background:#fef3c7; color:#d97706; } .pp-bloquant { background:#fef2f2; color:#dc2626; }
.qci-cdate { font-size:.58rem; color:#94a3b8; margin-left:auto; }
.qci-ctxt { font-size:.75rem; color:#1e293b; line-height:1.5; background:#f8fafc; padding:7px 10px; border-radius:8px; border:1px solid #e9ecef; white-space:pre-wrap; }
.qci-cmsg.mine .qci-ctxt { background:#e3f2fd; border-color:#bbdefb; }
.fp-bloquant .qci-cbody2 { padding-left:6px; border-left:2px solid #dc2626; }
.fp-urgent   .qci-cbody2 { padding-left:6px; border-left:2px solid #f59e0b; }
.qci-chat-compose { border-top:1px solid #e2e8f0; padding:10px 12px 12px; background:#f8fafc; flex-shrink:0; display:flex; flex-direction:column; gap:7px; }
.qci-chat-opts { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.qci-chat-sel { border:1px solid #d1d5db; border-radius:6px; padding:4px 9px; font-size:.69rem; color:#1a1a2e; background:#fff; cursor:pointer; font-family:inherit; }
.qci-prios { display:flex; gap:4px; }
.qci-priobtn { display:inline-flex; align-items:center; gap:3px; padding:3px 8px; border-radius:5px; border:1px solid #e5e7eb; background:#fff; font-size:.62rem; font-weight:600; cursor:pointer; color:#6b7280; transition:all .12s; font-family:inherit; }
.qci-priobtn.active, .qci-priobtn:hover { background:#f3f4f6; }
.ppb-urgent.active   { background:#fef3c7; color:#d97706; border-color:#fde68a; }
.ppb-bloquant.active { background:#fef2f2; color:#dc2626; border-color:#fecaca; }
.qci-chat-row { display:flex; gap:8px; align-items:flex-end; }
.qci-chat-ta { flex:1; border:1px solid #d1d5db; border-radius:8px; padding:8px 10px; font-size:.77rem; color:#1a1a2e; font-family:inherit; resize:none; outline:none; background:#fff; transition:border-color .12s; }
.qci-chat-ta:focus { border-color:#1565C0; }
.qci-chat-send { width:34px; height:34px; border-radius:8px; border:none; background:#1565C0; color:#fff; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:.82rem; transition:filter .12s; }
.qci-chat-send:disabled { opacity:.35; cursor:not-allowed; }
.qci-chat-send:not(:disabled):hover { filter:brightness(1.12); }
.qci-chat-hint { font-size:.57rem; color:#94a3b8; }

/* ══ TOAST ═══════════════════════════════════════════════════════ */
.qci-toast { position:fixed; top:18px; right:18px; z-index:9999; display:flex; align-items:center; gap:8px; padding:10px 16px; border-radius:8px; font-size:.8rem; font-weight:600; box-shadow:0 4px 18px rgba(0,0,0,.12); }
.toast-success { background:#d1e7dd; color:#0f5132; border:1px solid rgba(15,81,50,.2); }
.toast-error   { background:#f8d7da; color:#842029; border:1px solid rgba(132,32,41,.2); }
.toast-t-enter-active, .toast-t-leave-active { transition:all .25s; }
.toast-t-enter-from, .toast-t-leave-to { opacity:0; transform:translateX(12px); }

/* ══ SPINNER ═════════════════════════════════════════════════════ */
.spin-dot { width:10px; height:10px; border:2px solid rgba(255,255,255,.3); border-top-color:#fff; border-radius:50%; animation:spin .6s linear infinite; display:inline-block; flex-shrink:0; }
.spin-sm  { width:12px; height:12px; }
@keyframes spin { to { transform:rotate(360deg); } }
</style>