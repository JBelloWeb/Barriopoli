<script setup>
// Reemplaza a window.prompt: en mobile es incómodo y no se puede estilar ni validar.
import { ref, watch } from 'vue'
import BottomSheet from '@/components/BottomSheet.vue'
import Boton from '@/components/ui/Boton.vue'
import CampoArea from '@/components/ui/CampoArea.vue'

const abierto = defineModel({ type: Boolean, default: false })
defineProps({ titulo: { type: String, default: 'Motivo del rechazo' }, procesando: { type: Boolean, default: false } })
const emit = defineEmits(['confirmar'])

const motivo = ref('')
watch(abierto, (valor) => { if (valor) motivo.value = '' })
</script>

<template>
  <BottomSheet v-model="abierto" :titulo="titulo">
    <form class="space-y-4" @submit.prevent="motivo.trim() && emit('confirmar', motivo.trim())">
      <CampoArea v-model="motivo" etiqueta="Contale qué falta o qué corregir" :filas="4" maxlength="500" required ayuda="Lo ve quien hizo la publicación." />
      <div class="flex gap-2">
        <Boton variante="secundario" class="flex-1" @click="abierto = false">Cancelar</Boton>
        <Boton tipo="submit" variante="peligro" class="flex-1" :cargando="procesando">Rechazar</Boton>
      </div>
    </form>
  </BottomSheet>
</template>
