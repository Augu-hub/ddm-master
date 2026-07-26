<template>
  <div class="ot-content">

    <!-- Informations générales -->
    <section class="ot-section">
      <h3 class="ot-section__ttl">Informations générales</h3>
      <div class="ot-grid">
        <div class="ot-field">
          <label class="ot-lbl">Date de l'entretien</label>
          <input type="date" class="ot-inp" v-model="d.date" />
        </div>
        <div class="ot-field ot-field--wide">
          <label class="ot-lbl">Interlocuteur(s) — Nom, Email</label>
          <input type="text" class="ot-inp" v-model="d.interlocuteurs" placeholder="Jean Dupont, j.dupont@org.fr" />
        </div>
        <div class="ot-field ot-field--full">
          <label class="ot-lbl">Objectif d'audit</label>
          <textarea class="ot-ta" v-model="d.objectif_audit" rows="2" placeholder="S'assurer que…"></textarea>
        </div>
      </div>
    </section>

    <!-- Questions -->
    <section class="ot-section">
      <div class="ot-section__bar">
        <h3 class="ot-section__ttl">Questions (QQOCPQ)</h3>
        <button class="btn-add" @click="d.questions.push({type:'Ouverte',question:'',reponse:''})">
          <i class="ti ti-plus"></i> Question
        </button>
      </div>
      <div class="ot-table-wrap">
        <table class="ot-tbl">
          <thead>
            <tr>
              <th style="width:34px" class="tc">N°</th>
              <th style="width:108px">Type</th>
              <th>Question</th>
              <th>Réponse / Observation</th>
              <th style="width:30px"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!d.questions.length">
              <td colspan="5" class="td-empty">Aucune question — cliquez sur « Question »</td>
            </tr>
            <tr v-for="(q, qi) in d.questions" :key="qi">
              <td class="tc fw">{{ qi + 1 }}</td>
              <td>
                <select class="ot-sel-sm" v-model="q.type">
                  <option>Ouverte</option>
                  <option>Fermée</option>
                  <option>Factuelle</option>
                  <option>Rebond</option>
                </select>
              </td>
              <td><textarea class="ot-ta-sm" v-model="q.question" rows="2" placeholder="Question…"></textarea></td>
              <td><textarea class="ot-ta-sm" v-model="q.reponse" rows="2" placeholder="Réponse…"></textarea></td>
              <td class="tc">
                <button class="btn-del" @click="d.questions.splice(qi, 1)">×</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Synthèse & Validation -->
    <section class="ot-section">
      <h3 class="ot-section__ttl">Synthèse &amp; Validation</h3>
      <div class="ot-field ot-field--full mb-3">
        <label class="ot-lbl">Points clés validés avec l'interlocuteur</label>
        <textarea class="ot-ta" v-model="d.synthese" rows="3" placeholder="Résumez les points validés…"></textarea>
      </div>
      <div class="ot-grid">
        <div class="ot-field">
          <label class="ot-lbl">Signature Auditeur</label>
          <input type="text" class="ot-inp" v-model="d.sig_auditeur" :placeholder="auditeurNom" />
        </div>
        <div class="ot-field">
          <label class="ot-lbl">Signature Interlocuteur</label>
          <input type="text" class="ot-inp" v-model="d.sig_interlocuteur" placeholder="Nom interlocuteur" />
        </div>
      </div>
    </section>

  </div>
</template>

<script setup lang="ts">
const props = defineProps<{ data: any; auditeurNom?: string }>()
const emit  = defineEmits(['update:data'])
// Proxy local pour v-model direct
import { computed } from 'vue'
const d = computed({
  get: () => props.data,
  set: (v) => emit('update:data', v),
})
</script>

<style scoped>
/* @import './outil-shared.css'; */
</style>