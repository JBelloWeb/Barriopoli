<script setup>
import { usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Boton from '@/components/ui/Boton.vue'
import EstadoVacio from '@/components/ui/EstadoVacio.vue'
import Insignia from '@/components/ui/Insignia.vue'
import Tarjeta from '@/components/ui/Tarjeta.vue'
import { pastillasEvento } from '@/lib/fechas'
import { nombreLegible } from '@/lib/texto'

defineOptions({ layout: AppLayout })
defineProps({ eventos: { type: Array, required: true } })

const page = usePage()

const estados = {
  pendiente: { texto: 'En revisión', tono: 'pendiente' },
  publicado: { texto: 'Publicado', tono: 'publicado' },
  rechazado: { texto: 'Rechazado', tono: 'rechazado' },
}
</script>

<template>
  <div class="mx-auto max-w-3xl px-4 pb-10 pt-[max(1rem,env(safe-area-inset-top))] md:px-8">
    <header class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="titulo-marca text-3xl text-violeta-700 md:text-4xl">{{ page.props.auth.puede.moderar ? 'Todos los eventos' : 'Mis eventos' }}</h1>
        <p class="mt-0.5 font-bold text-niebla-600">Lo que publicaste en Barriopoli</p>
      </div>
      <Boton href="/eventos/crear" icono="mas" chico>Nuevo evento</Boton>
    </header>

    <ul v-if="eventos.length" class="mt-6 space-y-3">
      <Tarjeta v-for="evento in eventos" :key="evento.id" as="li">
        <div class="flex flex-wrap items-center gap-2">
          <Insignia :tono="estados[evento.estado].tono">{{ estados[evento.estado].texto }}</Insignia>
          <span class="flex items-center gap-1.5 text-sm font-bold text-niebla-600">
            <span class="size-2.5 rounded-full" :style="{ backgroundColor: evento.categoria.color }" aria-hidden="true"></span>
            {{ evento.categoria.nombre }}
          </span>
        </div>
        <h2 class="mt-2 text-lg font-bold leading-tight">{{ evento.titulo }}</h2>
        <div class="mt-2 flex flex-wrap gap-1.5">
          <span v-for="p in pastillasEvento(evento.inicio, evento.fin)" :key="p" class="pastilla px-2.5 py-1 text-sm">{{ p }}</span>
          <span class="inline-flex items-center rounded-full bg-violeta-50 px-2.5 py-1 text-sm font-bold text-violeta-700">{{ nombreLegible(evento.barrio.nombre) }}</span>
        </div>
        <p v-if="evento.motivo_rechazo" class="mt-3 rounded-2xl bg-error/8 px-3 py-2 text-sm text-error">
          <span class="font-bold">Motivo:</span> {{ evento.motivo_rechazo }}
        </p>
        <div class="-ml-2 mt-2 flex flex-wrap gap-x-1">
          <Boton :href="`/eventos/${evento.id}`" variante="fantasma" chico>Ver</Boton>
          <Boton :href="`/eventos/${evento.id}/editar`" variante="fantasma" icono="editar" chico>Editar</Boton>
          <Boton :href="`/eventos/${evento.id}/duplicar`" variante="fantasma" icono="copiar" chico>Duplicar</Boton>
        </div>
      </Tarjeta>
    </ul>
    <EstadoVacio v-else titulo="Mostrá lo que pasa en tu barrio" texto="Todavía no publicaste eventos.">
      <Boton href="/eventos/crear" icono="mas">Publicar el primero</Boton>
    </EstadoVacio>
  </div>
</template>
