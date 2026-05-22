<template>
  <header class="ft-header">
    <div class="ft-hrow">
      <a :href="backUrl" class="ft-back"><i class="ti ti-arrow-left"></i></a>

      <div class="ft-hinfo">
        <div class="ft-chips">
          <code class="ft-code">{{ form.code || 'FT-AUTO' }}</code>
          <span class="ft-chip" :class="'chip-' + (form.validation_status || 'draft')">
            <i :class="vstIcon(form.validation_status || 'draft')"></i>
            {{ vstLbl(form.validation_status || 'draft') }}
          </span>
          <span class="ft-chip chip-type">FT</span>
          <span v-if="auditorRole" class="ft-chip" :class="'chip-role-' + auditorRole">{{ auditorRole }}</span>
          <span v-if="auditeurNom" class="ft-chip chip-auditor">
            <i class="ti ti-user-check"></i> {{ auditeurNom }}
          </span>
          <span v-if="nbOutils > 0" class="ft-chip chip-outils">
            <i class="ti ti-tool"></i> {{ nbOutils }} outil(s)
          </span>
        </div>

        <h1 class="ft-title">Fiche de Test — Travaux d'Audit</h1>

        <div class="ft-meta">
          <span v-if="missionLibelle"><i class="ti ti-building"></i> {{ missionLibelle }}</span>
          <span v-if="programmeData?.found" class="ft-meta--prog">
            <i class="ti ti-target"></i> {{ programmeData.programme_label }}
            · {{ programmeData.total_objectifs }} objectif(s)
            · {{ programmeData.total_tests }} test(s)
          </span>
        </div>
      </div>

      <div class="ft-hactions">
        <button v-if="!isLocked" class="btn btn-ghost btn-sm" :disabled="processing" @click="$emit('annuler')">
          <i class="ti ti-x"></i>
        </button>
        <button v-if="!isLocked" class="btn btn-save btn-sm" :disabled="processing" @click="$emit('save')">
          <span v-if="processing" class="spin-s"></span>
          <i v-else class="ti ti-device-floppy"></i>
          {{ form.id ? 'Mettre à jour' : 'Enregistrer' }}
        </button>
        <button v-if="form.id && form.validation_status === 'draft'" class="btn btn-sub btn-sm"
                :disabled="processing" @click="$emit('soumettre')">
          <i class="ti ti-send"></i> Soumettre
        </button>
        <template v-if="canManage && form.validation_status === 'in_review'">
          <button class="btn btn-ok btn-sm" :disabled="processing" @click="$emit('valider')">
            <i class="ti ti-circle-check"></i> Valider
          </button>
          <button class="btn btn-rej btn-sm" :disabled="processing" @click="$emit('rejeter')">
            <i class="ti ti-circle-x"></i> Rejeter
          </button>
        </template>
      </div>
    </div>

    <!-- Bannières statut -->
    <div v-if="form.validation_status === 'validated'" class="ft-banner banner-lock">
      <i class="ti ti-lock"></i> Fiche <strong>validée définitivement</strong> — lecture seule
    </div>
    <div v-else-if="form.validation_status === 'in_review'" class="ft-banner banner-review">
      <i class="ti ti-clock"></i> Soumise pour validation
      <span v-if="canManage"> · Vous pouvez valider ou rejeter.</span>
    </div>
    <div v-else-if="form.validation_status === 'draft' && form.validation_note" class="ft-banner banner-reject">
      <i class="ti ti-circle-x"></i> Rejetée — <em>{{ form.validation_note }}</em>
    </div>
  </header>
</template>

<script setup lang="ts">
defineProps<{
  form: any
  backUrl?: string
  auditorRole?: string
  auditeurNom?: string
  missionLibelle?: string
  programmeData?: any
  nbOutils?: number
  processing?: boolean
  isLocked?: boolean
  canManage?: boolean
}>()

defineEmits(['save', 'annuler', 'soumettre', 'valider', 'rejeter'])

function vstLbl(s: string) { return ({ draft: 'Brouillon', in_review: 'En attente', validated: 'Validé ✓' } as any)[s] ?? s }
function vstIcon(s: string) { return ({ draft: 'ti ti-pencil', in_review: 'ti ti-clock', validated: 'ti ti-circle-check' } as any)[s] ?? 'ti ti-circle' }
</script>

