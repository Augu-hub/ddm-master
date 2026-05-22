<template>
    <div class="ph-card"
        :class="[{
            'phc-pending':    ph.auditeurs_affectes?.length > 0 && ph.phase_status === 'pending',
            'phc-inprogress': ph.phase_status === 'in_progress',
            'phc-done':       ph.phase_status === 'completed',
            'phc-disabled':   ph.is_disabled,
            'phc-validated':  ph.validation_status === 'validated',
            'ph-card-child':  isChild,
            'ph-card-parent': isParent,
        }]">

        <!-- En-tête -->
        <div class="phc-head" :style="`border-left:3px solid ${ph._grpColor || mc}`">
            <div class="phc-head-l">
                <!-- Bouton expand/collapse si phase parente -->
                <button v-if="isParent" class="phc-expand-btn" :class="{expanded}" @click.stop="$emit('toggleExpand')"
                    :style="`color:${ph._grpColor || mc}`" :title="expanded ? 'Replier les sous-phases' : 'Voir les sous-phases'">
                    <i :class="expanded ? 'ti ti-chevron-down' : 'ti ti-chevron-right'"></i>
                    <span class="phc-expand-cnt">{{ childCount }}</span>
                </button>
                <i v-else-if="isChild" class="ti ti-corner-down-right phc-child-icon" :style="`color:${ph._grpColor || mc}80`"></i>

                <span class="phc-type-chip" :style="`color:${ph._grpColor || mc};background:${ph._grpColor || mc}12;border-color:${ph._grpColor || mc}25`">
                    {{ ph._grpLabel }}
                </span>
                <code class="phc-code">{{ ph.code_full || ph.code }}</code>
                <span v-if="ph.entity_name" class="phc-ent"><i class="ti ti-building"></i>{{ ph.entity_name }}</span>
                <span v-if="ph.validation_status && ph.validation_status !== 'draft'"
                    class="phc-valst" :class="`valst-${ph.validation_status}`">
                    <i :class="valStIcon(ph.validation_status)"></i>
                    {{ valStLbl(ph.validation_status) }}
                </span>
            </div>
            <div class="phc-head-r">
                <span class="phc-st" :class="`phst-${ph.phase_status}`">{{ phStLbl(ph.phase_status) }}</span>
                <span v-if="ph.progression > 0" class="phc-pct" :style="`color:${ph._grpColor || mc}`">{{ ph.progression }}%</span>

                <button v-if="canStart" class="phc-btn phb-start" title="Démarrer" @click.stop="$emit('start')">
                    <i class="ti ti-player-play-filled"></i>
                </button>
                <a v-if="ph.form_url && isAffected && ph.phase_status !== 'pending'"
                    :href="ph.form_url" class="phc-btn phb-form"
                    :class="{'phb-form-locked': ph.validation_status==='validated','phb-form-review': ph.validation_status==='in_review'}">
                    <i :class="formBtnIcon(ph)"></i>
                    <span class="phb-form-lbl">{{ formBtnLabel(ph) }}</span>
                </a>
                <button class="phc-btn phb-chat"
                    :class="{ 'phbn-unread': unreadCount > 0 }"
                    :title="`Chat (${msgCount} msg, ${unreadCount} non lu(s))`"
                    @click.stop="$emit('openChat')">
                    <i class="ti ti-message-circle"></i>
                    <span v-if="unreadCount > 0" class="phb-badge">{{ unreadCount }}</span>
                    <span v-else-if="msgCount > 0" class="phb-badge phb-badge-read">{{ msgCount }}</span>
                </button>
                <button v-if="canManage" class="phc-btn" :class="ph.is_disabled ? 'phb-enable' : 'phb-disable'"
                    :title="ph.is_disabled ? 'Activer' : 'Désactiver'"
                    @click.stop="$emit('toggleDisabled')">
                    <i :class="ph.is_disabled ? 'ti ti-player-play' : 'ti ti-player-pause'"></i>
                </button>
                <button class="phc-btn phb-note"
                    :class="{ 'phbn-on': markings.some(m => m.is_mine) }"
                    :disabled="ph.is_disabled" title="Ma note"
                    @click.stop="$emit('openNote')">
                    <i class="ti ti-notes"></i>
                </button>
                <button v-if="canValidate" class="phc-btn phb-validate" title="Valider" @click.stop="$emit('openValidate')">
                    <i class="ti ti-circle-check"></i>
                </button>
            </div>
        </div>

        <!-- Corps -->
        <div class="phc-body">
            <h3 class="phc-title" :class="{ 'phc-title-off': ph.is_disabled }">{{ ph.label }}</h3>
            <p v-if="ph.description" class="phc-desc">{{ ph.description }}</p>

            <div class="phc-dates-wrap">
                <div class="phc-dates">
                    <span class="phcd">
                        <i class="ti ti-calendar-event"></i>
                        <em>Prévu</em>
                        <strong>{{ ph.planned_start_fr || '—' }}</strong>
                        <i class="ti ti-arrow-right phcd-arr"></i>
                        <strong>{{ ph.planned_end_fr || '—' }}</strong>
                    </span>
                    <span v-if="ph.actual_start_fr" class="phcd phcd-real">
                        <i class="ti ti-calendar-check"></i>
                        <em>Réel</em>
                        <strong>{{ ph.actual_start_fr }}</strong>
                        <i class="ti ti-arrow-right phcd-arr"></i>
                        <strong>{{ ph.actual_end_fr || '—' }}</strong>
                    </span>
                    <span v-if="ph.planned_duration" class="phcd-dur">{{ ph.planned_duration }}j</span>
                </div>
                <div v-if="ph.progression > 0 || ph.phase_status === 'in_progress'" class="phc-bar">
                    <div class="phc-bar-fill"
                        :style="`width:${ph.progression ?? 0}%;background:${ph._grpColor || mc}`"
                        :class="{ 'phc-bar-anim': ph.phase_status === 'in_progress' }"></div>
                </div>
            </div>

            <!-- Auditeurs affectés -->
            <template v-if="ph.auditeurs_affectes?.length">
                <div class="phc-auds-row">
                    <span v-if="ph.phase_status === 'pending'" class="phc-todo-badge"><i class="ti ti-circle-dot"></i> À faire</span>
                    <span v-if="!isAffected && ph.phase_status !== 'pending'" class="phc-readonly-badge"><i class="ti ti-eye"></i> Lecture seule</span>
                    <div class="phc-auds">
                        <div v-for="a in ph.auditeurs_affectes" :key="a.auditeur_id"
                            class="phc-aud" :class="[`av-${a.role_code}`, { 'phc-aud-me': a.is_me }]"
                            :title="`${a.full_name} · ${a.role_code}`">
                            <span class="phca-av">{{ a.initiales || ini2(a.full_name) }}</span>
                            <span class="phca-name">{{ shortN(a.full_name) }}</span>
                            <span class="phca-role" :class="`rb-${a.role_code}`">{{ a.role_code }}</span>
                            <span v-if="a.is_me" class="phca-me-dot"></span>
                        </div>
                    </div>
                </div>
            </template>

            <!-- CTA Formulaire -->
            <div v-if="ph.form_url && isAffected && ph.phase_status !== 'pending'"
                class="phc-form-cta" :style="`border-color:${ph._grpColor || mc}30;background:${ph._grpColor || mc}06`">
                <div class="phcf-left">
                    <div class="phcf-icon" :style="`background:${ph._grpColor || mc}15;color:${ph._grpColor || mc}`">
                        <i :class="formBtnIcon(ph)"></i>
                    </div>
                    <div class="phcf-info">
                        <span class="phcf-label">{{ ph.form_label || 'Formulaire' }}</span>
                        <span class="phcf-status" :class="`phcfs-${ph.validation_status || 'draft'}`">{{ formStatusLabel(ph.validation_status) }}</span>
                    </div>
                </div>
                <a :href="ph.form_url" class="phcf-btn" :style="`background:${ph._grpColor || mc};color:#fff`">
                    <i :class="ph.validation_status === 'validated' ? 'ti ti-eye' : 'ti ti-edit'"></i>
                    {{ ph.validation_status === 'validated' ? 'Consulter' : 'Remplir' }}
                </a>
            </div>
        </div>

        <!-- Marquages -->
        <div v-if="markings.length" class="phc-mks">
            <div v-for="mk in markings" :key="mk.id"
                class="phc-mk" :class="[{ 'phc-mk-mine': mk.is_mine }, `phc-mk-${mk.status}`]">
                <div class="phcmk-av" :class="`av-${mk.author_role}`">{{ mk.author_initials }}</div>
                <div class="phcmk-body">
                    <div class="phcmk-meta">
                        <span class="phcmk-who" :class="`rb-${mk.author_role}`">{{ mk.author_name }}</span>
                        <span class="phcmk-r">{{ mk.author_role }}</span>
                        <span class="phcmk-st" :class="`mkst-${mk.status}`">{{ mkStLbl(mk.status) }}</span>
                        <span class="phcmk-date">{{ mk.created_at_fr }}</span>
                    </div>
                    <p class="phcmk-txt">{{ mk.content }}</p>
                </div>
            </div>
        </div>

        <!-- Aperçu chat -->
        <div v-if="chatPreviewMsgs.length" class="phc-chat-prev">
            <div class="phcp-hd">
                <i class="ti ti-message-circle" :style="`color:${ph._grpColor || mc}`"></i>
                <span>{{ msgCount }} message(s)</span>
                <span v-if="unreadCount > 0" class="phcp-unread-badge">{{ unreadCount }} nouveau(x)</span>
                <button class="phcp-all" @click.stop="$emit('openChat')">Voir tout</button>
            </div>
            <div v-for="msg in chatPreviewMsgs" :key="msg.id"
                class="phcp-msg" :class="[{ 'phcp-msg-unread': !msg.is_read && !msg.is_mine }]">
                <div class="phcpm-av" :class="`av-${msg.author_role}`">{{ msg.author_initials }}</div>
                <div class="phcpm-body">
                    <div class="phcpm-meta">
                        <span :class="`rb-${msg.author_role}`">{{ msg.author_role }}</span>
                        <span class="phcpm-who">{{ shortN(msg.author_name) }}</span>
                        <span class="phcpm-date">{{ msg.created_at_fr }}</span>
                    </div>
                    <p class="phcpm-txt">{{ msg.content }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps({
    ph:              { type: Object,  required: true },
    mc:              { type: String,  default: '#2563eb' },
    canManage:       { type: Boolean, default: false },
    auditorId:       { type: Number,  default: 0 },
    isParent:        { type: Boolean, default: false },
    isChild:         { type: Boolean, default: false },
    childCount:      { type: Number,  default: 0 },
    expanded:        { type: Boolean, default: true },
    unreadCount:     { type: Number,  default: 0 },
    msgCount:        { type: Number,  default: 0 },
    markings:        { type: Array as () => any[], default: () => [] },
    chatPreviewMsgs: { type: Array as () => any[], default: () => [] },
});

defineEmits(['toggleExpand','start','openChat','openNote','openValidate','toggleDisabled']);

const isAffected = computed(() =>
    props.ph.auditeurs_affectes?.some((a: any) => a.auditeur_id === props.auditorId)
    || props.canManage
);
const canStart = computed(() =>
    props.ph.phase_status === 'pending' && !props.ph.is_disabled && isAffected.value
);
const canValidate = computed(() =>
    props.ph.author_role === 'DM' && props.ph.validation_status === 'submitted'
);

function formBtnIcon(ph: any) {
    if (ph.validation_status === 'validated') return 'ti ti-lock';
    if (ph.validation_status === 'in_review') return 'ti ti-clock';
    return ph.form_icon || 'ti ti-file-description';
}
function formBtnLabel(ph: any) {
    if (ph.validation_status === 'validated') return 'Validé';
    if (ph.validation_status === 'in_review') return 'En révision';
    return ph.form_label || 'Formulaire';
}
function formStatusLabel(status: string) {
    return ({ draft:'À remplir', in_review:'En révision', validated:'Validé ✓', rejected:'Rejeté' } as any)[status] ?? 'À remplir';
}
function valStIcon(s: string) { return ({submitted:'ti ti-clock',validated:'ti ti-circle-check',rejected:'ti ti-circle-x'} as any)[s] ?? 'ti ti-circle'; }
function valStLbl(s: string)  { return ({submitted:'En attente',validated:'Validé ✓',rejected:'Rejeté'} as any)[s] ?? s; }
function phStLbl(s: string)   { return ({pending:'À faire',in_progress:'En cours',completed:'Terminé',skipped:'Ignorée'} as any)[s] ?? s; }
function mkStLbl(s: string)   { return ({draft:'Brouillon',submitted:'Soumis',validated:'Validé',rejected:'Rejeté'} as any)[s] ?? s; }
function ini2(full: string)   { return (full ?? '').trim().split(/\s+/).map((p:string) => p[0]).join('').toUpperCase().slice(0,2) || '?'; }
function shortN(full: string) {
    const p = (full ?? '').trim().split(/\s+/).filter(Boolean);
    return !p.length ? '—' : p.length === 1 ? p[0] : `${p[0]} ${p[1][0]}.`;
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap');
*,*::before,*::after { box-sizing:border-box; margin:0; padding:0; }

/* ═══ CARD ═══ */
.ph-card {
    width:100%;
    border-radius:12px; border:1px solid #e2e8f0;
    background:#ffffff; box-shadow:0 1px 4px rgba(0,0,0,.04);
    overflow:hidden; transition:box-shadow .15s, border-color .15s;
    font-family:'Plus Jakarta Sans',sans-serif;
}
.ph-card:hover { box-shadow:0 4px 16px rgba(0,0,0,.08); border-color:#cbd5e1; }
.ph-card-parent { border-radius:12px; }
.ph-card-child  { border-radius:10px; border-left-width:2px !important; }

.phc-pending    { border-color:#c7d2fe !important; background:#fafbff !important; }
.phc-inprogress { border-color:#fcd34d !important; background:#fffef5 !important; }
.phc-done       { border-color:#bbf7d0 !important; background:#f0fdf4 !important; }
.phc-disabled   { opacity:.45; pointer-events:none; }
.phc-validated  { border-color:#bbf7d0 !important; }

/* En-tête */
.phc-head {
    display:flex; align-items:center; justify-content:space-between;
    gap:10px; padding:12px 16px 10px;
    border-bottom:1px solid #f8fafc; background:#fafbfc;
}
.phc-head-l { display:flex; align-items:center; gap:8px; flex-wrap:wrap; min-width:0; }
.phc-head-r { display:flex; align-items:center; gap:6px; flex-shrink:0; flex-wrap:wrap; }

/* Expand btn */
.phc-expand-btn {
    display:inline-flex; align-items:center; gap:5px;
    padding:3px 8px; border-radius:7px; border:1px solid currentColor;
    background:transparent; cursor:pointer; font-size:.66rem; font-weight:700;
    transition:all .15s; opacity:.6; font-family:'Plus Jakarta Sans',sans-serif;
}
.phc-expand-btn:hover { opacity:1; }
.phc-expand-btn.expanded { opacity:.85; }
.phc-expand-cnt {
    background:currentColor; color:#fff; border-radius:8px;
    padding:0 5px; font-size:.58rem;
    /* trick : text hérite currentColor mais bg aussi → on inverse avec mix-blend */
    filter:invert(1) brightness(2);
}
.phc-child-icon { font-size:.8rem; }

.phc-type-chip {
    font-size:.6rem; font-weight:700; padding:2px 9px;
    border-radius:20px; border:1px solid; letter-spacing:.03em; white-space:nowrap;
}
.phc-code { font-family:'JetBrains Mono',monospace; font-size:.65rem; font-weight:700; color:#475569; letter-spacing:.02em; }
.phc-ent  { display:inline-flex; align-items:center; gap:3px; font-size:.64rem; color:#94a3b8; }
.phc-valst {
    display:inline-flex; align-items:center; gap:4px;
    font-size:.6rem; font-weight:700; padding:2px 8px;
    border-radius:8px; text-transform:uppercase; letter-spacing:.05em;
}
.valst-submitted { background:#fef9c3; color:#a16207; }
.valst-validated { background:#dcfce7; color:#16a34a; }
.valst-rejected  { background:#fee2e2; color:#dc2626; }
.valst-in_review { background:#ede9fe; color:#7c3aed; }

.phc-st {
    font-size:.6rem; font-weight:700; padding:3px 9px;
    border-radius:8px; text-transform:uppercase; letter-spacing:.05em; white-space:nowrap;
}
.phst-pending     { background:#f1f5f9; color:#64748b; }
.phst-in_progress { background:#fef9c3; color:#a16207; }
.phst-completed   { background:#dcfce7; color:#16a34a; }
.phst-skipped     { background:#f1f5f9; color:#94a3b8; }
.phc-pct { font-size:.68rem; font-weight:700; }

/* Boutons actions */
.phc-btn {
    height:28px; padding:0 10px; border-radius:7px;
    display:inline-flex; align-items:center; justify-content:center; gap:5px;
    font-size:.7rem; cursor:pointer; border:none; transition:all .12s;
    flex-shrink:0; text-decoration:none;
    font-family:'Plus Jakarta Sans',sans-serif; font-weight:600; white-space:nowrap;
}
.phc-btn:disabled { opacity:.3; cursor:not-allowed; }
.phb-start    { background:#dcfce7; color:#16a34a; border:1px solid #bbf7d0; }
.phb-start:hover { background:#16a34a; color:#fff; }
.phb-form     { background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe; }
.phb-form:hover { background:#2563eb; color:#fff; }
.phb-form-locked { background:#dcfce7 !important; color:#16a34a !important; border-color:#bbf7d0 !important; }
.phb-form-review { background:#ede9fe !important; color:#7c3aed !important; border-color:#ddd6fe !important; }
.phb-form-lbl { font-size:.66rem; }
.phb-chat { background:#faf5ff; color:#7c3aed; border:1px solid #e9d5ff; position:relative; width:28px; padding:0; }
.phb-chat:hover { background:#7c3aed; color:#fff; }
.phb-badge {
    position:absolute; top:-5px; right:-5px;
    width:15px; height:15px; background:#dc2626;
    border-radius:50%; font-size:.42rem; font-weight:700;
    color:#fff; display:flex; align-items:center; justify-content:center;
    border:1.5px solid #fff;
}
.phb-badge-read { background:#94a3b8 !important; }
.phbn-unread { border-color:#e9d5ff !important; }
.phb-disable  { background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; width:28px; padding:0; }
.phb-enable   { background:#fef9c3; color:#a16207; border:1px solid #fde68a; width:28px; padding:0; }
.phb-note     { background:#f8fafc; color:#94a3b8; border:1px solid #e2e8f0; width:28px; padding:0; }
.phbn-on      { background:#fef9c3 !important; color:#a16207 !important; border-color:#fde68a !important; }
.phb-validate { background:#dcfce7; color:#16a34a; border:1px solid #bbf7d0; width:28px; padding:0; }
.phb-validate:hover { background:#16a34a; color:#fff; }

/* Corps */
.phc-body { padding:14px 16px 16px; display:flex; flex-direction:column; gap:12px; }
.phc-title     { font-size:.95rem; font-weight:700; color:#1e293b; line-height:1.4; letter-spacing:-.01em; }
.phc-title-off { color:#94a3b8; text-decoration:line-through; }
.phc-desc      { font-size:.72rem; color:#64748b; line-height:1.5; }

.phc-dates-wrap { display:flex; flex-direction:column; gap:8px; }
.phc-dates { display:flex; flex-wrap:wrap; gap:14px; }
.phcd {
    display:inline-flex; align-items:center; gap:5px;
    font-size:.72rem; color:#475569; font-family:'JetBrains Mono',monospace;
}
.phcd em { font-style:normal; font-size:.6rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; font-family:'Plus Jakarta Sans',sans-serif; color:#94a3b8; }
.phcd strong { color:#334155; font-weight:500; }
.phcd-arr   { font-size:.65rem; color:#cbd5e1; }
.phcd-real strong { color:#16a34a; }
.phcd-dur   { font-size:.66rem; font-weight:700; background:#f1f5f9; color:#64748b; padding:2px 8px; border-radius:8px; align-self:center; }
.phc-bar    { height:4px; background:#f1f5f9; border-radius:4px; overflow:hidden; }
.phc-bar-fill { height:100%; border-radius:4px; transition:width .5s ease; }
@keyframes bar-pulse { 0%,100%{opacity:1} 50%{opacity:.6} }
.phc-bar-anim { animation:bar-pulse 2s ease infinite; }

/* Auditeurs */
.phc-auds-row {
    display:flex; align-items:center; gap:10px; flex-wrap:wrap;
    padding:10px 12px; border-radius:9px; background:#f8fafc; border:1px solid #f1f5f9;
}
.phc-todo-badge {
    display:inline-flex; align-items:center; gap:5px;
    font-size:.64rem; font-weight:700; color:#334155;
    background:#f1f5f9; border:1px solid #e2e8f0;
    padding:4px 11px; border-radius:20px;
    letter-spacing:.03em; text-transform:uppercase; flex-shrink:0;
}
.phc-readonly-badge {
    display:inline-flex; align-items:center; gap:4px;
    font-size:.6rem; font-weight:600; padding:2px 8px; border-radius:8px;
    background:#f8fafc; color:#94a3b8; border:1px solid #e2e8f0;
}
.phc-auds { display:flex; flex-wrap:wrap; gap:6px; }
.phc-aud  {
    display:inline-flex; align-items:center; gap:5px;
    padding:4px 10px 4px 4px; border-radius:20px;
    border:1px solid #e2e8f0; font-size:.68rem;
    cursor:default; position:relative; background:#fff;
}
.phc-aud-me { border-color:#bae6fd !important; background:#f0f9ff !important; }
.phca-av   { width:22px; height:22px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.52rem; font-weight:700; }
.phca-name { font-weight:600; color:#334155; }
.phca-role { font-size:.56rem; font-weight:600; opacity:.8; padding:1px 4px; border-radius:4px; }
.phca-me-dot { position:absolute; top:1px; right:1px; width:6px; height:6px; background:#0891b2; border-radius:50%; border:1px solid #fff; }

/* Rôles avatars */
.av-DM { background:#fef9c3; color:#a16207; }
.av-CM { background:#eff6ff; color:#2563eb; }
.av-AS { background:#dcfce7; color:#16a34a; }
.av-AJ { background:#faf5ff; color:#7c3aed; }
.rb-DM { background:#fef9c3; color:#a16207; border:1px solid #fde68a; }
.rb-CM { background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe; }
.rb-AS { background:#dcfce7; color:#16a34a; border:1px solid #bbf7d0; }
.rb-AJ { background:#faf5ff; color:#7c3aed; border:1px solid #ddd6fe; }

/* CTA Formulaire */
.phc-form-cta {
    display:flex; align-items:center; justify-content:space-between; gap:12px;
    padding:12px 14px; border-radius:10px; border:1px solid;
    transition:box-shadow .15s;
}
.phc-form-cta:hover { box-shadow:0 2px 10px rgba(0,0,0,.06); }
.phcf-left  { display:flex; align-items:center; gap:11px; min-width:0; }
.phcf-icon  { width:36px; height:36px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0; }
.phcf-info  { display:flex; flex-direction:column; gap:3px; }
.phcf-label { font-size:.78rem; font-weight:700; color:#1e293b; }
.phcf-status { font-size:.65rem; font-weight:600; }
.phcfs-draft     { color:#94a3b8; }
.phcfs-in_review { color:#7c3aed; }
.phcfs-validated { color:#16a34a; }
.phcfs-rejected  { color:#dc2626; }
.phcf-btn {
    display:inline-flex; align-items:center; gap:6px;
    padding:8px 16px; border-radius:8px; font-size:.76rem; font-weight:700;
    text-decoration:none; flex-shrink:0; transition:opacity .12s, transform .1s;
    box-shadow:0 2px 8px rgba(0,0,0,.12); font-family:'Plus Jakarta Sans',sans-serif;
}
.phcf-btn:hover { opacity:.9; transform:translateY(-1px); }

/* Marquages */
.phc-mks { display:flex; flex-direction:column; gap:4px; padding:10px 16px; border-top:1px solid #f1f5f9; background:#fafbfc; }
.phc-mk { display:flex; gap:10px; padding:8px 10px; border-radius:8px; background:#fff; border:1px solid #f1f5f9; }
.phc-mk-mine { background:#eff6ff !important; border-color:#bfdbfe !important; }
.phcmk-av   { width:24px; height:24px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.52rem; font-weight:700; flex-shrink:0; }
.phcmk-body { flex:1; min-width:0; }
.phcmk-meta { display:flex; align-items:center; gap:6px; flex-wrap:wrap; margin-bottom:4px; }
.phcmk-who  { font-size:.7rem; font-weight:600; }
.phcmk-r    { font-size:.58rem; color:#64748b; }
.phcmk-date { font-size:.58rem; color:#94a3b8; margin-left:auto; }
.phcmk-txt  { font-size:.73rem; color:#475569; line-height:1.5; }
.phcmk-st   { font-size:.58rem; font-weight:600; padding:1px 6px; border-radius:6px; }
.mkst-draft     { background:#f1f5f9; color:#64748b; }
.mkst-submitted { background:#eff6ff; color:#2563eb; }
.mkst-validated { background:#dcfce7; color:#16a34a; }
.mkst-rejected  { background:#fee2e2; color:#dc2626; }

/* Aperçu chat */
.phc-chat-prev { border-top:1px solid #f1f5f9; padding:10px 16px 12px; background:#fdfaff; display:flex; flex-direction:column; gap:6px; }
.phcp-hd  { display:flex; align-items:center; gap:7px; font-size:.64rem; color:#94a3b8; margin-bottom:2px; }
.phcp-unread-badge { background:#dc2626; color:#fff; font-size:.56rem; font-weight:700; padding:1px 7px; border-radius:10px; }
.phcp-all { margin-left:auto; font-size:.6rem; color:#7c3aed; background:none; border:none; cursor:pointer; font-family:'Plus Jakarta Sans',sans-serif; font-weight:600; }
.phcp-all:hover { text-decoration:underline; }
.phcp-msg { display:flex; gap:8px; padding:6px 8px; border-radius:7px; background:#fff; border:1px solid #f1f5f9; }
.phcp-msg-unread { border-color:#ddd6fe !important; background:#faf5ff !important; }
.phcpm-av  { width:20px; height:20px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.46rem; font-weight:700; flex-shrink:0; }
.phcpm-body { flex:1; min-width:0; }
.phcpm-meta { display:flex; align-items:center; gap:5px; margin-bottom:3px; flex-wrap:wrap; }
.phcpm-meta span { font-size:.58rem; }
.phcpm-who  { font-weight:600; color:#334155; }
.phcpm-date { color:#94a3b8; margin-left:auto; }
.phcpm-txt  { font-size:.71rem; color:#64748b; line-height:1.4; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
</style>