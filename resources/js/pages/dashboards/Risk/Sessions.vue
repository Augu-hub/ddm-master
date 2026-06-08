<template>
  <VerticalLayout>
    <Head title="RISK — Administrateurs risque" />

    <!-- ══════════ HEADER ══════════ -->
    <div class="d-flex align-items-center justify-content-between mb-3">
      <div class="d-flex align-items-center gap-3">
        <div class="rs-icon">
          <i class="ti ti-shield-star"></i>
        </div>
        <div>
          <h4 class="mb-0 fw-bold">Administrateurs risque par entité</h4>
          <small class="text-muted">
            Désignez un responsable risque parmi les utilisateurs affectés aux fonctions de chaque entité
          </small>
        </div>
      </div>
    </div>

    <!-- ══════════ KPI ══════════ -->
    <div class="rs-kpi-row mb-3">
      <div class="rs-kpi rs-kpi--a">
        <i class="ti ti-building rs-kpi-icon"></i>
        <div>
          <div class="rs-kpi-val">{{ props.stats.entities ?? 0 }}</div>
          <div class="rs-kpi-lbl">Entités</div>
        </div>
      </div>
      <div class="rs-kpi rs-kpi--b">
        <i class="ti ti-link rs-kpi-icon"></i>
        <div>
          <div class="rs-kpi-val">{{ props.stats.assignments ?? 0 }}</div>
          <div class="rs-kpi-lbl">Liaisons</div>
        </div>
      </div>
      <div class="rs-kpi rs-kpi--c">
        <i class="ti ti-user-check rs-kpi-icon"></i>
        <div>
          <div class="rs-kpi-val">{{ props.stats.with_user ?? 0 }}</div>
          <div class="rs-kpi-lbl">Avec utilisateur</div>
        </div>
      </div>
      <div class="rs-kpi rs-kpi--d">
        <i class="ti ti-shield-star rs-kpi-icon"></i>
        <div>
          <div class="rs-kpi-val">{{ props.stats.admins ?? 0 }}</div>
          <div class="rs-kpi-lbl">Admins désignés</div>
        </div>
      </div>
    </div>

    <!-- ══════════ RECHERCHE ══════════ -->
    <div class="d-flex align-items-center gap-2 mb-3">
      <b-form-input
        v-model="search"
        placeholder="Rechercher une entité…"
        size="sm"
        style="max-width:280px"
      />
      <span class="text-muted small">{{ filteredEntities.length }} entité(s)</span>
    </div>

    <!-- ══════════ ENTITÉS ══════════ -->
    <div class="rs-entities">
      <div
        v-for="entity in filteredEntities"
        :key="entity.id"
        class="rs-entity-block"
      >
        <!-- En-tête entité -->
        <div
          :class="['rs-entity-hd', entityAdmin(entity.id) ? 'rs-entity-hd--ok' : 'rs-entity-hd--pending']"
          @click="toggleExpand(entity.id)"
        >
          <!-- Niveau indentation -->
          <div :style="'width:' + (entity.level * 20) + 'px; flex-shrink:0'"></div>

          <!-- Icône entité -->
          <div :class="['rs-entity-av', entity.level === 0 ? 'rs-entity-av--root' : 'rs-entity-av--child']">
            {{ (entity.code_base || entity.name).charAt(0) }}
          </div>

          <!-- Infos -->
          <div class="flex-grow-1 overflow-hidden">
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <span class="rs-entity-name">{{ entity.name }}</span>
              <span v-if="entity.code_base" class="rs-code">{{ entity.code_base }}</span>
              <span v-if="entity.level === 0" class="rs-badge rs-badge--root">Racine</span>
            </div>
            <div v-if="entity.parent_name" class="rs-entity-sub">↳ {{ entity.parent_name }}</div>
          </div>

          <!-- Admin actuel badge -->
          <div class="rs-admin-col">
            <div v-if="entityAdmin(entity.id)" class="rs-admin-chip rs-admin-chip--set">
              <i class="ti ti-shield-star me-1"></i>
              <span>{{ entityAdmin(entity.id).user_name }}</span>
              <span class="rs-admin-fn ms-1">· {{ entityAdmin(entity.id).function_code || entityAdmin(entity.id).function_name }}</span>
            </div>
            <div v-else class="rs-admin-chip rs-admin-chip--none">
              <i class="ti ti-shield-off me-1"></i>
              <span>Aucun admin risque</span>
            </div>
          </div>

          <!-- Compteur fonctions + toggle -->
          <div class="d-flex align-items-center gap-2 flex-shrink-0">
            <span :class="['rs-cnt', entityAssignments(entity.id).length ? 'rs-cnt--has' : 'rs-cnt--none']">
              {{ entityAssignments(entity.id).length }} fonction(s)
            </span>
            <i :class="['ti', expanded.includes(entity.id) ? 'ti-chevron-up' : 'ti-chevron-down', 'rs-toggle-icon']"></i>
          </div>
        </div>

        <!-- Fonctions de l'entité (dépliable) -->
        <div v-if="expanded.includes(entity.id)" class="rs-fns">

          <!-- Aucune liaison -->
          <div v-if="entityAssignments(entity.id).length === 0" class="rs-fn-empty">
            <i class="ti ti-link-off me-2"></i>
            Aucune fonction liée à cette entité
          </div>

          <!-- Lignes fonctions -->
          <div
            v-for="a in entityAssignments(entity.id)"
            :key="a.id"
            :class="['rs-fn-row', { 'rs-fn-row--admin': a.is_risk_admin }]"
          >
            <!-- Bande admin gauche -->
            <div :class="['rs-fn-strip', a.is_risk_admin ? 'rs-fn-strip--on' : '']"></div>

            <!-- Avatar fonction -->
            <div :class="['rs-fn-av', a.is_risk_admin ? 'rs-fn-av--admin' : '']">
              {{ a.function_code || a.function_name.charAt(0) }}
            </div>

            <!-- Infos fonction -->
            <div class="flex-grow-1 overflow-hidden">
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="rs-fn-name">{{ a.function_name }}</span>
                <span v-if="a.function_code" class="rs-fncode">{{ a.function_code }}</span>
                <span v-if="a.is_risk_admin" class="rs-badge rs-badge--admin">
                  <i class="ti ti-shield-star me-1"></i>Admin risque
                </span>
              </div>

              <!-- Utilisateur affecté -->
              <div v-if="a.user_id" class="rs-user-line mt-1">
                <div class="rs-user-av">
                  {{ a.user_name.charAt(0) }}
                </div>
                <div>
                  <div class="rs-user-name">{{ a.user_name }}</div>
                  <div class="rs-user-email">{{ a.user_email }}</div>
                  <div v-if="a.user_matricule" class="rs-user-mat">{{ a.user_matricule }}</div>
                </div>

                <!-- Bouton désigner / retirer admin -->
                <button
                  :class="['rs-admin-btn', a.is_risk_admin ? 'rs-admin-btn--on' : 'rs-admin-btn--off']"
                  @click.stop="toggleAdmin(a)"
                >
                  <i :class="['ti', a.is_risk_admin ? 'ti-shield-star' : 'ti-shield-plus']"></i>
                  {{ a.is_risk_admin ? 'Admin risque ✓' : 'Désigner admin risque' }}
                </button>
              </div>

              <!-- Pas d'utilisateur -->
              <div v-else class="rs-no-user">
                <i class="ti ti-user-off me-1"></i>
                Aucun utilisateur affecté à cette fonction
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- Aucune entité -->
      <div v-if="filteredEntities.length === 0" class="rs-empty">
        <i class="ti ti-building-off rs-empty-icon"></i>
        <p class="text-muted mt-3">Aucune entité trouvée</p>
      </div>
    </div>

  </VerticalLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import VerticalLayout from '@/layoutsparam/VerticalLayout.vue'

