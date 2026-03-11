// resources/js/stores/auditStore.ts
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

interface Mission {
  id: number
  code: string
  mission_objective: string
  requested_date?: string
  coefficient?: number
  level?: string
  entity?: { id: number; name: string }
  [key: string]: any
}

interface Factor {
  id: number
  label: string
  weight?: number
  order_position?: number
  [key: string]: any
}

interface FactorScores {
  [missionId: number]: {
    [factorId: number]: number
  }
}

export const useAuditStore = defineStore('audit', () => {
  // STATE
  const missions = ref<Mission[]>([])
  const factors = ref<Factor[]>([])
  const factorScores = ref<FactorScores>({})
  const loading = ref(false)
  const saving = ref<Record<number, boolean>>({})
  const error = ref<string | null>(null)

  // COMPUTED - SAFE DATA
  const allMissions = computed(() => {
    const arr = missions.value || []
    return Array.isArray(arr) ? arr.filter(m => m) : []
  })

  const allFactors = computed(() => {
    const arr = factors.value || []
    return Array.isArray(arr) ? arr.filter(f => f) : []
  })

  // GET YEARS
  const availableYears = computed(() => {
    const years = new Set<number>()
    allMissions.value.forEach(m => {
      if (m?.requested_date) {
        const year = new Date(m.requested_date).getFullYear()
        years.add(year)
      }
    })
    return Array.from(years).sort((a, b) => b - a)
  })

  // GETTERS
  const getMissionById = (id: number) => {
    return allMissions.value.find(m => m?.id === id)
  }

  const getScore = (missionId: number, factorId: number): number | null => {
    return factorScores.value[missionId]?.[factorId] || null
  }

  const getTotal = (missionId: number): number => {
    if (!factorScores.value[missionId]) return 0
    return Object.values(factorScores.value[missionId])
      .filter(s => s !== null && s !== undefined)
      .reduce((a, b) => a + b, 0)
  }

  const getCoeff = (missionId: number): number => {
    if (!allFactors.value || allFactors.value.length === 0) return 0
    return getTotal(missionId) / allFactors.value.length
  }

  const getLevel = (missionId: number): string => {
    const coeff = getCoeff(missionId)
    if (coeff >= 3.0) return 'Critique'
    if (coeff >= 2.0) return 'Considérable'
    if (coeff >= 1.0) return 'Important'
    return 'Mineur'
  }

  // CALCULATE WEIGHTED SCORE (PAR IMPORTANCE)
  const getWeightedScore = (missionId: number): number => {
    let weightedScore = 0

    allFactors.value.forEach(factor => {
      const score = getScore(missionId, factor.id) || 0
      const weight = (factor.weight || 25) / 100
      weightedScore += score * weight
    })

    return weightedScore
  }

  // MISSIONS TRIÉES PAR WEIGHTED SCORE
  const missionsRanked = computed(() => {
    return allMissions.value
      .map(m => ({
        ...m,
        _weighted: getWeightedScore(m.id),
      }))
      .sort((a, b) => (b._weighted || 0) - (a._weighted || 0))
  })

  // GET RANK
  const getRank = (missionId: number): string => {
    const idx = missionsRanked.value.findIndex(m => m?.id === missionId)
    if (idx === -1) return '—'
    const n = idx + 1
    if (n === 1) return '1er'
    if (n === 2) return '2e'
    if (n === 3) return '3e'
    return `${n}e`
  }

  // ACTIONS
  const initializeData = (
    missionsData: Mission[],
    factorsData: Factor[],
    scoresData: FactorScores
  ) => {
    missions.value = missionsData || []
    factors.value = factorsData || []
    factorScores.value = scoresData || {}
    console.log('✅ Store initialized')
  }

  const updateScore = (missionId: number, factorId: number, score: number) => {
    if (!missionId || !factorId) return

    if (!factorScores.value[missionId]) {
      factorScores.value[missionId] = {}
    }

    factorScores.value[missionId][factorId] = score
    console.log(`📝 Score updated: Mission ${missionId}, Factor ${factorId} = ${score}`)
  }

  const saveMissionScore = async (missionId: number): Promise<boolean> => {
    try {
      saving.value[missionId] = true
      error.value = null

      const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')

      if (!token) {
        throw new Error('CSRF token not found')
      }

      console.log(`📤 Saving Mission ${missionId}`)

      // STEP 1: Enregistrer les scores
      const updateResponse = await fetch(
        `/m/audit.core/api/audit/prioritization/${missionId}`,
        {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
          },
          body: JSON.stringify({
            factor_scores: factorScores.value[missionId] || {},
          }),
        }
      )

      if (!updateResponse.ok) {
        throw new Error(`Update failed: ${updateResponse.status}`)
      }

      console.log(`✅ Scores saved for mission ${missionId}`)

      // STEP 2: Calculer coefficient et level
      const calcResponse = await fetch(
        `/m/audit.core/api/audit/prioritization/${missionId}/calculate`,
        {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
          },
          body: JSON.stringify({}),
        }
      )

      if (!calcResponse.ok) {
        throw new Error(`Calculate failed: ${calcResponse.status}`)
      }

      const calcData = await calcResponse.json()

      if (calcData.success) {
        // UPDATE LOCAL MISSION
        const mission = getMissionById(missionId)
        if (mission) {
          mission.coefficient = calcData.data.coefficient
          mission.level = calcData.data.level
        }

        console.log(`✅ Calculated: coeff=${calcData.data.coefficient}, level=${calcData.data.level}`)
        return true
      } else {
        throw new Error(calcData.error || 'Calculate failed')
      }

    } catch (err) {
      console.error('❌ Save error:', err)
      error.value = err instanceof Error ? err.message : 'Unknown error'
      return false
    } finally {
      saving.value[missionId] = false
    }
  }

  const resetScores = () => {
    factorScores.value = {}
  }

  return {
    // STATE
    missions,
    factors,
    factorScores,
    loading,
    saving,
    error,

    // COMPUTED
    allMissions,
    allFactors,
    availableYears,
    missionsRanked,

    // GETTERS
    getMissionById,
    getScore,
    getTotal,
    getCoeff,
    getLevel,
    getWeightedScore,
    getRank,

    // ACTIONS
    initializeData,
    updateScore,
    saveMissionScore,
    resetScores,
  }
})