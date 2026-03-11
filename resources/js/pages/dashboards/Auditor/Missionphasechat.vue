<template>
  <div class="mpc-wrap" :class="{ 'mpc-open': isOpen }">

    <!-- ── Trigger bouton ── -->
    <button class="mpc-trigger" @click="toggleChat" :title="`Chat ${phaseLabel}`">
      <span class="mpc-trigger-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
        </svg>
      </span>
      <span class="mpc-trigger-label">Chat</span>
      <span v-if="unreadCount > 0" class="mpc-badge">{{ unreadCount > 99 ? '99+' : unreadCount }}</span>
    </button>

    <!-- ── Panneau chat ── -->
    <transition name="mpc-slide">
      <div v-if="isOpen" class="mpc-panel">

        <!-- Header -->
        <div class="mpc-header">
          <div class="mpc-header-left">
            <span class="mpc-phase-dot" :class="`dot-${phaseType.toLowerCase()}`"></span>
            <div class="mpc-header-info">
              <span class="mpc-header-title">{{ phaseLabel }}</span>
              <span class="mpc-header-sub">{{ messages.length }} message{{ messages.length !== 1 ? 's' : '' }}</span>
            </div>
          </div>
          <div class="mpc-header-actions">
            <button class="mpc-icon-btn" @click="loadMessages" :disabled="loading" title="Actualiser">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" :class="{ 'spin': loading }">
                <polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/>
                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
              </svg>
            </button>
            <button class="mpc-icon-btn" @click="isOpen = false" title="Fermer">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- Messages list -->
        <div class="mpc-messages" ref="scrollEl">

          <div v-if="loading && messages.length === 0" class="mpc-loader">
            <span class="mpc-spinner"></span>
            <span>Chargement…</span>
          </div>

          <div v-else-if="messages.length === 0" class="mpc-empty">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            <span>Aucun message pour cette phase</span>
          </div>

          <template v-else>
            <!-- Épinglés -->
            <div v-if="pinnedMessages.length" class="mpc-pinned-section">
              <div class="mpc-section-label">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="11" height="11">
                  <line x1="12" y1="17" x2="12" y2="22"/><path d="M5 17H19V13L17 11V5H7V11L5 13V17Z"/>
                </svg>
                Épinglés
              </div>
              <div
                v-for="msg in pinnedMessages"
                :key="`pin-${msg.id}`"
                class="mpc-msg mpc-msg-pinned"
                :class="{ 'is-mine': msg.is_mine }"
              >
                <div class="mpc-avatar" :class="`role-${msg.author_role?.toLowerCase()}`">{{ msg.author_initials }}</div>
                <div class="mpc-bubble">
                  <div class="mpc-meta">
                    <span class="mpc-author">{{ msg.author_name }}</span>
                    <span class="mpc-role-badge" :class="`role-${msg.author_role?.toLowerCase()}`">{{ msg.author_role }}</span>
                    <span class="mpc-time">{{ msg.created_at_fr }}</span>
                  </div>
                  <div class="mpc-content">{{ msg.content }}</div>
                </div>
              </div>
            </div>

            <!-- Tous les messages -->
            <template v-for="(msg, idx) in messages" :key="msg.id">
              <!-- Séparateur de date -->
              <div v-if="showDateSep(idx)" class="mpc-date-sep">
                <span>{{ getDateLabel(msg.created_at_raw) }}</span>
              </div>

              <div
                class="mpc-msg"
                :class="{
                  'is-mine': msg.is_mine,
                  'is-reply': msg.parent_id,
                  'is-unread': !msg.is_read && !msg.is_mine,
                  [`type-${msg.type}`]: true
                }"
              >
                <div v-if="!msg.is_mine" class="mpc-avatar" :class="`role-${msg.author_role?.toLowerCase()}`">
                  {{ msg.author_initials }}
                </div>
                <div class="mpc-bubble-wrap">
                  <!-- Reply preview -->
                  <div v-if="msg.parent_id" class="mpc-reply-preview">
                    <span class="mpc-reply-bar"></span>
                    <span class="mpc-reply-text">{{ getParentSnippet(msg.parent_id) }}</span>
                  </div>

                  <div class="mpc-bubble" :class="`type-${msg.type}`">
                    <div v-if="!msg.is_mine" class="mpc-meta">
                      <span class="mpc-author">{{ msg.author_name }}</span>
                      <span class="mpc-role-badge" :class="`role-${msg.author_role?.toLowerCase()}`">{{ msg.author_role }}</span>
                    </div>

                    <!-- Type badge pour instructions/corrections/etc -->
                    <div v-if="msg.type && msg.type !== 'message'" class="mpc-type-tag" :class="`tag-${msg.type}`">
                      <component :is="typeIcon(msg.type)" class="mpc-type-icon" />
                      {{ typeLabel(msg.type) }}
                      <span v-if="msg.priority && msg.priority !== 'normal'" class="mpc-priority" :class="`prio-${msg.priority}`">
                        {{ msg.priority }}
                      </span>
                    </div>

                    <div class="mpc-content">{{ msg.content }}</div>

                    <div class="mpc-msg-footer">
                      <span class="mpc-time">{{ msg.created_at_fr }}</span>
                      <span v-if="msg.is_pinned" class="mpc-pin-icon" title="Épinglé">
                        <svg viewBox="0 0 24 24" fill="currentColor" width="10" height="10">
                          <path d="M5 17H19V13L17 11V5H7V11L5 13V17Z M12 17V22"/>
                        </svg>
                      </span>
                      <span v-if="msg.is_mine" class="mpc-read-tick" :class="{ read: msg.is_read }">
                        <svg viewBox="0 0 16 10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="10">
                          <polyline points="1,5 5,9 15,1"/>
                        </svg>
                      </span>
                    </div>
                  </div>

                  <!-- Actions hover -->
                  <div class="mpc-msg-actions">
                    <button class="mpc-action-btn" @click="replyTo(msg)" title="Répondre">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 17 4 12 9 7"/><path d="M20 18v-2a4 4 0 0 0-4-4H4"/>
                      </svg>
                    </button>
                    <button v-if="canPin" class="mpc-action-btn" @click="togglePin(msg)" :title="msg.is_pinned ? 'Désépingler' : 'Épingler'">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="17" x2="12" y2="22"/><path d="M5 17H19V13L17 11V5H7V11L5 13V17Z"/>
                      </svg>
                    </button>
                  </div>
                </div>

                <div v-if="msg.is_mine" class="mpc-avatar mpc-avatar-mine" :class="`role-${msg.author_role?.toLowerCase()}`">
                  {{ msg.author_initials }}
                </div>
              </div>
            </template>
          </template>
        </div>

        <!-- Reply preview bar -->
        <transition name="mpc-fade">
          <div v-if="replyingTo" class="mpc-reply-bar-wrap">
            <div class="mpc-reply-bar-content">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
                <polyline points="9 17 4 12 9 7"/><path d="M20 18v-2a4 4 0 0 0-4-4H4"/>
              </svg>
              <span class="mpc-reply-name">{{ replyingTo.author_name }}</span>
              <span class="mpc-reply-snippet">{{ replyingTo.content.substring(0, 60) }}{{ replyingTo.content.length > 60 ? '…' : '' }}</span>
            </div>
            <button class="mpc-icon-btn" @click="replyingTo = null">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
              </svg>
            </button>
          </div>
        </transition>

        <!-- Input -->
        <div class="mpc-input-wrap">
          <div class="mpc-type-selector">
            <select v-model="newType" class="mpc-select">
              <option value="message">💬 Message</option>
              <option v-if="canInstruct" value="instruction">📋 Instruction</option>
              <option v-if="canInstruct" value="correction">✏️ Correction</option>
              <option v-if="canInstruct" value="validation">✅ Validation</option>
              <option v-if="canInstruct" value="rejet">❌ Rejet</option>
              <option value="info">ℹ️ Info</option>
            </select>
            <select v-if="newType !== 'message' && newType !== 'info'" v-model="newPriority" class="mpc-select mpc-select-sm">
              <option value="normal">Normal</option>
              <option value="urgent">🔴 Urgent</option>
              <option value="bloquant">⛔ Bloquant</option>
            </select>
          </div>
          <div class="mpc-textarea-row">
            <textarea
              ref="inputEl"
              v-model="newMessage"
              class="mpc-textarea"
              placeholder="Écrire un message…"
              rows="2"
              @keydown.enter.exact.prevent="sendMessage"
              @keydown.enter.shift.exact="newMessage += '\n'"
            ></textarea>
            <button
              class="mpc-send-btn"
              :disabled="!newMessage.trim() || sending"
              @click="sendMessage"
              title="Envoyer (Entrée)"
            >
              <span v-if="sending" class="mpc-spinner-sm"></span>
              <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
              </svg>
            </button>
          </div>
          <div class="mpc-input-hint">Entrée pour envoyer · Maj+Entrée pour saut de ligne</div>
        </div>

      </div>
    </transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted, onUnmounted, h } from 'vue'