const props = defineProps({
  entities:    { type: Array,  default: () => [] },
  assignments: { type: Array,  default: () => [] },
  stats:       { type: Object, default: () => ({}) },
})

// ── ÉTAT ──────────────────────────────────────────────────────────────────────
const search   = ref('')
const expanded = ref([])   // IDs entités dépliées

// ── COMPUTED ──────────────────────────────────────────────────────────────────
const filteredEntities = computed(() => {
  const q = search.value.toLowerCase()
  if (!q) return props.entities
  return props.entities.filter(e =>
    (e.name      || '').toLowerCase().includes(q) ||
    (e.code_base || '').toLowerCase().includes(q)
  )
})

// Liaisons d'une entité
function entityAssignments(entityId) {
  return (props.assignments || []).filter(a => a.entity_id === entityId)
}

// Admin risque actuel d'une entité
function entityAdmin(entityId) {
  return (props.assignments || []).find(a => a.entity_id === entityId && a.is_risk_admin && a.user_id) || null
}

// ── ACTIONS ───────────────────────────────────────────────────────────────────
function toggleExpand(id) {
  const i = expanded.value.indexOf(id)
  if (i === -1) expanded.value.push(id)
  else          expanded.value.splice(i, 1)
}

function toggleAdmin(assignment) {
  if (!assignment.user_id) return

  if (assignment.is_risk_admin) {
    if (!confirm('Retirer « ' + assignment.user_name + ' » comme administrateur risque ?')) return
  } else {
    const current = entityAdmin(assignment.entity_id)
    const msg = current
      ? 'Remplacer « ' + current.user_name + ' » par « ' + assignment.user_name + ' » comme administrateur risque ?'
      : 'Désigner « ' + assignment.user_name + ' » comme administrateur risque de cette entité ?'
    if (!confirm(msg)) return
  }

  router.patch(
    `/m/risk.core/sessions/assignments/${assignment.id}/set-admin`,
    {},
    { preserveScroll: true }
  )
}
</script>

