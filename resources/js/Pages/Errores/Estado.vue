<script setup>
// Páginas de error con la identidad de marca. Sin AppLayout: puede fallar justo lo que el layout necesita.
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import Isotipo from '@/components/marca/Isotipo.vue'
import Onda from '@/components/marca/Onda.vue'
import Boton from '@/components/ui/Boton.vue'

const props = defineProps({ estado: { type: Number, required: true } })

const TEXTOS = {
  403: ['Este lugar es solo para vecinos con permiso', 'No tenés acceso a esta página.'],
  404: ['Nos perdimos en el barrio', 'La página que buscás no existe o se mudó.'],
  419: ['La página se quedó dormida', 'Pasó mucho tiempo sin actividad. Recargá y probá de nuevo.'],
  429: ['Vamos más despacio', 'Hiciste muchos pedidos seguidos. Esperá un momento.'],
  500: ['Algo se rompió', 'Ya estamos en eso. Probá de nuevo en un rato.'],
  503: ['Estamos de mantenimiento', 'Volvemos enseguida.'],
}
const texto = computed(() => TEXTOS[props.estado] ?? TEXTOS[500])
const recargar = () => window.location.reload()
</script>

<template>
  <Head :title="texto[0]" />
  <main class="relative flex min-h-dvh flex-col items-center justify-center overflow-hidden bg-papel px-6 text-center">
    <Onda class="pointer-events-none absolute -left-6 -top-6 w-36 text-violeta-500 md:w-56" />
    <Onda espejo class="pointer-events-none absolute -bottom-6 -right-6 w-36 text-violeta-500 md:w-56" />

    <Isotipo class="relative h-24 w-auto" />
    <p class="relative mt-6"><span class="pastilla">Error {{ estado }}</span></p>
    <h1 class="titulo-marca relative mt-4 max-w-md text-4xl text-violeta-700">{{ texto[0] }}</h1>
    <p class="relative mt-2 max-w-sm text-lg text-niebla-600">{{ texto[1] }}</p>
    <div class="relative mt-8 flex flex-wrap justify-center gap-3">
      <Boton href="/barrios" icono="pin">Volver al mapa</Boton>
      <Boton v-if="estado === 419" variante="secundario" @click="recargar">Recargar</Boton>
    </div>
  </main>
</template>
