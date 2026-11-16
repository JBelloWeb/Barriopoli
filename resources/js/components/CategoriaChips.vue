<script setup>
const seleccion = defineModel({ type: Array, default: () => [] })
defineProps({ categorias: { type: Array, required: true } })

const alternar = (id) => {
  seleccion.value = seleccion.value.includes(id)
    ? seleccion.value.filter((actual) => actual !== id)
    : [...seleccion.value, id]
}
</script>

<template>
  <!-- Scroll horizontal con snap en vez de un <select>: se ven las opciones y se elige con un toque. -->
  <div class="flex snap-x gap-2 overflow-x-auto px-4 py-2.5 [scrollbar-width:none]" role="group" aria-label="Filtrar por categoría">
    <button
      v-for="categoria in categorias"
      :key="categoria.id"
      type="button"
      :aria-pressed="seleccion.includes(categoria.id)"
      class="flex min-h-11 shrink-0 snap-start items-center gap-2 rounded-full border-2 px-4 font-semibold transition motion-reduce:transition-none"
      :class="seleccion.includes(categoria.id)
        ? 'border-violeta-700 bg-violeta-700 text-white'
        : 'border-niebla-200 bg-white text-tinta hover:border-violeta-300'"
      @click="alternar(categoria.id)"
    >
      <!-- El color de la categoría va en un punto: como texto no garantiza contraste. -->
      <span class="size-2.5 rounded-full ring-2 ring-white/70" :style="{ backgroundColor: categoria.color }" aria-hidden="true"></span>
      <span aria-hidden="true">{{ categoria.icono }}</span>
      {{ categoria.nombre }}
    </button>
  </div>
</template>