import axios from 'axios'

// ── Props ────────────────────────────────────────────────────────
const props = defineProps<{
  missionId: number
  phaseType: string          // PREPARATION | VERIFICATION | CONCLUSION | SUIVI
  myRole?: string            // DM | CM | AS | AJ
  assignmentId?: number
  formCode?: string
  autoOpen?: boolean
}>()

// ── State ────────────────────────────────────────────────────────
const isOpen      = ref(props.autoOpen ?? false)
const messages    = ref<any[]>([])
const loading     = ref(false)
const sending     = ref(false)
const newMessage  = ref('')
const newType     = ref('message')
const newPriority = ref('normal')
const replyingTo  = ref<any>(null)
const unreadCount = ref(0)
const scrollEl    = ref<HTMLElement | null>(null)
const inputEl     = ref<HTMLTextAreaElement | null>(null)
let   pollTimer: ReturnType<typeof setInterval> | null = null

// ── Computed ─────────────────────────────────────────────────────
const phaseLabel = computed(() => ({
  PREPARATION:  'Préparation',
  VERIFICATION: 'Vérification',
  CONCLUSION:   'Conclusion',
  SUIVI:        'Suivi',
}[props.phaseType] ?? props.phaseType))

const canPin      = computed(() => ['DM','CM'].includes(props.myRole ?? ''))
const canInstruct = computed(() => ['DM','CM'].includes(props.myRole ?? ''))

