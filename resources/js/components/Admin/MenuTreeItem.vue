<template>
  <div class="tree-node" :class="{ expanded, hasChildren, isDeep: level >= 3 }">
    <!-- Nœud Principal -->
    <div class="node-row" :style="{ '--indent': level * 20 + 'px' }">
      <!-- Expand/Collapse -->
      <div class="node-expand">
        <button
          v-if="hasChildren"
          @click="expanded = !expanded"
          class="expand-btn"
          :class="{ active: expanded }"
          title="Développer/Réduire"
        >
          <i class="ti ti-chevron-right"></i>
        </button>
        <div v-else class="expand-spacer"></div>
      </div>

      <!-- Menu Content -->
      <div class="node-menu">
        <div class="menu-icon-wrapper">
          <i v-if="menu.icon" :class="menu.icon" class="menu-icon"></i>
          <span v-else class="menu-icon-empty">•</span>
        </div>
        <div class="menu-info">
          <div class="menu-label">{{ menu.label }}</div>
          <div class="menu-key">{{ menu.key }}</div>
        </div>
      </div>

      <!-- Module Badge -->
      <div class="node-module">
        <span v-if="menu.module_name" class="module-badge">
          {{ menu.module_name }}
        </span>
        <span v-else class="module-empty">—</span>
      </div>

      <!-- Status Badge -->
      <div class="node-status">
        <span class="status-badge" :class="menu.visible ? 'visible' : 'hidden'">
          {{ menu.visible ? '👁️' : '🚫' }}
        </span>
      </div>

      <!-- Actions -->
      <div class="node-actions">
        <button @click="$emit('edit', menu)" class="action-btn edit" title="Modifier">
          <i class="ti ti-pencil"></i>
        </button>
        <button @click="$emit('toggle-visible', menu)" class="action-btn" title="Visibilité">
          <i class="ti ti-eye"></i>
        </button>
        <button @click="$emit('add-child', menu.id)" class="action-btn add" title="Ajouter enfant">
          <i class="ti ti-plus"></i>
        </button>
        <button @click="$emit('delete', menu)" class="action-btn delete" title="Supprimer">
          <i class="ti ti-trash"></i>
        </button>
      </div>
    </div>

    <!-- Enfants (Récursif avec expansion) -->
    <template v-if="hasChildren && expanded">
      <div class="children-container">
        <MenuTreeItem
          v-for="child in menu.children"
          :key="child.id"
          :menu="child"
          :modules="modules"
          :all-icons="allIcons"
          :level="level + 1"
          @edit="$emit('edit', $event)"
          @delete="$emit('delete', $event)"
          @toggle-visible="$emit('toggle-visible', $event)"
          @add-child="$emit('add-child', $event)"
        />
      </div>
    </template>

    <!-- Indicateur d'enfants cachés -->
    <div v-if="hasChildren && !expanded" class="children-hidden">
      +{{ menu.children.length }} caché(s)
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  menu: Object,
  modules: Array,
  allIcons: Array,
  level: { type: Number, default: 0 }
})

defineEmits(['edit', 'delete', 'toggle-visible', 'add-child'])

const expanded = ref(true) // Expanded par défaut

const hasChildren = computed(() => {
  return props.menu.children && props.menu.children.length > 0
})
</script>

<style scoped>
.tree-node {
  border-left: 2px solid transparent;
  transition: all 0.2s;
}

.tree-node.hasChildren {
  border-left-color: #e0e0e0;
}

.tree-node:hover {
  border-left-color: #667eea;
}

.node-row {
  display: grid;
  grid-template-columns: 40px 1fr 200px 120px 150px;
  gap: 1rem;
  align-items: center;
  padding: 0.875rem 1.5rem;
  padding-left: calc(var(--indent, 0px) + 1.5rem);
  border-bottom: 1px solid #f0f0f0;
  transition: all 0.2s;
}

.node-row:hover {
  background: #f8f9fa;
}

.node-expand {
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 40px;
}

.expand-btn {
  background: none;
  border: none;
  padding: 0.3rem;
  cursor: pointer;
  color: #667eea;
  font-size: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 6px;
  transition: all 0.2s;
}

.expand-btn:hover {
  background: rgba(102, 126, 234, 0.1);
}

.expand-btn.active {
  transform: rotate(90deg);
}

.expand-spacer {
  width: 32px;
  height: 32px;
}

.node-menu {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  min-width: 0;
  flex: 1;
}

.menu-icon-wrapper {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(102, 126, 234, 0.1);
  color: #667eea;
  flex-shrink: 0;
  font-size: 1.1rem;
}

