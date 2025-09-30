import { defineStore } from 'pinia'

export const useMenuStore = defineStore('menu', {
  state: () => ({
    loadingChartsEntities: false,
    loadedChartsEntities: false,
    chartsEntitiesError: '',
    chartsEntitiesChildren: [] as any[],
  }),
  actions: {
    async loadChartsEntities() {
      if (this.loadedChartsEntities || this.loadingChartsEntities) return
      this.loadingChartsEntities = true
      this.chartsEntitiesError = ''
      try {
        const res = await fetch('/api/menu/entities', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        if (!res.ok) throw new Error('HTTP '+res.status)
        const data = await res.json()
        this.chartsEntitiesChildren = Array.isArray(data) ? data : []
        this.loadedChartsEntities = true
      } catch (e: any) {
        this.chartsEntitiesError = e?.message || 'Erreur inconnue'
      } finally {
        this.loadingChartsEntities = false
      }
    }
  }
})