const pinnedMessages = computed(() => messages.value.filter(m => m.is_pinned))

// ── Helpers ──────────────────────────────────────────────────────
function baseUrl() {
  return `${window.location.origin}/m/audit.core/missions/${props.missionId}/chat/${props.phaseType}`
}

function getParentSnippet(parentId: number): string {
  const p = messages.value.find(m => m.id === parentId)
  if (!p) return '…'
  return `${p.author_name}: ${p.content.substring(0, 50)}${p.content.length > 50 ? '…' : ''}`
}

function showDateSep(idx: number): boolean {
  if (idx === 0) return true
  const prev = messages.value[idx - 1]
  const curr = messages.value[idx]
  if (!prev?.created_at_raw || !curr?.created_at_raw) return false
  return prev.created_at_raw.substring(0, 10) !== curr.created_at_raw.substring(0, 10)
}

function getDateLabel(raw: string): string {
  if (!raw) return ''
  const d = new Date(raw)
  const today = new Date()
  const yesterday = new Date(today); yesterday.setDate(today.getDate() - 1)
  if (d.toDateString() === today.toDateString())     return "Aujourd'hui"
  if (d.toDateString() === yesterday.toDateString()) return 'Hier'
  return d.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' })
}

function typeLabel(type: string): string {
  return { instruction: 'Instruction', correction: 'Correction', validation: 'Validation', rejet: 'Rejet', info: 'Info' }[type] ?? type
}

