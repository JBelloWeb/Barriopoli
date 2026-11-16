<script setup>
import Isotipo from '@/components/marca/Isotipo.vue'

// "apilado": isotipo arriba (portadas, login). "horizontal": isotipo a la izquierda (cabeceras).
defineProps({
  orientacion: { type: String, default: 'horizontal', validator: (v) => ['horizontal', 'apilado'].includes(v) },
})
</script>

<template>
  <span
    class="inline-flex items-center text-violeta-500"
    :class="orientacion === 'apilado' ? 'flex-col gap-3' : 'gap-2'"
  >
    <Isotipo :class="orientacion === 'apilado' ? 'h-20 w-auto' : 'h-8 w-auto'" />
    <span
      class="titulo-marca"
      :class="orientacion === 'apilado' ? 'text-5xl' : 'text-2xl'"
    >Barriopoli</span>
  </span>
</template>
