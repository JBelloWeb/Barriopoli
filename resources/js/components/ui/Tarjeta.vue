<script setup>
    defineProps({
      variante: { type: String, default: 'base', validator: (v) => ['base', 'marca', 'suave', 'plana'].includes(v) },
      as: { type: String, default: 'div' },
      sinPadding: { type: Boolean, default: false },
    })

    const VARIANTES = {
      base: 'bg-white shadow-tarjeta',
      marca: 'marco-marca bg-violeta-600 text-papel',
      suave: 'bg-durazno-50 ring-1 ring-durazno-200',
      plana: 'bg-white ring-1 ring-niebla-200',
    }
</script>

<template>
  <component :is="as" class="rounded-tarjeta" :class="[VARIANTES[variante], { 'p-4 md:p-5': !sinPadding }]">
    <slot />
  </component>
</template>