function typeIcon(type: string) {
  const icons: Record<string, any> = {
    instruction: () => h('svg', { viewBox:'0 0 24 24', fill:'none', stroke:'currentColor', 'stroke-width':'2', 'stroke-linecap':'round', 'stroke-linejoin':'round' }, [
      h('path', { d:'M9 11l3 3L22 4' }), h('path', { d:'M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11' })
    ]),
    correction: () => h('svg', { viewBox:'0 0 24 24', fill:'none', stroke:'currentColor', 'stroke-width':'2', 'stroke-linecap':'round', 'stroke-linejoin':'round' }, [
      h('path', { d:'M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7' }),
      h('path', { d:'M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z' })
    ]),
    validation: () => h('svg', { viewBox:'0 0 24 24', fill:'none', stroke:'currentColor', 'stroke-width':'2', 'stroke-linecap':'round', 'stroke-linejoin':'round' }, [
      h('polyline', { points:'20 6 9 17 4 12' })
    ]),
    rejet: () => h('svg', { viewBox:'0 0 24 24', fill:'none', stroke:'currentColor', 'stroke-width':'2', 'stroke-linecap':'round', 'stroke-linejoin':'round' }, [
      h('circle', { cx:'12', cy:'12', r:'10' }),
      h('line', { x1:'15', y1:'9', x2:'9', y2:'15' }), h('line', { x1:'9', y1:'9', x2:'15', y2:'15' })
    ]),
    info: () => h('svg', { viewBox:'0 0 24 24', fill:'none', stroke:'currentColor', 'stroke-width':'2', 'stroke-linecap':'round', 'stroke-linejoin':'round' }, [
      h('circle', { cx:'12', cy:'12', r:'10' }),
      h('line', { x1:'12', y1:'8', x2:'12', y2:'12' }), h('line', { x1:'12', y1:'16', x2:'12.01', y2:'16' })
    ]),
  }
  return icons[type] ?? icons['info']
}

// ── API ──────────────────────────────────────────────────────────
async function loadMessages() {
  loading.value = true
  try {
    const { data } = await axios.get(baseUrl())
    messages.value  = data.messages ?? []
    unreadCount.value = 0
    await nextTick()
    scrollToBottom()
  } catch (e) {
    console.error('[MissionPhaseChat] loadMessages error', e)
  } finally {
    loading.value = false
  }
}

async function sendMessage() {
  const content = newMessage.value.trim()
  if (!content || sending.value) return
  sending.value = true
  try {
    const payload: any = {
      content,
      type:          newType.value,
      priority:      newPriority.value,
      assignment_id: props.assignmentId,
      form_code:     props.formCode,
    }
    if (replyingTo.value) payload.parent_id = replyingTo.value.id

    const { data } = await axios.post(baseUrl(), payload)
    if (data.success && data.message) {
      messages.value.push(data.message)
      newMessage.value  = ''
      newType.value     = 'message'
      newPriority.value = 'normal'
      replyingTo.value  = null
      await nextTick()
      scrollToBottom()
    }
  } catch (e) {
    console.error('[MissionPhaseChat] sendMessage error', e)
  } finally {
    sending.value = false
  }
}

async function togglePin(msg: any) {
  try {
    const origin = window.location.origin
    const { data } = await axios.patch(
      `${origin}/m/audit.core/missions/${props.missionId}/chat/${msg.id}/pin`
    )
    if (data.success) msg.is_pinned = data.is_pinned
  } catch (e) {
    console.error('[MissionPhaseChat] pin error', e)
  }
}

function replyTo(msg: any) {
  replyingTo.value = msg
  nextTick(() => inputEl.value?.focus())
}

function scrollToBottom() {
  if (scrollEl.value) {
    scrollEl.value.scrollTop = scrollEl.value.scrollHeight
  }
}

// ── Polling auto (toutes les 15s quand ouvert) ───────────────────
async function pollMessages() {
  if (!isOpen.value || loading.value || sending.value) return
  try {
    const { data } = await axios.get(baseUrl())
    const incoming = data.messages ?? []
    // Ajouter seulement les nouveaux messages
    const existingIds = new Set(messages.value.map((m: any) => m.id))
    const newOnes = incoming.filter((m: any) => !existingIds.has(m.id))
    if (newOnes.length) {
      messages.value.push(...newOnes)
      await nextTick()
      scrollToBottom()
    }
  } catch {}
}

