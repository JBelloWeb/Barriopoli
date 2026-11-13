<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/layouts/AuthLayout.vue'
import CampoTexto from '@/components/CampoTexto.vue'

const form = useForm({ email: '', password: '', remember: true })
const enviar = () => form.post('/login', { onFinish: () => form.reset('password') })
</script>

<template>
  <AuthLayout titulo="Ingresá">
    <form class="space-y-4" @submit.prevent="enviar">
      <CampoTexto v-model="form.email" etiqueta="Email" tipo="email" inputmode="email" autocomplete="email" required :error="form.errors.email" />
      <CampoTexto v-model="form.password" etiqueta="Contraseña" tipo="password" autocomplete="current-password" required :error="form.errors.password" />
      <label class="flex min-h-11 items-center gap-2">
        <input v-model="form.remember" type="checkbox" class="size-5" /> Mantener la sesión
      </label>
      <button :disabled="form.processing" class="min-h-12 w-full rounded-lg bg-violeta-oscuro font-bold text-white disabled:opacity-60">Ingresar</button>
    </form>
    <div class="mt-5 flex flex-col items-center">
      <Link href="/forgot-password" class="min-h-11 py-2 underline">Olvidé mi contraseña</Link>
      <Link href="/register" class="min-h-11 py-2 underline">Crear una cuenta</Link>
    </div>
  </AuthLayout>
</template>
