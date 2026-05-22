<!-- ════════ OUTIL IV — APPROCHE PROCESSUS ════════ -->
<template>
  <VerticalLayoutAudit>
    <div class="container-fluid py-3">

      <OutilHeader code-outil="IV" titre="Approche Processus"
        sous-titre="Description méthodique des activités | Identification Objectifs / Risques / Contrôles"
        couleur="#b45309" icone="ti-sitemap"
        :form-code="form.code" :statut="form.validation_status"
        :can-manage="canManage" :back-url="props.backUrl"
        :processing="processing" @sauvegarder="submit" />

      <!-- INFORMATIONS GÉNÉRALES -->
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-header py-2 px-3 fw-bold small" style="background:#b45309;color:#fff">
          <i class="ti ti-info-circle me-1"></i> INFORMATIONS GÉNÉRALES
        </div>
        <div class="card-body p-3">
          <div class="row g-3">
            <div class="col-md-4"><label class="form-label small fw-semibold mb-1">Mission</label><input type="text" class="form-control form-control-sm" :value="missionLibelle" disabled style="background:#f8fafc" /></div>
            <div class="col-md-4"><label class="form-label small fw-semibold mb-1">Domaine audité</label><input type="text" class="form-control form-control-sm" v-model="fields.domaine" :disabled="isLocked" placeholder="Ex : Direction Financière" style="font-size:.8rem" /></div>
            <div class="col-md-2"><label class="form-label small fw-semibold mb-1">Date</label><input type="date" class="form-control form-control-sm" v-model="fields.date" :disabled="isLocked" /></div>
            <div class="col-md-2"><label class="form-label small fw-semibold mb-1">Auditeur(s)</label><input type="text" class="form-control form-control-sm" :value="props.auditeurNom" disabled style="background:#f8fafc;font-size:.8rem" /></div>
          </div>
        </div>
      </div>

      <!-- ONGLETS 3 TYPES DE PROCESSUS -->
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-header p-0" style="background:#b45309">
          <ul class="nav nav-tabs border-0" style="padding:0 12px">
            <li class="nav-item" v-for="tp in typesProcessus" :key="tp.code">
              <button class="nav-link border-0 py-2 px-3 fw-semibold"
                      :class="activeTab === tp.code ? 'active' : ''"
                      style="font-size:.75rem;border-radius:4px 4px 0 0"
                      :style="activeTab === tp.code ? `background:#fff;color:${tp.color}` : 'color:#fff;background:transparent'"
                      @click="activeTab = tp.code">
                {{ tp.label }}
              </button>
            </li>
          </ul>
        </div>
        <div v-for="tp in typesProcessus" :key="tp.code">
          <div v-if="activeTab === tp.code">
            <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom"
                 :style="`background:${tp.color}11`">
              <span class="small fw-semibold" :style="`color:${tp.color}`">{{ tp.description }}</span>
              <button v-if="!isLocked" class="btn btn-sm" :style="`background:${tp.color};color:#fff;font-size:.7rem`"
                      @click="ajouterProcessus(tp.code)">
                <i class="ti ti-plus"></i> Processus
              </button>
            </div>
            <div class="table-responsive">
              <table class="table table-bordered table-sm align-middle mb-0" style="min-width:1000px">
                <thead>
                  <tr>
                    <th style="min-width:150px" :style="`background:${tp.color};color:#fff;font-size:.72rem`">Nom du processus</th>
                    <th style="min-width:120px" :style="`background:${tp.color};color:#fff;font-size:.72rem`">Finalité</th>
                    <th style="min-width:120px" :style="`background:${tp.color};color:#fff;font-size:.72rem`">Éléments entrants</th>
                    <th style="min-width:120px" :style="`background:${tp.color};color:#fff;font-size:.72rem`">Éléments sortants</th>
                    <th style="min-width:160px" :style="`background:${tp.color};color:#fff;font-size:.72rem`">Activités principales</th>
                    <th style="min-width:100px" :style="`background:${tp.color};color:#fff;font-size:.72rem`">Clients du processus</th>
                    <th style="min-width:100px" :style="`background:${tp.color};color:#fff;font-size:.72rem`">Fournisseurs</th>
                    <th style="min-width:90px" :style="`background:${tp.color};color:#fff;font-size:.72rem`">Contrats interface</th>
                    <th v-if="!isLocked" style="width:40px;background:#f1f5f9"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="!(processusData[tp.code]?.length)">
                    <td :colspan="isLocked ? 8 : 9" class="text-center text-muted py-4 fst-italic small">
                      Aucun processus. Cliquez sur « Processus » pour ajouter.
                    </td>
                  </tr>
                  <tr v-for="(proc, pi) in (processusData[tp.code] || [])" :key="pi" :class="pi % 2 === 0 ? '' : 'table-light'">
                    <td><input v-if="!isLocked" type="text" class="form-control form-control-sm" v-model="proc.nom" style="font-size:.72rem" /><span v-else class="small">{{ proc.nom }}</span></td>
                    <td><input v-if="!isLocked" type="text" class="form-control form-control-sm" v-model="proc.finalite" style="font-size:.72rem" /><span v-else class="small">{{ proc.finalite }}</span></td>
                    <td><input v-if="!isLocked" type="text" class="form-control form-control-sm" v-model="proc.entrants" style="font-size:.72rem" /><span v-else class="small">{{ proc.entrants }}</span></td>
                    <td><input v-if="!isLocked" type="text" class="form-control form-control-sm" v-model="proc.sortants" style="font-size:.72rem" /><span v-else class="small">{{ proc.sortants }}</span></td>
                    <td><textarea v-if="!isLocked" class="form-control form-control-sm" v-model="proc.activites" rows="2" style="font-size:.72rem;resize:vertical"></textarea><p v-else class="mb-0 small" style="white-space:pre-wrap">{{ proc.activites }}</p></td>
                    <td><input v-if="!isLocked" type="text" class="form-control form-control-sm" v-model="proc.clients" style="font-size:.72rem" /><span v-else class="small">{{ proc.clients }}</span></td>
                    <td><input v-if="!isLocked" type="text" class="form-control form-control-sm" v-model="proc.fournisseurs" style="font-size:.72rem" /><span v-else class="small">{{ proc.fournisseurs }}</span></td>
                    <td><input v-if="!isLocked" type="text" class="form-control form-control-sm" v-model="proc.contrats" style="font-size:.72rem" /><span v-else class="small">{{ proc.contrats }}</span></td>
                    <td v-if="!isLocked" class="text-center"><button class="btn btn-sm btn-outline-danger" style="font-size:.65rem;padding:3px 6px" @click="processusData[tp.code].splice(pi,1)"><i class="ti ti-trash"></i></button></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <OutilFooter :form="form" :processing="processing" :is-locked="isLocked" :can-manage="canManage"
                   @save="submit" @cancel="annuler" @soumettre="soumettre"
                   @valider="valider('validate')" @rejeter="promptReject" />
    </div>
    <OutilToast :toast="toast" @close="toast.show = false" />
  </VerticalLayoutAudit>