function startPolling() {
  if (pollTimer) return
  pollTimer = setInterval(pollMessages, 15000)
}
function stopPolling() {
  if (pollTimer) { clearInterval(pollTimer); pollTimer = null }
}

// ── Toggle ───────────────────────────────────────────────────────
function toggleChat() {
  isOpen.value = !isOpen.value
  if (isOpen.value && messages.value.length === 0) loadMessages()
}

// ── Watchers ─────────────────────────────────────────────────────
watch(isOpen, (val) => {
  if (val) {
    startPolling()
    nextTick(scrollToBottom)
  } else {
    stopPolling()
  }
})

onMounted(() => {
  if (props.autoOpen) {
    loadMessages()
    startPolling()
  }
})
onUnmounted(() => stopPolling())
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap');

/* ══ Variables ═══════════════════════════════════════════════════ */
.mpc-wrap {
  --c-bg:        #ffffff;
  --c-surface:   #f8fafc;
  --c-border:    #e2e8f0;
  --c-border-md: #cbd5e1;
  --c-text:      #1e293b;
  --c-sub:       #64748b;
  --c-muted:     #94a3b8;
  --c-accent:    #3b82f6;
  --c-mine-bg:   #eff6ff;
  --c-mine-brd:  #bfdbfe;
  --c-pin-bg:    #fefce8;
  --c-pin-brd:   #fde68a;

  --role-dm: #7c3aed; --role-dm-bg: #f5f3ff;
  --role-cm: #0284c7; --role-cm-bg: #f0f9ff;
  --role-as: #059669; --role-as-bg: #f0fdf4;
  --role-aj: #d97706; --role-aj-bg: #fffbeb;

  --shadow-sm: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
  --shadow-lg: 0 10px 40px rgba(0,0,0,.12), 0 4px 16px rgba(0,0,0,.08);

  font-family: 'DM Sans', sans-serif;
  position: relative;
  display: inline-block;
}

/* ══ Trigger ══════════════════════════════════════════════════════ */
.mpc-trigger {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px 6px 8px;
  background: var(--c-bg);
  border: 1px solid var(--c-border);
  border-radius: 8px;
  color: var(--c-sub);
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: all .15s ease;
  position: relative;
  box-shadow: var(--shadow-sm);
}
.mpc-trigger:hover {
  border-color: var(--c-accent);
  color: var(--c-accent);
  background: #eff6ff;
}
.mpc-trigger-icon {
  display: flex; align-items: center;
  width: 16px; height: 16px;
}
.mpc-trigger-icon svg { width: 16px; height: 16px; }
.mpc-badge {
  position: absolute;
  top: -6px; right: -6px;
  background: #ef4444;
  color: #fff;
  font-size: 10px;
  font-weight: 700;
  font-family: 'JetBrains Mono', monospace;
  min-width: 18px; height: 18px;
  border-radius: 9px;
  display: flex; align-items: center; justify-content: center;
  padding: 0 4px;
  border: 2px solid #fff;
}

/* ══ Panel ════════════════════════════════════════════════════════ */
.mpc-panel {
  position: absolute;
  bottom: calc(100% + 8px);
  right: 0;
  width: 360px;
  max-width: 95vw;
  background: var(--c-bg);
  border: 1px solid var(--c-border);
  border-radius: 14px;
  box-shadow: var(--shadow-lg);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  z-index: 1000;
}

