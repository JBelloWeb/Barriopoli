<script setup>
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

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
    class="min-h-11 shrink-0 rounded-full border px-3 text-sm"
    :class="esPropio ? 'border-violeta-oscuro bg-violeta-oscuro text-white' : 'border-gray-300 bg-white'"
    :aria-pressed="esPropio"
    @click="alternar"
  >
    {{ esPropio ? '🏠 Mi barrio' : 'Es mi barrio' }}
  </button>
</template>
