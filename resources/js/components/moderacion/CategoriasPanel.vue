<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import BottomSheet from '@/components/BottomSheet.vue'
import CampoTexto from '@/components/CampoTexto.vue'

defineProps({ categorias: { type: Array, required: true } })

const hojaAbierta = ref(false)
const edicionId = ref(null)
const form = useForm({ nombre: '', color: '#8e66cc', icono: '', activa: true, orden: 0 })

const abrir = (categoria = null) => {
  edicionId.value = categoria?.id ?? null
  form.clearErrors()
  form.nombre = categoria?.nombre ?? ''
  form.color = categoria?.color ?? '#8e66cc'
  form.icono = categoria?.icono ?? ''
  form.activa = categoria?.activa ?? true
  form.orden = categoria?.orden ?? 0
  hojaAbierta.value = true
}

const guardar = () => {
  const opciones = { preserveScroll: true, onSuccess: () => { hojaAbierta.value = false } }
  if (edicionId.value) form.put(`/moderacion/categorias/${edicionId.value}`, opciones)
  else form.post('/moderacion/categorias', opciones)
}

const eliminar = () => {
  if (window.confirm('¿Eliminar esta categoría?')) {
    router.delete(`/moderacion/categorias/${edicionId.value}`, {
      preserveScroll: true,
      onSuccess: () => { hojaAbierta.value = false },
    })
  }
}
</script>

<template>
  <button class="mt-4 min-h-12 w-full rounded-lg bg-violeta-oscuro font-bold text-white" @click="abrir()">Nueva categoría</button>

  <ul class="mt-3 divide-y">
    <li v-for="categoria in categorias" :key="categoria.id">
      <button class="flex min-h-14 w-full items-center gap-3 text-left" @click="abrir(categoria)">
        <span class="size-4 shrink-0 rounded-full" :style="{ backgroundColor: categoria.color }" aria-hidden="true"></span>
        <span class="flex-1">{{ categoria.icono }} {{ categoria.nombre }}</span>
        <span v-if="!categoria.activa" class="text-sm text-gray-500">Inactiva</span>
      </button>
    </li>
  </ul>

  <BottomSheet v-model="hojaAbierta" :titulo="edicionId ? 'Editar categoría' : 'Nueva categoría'">
    <form class="space-y-4" @submit.prevent="guardar">
      <CampoTexto v-model="form.nombre" etiqueta="Nombre" required :error="form.errors.nombre" />
      <CampoTexto v-model="form.icono" etiqueta="Ícono (un emoji)" :error="form.errors.icono" />
      <label class="flex min-h-11 items-center justify-between font-bold">
        Color <input v-model="form.color" type="color" class="h-11 w-16" />
      </label>
      <p v-if="form.errors.color" class="text-sm text-red-700">{{ form.errors.color }}</p>
      <label class="flex min-h-11 items-center gap-2">
        <input v-model="form.activa" type="checkbox" class="size-5" /> Activa
      </label>
      <button :disabled="form.processing" class="min-h-12 w-full rounded-lg bg-violeta-oscuro font-bold text-white">Guardar</button>
      <button v-if="edicionId" type="button" class="min-h-11 w-full text-red-700" @click="eliminar">Eliminar</button>
    </form>
  </BottomSheet>
</template>
