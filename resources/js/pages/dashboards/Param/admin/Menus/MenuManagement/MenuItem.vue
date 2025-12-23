<template>
  <div class="menu-item-wrapper">
    <div class="menu-item" :class="{ 'has-children': menu.children?.length > 0, 'is-hidden': !menu.visible }">
      <div class="item-header">
        <!-- Drag Handle -->
        <div class="drag-handle">
          <i class="ti ti-grip-vertical"></i>
        </div>

        <!-- Icône et libellé -->
        <div class="item-info">
          <div class="item-icon-wrapper">
            <i v-if="menu.icon" :class="menu.icon"></i>
            <i v-else class="ti ti-circle-filled text-muted"></i>
          </div>
          <div class="item-details">
            <h5 class="item-label">{{ menu.label }}</h5>
            <span class="item-key">{{ menu.key }}</span>
            <div v-if="menu.type === 'title'" class="badge bg-info-subtle text-info ms-2">
              <i class="ti ti-heading me-1"></i>
              Titre
            </div>
            <div v-if="menu.module_id" class="badge bg-warning-subtle text-warning ms-2">
              <i class="ti ti-database me-1"></i>
              Module
            </div>
            <div v-if="!menu.visible" class="badge bg-secondary-subtle text-secondary ms-2">
              <i class="ti ti-eye-off me-1"></i>
              Caché
            </div>
          </div>
        </div>

        <!-- Meta info -->
        <div class="item-meta">
          <span v-if="menu.url" class="meta-badge">
            <i class="ti ti-link me-1"></i>
            {{ truncate(menu.url, 30) }}
          </span>
          <span v-if="menu.route_name" class="meta-badge">
            <i class="ti ti-route me-1"></i>
            {{ truncate(menu.route_name, 25) }}
          </span>
        </div>

        <!-- Actions -->
        <div class="item-actions">
          <b-button
            size="sm"
            variant="outline-info"
            class="action-btn"
            @click="toggleExpand"
            v-if="menu.children?.length > 0"
            v-b-tooltip.hover="expanded ? 'Réduire' : 'Développer'"
          >
            <i :class="expanded ? 'ti ti-chevron-down' : 'ti ti-chevron-right'"></i>
          </b-button>

          <b-button
            size="sm"
            variant="outline-success"
            class="action-btn"
            @click="$emit('add-child', menu)"
            v-b-tooltip.hover="'Ajouter un sous-menu'"
          >
            <i class="ti ti-plus"></i>
          </b-button>

          <b-button
            size="sm"
            :variant="menu.visible ? 'outline-primary' : 'outline-warning'"
            class="action-btn"
            @click="$emit('toggle-visibility', menu)"
            v-b-tooltip.hover="menu.visible ? 'Masquer' : 'Afficher'"
          >
            <i :class="menu.visible ? 'ti ti-eye' : 'ti ti-eye-off'"></i>
          </b-button>

          <b-button
            size="sm"
            variant="outline-primary"
            class="action-btn"
            @click="$emit('edit', menu)"
            v-b-tooltip.hover="'Modifier'"
          >
            <i class="ti ti-edit"></i>
          </b-button>

          <b-button
            size="sm"
            variant="outline-danger"
            class="action-btn"
            @click="$emit('delete', menu)"
            v-b-tooltip.hover="'Supprimer'"
          >
            <i class="ti ti-trash"></i>
          </b-button>
        </div>
      </div>

      <!-- Enfants (sous-menus) -->
      <div
        v-if="expanded && menu.children?.length > 0"
        class="submenu-list"
        ref="submenuListEl"
      >
        <menu-item
          v-for="child in menu.children"
          :key="child.id"
          :menu="child"
          :modules="modules"
          :icons="icons"
          :level="(level || 0) + 1"
          @edit="$emit('edit', $event)"
          @delete="$emit('delete', $event)"
          @add-child="$emit('add-child', $event)"
          @update="$emit('update', $event)"
          @toggle-visibility="$emit('toggle-visibility', $event)"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import Sortable from 'sortablejs'