<style scoped>
/* ── HEADER ICON ───────────────────────────── */
.rs-icon {
  width: 44px; height: 44px; flex-shrink: 0;
  background: linear-gradient(135deg, #1e293b, #312e81);
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  color: #a5b4fc; font-size: 22px;
}

/* ── KPI ───────────────────────────────────── */
.rs-kpi-row { display: flex; gap: 10px; flex-wrap: wrap; }
.rs-kpi {
  flex: 1; min-width: 120px;
  background: #fff; border: 1px solid #e8ecf0;
  border-left: 4px solid; border-radius: 8px;
  padding: 10px 14px;
  display: flex; align-items: center; gap: 12px;
  box-shadow: 0 1px 3px rgba(0,0,0,.04);
}
.rs-kpi--a { border-left-color: #6366f1; } .rs-kpi--a .rs-kpi-icon { color: #6366f1; }
.rs-kpi--b { border-left-color: #22c55e; } .rs-kpi--b .rs-kpi-icon { color: #22c55e; }
.rs-kpi--c { border-left-color: #0ea5e9; } .rs-kpi--c .rs-kpi-icon { color: #0ea5e9; }
.rs-kpi--d { border-left-color: #f59e0b; } .rs-kpi--d .rs-kpi-icon { color: #f59e0b; }
.rs-kpi-icon { font-size: 20px; }
.rs-kpi-val  { font-size: 1.3rem; font-weight: 800; color: #0f172a; line-height: 1; }
.rs-kpi-lbl  { font-size: .62rem; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; }

/* ── ENTITÉS LISTE ─────────────────────────── */
.rs-entities { display: flex; flex-direction: column; gap: 10px; }

.rs-entity-block {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  overflow: hidden;
}

/* En-tête entité */
.rs-entity-hd {
  display: flex; align-items: center; gap: 12px;
  padding: 12px 16px; cursor: pointer;
  transition: background .1s;
}
.rs-entity-hd:hover          { background: #f8fafc; }
.rs-entity-hd--ok            { border-left: 4px solid #22c55e; }
.rs-entity-hd--pending       { border-left: 4px solid #fbbf24; }

.rs-entity-av {
  width: 40px; height: 40px; border-radius: 8px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-weight: 800; font-size: .85rem;
}
.rs-entity-av--root  { background: linear-gradient(135deg,#6366f1,#818cf8); color:#fff; }
.rs-entity-av--child { background: #e0e7ff; color: #4338ca; }

.rs-entity-name { font-size: .88rem; font-weight: 700; color: #1e293b; }
.rs-entity-sub  { font-size: .63rem; color: #94a3b8; margin-top: 1px; }

/* Colonne admin */
.rs-admin-col { flex-shrink: 0; }
.rs-admin-chip {
  display: inline-flex; align-items: center;
  font-size: .68rem; font-weight: 600;
  padding: 4px 10px; border-radius: 20px;
  white-space: nowrap;
}
.rs-admin-chip--set  {
  background: linear-gradient(135deg,#fef9c3,#fde68a);
  color: #78350f; border: 1px solid #fbbf24;
}
.rs-admin-chip--none {
  background: #fee2e2; color: #b91c1c;
}
.rs-admin-fn { color: #92400e; opacity: .8; }

/* Compteur + toggle */
.rs-cnt {
  display: inline-flex; align-items: center; justify-content: center;
  font-size: .65rem; font-weight: 700;
  padding: 2px 8px; border-radius: 20px;
}
.rs-cnt--has  { background: #dbeafe; color: #1d4ed8; }
.rs-cnt--none { background: #f1f5f9; color: #94a3b8; }

.rs-toggle-icon { font-size: 14px; color: #94a3b8; }

/* ── FONCTIONS (dépliées) ──────────────────── */
.rs-fns {
  border-top: 1px solid #f1f5f9;
  background: #fafbfc;
}

.rs-fn-empty {
  padding: 16px 20px;
  font-size: .78rem; color: #94a3b8;
  display: flex; align-items: center;
}

.rs-fn-row {
  display: flex; align-items: flex-start; gap: 12px;
  padding: 14px 16px 14px 0;
  border-bottom: 1px solid #f1f5f9;
  transition: background .1s;
}
.rs-fn-row:hover      { background: #f8fafc; }
.rs-fn-row--admin     { background: linear-gradient(to right, #fffbeb 0%, #fafbfc 60%); }
.rs-fn-row:last-child { border-bottom: none; }

.rs-fn-strip {
  width: 4px; align-self: stretch; flex-shrink: 0;
}
.rs-fn-strip--on { background: linear-gradient(to bottom, #f59e0b, #fbbf24); }

.rs-fn-av {
  width: 36px; height: 36px; border-radius: 8px; flex-shrink: 0;
  background: linear-gradient(135deg,#6366f1,#818cf8);
  color: #fff; font-size: .63rem; font-weight: 800;
  display: flex; align-items: center; justify-content: center;
}
.rs-fn-av--admin {
  background: linear-gradient(135deg,#f59e0b,#fbbf24);
  color: #78350f;
}

.rs-fn-name { font-size: .8rem; font-weight: 600; color: #1e293b; }

/* ── CODES / BADGES ────────────────────────── */
.rs-code {
  font-family: monospace; font-size: .63rem; font-weight: 700;
  background: #e0e7ff; color: #4338ca;
  border-radius: 4px; padding: 1px 5px; white-space: nowrap;
}
.rs-fncode {
  font-family: monospace; font-size: .62rem; font-weight: 700;
  background: #f1f5f9; color: #475569;
  border-radius: 4px; padding: 1px 5px; white-space: nowrap;
}
.rs-badge {
  display: inline-flex; align-items: center;
  font-size: .62rem; font-weight: 600;
  padding: 1px 7px; border-radius: 20px; white-space: nowrap;
}
.rs-badge--root  { background: #ede9fe; color: #5b21b6; }
.rs-badge--admin { background: linear-gradient(135deg,#fde68a,#fbbf24); color: #78350f; border: 1px solid #f59e0b; }

/* ── UTILISATEUR ───────────────────────────── */
.rs-user-line {
  display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
  margin-top: 6px;
}
.rs-user-av {
  width: 32px; height: 32px; border-radius: 50%; flex-shrink: 0;
  background: linear-gradient(135deg, #0ea5e9, #38bdf8);
  color: #fff; font-weight: 700; font-size: .8rem;
  display: flex; align-items: center; justify-content: center;
}
.rs-user-name  { font-size: .78rem; font-weight: 600; color: #1e293b; line-height: 1.2; }
.rs-user-email { font-size: .65rem; color: #64748b; }
.rs-user-mat   { font-size: .62rem; color: #94a3b8; font-family: monospace; }

.rs-no-user {
  display: flex; align-items: center;
  font-size: .72rem; color: #94a3b8; margin-top: 4px;
}

/* ── BOUTON ADMIN ──────────────────────────── */
.rs-admin-btn {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: .68rem; font-weight: 700;
  padding: 5px 12px; border-radius: 20px;
  border: none; cursor: pointer; white-space: nowrap;
  transition: all .15s;
}
.rs-admin-btn--off {
  background: #f1f5f9; color: #475569;
}
.rs-admin-btn--off:hover {
  background: #fef3c7; color: #92400e;
}
.rs-admin-btn--on {
  background: linear-gradient(135deg,#fde68a,#fbbf24);
  color: #78350f; border: 1px solid #f59e0b;
}
.rs-admin-btn--on:hover {
  background: #fee2e2; color: #b91c1c; border-color: #fca5a5;
}

/* ── EMPTY ─────────────────────────────────── */
.rs-empty {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  min-height: 200px; background: #f8fafc;
  border-radius: 10px; border: 2px dashed #e2e8f0;
}
.rs-empty-icon { font-size: 3rem; color: #cbd5e1; }

/* FORM */
.form-select-sm { font-size: .75rem; height: 28px; padding: .18rem .45rem; }
.btn-sm { font-size: .72rem; padding: .15rem .5rem; }
</style>