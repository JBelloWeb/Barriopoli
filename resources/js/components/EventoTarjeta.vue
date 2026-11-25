<script setup>
  import { computed } from 'vue';
  import Onda from '@/components/marca/Onda.vue';
  import Insignia from '@/components/ui/Insignia.vue';
  import { pastillasEvento } from '@/lib/fechas';
  import { nombreLegible } from '@/lib/texto';

  const props = defineProps({
    evento: { type: Object, required: true },
    compacta: { type: Boolean, default: false }, 
  });
  defineEmits(['abrir']);

  const pastillas = computed(() => pastillasEvento(props.evento.inicio, props.evento.fin));
  const barrio = computed(() => nombreLegible(props.evento.barrio.nombre));
</script>

<template>
  <article
    class="group relative overflow-hidden rounded-tarjeta bg-white shadow-tarjeta transition duration-200 ease-marca hover:-translate-y-0.5 hover:shadow-flotante motion-reduce:transition-none motion-reduce:hover:translate-y-0"
    :class="[compacta ? 'flex' : 'flex sm:flex-col', { 'opacity-75': evento.cancelado }]"
  >
    <div
      class="relative shrink-0 overflow-hidden bg-violeta-500"
      :class="compacta ? 'w-24' : 'w-28 sm:aspect-[16/9] sm:w-auto'"
    >
      <img v-if="evento.imagen_url" :src="evento.imagen_url" alt="" loading="lazy" class="size-full object-cover" />
      <template v-else>
        <Onda class="absolute -left-4 -top-4 h-[130%] text-violeta-400" />
        <span class="absolute inset-0 grid place-items-center">
          <span class="grid place-items-center rounded-full bg-durazno-200 shadow-tarjeta" :class="compacta ? 'size-12 text-2xl' : 'size-12 text-2xl sm:size-16 sm:text-3xl'" aria-hidden="true">{{ evento.categoria.icono }}</span>
        </span>
      </template>
    </div>

    <div class="flex min-w-0 flex-1 flex-col p-4" :class="compacta ? 'py-3' : 'py-3 sm:py-4'">
      <p class="flex items-center gap-2 text-sm font-bold text-niebla-600">
        <span class="size-2.5 shrink-0 rounded-full" :style="{ backgroundColor: evento.categoria.color }" aria-hidden="true"></span>
        {{ evento.categoria.nombre }}
        <Insignia v-if="evento.cancelado" tono="cancelado" class="ml-auto">Cancelado</Insignia>

        <span v-if="$slots.accion" class="relative z-10 -my-2 -mr-2" :class="{ 'ml-auto': !evento.cancelado }"><slot name="accion" /></span>
      </p>
      <h3 class="mt-1 text-lg font-bold leading-tight text-tinta" :class="compacta ? 'line-clamp-1' : 'line-clamp-2'">

        <button type="button" class="text-left after:absolute after:inset-0 after:content-[''] focus-visible:outline-none" @click="$emit('abrir', evento)">
          {{ evento.titulo }}
        </button>
      </h3>
      <div class="mt-3 flex flex-wrap gap-1.5">
        <span v-for="p in pastillas" :key="p" class="pastilla px-2.5 py-1 text-sm">{{ p }}</span>
        <span v-if="!compacta" class="inline-flex items-center rounded-full bg-violeta-50 px-2.5 py-1 text-sm font-bold text-violeta-700">{{ barrio }}</span>
      </div>
      <p v-if="compacta" class="mt-1.5 truncate text-sm text-niebla-600">{{ evento.direccion }} · {{ barrio }}</p>
    </div>

    <span class="pointer-events-none absolute inset-0 rounded-tarjeta ring-violeta-700 group-has-[:focus-visible]:ring-3" aria-hidden="true"></span>
  </article>
</template>