</template>

<script setup lang="ts">
import { ref, computed, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import VerticalLayoutAudit from '@/layouts/VerticalLayoutAudit.vue'
import OutilHeader from '@/components/Outils/OutilHeader.vue'
import OutilFooter from '@/components/Outils/OutilFooter.vue'
import OutilToast  from '@/components/Outils/OutilToast.vue'

const props = withDefaults(defineProps<{ mission?: any; form?: any; auditorRole?: string; auditeurNom?: string; missionContext?: any; backUrl?: string; urlStore?: string; urlUpdate?: string; urlSoumettre?: string; urlValider?: string }>(), { missionContext: () => ({}) })

const form       = reactive<any>({ id: null, code: '', validation_status: 'draft', ...(props.form ?? {}) })
const dynUrls    = reactive({ update: props.urlUpdate ?? null as string|null, soumettre: props.urlSoumettre ?? null as string|null, valider: props.urlValider ?? null as string|null })
const processing = ref(false)
const activeTab  = ref('realisation')
const toast      = ref({ show: false, type: 'success', msg: '' })
let _tt: any

const fields = reactive({ date: props.form?.date ?? '', domaine: props.form?.domaine ?? '' })
const processusData = reactive<Record<string, any[]>>({
  realisation: safeArr(props.form?.processus_realisation),
  management:  safeArr(props.form?.processus_management),
  support:     safeArr(props.form?.processus_support),
})

const typesProcessus = [
  { code: 'realisation', label: '1. Réalisation', description: 'Processus produisant des produits ou services à destination des clients', color: '#065f46' },
  { code: 'management',  label: '2. Management',  description: 'Processus de pilotage, direction et orientation stratégique',             color: '#1e40af' },
  { code: 'support',     label: '3. Support',      description: 'Processus de soutien (RH, IT, Finance, Logistique…)',                    color: '#6d28d9' },
]

function ajouterProcessus(type: string) {
  if (!processusData[type]) processusData[type] = []
  processusData[type].push({ nom: '', finalite: '', entrants: '', sortants: '', activites: '', clients: '', fournisseurs: '', contrats: '' })
}

const canManage      = computed(() => ['DM','CM'].includes(props.auditorRole ?? ''))
const isLocked       = computed(() => form.validation_status === 'validated' || (form.validation_status === 'in_review' && !canManage.value))
const missionLibelle = computed(() => props.mission?.libelle ?? props.missionContext?.mission_libelle ?? '')

async function submit(silent = false) {
  processing.value = !silent
  try {
    const url = form.id ? (dynUrls.update ?? props.urlUpdate) : props.urlStore
    if (!url) { if (!silent) showToast('error', 'URL indisponible.'); return }
    const res = await fetch(url, { method: form.id ? 'PUT' : 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() }, body: JSON.stringify({ ...fields, mission_id: props.missionContext?.mission_id, assignment_id: props.missionContext?.assignment_id, processus_realisation: JSON.stringify(processusData.realisation), processus_management: JSON.stringify(processusData.management), processus_support: JSON.stringify(processusData.support) }) })
    const d = await res.json()
    if (d.success || res.ok) { if (!silent) showToast('success', form.id ? 'Mis à jour.' : 'Créé.'); if (d.form) Object.assign(form, d.form); if (d.urlUpdate) dynUrls.update = d.urlUpdate; if (d.urlSoumettre) dynUrls.soumettre = d.urlSoumettre; if (d.urlValider) dynUrls.valider = d.urlValider }
    else { if (!silent) showToast('error', d.message ?? 'Erreur.') }
  } catch { if (!silent) showToast('error', 'Erreur réseau.') }
  finally { processing.value = false }
}
async function soumettre() { processing.value = true; const d = await (await fetch(dynUrls.soumettre ?? '', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() }, body: JSON.stringify({ mission_id: props.missionContext?.mission_id, assignment_id: props.missionContext?.assignment_id }) })).json(); if (d.success) { form.validation_status = 'in_review'; showToast('success', 'Soumis.') } else showToast('error', d.error ?? 'Erreur'); processing.value = false }
async function valider(action: string, note?: string) { processing.value = true; const d = await (await fetch(dynUrls.valider ?? '', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() }, body: JSON.stringify({ action, note, mission_id: props.missionContext?.mission_id, assignment_id: props.missionContext?.assignment_id }) })).json(); if (d.success) { form.validation_status = d.status; showToast('success', action === 'validate' ? 'Validé ✓' : 'Rejeté.') } else showToast('error', d.error ?? 'Erreur'); processing.value = false }
function promptReject() { const n = prompt('Motif :', ''); if (n?.trim()) valider('reject', n.trim()) }
function annuler() { if (props.backUrl) router.visit(props.backUrl) }
function showToast(t: string, m: string) { if (_tt) clearTimeout(_tt); toast.value = { show: true, type: t, msg: m }; _tt = setTimeout(() => { toast.value.show = false }, 4000) }
function safeArr(v: any): any[] { if (Array.isArray(v)) return [...v]; if (!v) return []; try { const d = JSON.parse(v); return Array.isArray(d) ? d : [] } catch { return [] } }
function csrf() { return (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? '' }
</script>