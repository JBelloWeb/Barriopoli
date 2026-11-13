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
  <div class="flex snap-x gap-2 overflow-x-auto px-4 py-2 [scrollbar-width:none]" role="group" aria-label="Filtrar por categoría">
    <button
      v-for="categoria in categorias"
      :key="categoria.id"
      type="button"
      :aria-pressed="seleccion.includes(categoria.id)"
      class="flex min-h-11 shrink-0 snap-start items-center gap-1.5 rounded-full border bg-white px-3 shadow-sm"
      :style="seleccion.includes(categoria.id) ? { backgroundColor: categoria.color, borderColor: categoria.color, color: 'white' } : undefined"
      @click="alternar(categoria.id)"
    >
      <span aria-hidden="true">{{ categoria.icono }}</span>
      {{ categoria.nombre }}
    </button>
  </div>
</template>
