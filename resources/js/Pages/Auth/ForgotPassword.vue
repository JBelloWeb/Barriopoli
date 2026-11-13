<script setup>
import { useForm } from '@inertiajs/vue3'
import AuthLayout from '@/layouts/AuthLayout.vue'
import CampoTexto from '@/components/CampoTexto.vue'

const form = useForm({ email: '' })
</script>

<template>
  <AuthLayout titulo="Recuperá tu contraseña">
    <form class="space-y-4" @submit.prevent="form.post('/forgot-password')">
      <CampoTexto v-model="form.email" etiqueta="Email" tipo="email" inputmode="email" autocomplete="email" required :error="form.errors.email" />
      <button :disabled="form.processing" class="min-h-12 w-full rounded-lg bg-violeta-oscuro font-bold text-white disabled:opacity-60">Enviar enlace</button>
    </form>
  </AuthLayout>
</template>