.menu-icon-empty {
  color: #d0d0d0;
  font-weight: 700;
  font-size: 1.25rem;
}

.menu-info {
  min-width: 0;
  flex: 1;
}

.menu-label {
  font-weight: 600;
  color: #212529;
  font-size: 0.95rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.menu-key {
  font-size: 0.75rem;
  color: #6c757d;
  font-family: 'Courier New', monospace;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-top: 0.2rem;
}

.node-module {
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 200px;
  flex-shrink: 0;
}

.module-badge {
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.15), rgba(118, 75, 162, 0.15));
  color: #667eea;
  padding: 0.4rem 0.75rem;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.85rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.module-empty {
  color: #ccc;
  font-weight: 600;
}

.node-status {
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 120px;
  flex-shrink: 0;
}

.status-badge {
  padding: 0.35rem 0.65rem;
  border-radius: 6px;
  font-size: 0.9rem;
  font-weight: 600;
  white-space: nowrap;
}

.status-badge.visible {
  background: rgba(86, 171, 47, 0.1);
  color: #2e7d32;
}

.status-badge.hidden {
  background: rgba(220, 53, 69, 0.1);
  color: #c62828;
}

.node-actions {
  display: flex;
  gap: 0.5rem;
  min-width: 150px;
  justify-content: center;
  flex-shrink: 0;
}

.action-btn {
  background: white;
  border: 1px solid #e0e0e0;
  padding: 0.35rem;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.9rem;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  color: #667eea;
}

.action-btn:hover {
  background: #f8f9fa;
  border-color: #667eea;
  color: #667eea;
}

.action-btn.add {
  color: #56ab2f;
}

.action-btn.add:hover {
  background: rgba(86, 171, 47, 0.1);
  border-color: #56ab2f;
}

.action-btn.delete {
  color: #dc3545;
}

.action-btn.delete:hover {
  background: rgba(220, 53, 69, 0.1);
  border-color: #dc3545;
}

.children-container {
  display: flex;
  flex-direction: column;
}

.children-hidden {
  font-size: 0.8rem;
  color: #999;
  padding: 0.4rem 1.5rem;
  padding-left: calc(var(--indent, 0px) + 1.5rem + 40px);
  font-style: italic;
}

/* Responsive */
@media (max-width: 1024px) {
  .node-row {
    grid-template-columns: 40px 1fr 150px 100px 120px;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    padding-left: calc(var(--indent, 0px) + 1rem);
  }

  .menu-label {
    font-size: 0.9rem;
  }

  .menu-key {
    font-size: 0.7rem;
  }

  .module-badge {
    font-size: 0.8rem;
    padding: 0.3rem 0.6rem;
  }

  .status-badge {
    font-size: 0.85rem;
    padding: 0.3rem 0.5rem;
  }

  .action-btn {
    width: 30px;
    height: 30px;
    font-size: 0.85rem;
  }
}

@media (max-width: 768px) {
  .node-row {
    grid-template-columns: 30px 1fr 30px;
    gap: 0.5rem;
    padding: 0.6rem 0.75rem;
    padding-left: calc(var(--indent, 0px) + 0.75rem);
  }

  .node-module,
  .node-status {
    display: none;
  }

  .node-actions {
    min-width: auto;
    justify-content: flex-end;
    gap: 0.25rem;
  }

  .action-btn {
    width: 28px;
    height: 28px;
    padding: 0.2rem;
    font-size: 0.8rem;
  }

  .menu-icon-wrapper {
    width: 32px;
    height: 32px;
    font-size: 1rem;
  }

  .menu-label {
    font-size: 0.85rem;
  }

  .menu-key {
    display: none;
  }

  .expand-btn {
    width: 28px;
    height: 28px;
    font-size: 0.9rem;
  }

  .expand-spacer {
    width: 28px;
    height: 28px;
  }

  .children-hidden {
    padding-left: calc(var(--indent, 0px) + 0.75rem + 30px);
  }
}

@media (max-width: 480px) {
  .node-row {
    grid-template-columns: 28px 1fr 28px;
    gap: 0.3rem;
    padding: 0.5rem 0.5rem;
    padding-left: calc(var(--indent, 0px) + 0.5rem);
  }

  .action-btn {
    width: 26px;
    height: 26px;
    font-size: 0.75rem;
  }

  .menu-icon-wrapper {
    width: 28px;
    height: 28px;
    font-size: 0.9rem;
  }

  .menu-label {
    font-size: 0.8rem;
  }
}
</style>