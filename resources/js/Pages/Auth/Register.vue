<script setup>
  import { useForm } from '@inertiajs/vue3'
  import AuthLayout from '@/layouts/AuthLayout.vue'
  import CampoTexto from '@/components/CampoTexto.vue'
  import Boton from '@/components/ui/Boton.vue'

  const form = useForm({ name: '', email: '', password: '', password_confirmation: '' })
  const enviar = () => form.post('/register', { onFinish: () => form.reset('password', 'password_confirmation') })
</script>

<template>
  <AuthLayout titulo="Creá tu cuenta" subtitulo="Conectá con tu barrio">
    <form class="space-y-4" @submit.prevent="enviar">
      <CampoTexto v-model="form.name" etiqueta="Nombre" autocomplete="name" required :error="form.errors.name" />
      <CampoTexto v-model="form.email" etiqueta="Email" tipo="email" inputmode="email" autocomplete="email" required :error="form.errors.email" />
      <CampoTexto v-model="form.password" etiqueta="Contraseña" tipo="password" autocomplete="new-password" required ayuda="Mínimo 8 caracteres, con letras y números." :error="form.errors.password" />
      <CampoTexto v-model="form.password_confirmation" etiqueta="Repetí la contraseña" tipo="password" autocomplete="new-password" required />
      <Boton tipo="submit" bloque :cargando="form.processing">Crear cuenta</Boton>
    </form>

    <template #pie>
      <Boton href="/login" variante="fantasma">Ya tengo cuenta</Boton>
    </template>
  </AuthLayout>
</template>
