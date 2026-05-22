<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div v-if="visible" class="ft-overlay" @click.self="$emit('close')">
        <div class="ft-modal">
          <div class="ft-modal__hdr">
            <div>
              <h2 class="ft-modal__ttl"><i class="ti ti-tool"></i> Choisir un outil IFACI</h2>
              <p class="ft-modal__sub">Procédure {{ procIdx + 1 }} · {{ testRef }}</p>
            </div>
            <button class="ft-modal__cls" @click="$emit('close')"><i class="ti ti-x"></i></button>
          </div>

          <div class="ft-modal__body">
            <div class="outil-grid">
              <button
                v-for="outil in outilsIfaci"
                :key="outil.code"
                class="outil-card"
                :class="selected === outil.code ? 'outil-card--sel' : ''"
                :style="selected === outil.code ? '--cc:' + outil.color : '--cc:#e2e8f0'"
                @click="$emit('select', outil.code)"
              >
                <div class="outil-card__num" :style="'background:' + outil.color">{{ outil.code }}</div>
                <div class="outil-card__info">
                  <span class="outil-card__label">{{ outil.label }}</span>
                  <span class="outil-card__sub">{{ subtitle(outil.code) }}</span>
                </div>
                <i v-if="selected === outil.code" class="ti ti-circle-check outil-card__chk"
                   :style="'color:' + outil.color"></i>
              </button>
            </div>
          </div>

          <div class="ft-modal__footer">
            <button class="btn btn-ghost btn-sm" @click="$emit('close')">Annuler</button>
            <button class="btn btn-save btn-sm" :disabled="!selected" @click="$emit('confirm')">
              <i class="ti ti-arrow-right"></i> Ouvrir l'outil
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
defineProps<{
  visible: boolean
  selected: string
  procIdx: number
  testRef: string
  outilsIfaci: { code: string; label: string; icon: string; color: string }[]
}>()

defineEmits(['close', 'select', 'confirm'])

function subtitle(code: string) {
  return ({
    I:   'Collecte d\'informations & preuves',
    II:  'Identification Qui fait Quoi',
    III: 'Représentation graphique du processus',
    IV:  'Description méthodique — Objectifs/Risques',
    V:   'Suivi d\'une transaction origine → dénouement',
  } as Record<string, string>)[code] ?? ''
}
</script>

<style scoped>
.ft-overlay { position: fixed; inset: 0; background: rgba(15,23,42,.5); z-index: 1040; display: flex; align-items: center; justify-content: center; padding: 1rem; }
.ft-modal { background: #fff; border-radius: 12px; width: 420px; max-width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,.25); overflow: hidden; }
.ft-modal__hdr { display: flex; align-items: flex-start; justify-content: space-between; padding: 1rem 1.25rem .75rem; border-bottom: 1px solid #e5e7eb; }
.ft-modal__ttl { font-size: .88rem; font-weight: 700; color: #111827; margin: 0; display: flex; align-items: center; gap: .5rem; }
.ft-modal__sub { font-size: .66rem; color: #6b7280; margin: .25rem 0 0; }
.ft-modal__cls { background: none; border: none; color: #6b7280; cursor: pointer; font-size: 1rem; padding: 2px; line-height: 1; }
.ft-modal__body { padding: 1rem 1.25rem; }
.ft-modal__footer { padding: .75rem 1.25rem; background: #f8fafc; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; gap: .5rem; }
.outil-grid { display: flex; flex-direction: column; gap: .45rem; }
.outil-card { display: flex; align-items: center; gap: .75rem; padding: .6rem .9rem; background: #fff; border: 2px solid var(--cc, #e5e7eb); border-radius: 8px; cursor: pointer; text-align: left; transition: all .15s; width: 100%; }
.outil-card:hover { border-color: #94a3b8; }
.outil-card--sel { background: color-mix(in srgb, var(--cc) 8%, #fff); }
.outil-card__num { display: flex; align-items: center; justify-content: center; min-width: 30px; height: 26px; border-radius: 5px; color: #fff; font-size: .76rem; font-weight: 700; font-family: monospace; flex-shrink: 0; }
.outil-card__info { flex: 1; min-width: 0; }
.outil-card__label { display: block; font-size: .76rem; font-weight: 600; color: #111827; }
.outil-card__sub { display: block; font-size: .6rem; color: #6b7280; margin-top: 1px; }
.outil-card__chk { font-size: 1.05rem; flex-shrink: 0; }
/* Buttons */
.btn { display: inline-flex; align-items: center; gap: 5px; padding: 6px 12px; border-radius: 6px; font-size: .78rem; font-weight: 600; border: none; cursor: pointer; font-family: inherit; transition: all .15s; }
.btn:disabled { opacity: .45; cursor: not-allowed; }
.btn-sm { padding: 4px 9px; font-size: .74rem; }
.btn-ghost { background: #fff; color: #374151; border: 1px solid #e5e7eb; }
.btn-ghost:hover:not(:disabled) { background: #f9fafb; }
.btn-save { background: #1e293b; color: #fff; }
.btn-save:hover:not(:disabled) { background: #0f172a; }
/* Transition */
.modal-fade-enter-active, .modal-fade-leave-active { transition: all .2s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; transform: scale(.96); }
</style>