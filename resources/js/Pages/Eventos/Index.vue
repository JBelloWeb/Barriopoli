<script setup>
    import { computed, ref, watch } from 'vue';
    import { InfiniteScroll, router, usePage } from '@inertiajs/vue3';
    import AppLayout from '@/layouts/AppLayout.vue';
    import BottomSheet from '@/components/BottomSheet.vue';
    import CategoriaChips from '@/components/CategoriaChips.vue';
    import EventoDetalle from '@/components/EventoDetalle.vue';
    import EventoTarjeta from '@/components/EventoTarjeta.vue'
    import Boton from '@/components/ui/Boton.vue';
    import EstadoVacio from '@/components/ui/EstadoVacio.vue';

    defineOptions({ layout: AppLayout });

    const props = defineProps({
      eventos: { type: Object, required: true },
      categorias: { type: Array, required: true },
      filtros: { type: Object, required: true },
    })

    const seleccion = ref(props.filtros.categorias)
    const activo = ref(null)
    const hojaAbierta = computed({
      get: () => activo.value !== null,
      set: (valor) => { if (!valor) activo.value = null },
    })

    const page = usePage()
    const alcance = ref(props.filtros.alcance ?? 'todos')
    const hayFavoritos = computed(() => (page.props.favoritosIds ?? []).length > 0)

    watch([seleccion, alcance], ([categorias, valor]) => {
      const params = { categorias, ...(valor === 'mis_barrios' ? { alcance: valor } : {}) }
      router.get('/eventos', params, { preserveState: true, preserveScroll: false, replace: true, reset: ['eventos'] })
    })

    const opcionesAlcance = [
      { valor: 'todos', texto: 'Toda la ciudad' },
      { valor: 'mis_barrios', texto: 'Mis barrios' },
    ]

    const limpiarFiltros = () => {
      seleccion.value = []
      alcance.value = 'todos'
    }
</script>

<template>
  <div class="mx-auto max-w-5xl">
    <header class="sticky top-0 z-10 bg-papel/95 pt-[max(1rem,env(safe-area-inset-top))] backdrop-blur md:px-4">
      <div class="flex flex-wrap items-end justify-between gap-3 px-4">
        <div>
          <h1 class="titulo-marca text-3xl text-violeta-700 md:text-4xl">Eventos</h1>
          <p class="mt-0.5 font-bold text-niebla-600">Lo que pasa en tus barrios</p>
        </div>

        <div v-if="hayFavoritos" role="radiogroup" aria-label="Alcance" class="flex rounded-full bg-niebla-100 p-1">
          <button
            v-for="o in opcionesAlcance"
            :key="o.valor"
            type="button"
            role="radio"
            :aria-checked="alcance === o.valor"
            class="min-h-10 rounded-full px-4 text-sm font-bold transition motion-reduce:transition-none"
            :class="alcance === o.valor ? 'bg-white text-violeta-700 shadow-sm' : 'text-niebla-600 hover:text-violeta-700'"
            @click="alcance = o.valor"
          >{{ o.texto }}</button>
        </div>
      </div>
      <CategoriaChips v-model="seleccion" :categorias="categorias" class="mt-2 border-b border-niebla-200" />
    </header>

    <InfiniteScroll data="eventos" :buffer="400">
      <ul class="grid gap-4 p-4 sm:grid-cols-2 md:px-8 lg:grid-cols-3">
        <li v-for="evento in eventos.data" :key="evento.id" class="flex">
          <EventoTarjeta :evento="evento" class="w-full" @abrir="activo = $event" />
        </li>
      </ul>
      <EstadoVacio
        v-if="!eventos.data.length"
        :texto="alcance === 'mis_barrios' ? 'No hay eventos próximos en tus barrios.' : 'No hay eventos con estos filtros.'"
      >
        <Boton v-if="seleccion.length || alcance !== 'todos'" variante="suave" @click="limpiarFiltros">Ver todos los eventos</Boton>
      </EstadoVacio>
    </InfiniteScroll>

    <BottomSheet v-model="hojaAbierta" :titulo="activo?.titulo ?? ''">
      <EventoDetalle v-if="activo" :evento="activo" />
    </BottomSheet>
  </div>
</template>
