<template>
  <div class="phase-rows">
    <!-- Phase Principale -->
    <tr class="phase-row" :style="{ backgroundColor: getLevelBgColor(phase.level) }">
      <!-- Expand/Collapse Icon -->
      <td class="text-center">
        <button 
          v-if="phase.children && phase.children.length > 0"
          @click="$emit('toggle-expand', phase.id)"
          class="btn btn-sm btn-link p-0 m-0"
          :title="isExpanded ? 'Replier' : 'Déplier'"
        >
          {{ isExpanded ? '▼' : '▶' }}
        </button>
      </td>

      <!-- Code -->
      <td class="fw-bold" :style="{ paddingLeft: `${phase.level * 20}px` }">
        <span class="badge" :class="getLevelBadgeClass(phase.level)">
          {{ phase.code_full }}
        </span>
      </td>

      <!-- Label -->
      <td>
        <span class="text-dark">{{ phase.label }}</span>
        <small class="d-block text-muted" v-if="phase.children && phase.children.length > 0">
          {{ phase.children.length }} enfant(s)
        </small>
      </td>

      <!-- Level -->
      <td class="text-center">
        <span class="badge bg-info">{{ phase.level }}</span>
      </td>

      <!-- Weight -->
      <td class="text-center">
        <span v-if="phase.weight > 0" class="badge bg-warning">
          {{ phase.weight }}/5
        </span>
        <span v-else class="text-muted">-</span>
      </td>

      <!-- Actions -->
      <td>
        <div class="btn-group btn-group-sm">
          <button 
            @click="$emit('edit', phase)"
            class="btn btn-outline-primary"
            title="Modifier"
          >
            ✏️
          </button>
          <button 
            @click="$emit('create-child', phase)"
            class="btn btn-outline-success"
            title="Ajouter enfant"
          >
            ➕
          </button>
          <button 
            @click="$emit('delete', phase.id)"
            class="btn btn-outline-danger"
            title="Supprimer"
          >
            🗑️
          </button>
        </div>
      </td>
    </tr>

    <!-- Enfants (si expanded) -->
    <template v-if="isExpanded && phase.children && phase.children.length > 0">
      <PhaseRow
        v-for="child in phase.children"
        :key="`phase-${child.id}`"
        :phase="child"
        :expanded-ids="expandedIds"
        :mission-type-id="missionTypeId"
        @toggle-expand="$emit('toggle-expand', $event)"
        @edit="$emit('edit', $event)"
        @delete="$emit('delete', $event)"
        @create-child="$emit('create-child', $event)"
      />
    </template>
  </div>
</template>

<script>
export default {
  name: 'PhaseRow',
  props: {
    phase: {
      type: Object,
      required: true,
    },
    expandedIds: {
      type: Set,
      required: true,
    },
    missionTypeId: {
      type: Number,
      required: true,
    },
  },
  computed: {
    isExpanded() {
      return this.expandedIds.has(this.phase.id);
    },
  },
  methods: {
    /**
     * Couleur de fond selon le niveau
     */
    getLevelBgColor(level) {
      const colors = {
        1: '#e7f3ff',  // Bleu clair
        2: '#f0f8e8',  // Vert clair
        3: '#fff9e6',  // Jaune clair
        4: '#ffe8e8',  // Rose clair
        5: '#f0e8ff',  // Violet clair
      };
      return colors[level] || '#ffffff';
    },

    /**
     * Classe badge selon le niveau
     */
    getLevelBadgeClass(level) {
      const classes = {
        1: 'bg-primary',      // Bleu
        2: 'bg-success',      // Vert
        3: 'bg-warning text-dark', // Jaune/Orange
        4: 'bg-danger',       // Rose/Rouge
        5: 'bg-purple',       // Violet
      };
      return classes[level] || 'bg-secondary';
    },
  },
};
</script>

<style scoped>
.phase-rows {
  display: contents;
}

.phase-row {
  transition: background-color 0.2s ease;
}

.phase-row:hover {
  background-color: rgba(13, 110, 253, 0.05) !important;
}

.phase-row td {
  vertical-align: middle;
  padding: 0.75rem;
  border-bottom: 1px solid #e9ecef;
}

.phase-row:last-child td {
  border-bottom: none;
}

.btn-link {
  color: #0d6efd;
  text-decoration: none;
}

.btn-link:hover {
  color: #0b5ed7;
  text-decoration: underline;
}

.btn-group-sm .btn {
  padding: 0.25rem 0.5rem;
  font-size: 0.85rem;
}

.badge {
  font-weight: 600;
  padding: 0.4rem 0.6rem;
}

.text-dark {
  color: #212529;
}

.text-muted {
  color: #6c757d;
}

.fw-bold {
  font-weight: 600;
}

.d-block {
  display: block;
}

@media (max-width: 768px) {
  .phase-row td:not(:nth-child(1)):not(:nth-child(2)) {
    display: none;
  }

  .phase-row .btn-group {
    flex-wrap: wrap;
  }
}
</style>