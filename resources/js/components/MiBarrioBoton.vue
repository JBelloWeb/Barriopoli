<script setup>
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

import Icono from '@/components/ui/Icono.vue'

const props = defineProps({ barrio: { type: Object, required: true } })
const page = usePage()

const esPropio = computed(() => page.props.barrioPropioId === props.barrio.id)

const alternar = () => {
  const desmarcar = esPropio.value
  const opciones = {
    preserveScroll: true,
    preserveState: true,
    only: ['favoritosIds', 'barrioPropioId'],
    // Marcarlo también lo agrega a favoritos (lo mismo hace el servidor).
    optimistic: (actuales) => (desmarcar
      ? { barrioPropioId: null }
      : {
          barrioPropioId: props.barrio.id,
          favoritosIds: actuales.favoritosIds.includes(props.barrio.id)
            ? actuales.favoritosIds
            : [...actuales.favoritosIds, props.barrio.id],
        }),
  }
  if (desmarcar) router.delete('/mi-barrio', opciones)
  else router.put(`/mi-barrio/${props.barrio.slug}`, {}, opciones)
}
</script>

<template>
  <button
    v-if="page.props.auth.user"
    type="button"
    class="inline-flex min-h-11 shrink-0 items-center gap-1.5 rounded-full px-4 text-sm font-semibold transition motion-reduce:transition-none"
    :class="esPropio ? 'bg-violeta-700 text-white' : 'bg-durazno-200 text-violeta-700 hover:bg-durazno-400/60'"
    :aria-pressed="esPropio"
    @click="alternar"
  >
    <Icono nombre="casa" :relleno="esPropio" class="size-5" />
    {{ esPropio ? 'Mi barrio' : 'Es mi barrio' }}
  </button>
</template>
