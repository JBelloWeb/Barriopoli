<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/layouts/AuthLayout.vue'
import CampoTexto from '@/components/CampoTexto.vue'

const form = useForm({ name: '', email: '', password: '', password_confirmation: '' })
const enviar = () => form.post('/register', { onFinish: () => form.reset('password', 'password_confirmation') })
</script>

<template>
  <AuthLayout titulo="Creá tu cuenta">
    <form class="space-y-4" @submit.prevent="enviar">
      <CampoTexto v-model="form.name" etiqueta="Nombre" autocomplete="name" required :error="form.errors.name" />
      <CampoTexto v-model="form.email" etiqueta="Email" tipo="email" inputmode="email" autocomplete="email" required :error="form.errors.email" />
      <CampoTexto v-model="form.password" etiqueta="Contraseña" tipo="password" autocomplete="new-password" required :error="form.errors.password" />
      <CampoTexto v-model="form.password_confirmation" etiqueta="Repetí la contraseña" tipo="password" autocomplete="new-password" required />
      <button :disabled="form.processing" class="min-h-12 w-full rounded-lg bg-violeta-oscuro font-bold text-white disabled:opacity-60">Crear cuenta</button>
    </form>
    <Link href="/login" class="mt-5 block min-h-11 py-2 text-center underline">Ya tengo cuenta</Link>
  </AuthLayout>
</template>
