// stores/phaseNavStore.ts
import { defineStore } from 'pinia'

export const usePhaseNavStore = defineStore('phaseNav', {
  state: () => ({
    phasesByType: [] as any[],
    missionCode: '',
    missionLabel: ''
  }),

  actions: {
    setPhases(phases: any[], code: string, label: string) {
      this.phasesByType = phases || []
      this.missionCode = code
      this.missionLabel = label
    },

    clearPhases() {
      this.phasesByType = []
      this.missionCode = ''
      this.missionLabel = ''
    }
  }
})