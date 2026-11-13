<script setup>
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import CampoTexto from '@/components/CampoTexto.vue'

defineOptions({ layout: AppLayout })
const form = useForm({ nombre_emprendimiento: '', descripcion: '', enlace: '' })
</script>

<template>
  <form class="mx-auto max-w-lg space-y-4 p-5" @submit.prevent="form.post('/emprendedor/solicitud')">
    <h1 class="text-2xl font-bold">Quiero publicar eventos</h1>
    <CampoTexto v-model="form.nombre_emprendimiento" etiqueta="Nombre del emprendimiento" required :error="form.errors.nombre_emprendimiento" />
    <div>
      <label for="descripcion" class="mb-1 block font-bold">Contanos qué hacés</label>
      <textarea id="descripcion" v-model="form.descripcion" rows="4" required class="w-full rounded-lg border border-gray-300 p-3 text-base"></textarea>
      <p v-if="form.errors.descripcion" class="mt-1 text-sm text-red-700">{{ form.errors.descripcion }}</p>
    </div>
    <CampoTexto v-model="form.enlace" etiqueta="Web o red social (opcional)" tipo="url" inputmode="url" :error="form.errors.enlace" />
    <button :disabled="form.processing" class="min-h-12 w-full rounded-lg bg-violeta-oscuro font-bold text-white">Enviar solicitud</button>
  </form>
</template>