/* ══ Header ═══════════════════════════════════════════════════════ */
.mpc-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 12px;
  border-bottom: 1px solid var(--c-border);
  background: var(--c-surface);
  gap: 8px;
}
.mpc-header-left {
  display: flex; align-items: center; gap: 8px;
}
.mpc-phase-dot {
  width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0;
}
.dot-preparation  { background: #3b82f6; }
.dot-verification { background: #8b5cf6; }
.dot-conclusion   { background: #10b981; }
.dot-suivi        { background: #f59e0b; }

.mpc-header-info { display: flex; flex-direction: column; gap: 0px; }
.mpc-header-title { font-size: 13px; font-weight: 600; color: var(--c-text); line-height: 1.3; }
.mpc-header-sub   { font-size: 11px; color: var(--c-muted); line-height: 1.2; }

.mpc-header-actions { display: flex; align-items: center; gap: 2px; }
.mpc-icon-btn {
  display: flex; align-items: center; justify-content: center;
  width: 28px; height: 28px;
  background: transparent; border: none; border-radius: 6px;
  color: var(--c-sub); cursor: pointer;
  transition: all .12s;
}
.mpc-icon-btn:hover { background: var(--c-border); color: var(--c-text); }
.mpc-icon-btn svg { width: 14px; height: 14px; }
.mpc-icon-btn:disabled { opacity: .4; cursor: not-allowed; }

/* ══ Messages area ════════════════════════════════════════════════ */
.mpc-messages {
  flex: 1;
  overflow-y: auto;
  padding: 10px 10px 4px;
  max-height: 340px;
  min-height: 120px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  scroll-behavior: smooth;
}
.mpc-messages::-webkit-scrollbar { width: 4px; }
.mpc-messages::-webkit-scrollbar-track { background: transparent; }
.mpc-messages::-webkit-scrollbar-thumb { background: var(--c-border-md); border-radius: 2px; }

/* ── Empty / loader ── */
.mpc-loader, .mpc-empty {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 8px; color: var(--c-muted); font-size: 12px;
  padding: 24px 0;
}
.mpc-empty svg { width: 28px; height: 28px; opacity: .35; }

/* ── Date separator ── */
.mpc-date-sep {
  display: flex; align-items: center; justify-content: center;
  margin: 4px 0;
}
.mpc-date-sep span {
  font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px;
  color: var(--c-muted); background: var(--c-surface);
  padding: 2px 8px; border-radius: 10px;
  border: 1px solid var(--c-border);
}

/* ── Pinned section ── */
.mpc-pinned-section {
  background: var(--c-pin-bg);
  border: 1px solid var(--c-pin-brd);
  border-radius: 8px;
  padding: 6px 8px;
  margin-bottom: 4px;
}
.mpc-section-label {
  display: flex; align-items: center; gap: 4px;
  font-size: 10px; font-weight: 600; text-transform: uppercase;
  letter-spacing: .5px; color: #92400e; margin-bottom: 6px;
}
.mpc-section-label svg { flex-shrink: 0; }

/* ══ Message ══════════════════════════════════════════════════════ */
.mpc-msg {
  display: flex;
  align-items: flex-end;
  gap: 6px;
  position: relative;
}
.mpc-msg:hover .mpc-msg-actions { opacity: 1; }
.mpc-msg.is-mine { flex-direction: row-reverse; }
.mpc-msg.is-unread .mpc-bubble { border-left: 2px solid var(--c-accent); }

/* Avatar */
.mpc-avatar {
  width: 26px; height: 26px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 9px; font-weight: 700; letter-spacing: .3px;
  flex-shrink: 0; text-transform: uppercase;
}
.mpc-avatar.role-dm { background: var(--role-dm-bg); color: var(--role-dm); }
.mpc-avatar.role-cm { background: var(--role-cm-bg); color: var(--role-cm); }
.mpc-avatar.role-as { background: var(--role-as-bg); color: var(--role-as); }
.mpc-avatar.role-aj { background: var(--role-aj-bg); color: var(--role-aj); }
.mpc-avatar-mine { border: 2px solid var(--c-mine-brd); }

/* Bubble wrap */
.mpc-bubble-wrap {
  display: flex; flex-direction: column; gap: 2px;
  max-width: calc(100% - 70px);
}
.mpc-msg.is-mine .mpc-bubble-wrap { align-items: flex-end; }

/* Bubble */
.mpc-bubble {
  background: var(--c-surface);
  border: 1px solid var(--c-border);
  border-radius: 10px 10px 10px 3px;
  padding: 7px 9px;
  font-size: 12.5px;
  line-height: 1.5;
  color: var(--c-text);
  word-break: break-word;
  transition: box-shadow .12s;
}
.mpc-bubble:hover { box-shadow: var(--shadow-sm); }
.mpc-msg.is-mine .mpc-bubble {
  background: var(--c-mine-bg);
  border-color: var(--c-mine-brd);
  border-radius: 10px 10px 3px 10px;
  color: #1e3a5f;
}

/* Meta (nom + rôle) */
.mpc-meta {
  display: flex; align-items: center; gap: 4px; margin-bottom: 2px; flex-wrap: wrap;
}
.mpc-author   { font-size: 11px; font-weight: 600; color: var(--c-sub); }
.mpc-role-badge {
  font-size: 9px; font-weight: 700; padding: 1px 5px; border-radius: 4px;
  text-transform: uppercase; letter-spacing: .4px;
}
.mpc-role-badge.role-dm { background: var(--role-dm-bg); color: var(--role-dm); }
.mpc-role-badge.role-cm { background: var(--role-cm-bg); color: var(--role-cm); }
.mpc-role-badge.role-as { background: var(--role-as-bg); color: var(--role-as); }
.mpc-role-badge.role-aj { background: var(--role-aj-bg); color: var(--role-aj); }

/* Type tag */
.mpc-type-tag {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 10px; font-weight: 600; padding: 2px 6px; border-radius: 5px;
  margin-bottom: 4px; text-transform: uppercase; letter-spacing: .3px;
}
.mpc-type-icon { width: 11px; height: 11px; }
.tag-instruction { background: #eff6ff; color: #2563eb; }
.tag-correction  { background: #fff7ed; color: #c2410c; }
.tag-validation  { background: #f0fdf4; color: #15803d; }
.tag-rejet       { background: #fef2f2; color: #dc2626; }
.tag-info        { background: #f0f9ff; color: #0369a1; }
.mpc-priority {
  font-size: 9px; padding: 1px 4px; border-radius: 3px; font-weight: 700;
}
.prio-urgent   { background: #fef3c7; color: #b45309; }
.prio-bloquant { background: #fce7f3; color: #be185d; }

/* Content */
.mpc-content { font-size: 12.5px; line-height: 1.5; white-space: pre-wrap; }

/* Footer */
.mpc-msg-footer {
  display: flex; align-items: center; gap: 4px;
  justify-content: flex-end; margin-top: 3px;
}
.mpc-time { font-size: 10px; color: var(--c-muted); font-family: 'JetBrains Mono', monospace; }
.mpc-pin-icon { color: #d97706; }
.mpc-read-tick { color: var(--c-muted); }
.mpc-read-tick.read { color: #3b82f6; }
.mpc-read-tick svg { width: 14px; height: 9px; }

/* Reply preview inside bubble */
.mpc-reply-preview {
  display: flex; gap: 6px; align-items: flex-start;
  background: rgba(0,0,0,.04); border-radius: 6px;
  padding: 4px 7px; margin-bottom: 4px;
}
.mpc-reply-bar {
  width: 2.5px; min-height: 100%; background: var(--c-accent); border-radius: 2px; flex-shrink: 0;
}
.mpc-reply-text { font-size: 11px; color: var(--c-sub); line-height: 1.4; }

/* Pinned variant */
.mpc-msg-pinned { margin-bottom: 4px; }
.mpc-msg-pinned .mpc-bubble {
  background: var(--c-pin-bg); border-color: var(--c-pin-brd); font-size: 11.5px;
}

/* Actions on hover */
.mpc-msg-actions {
  position: absolute; top: -14px;
  right: 34px;
  display: flex; gap: 2px;
  background: var(--c-bg); border: 1px solid var(--c-border);
  border-radius: 6px; padding: 2px;
  opacity: 0; transition: opacity .15s;
  box-shadow: var(--shadow-sm);
  z-index: 10;
}
.mpc-msg.is-mine .mpc-msg-actions { right: auto; left: 34px; }
.mpc-action-btn {
  display: flex; align-items: center; justify-content: center;
  width: 22px; height: 22px; background: transparent; border: none;
  border-radius: 4px; color: var(--c-sub); cursor: pointer;
}
.mpc-action-btn:hover { background: var(--c-border); color: var(--c-text); }
.mpc-action-btn svg { width: 12px; height: 12px; }

/* ══ Reply bar ════════════════════════════════════════════════════ */
.mpc-reply-bar-wrap {
  display: flex; align-items: center; justify-content: space-between;
  padding: 5px 10px; background: #eff6ff; border-top: 1px solid #bfdbfe;
  gap: 6px;
}
.mpc-reply-bar-content {
  display: flex; align-items: center; gap: 6px; overflow: hidden; flex: 1;
}
.mpc-reply-bar-content svg { color: var(--c-accent); flex-shrink: 0; }
.mpc-reply-name  { font-size: 11px; font-weight: 600; color: var(--c-accent); flex-shrink: 0; }
.mpc-reply-snippet { font-size: 11px; color: var(--c-sub); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

/* ══ Input ════════════════════════════════════════════════════════ */
.mpc-input-wrap {
  padding: 8px 10px;
  border-top: 1px solid var(--c-border);
  background: var(--c-surface);
  display: flex; flex-direction: column; gap: 5px;
}
.mpc-type-selector { display: flex; gap: 5px; }
.mpc-select {
  flex: 1; font-size: 11px; padding: 3px 6px;
  border: 1px solid var(--c-border); border-radius: 6px;
  background: var(--c-bg); color: var(--c-text);
  cursor: pointer; outline: none;
  font-family: 'DM Sans', sans-serif;
}
.mpc-select:focus { border-color: var(--c-accent); }
.mpc-select-sm { flex: 0 0 auto; max-width: 120px; }

.mpc-textarea-row { display: flex; gap: 6px; align-items: flex-end; }
.mpc-textarea {
  flex: 1; font-size: 12.5px; line-height: 1.5;
  padding: 7px 10px; border: 1px solid var(--c-border); border-radius: 8px;
  background: var(--c-bg); color: var(--c-text); resize: none; outline: none;
  font-family: 'DM Sans', sans-serif; max-height: 80px; overflow-y: auto;
  transition: border-color .12s;
}
.mpc-textarea:focus { border-color: var(--c-accent); }
.mpc-textarea::placeholder { color: var(--c-muted); }

.mpc-send-btn {
  width: 34px; height: 34px; border-radius: 8px; flex-shrink: 0;
  background: var(--c-accent); border: none; color: #fff;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all .15s;
}
.mpc-send-btn:hover:not(:disabled) { background: #2563eb; }
.mpc-send-btn:disabled { background: var(--c-border-md); cursor: not-allowed; }
.mpc-send-btn svg { width: 14px; height: 14px; }

.mpc-input-hint {
  font-size: 9.5px; color: var(--c-muted); text-align: right;
}

/* ══ Spinner ══════════════════════════════════════════════════════ */
.mpc-spinner {
  width: 18px; height: 18px; border-radius: 50%;
  border: 2px solid var(--c-border);
  border-top-color: var(--c-accent);
  animation: spin .6s linear infinite;
}
.mpc-spinner-sm {
  width: 14px; height: 14px; border-radius: 50%;
  border: 2px solid rgba(255,255,255,.3);
  border-top-color: #fff;
  animation: spin .6s linear infinite;
  display: inline-block;
}
.spin { animation: spin .6s linear infinite; }

@keyframes spin { to { transform: rotate(360deg); } }

/* ══ Transitions ══════════════════════════════════════════════════ */
.mpc-slide-enter-active,
.mpc-slide-leave-active {
  transition: all .18s cubic-bezier(.4,0,.2,1);
  transform-origin: bottom right;
}
.mpc-slide-enter-from,
.mpc-slide-leave-to {
  opacity: 0;
  transform: scale(.95) translateY(4px);
}
.mpc-fade-enter-active, .mpc-fade-leave-active { transition: opacity .15s; }
.mpc-fade-enter-from, .mpc-fade-leave-to { opacity: 0; }
</style>