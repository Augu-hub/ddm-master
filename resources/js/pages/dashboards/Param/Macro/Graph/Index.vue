<template>
  <VerticalLayout>
    <Head :title="'Graphe des Processus — ' + entity.name" />

    <div class="px-6 py-4">

      <h2 class="text-xl font-bold mb-4">
        📊 Graphe des Processus — {{ entity.name }}
      </h2>

      <MacroGraphAuto :entity="entity" />
    </div>
  </VerticalLayout>
</template>

<script setup>
const props = defineProps({
  entity: Object,
})
</script>
