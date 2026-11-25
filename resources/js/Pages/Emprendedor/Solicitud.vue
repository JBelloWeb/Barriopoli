<script setup>
  import { useForm } from '@inertiajs/vue3'
  import AppLayout from '@/layouts/AppLayout.vue'
  import CampoTexto from '@/components/CampoTexto.vue'
  import Onda from '@/components/marca/Onda.vue'
  import Boton from '@/components/ui/Boton.vue'
  import CampoArea from '@/components/ui/CampoArea.vue'
  import Tarjeta from '@/components/ui/Tarjeta.vue'

  defineOptions({ layout: AppLayout })
  const form = useForm({ nombre_emprendimiento: '', descripcion: '', enlace: '' })


  const pasos = [
    ['Contanos', 'Qué hacés y dónde podemos conocerte.'],
    ['Lo revisamos', 'Un moderador valida tu emprendimiento.'],
    ['Publicás', 'Tus eventos aparecen en el mapa del barrio.'],
  ]
</script>

<template>
  <div class="mx-auto max-w-lg px-4 pb-10 pt-[max(1rem,env(safe-area-inset-top))]">
    <Boton href="/perfil" variante="fantasma" icono="atras" chico class="-ml-2">Perfil</Boton>

    <Tarjeta variante="marca" class="relative mt-3 overflow-hidden">
      <Onda espejo class="pointer-events-none absolute -right-4 -top-4 w-28 text-violeta-400" />
      <h1 class="titulo-marca relative text-3xl text-durazno-200">Mostrá lo que hacés en tu barrio</h1>
      <p class="relative mt-1 font-bold">Sumate como emprendedor y publicá tus eventos.</p>
      <ol class="relative mt-5 space-y-3">
        <li v-for="([titulo, texto], i) in pasos" :key="titulo" class="flex items-start gap-3">
          <span class="grid size-9 shrink-0 place-items-center rounded-full bg-durazno-200 text-lg font-bold text-violeta-700">{{ i + 1 }}</span>
          <span><span class="block text-xl font-bold leading-tight text-durazno-200">{{ titulo }}</span><span class="text-sm font-semibold">{{ texto }}</span></span>
        </li>
      </ol>
    </Tarjeta>

    <Tarjeta as="form" class="mt-4 space-y-5" @submit.prevent="form.post('/emprendedor/solicitud')">
      <CampoTexto v-model="form.nombre_emprendimiento" etiqueta="Nombre del emprendimiento" required :error="form.errors.nombre_emprendimiento" />
      <CampoArea v-model="form.descripcion" etiqueta="Contanos qué hacés" required placeholder="Ferias, talleres, música, gastronomía…" :error="form.errors.descripcion" />
      <CampoTexto v-model="form.enlace" etiqueta="Web o red social (opcional)" tipo="url" inputmode="url" placeholder="https://" :error="form.errors.enlace" />
      <Boton tipo="submit" bloque :cargando="form.processing">Enviar solicitud</Boton>
    </Tarjeta>
  </div>
</template>