defineProps({
  menu: { type: Object, required: true },
  modules: { type: Array, default: () => [] },
  icons: { type: Array, default: () => [] },
  level: { type: Number, default: 0 }
})

const emit = defineEmits(['edit', 'delete', 'add-child', 'update', 'toggle-visibility'])

const expanded = ref(true)
const submenuListEl = ref(null)

onMounted(() => {
  if (submenuListEl.value) {
    new Sortable(submenuListEl.value, {
      animation: 200,
      group: 'menus',
      handle: '.drag-handle',
      ghostClass: 'ghost',
      onEnd: () => {
        emit('update', props.menu)
      }
    })
  }
})

function toggleExpand() {
  expanded.value = !expanded.value
}

function truncate(str, len) {
  return str?.length > len ? str.substring(0, len) + '...' : str
}
</script>

<style scoped>
.menu-item-wrapper {
  margin-bottom: 0.5rem;
}

.menu-item {
  background: white;
  border: 2px solid #e9ecef;
  border-radius: 10px;
  transition: all 0.2s;
  overflow: hidden;
}

.menu-item:hover {
  border-color: #667eea;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
}

.menu-item.is-hidden {
  opacity: 0.6;
  background: #f8f9fa;
}

.menu-item.has-children {
  border-left: 4px solid #667eea;
}

.item-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.875rem 1rem;
  flex-wrap: wrap;
}

.drag-handle {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 6px;
  background: #f0f0f0;
  cursor: grab;
  color: #6c757d;
  transition: all 0.2s;
  flex-shrink: 0;
}

.drag-handle:hover {
  background: #667eea;
  color: white;
}

.drag-handle:active {
  cursor: grabbing;
}

.item-info {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  flex: 1;
  min-width: 0;
}

.item-icon-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 8px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  font-size: 1rem;
  flex-shrink: 0;
}

.item-details {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
  min-width: 0;
}

.item-label {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 600;
  color: #212529;
}

.item-key {
  font-family: 'Courier New', monospace;
  font-size: 0.75rem;
  color: #6c757d;
  background: #f8f9fa;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
}

.badge {
  font-size: 0.75rem;
  padding: 0.375rem 0.75rem;
  display: inline-flex;
  align-items: center;
  white-space: nowrap;
}

.item-meta {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
  font-size: 0.8rem;
  color: #6c757d;
  padding: 0 0.5rem;
  flex-shrink: 0;
}

.meta-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.375rem 0.5rem;
  background: #f0f6ff;
  border-radius: 4px;
  color: #667eea;
  font-weight: 500;
}

.item-actions {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  flex-shrink: 0;
}

.action-btn {
  width: 32px;
  height: 32px;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  transition: all 0.2s;
}

.action-btn:hover:not(:disabled) {
  transform: translateY(-2px);
}

.submenu-list {
  background: #f8f9fa;
  padding: 0.75rem 0.75rem 0.75rem 2.5rem;
  border-top: 1px dashed #dee2e6;
}

.ghost {
  opacity: 0.5;
  background: #e8f0fe;
}

@media (max-width: 991px) {
  .item-header {
    gap: 0.5rem;
  }

  .item-meta {
    display: none;
  }

  .item-details {
    flex-direction: column;
    align-items: flex-start;
  }

  .item-actions {
    width: 100%;
    justify-content: flex-end;
  }
}

@media (max-width: 767px) {
  .item-header {
    flex-wrap: wrap;
    gap: 0.5rem;
  }

  .drag-handle {
    width: 28px;
    height: 28px;
    font-size: 0.85rem;
  }

  .item-icon-wrapper {
    width: 32px;
    height: 32px;
  }

  .item-label {
    font-size: 0.85rem;
  }

  .action-btn {
    width: 28px;
    height: 28px;
    font-size: 0.85rem;
  }

  .submenu-list {
    padding: 0.5rem 0.5rem 0.5rem 2rem;
  }
}
</style>