<script setup>
    import AppLayout from '@/layouts/AppLayout.vue'
    import EventoDetalle from '@/components/EventoDetalle.vue'
    import Boton from '@/components/ui/Boton.vue'
    import Icono from '@/components/ui/Icono.vue'

    defineOptions({ layout: AppLayout })
    defineProps({ evento: { type: Object, required: true } })
</script>

<template>
  <div class="mx-auto max-w-xl px-4 pb-10 pt-[max(1rem,env(safe-area-inset-top))]">
    <nav class="-ml-2 flex flex-wrap gap-x-2" aria-label="Volver">
      <Boton href="/eventos" variante="fantasma" icono="atras" chico>Eventos</Boton>
      <Boton :href="`/barrios/${evento.barrio.slug}`" variante="fantasma" icono="pin" chico>Ver en el mapa</Boton>
    </nav>
    <h1 class="titulo-marca mt-3 text-3xl text-violeta-700 md:text-4xl">{{ evento.titulo }}</h1>
    <p v-if="evento.estado !== 'publicado'" class="mt-3 flex items-center gap-2 rounded-2xl bg-durazno-100 px-4 py-3 text-sm font-semibold">
      <Icono nombre="alerta" class="size-5 text-alerta" />
      {{ evento.estado === 'pendiente' ? 'En revisión: todavía no es público.' : 'Rechazado: no es público.' }}
    </p>
    <div class="mt-5"><EventoDetalle :evento="evento" /></div>
  </div>
</template>
