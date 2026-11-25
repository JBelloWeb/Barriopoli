<script setup>
  defineProps({
    tono: {
      type: String,
      default: 'marca',
      validator: (v) => ['marca', 'pendiente', 'publicado', 'rechazado', 'cancelado', 'neutro', 'contador'].includes(v),
    },
  })

  const TONOS = {
    marca: 'bg-violeta-100 text-violeta-700',
    pendiente: 'bg-durazno-100 text-alerta',
    publicado: 'bg-exito/10 text-exito',
    rechazado: 'bg-error/10 text-error',
    cancelado: 'bg-error/10 text-error',
    neutro: 'bg-niebla-100 text-niebla-600',
    contador: 'min-w-5 h-5 justify-center bg-durazno-200 px-1.5 text-violeta-800 ring-2 ring-papel',
  }
</script>

<template>
  <span class="inline-flex items-center gap-1 rounded-full text-xs font-bold" :class="[TONOS[tono], tono === 'contador' ? '' : 'px-2.5 py-1']">
    <slot />
  </span>
</template>
