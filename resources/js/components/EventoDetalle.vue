<script setup>
  import { computed } from 'vue';
  import { Link } from '@inertiajs/vue3';
  import AgendaBoton from '@/components/AgendaBoton.vue';
  import Boton from '@/components/ui/Boton.vue';
  import Icono from '@/components/ui/Icono.vue' ;
  import Insignia from '@/components/ui/Insignia.vue';
  import { pastillasEvento } from '@/lib/fechas';
  import { nombreLegible } from '@/lib/texto';

  const props = defineProps({ evento: { type: Object, required: true } });
  const pastillas = computed(() => pastillasEvento(props.evento.inicio, props.evento.fin));
  const barrio = computed(() => nombreLegible(props.evento.barrio.nombre));
</script>

<template>
  <article>
    <img v-if="evento.imagen_url" :src="evento.imagen_url" :alt="evento.titulo" loading="lazy" class="mb-4 aspect-video w-full rounded-3xl object-cover" />

    <div class="flex flex-wrap items-center gap-2">
      <span class="flex items-center gap-2 font-bold">
        <span class="size-3 rounded-full" :style="{ backgroundColor: evento.categoria.color }" aria-hidden="true"></span>
        <span aria-hidden="true">{{ evento.categoria.icono }}</span>{{ evento.categoria.nombre }}
      </span>
      <Insignia v-if="evento.cancelado" tono="cancelado">Cancelado</Insignia>
    </div>

    <div class="mt-3 flex flex-wrap gap-2">
      <span v-for="p in pastillas" :key="p" class="pastilla">{{ p }}</span>
    </div>

    <p class="mt-4 flex items-start gap-2 font-semibold">
      <Icono nombre="pin" class="mt-0.5 size-5 text-violeta-500" />
      <span>{{ evento.direccion }} · <span class="text-violeta-700">{{ barrio }}</span></span>
    </p>
    <p v-if="evento.organizador" class="mt-2 flex items-center gap-2 text-sm text-niebla-600">
      <Icono nombre="tienda" class="size-5 text-violeta-500" />
      <span>Organiza
        <Link v-if="evento.organizador_id" :href="`/emprendimientos/${evento.organizador_id}`" class="font-bold text-violeta-700 underline underline-offset-4">{{ evento.organizador }}</Link>
        <span v-else class="font-bold text-tinta">{{ evento.organizador }}</span>
      </span>
    </p>

    <p v-if="evento.descripcion" class="mt-4 whitespace-pre-line leading-relaxed">{{ evento.descripcion }}</p>

    <AgendaBoton v-if="evento.estado === 'publicado' && !evento.cancelado" :evento="evento" />

    <div v-if="!evento.cancelado" class="mt-2 flex flex-wrap justify-center gap-x-2">
      <Boton :href="`/eventos/${evento.id}`" variante="fantasma" chico>Ver página</Boton>
      <Boton v-if="evento.editable" :href="`/eventos/${evento.id}/editar`" variante="fantasma" icono="editar" chico>Editar</Boton>
    </div>
  </article>
</template>
