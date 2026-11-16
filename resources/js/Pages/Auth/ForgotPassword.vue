<script setup>
import { useForm } from '@inertiajs/vue3'
import AuthLayout from '@/layouts/AuthLayout.vue'
import CampoTexto from '@/components/CampoTexto.vue'
import Boton from '@/components/ui/Boton.vue'

const form = useForm({ email: '' })
</script>

<template>
  <AuthLayout titulo="Recuperá tu contraseña" subtitulo="Te mandamos un enlace por email">
    <form class="space-y-4" @submit.prevent="form.post('/forgot-password')">
      <CampoTexto v-model="form.email" etiqueta="Email" tipo="email" inputmode="email" autocomplete="email" required :error="form.errors.email" />
      <Boton tipo="submit" bloque :cargando="form.processing">Enviar enlace</Boton>
    </form>

    <template #pie>
      <Boton href="/login" variante="fantasma" icono="atras">Volver a ingresar</Boton>
    </template>
  </AuthLayout>
</template>
