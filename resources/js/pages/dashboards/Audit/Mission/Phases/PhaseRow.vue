<template>
  <tr class="phase-row"
    :class="[`level-${phase.level}`, `type-${(phase.phase_type||'').toLowerCase()}`, { 'is-expanded': isExpanded }]">

    <!-- Toggle expand -->
    <td class="col-toggle">
      <button v-if="hasChildren" @click="$emit('toggle-expand', phase.id)" class="toggle-btn" :title="isExpanded?'Replier':'Déplier'">
        <span>{{ isExpanded ? '▼' : '▶' }}</span>
      </button>
      <span v-else class="no-toggle"></span>
    </td>

    <!-- Code -->
    <td class="col-code">
      <div class="code-block" :style="{ marginLeft: `${(phase.level-1)*18}px` }">
        <span class="code-badge" :class="'badge-'+(phase.phase_type||'').toLowerCase()">{{ phase.code_full }}</span>
        <span v-if="phase.is_decomposable" title="Décomposable">⚙️</span>
      </div>
    </td>

    <!-- Libellé -->
    <td class="col-label">
      <div :style="{ paddingLeft: `${(phase.level-1)*18}px` }">
        <span class="label-text" :class="{'is-child': phase.level>1}">{{ phase.label }}</span>
      </div>
    </td>

    <!-- Description tronquée -->
    <td class="col-desc">
      <span v-if="phase.description" class="desc-text" @click="$emit('view-details', phase)" title="Cliquer pour détails complets">
        {{ truncate(phase.description, 90) }}
        <span v-if="phase.description.length>90" class="desc-more">voir +</span>
      </span>
      <span v-else class="desc-empty">—</span>
    </td>

    <!-- Type -->
    <td class="col-type">
      <span class="type-pill" :class="'pill-'+(phase.phase_type||'').toLowerCase()">{{ typeLabel(phase.phase_type) }}</span>
    </td>

    <!-- Poids -->
    <td class="col-weight">
      <span v-for="n in 5" :key="n" :class="n<=phase.weight?'dot-on':'dot-off'">●</span>
    </td>

    <!-- Actions -->
    <td class="col-actions">
      <div class="actions-group">
        <button @click="$emit('view-details', phase)" class="action-btn" title="Détails">👁</button>
        <button @click="$emit('edit', phase)"         class="action-btn action-edit" title="Modifier">✏️</button>
        <button v-if="phase.is_decomposable || phase.level<3"
                @click="$emit('create-child', phase)" class="action-btn action-add" title="Ajouter sous-phase">➕</button>
        <!-- ✅ Passe l'objet phase complet (pas juste l'ID) -->
        <button @click="$emit('delete', phase)"       class="action-btn action-del" title="Supprimer">🗑</button>
      </div>
    </td>
  </tr>

  <!-- Récursion sous-phases -->
  <template v-if="hasChildren && isExpanded">
    <PhaseRow
      v-for="child in phase.children" :key="child.id"
      :phase="child" :expanded-ids="expandedIds" :mission-type-id="missionTypeId"
      @toggle-expand="$emit('toggle-expand', $event)"
      @edit="$emit('edit', $event)"
      @delete="$emit('delete', $event)"
      @create-child="$emit('create-child', $event)"
      @view-details="$emit('view-details', $event)"
    />
  </template>
</template>

<script>
export default {
  name: 'PhaseRow',
  props: {
    phase:         { type: Object,  required: true },
    expandedIds:   { type: Set,     required: true },
    missionTypeId: { type: Number,  required: true },
  },
  emits: ['toggle-expand','edit','delete','create-child','view-details'],
  computed: {
    hasChildren() { return this.phase.children?.length > 0; },
    isExpanded()  { return this.expandedIds.has(this.phase.id); },
  },
  methods: {
    truncate(str, len) { return str?.length > len ? str.substring(0,len)+'…' : str||''; },
    typeLabel(type) {
      return { PREPARATION:'PRÉP.', VERIFICATION:'VÉRIF.', CONCLUSION:'CONC.', SUIVI:'SUIVI' }[type] || type || '—';
    },
  },
};
</script>

<style scoped>
.phase-row { transition:background .12s; }
.phase-row:hover { background:#F0F7FF !important; }
.phase-row.level-1 { background:#FDFEFE; font-weight:600; }
.phase-row.level-2 { background:#FAFBFC; font-weight:400; }
.phase-row.type-preparation.level-1 { border-left:3px solid #2E86AB; }
.phase-row.type-verification.level-1 { border-left:3px solid #1E8449; }
.phase-row.type-conclusion.level-1  { border-left:3px solid #D68910; }
.phase-row.type-suivi.level-1       { border-left:3px solid #7D3C98; }
td { padding:9px 14px; vertical-align:middle; border-bottom:1px solid #F0F0F0; }
.col-toggle { width:36px; text-align:center; padding:0 4px; }
.toggle-btn { background:none; border:1px solid #DEE2E6; border-radius:4px; width:24px; height:24px; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:.65rem; transition:all .15s; }
.toggle-btn:hover { background:#E8F4FD; border-color:#2E86AB; }
.no-toggle { display:inline-block; width:24px; }
.is-expanded .toggle-btn { background:#E8F4FD; }
.col-code { white-space:nowrap; }
.code-block { display:flex; align-items:center; gap:6px; }
.code-badge { font-family:monospace; font-size:.78rem; font-weight:700; padding:2px 8px; border-radius:4px; white-space:nowrap; }
.badge-preparation { background:#D6EAF8; color:#1A5276; }
.badge-verification { background:#D5F5E3; color:#1E8449; }
.badge-conclusion  { background:#FEF9E7; color:#9A7D0A; }
.badge-suivi       { background:#EDE7F6; color:#6C3483; }
.col-label { max-width:280px; }
.label-text { display:block; font-size:.9rem; color:#2C3E50; line-height:1.35; }
.label-text.is-child { font-size:.86rem; color:#495057; font-weight:400; }
.col-desc { max-width:320px; }
.desc-text { font-size:.82rem; color:#6C757D; line-height:1.4; cursor:pointer; }
.desc-text:hover { color:#2E86AB; }
.desc-more { color:#2E86AB; font-size:.75rem; font-weight:600; margin-left:4px; text-decoration:underline; }
.desc-empty { color:#BDC3C7; font-size:.82rem; }
.col-type { white-space:nowrap; }
.type-pill { display:inline-block; padding:2px 8px; border-radius:20px; font-size:.71rem; font-weight:700; letter-spacing:.04em; }
.pill-preparation { background:#2E86AB; color:#fff; }
.pill-verification { background:#1E8449; color:#fff; }
.pill-conclusion  { background:#D68910; color:#fff; }
.pill-suivi       { background:#7D3C98; color:#fff; }
.col-weight { white-space:nowrap; }
.dot-on { color:#F4D03F; font-size:.7rem; } .dot-off { color:#E5E7E9; font-size:.7rem; }
.col-actions { white-space:nowrap; }
.actions-group { display:flex; gap:3px; align-items:center; }
.action-btn { background:none; border:1px solid transparent; border-radius:4px; width:28px; height:28px; display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:.85rem; transition:all .15s; opacity:.55; }
.phase-row:hover .action-btn { opacity:1; }
.action-btn:hover     { border-color:#DEE2E6; background:#F8F9FA; }
.action-del:hover     { background:#FDEDEC; border-color:#F1948A; }
.action-add:hover     { background:#EAFAF1; border-color:#82E0AA; }
.action-edit:hover    { background:#EBF5FB; border-color:#AED6F1; }
</style>