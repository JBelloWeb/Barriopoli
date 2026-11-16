<script setup>
// Pantallas vacías con voz de marca. Si llega un barrio, se dibuja su silueta; si no, el isotipo.
import Isotipo from '@/components/marca/Isotipo.vue'
import SiluetaBarrio from '@/components/marca/SiluetaBarrio.vue'

defineProps({
  titulo: { type: String, default: 'Todo lo que pasa, a la vuelta.' },
  texto: { type: String, default: '' },
  geojson: { type: [Object, String], default: null },
})
</script>

<template>
  <div class="flex flex-col items-center px-6 py-12 text-center">
    <SiluetaBarrio v-if="geojson" :geojson="geojson" class="w-36" :contornos="4" />
    <Isotipo v-else variante="mono" class="h-20 text-lila-400" />
    <p class="mt-6 text-xl font-bold text-violeta-700">{{ titulo }}</p>
    <p v-if="texto" class="mt-1 max-w-xs text-niebla-600">{{ texto }}</p>
    <div v-if="$slots.default" class="mt-6"><slot /></div>
  </div>
</template>
