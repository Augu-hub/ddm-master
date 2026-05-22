<template>
  <div class="ot-content">

    <section class="ot-section">
      <div class="ot-grid">
        <div class="ot-field ot-field--wide">
          <label class="ot-lbl">Processus audité</label>
          <input type="text" class="ot-inp" v-model="d.processus" placeholder="Ex : Processus achats" />
        </div>
        <div class="ot-field">
          <label class="ot-lbl">Date</label>
          <input type="date" class="ot-inp" v-model="d.date" />
        </div>
      </div>
    </section>

    <!-- Acteurs -->
    <section class="ot-section">
      <div class="ot-section__bar">
        <h3 class="ot-section__ttl">Acteurs du processus</h3>
        <button v-if="d.acteurs.length < 8" class="btn-add" @click="d.acteurs.push('')">
          <i class="ti ti-plus"></i> Acteur
        </button>
      </div>
      <div class="acteurs-row">
        <div v-for="(a, ai) in d.acteurs" :key="ai" class="acteur-item">
          <span class="acteur-badge" :style="'background:' + COLORS[ai % COLORS.length]">A{{ ai + 1 }}</span>
          <input type="text" class="ot-inp-sm" style="width:110px" v-model="d.acteurs[ai]"
                 :placeholder="'Acteur ' + (ai + 1)" />
          <button v-if="d.acteurs.length > 1" class="btn-del-sm"
                  @click="retirerActeur(ai)"><i class="ti ti-x"></i></button>
        </div>
      </div>
      <p class="legend-text">R = Réalise · A = Approuve · C = Consulté · I = Informé</p>
    </section>

    <!-- Tâches -->
    <section class="ot-section">
      <div class="ot-section__bar">
        <h3 class="ot-section__ttl">Tâches &amp; Séparation des fonctions</h3>
        <button class="btn-add"
                @click="d.taches.push({libelle:'', roles: new Array(d.acteurs.length).fill('')})">
          <i class="ti ti-plus"></i> Tâche
        </button>
      </div>
      <div class="ot-table-wrap">
        <table class="ot-tbl" style="min-width:400px">
          <thead>
            <tr>
              <th style="width:34px" class="tc">N°</th>
              <th style="min-width:150px">Tâche du processus</th>
              <th v-for="(a, ai) in d.acteurs" :key="ai" class="tc" style="width:44px"
                  :style="'color:#fff;background:' + COLORS[ai % COLORS.length]">
                A{{ ai + 1 }}
              </th>
              <th style="width:30px"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!d.taches.length">
              <td :colspan="2 + d.acteurs.length + 1" class="td-empty">Aucune tâche</td>
            </tr>
            <tr v-for="(t, ti) in d.taches" :key="ti">
              <td class="tc fw">{{ ti + 1 }}</td>
              <td>
                <input type="text" class="ot-inp-sm" v-model="t.libelle" placeholder="Libellé de la tâche…" />
              </td>
              <td v-for="(a, ai) in d.acteurs" :key="ai" class="tc" style="padding:3px">
                <select class="ot-sel-xs" v-model="t.roles[ai]">
                  <option value="">—</option>
                  <option>R</option><option>A</option><option>C</option><option>I</option>
                </select>
              </td>
              <td class="tc">
                <button class="btn-del" @click="d.taches.splice(ti, 1)">×</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Observations -->
    <section class="ot-section">
      <label class="ot-lbl">Observations — fonctions incompatibles</label>
      <textarea class="ot-ta" v-model="d.observations" rows="4"
                placeholder="Identifiez les cumuls de rôles incompatibles…"></textarea>
    </section>

  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
const props = defineProps<{ data: any }>()
const emit  = defineEmits(['update:data'])
const d = computed({ get: () => props.data, set: (v) => emit('update:data', v) })

const COLORS = ['#1e40af','#065f46','#6d28d9','#b45309','#be185d','#0f172a','#047857','#7c3aed']

function retirerActeur(ai: number) {
  d.value.acteurs.splice(ai, 1)
  d.value.taches.forEach((t: any) => { if (t.roles) t.roles.splice(ai, 1) })
}
</script>

<style scoped>
@import './outil-shared.css';
</style>