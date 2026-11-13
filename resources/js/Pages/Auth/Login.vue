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
    <Link href="/forgot-password" class="mt-3 block min-h-11 py-2 text-center underline">Olvidé mi contraseña</Link>

    <div class="mt-6 border-t border-gray-200 pt-6 text-center">
      <p class="mb-3 text-gray-600">¿Primera vez en Barriopoli?</p>
      <Link href="/register" class="flex min-h-12 w-full items-center justify-center rounded-lg border-2 border-violeta-oscuro font-bold text-violeta-oscuro hover:bg-violeta-oscuro hover:text-white">
        Crear cuenta
      </Link>
    </div>
  </AuthLayout>
</template>