<style scoped>
.ft-header { background: #fff; border-bottom: 1px solid #e5e7eb; padding: 12px 20px 0; position: sticky; top: 0; z-index: 50; box-shadow: 0 1px 4px rgba(0,0,0,.05); }
.ft-hrow { display: flex; align-items: flex-start; gap: 10px; padding-bottom: 10px; }
.ft-back { display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border: 1px solid #e5e7eb; border-radius: 7px; color: #6b7280; text-decoration: none; flex-shrink: 0; margin-top: 2px; }
.ft-back:hover { background: #f3f4f6; }
.ft-hinfo { flex: 1; min-width: 0; }
.ft-chips { display: flex; align-items: center; gap: 5px; flex-wrap: wrap; margin-bottom: 3px; }
.ft-code { font-size: .68rem; font-weight: 700; background: #1e293b; color: #fff; padding: 2px 7px; border-radius: 4px; font-family: ui-monospace, monospace; }
.ft-chip { display: inline-flex; align-items: center; gap: 3px; font-size: .66rem; font-weight: 600; padding: 2px 7px; border-radius: 9px; border: 1px solid transparent; }
.chip-draft { background: #f3f4f6; color: #6b7280; border-color: #e5e7eb; }
.chip-in_review { background: #e3f2fd; color: #1565C0; border-color: rgba(21,101,192,.2); }
.chip-validated { background: #ecfdf5; color: #059669; border-color: #a7f3d0; }
.chip-type { background: #ede9fe; color: #7c3aed; border-color: #c4b5fd; }
.chip-auditor { background: #0f172a; color: #e2e8f0; border-color: #334155; }
.chip-outils { background: #fdf4ff; color: #be185d; border-color: #f5d0fe; font-weight: 700; }
.chip-role-DM { background: #f5f3ff; color: #7c3aed; border-color: #ddd6fe; }
.chip-role-CM { background: #f0f9ff; color: #0284c7; border-color: #bae6fd; }
.chip-role-AS { background: #f0fdf4; color: #059669; border-color: #a7f3d0; }
.chip-role-AJ { background: #fffbeb; color: #d97706; border-color: #fde68a; }
.ft-title { font-size: 1rem; font-weight: 800; color: #111827; margin: 0 0 3px; }
.ft-meta { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; font-size: .72rem; color: #6b7280; }
.ft-meta span { display: flex; align-items: center; gap: 3px; }
.ft-meta--prog { color: #7c3aed; font-weight: 600; }
.ft-hactions { display: flex; align-items: center; gap: 6px; flex-shrink: 0; flex-wrap: wrap; }
.ft-banner { display: flex; align-items: center; gap: 7px; padding: 6px 0; font-size: .76rem; font-weight: 500; }
.banner-lock { color: #059669; border-top: 1px solid #a7f3d0; }
.banner-review { color: #1565C0; border-top: 1px solid #bfdbfe; }
.banner-reject { color: #dc2626; border-top: 1px solid #fecaca; }
/* Buttons */
.btn { display: inline-flex; align-items: center; gap: 5px; padding: 6px 12px; border-radius: 6px; font-size: .78rem; font-weight: 600; border: none; cursor: pointer; font-family: inherit; transition: all .15s; }
.btn:disabled { opacity: .45; cursor: not-allowed; }
.btn-sm { padding: 4px 9px; font-size: .74rem; }
.btn-ghost { background: #fff; color: #374151; border: 1px solid #e5e7eb; }
.btn-ghost:hover:not(:disabled) { background: #f9fafb; }
.btn-save { background: #1e293b; color: #fff; }
.btn-save:hover:not(:disabled) { background: #0f172a; }
.btn-sub { background: #eff6ff; color: #2563EB; border: 1px solid #bfdbfe; }
.btn-ok { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
.btn-rej { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
.spin-s { width: 10px; height: 10px; border-radius: 50%; border: 2px solid currentColor; border-top-color: transparent; animation: spin .6s linear infinite; display: inline-block; }
@keyframes spin { to { transform: rotate(360deg) } }
</style>