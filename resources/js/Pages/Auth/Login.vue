<script setup>
  import { useForm } from '@inertiajs/vue3'
  import AuthLayout from '@/layouts/AuthLayout.vue'
  import CampoTexto from '@/components/CampoTexto.vue'
  import Boton from '@/components/ui/Boton.vue'

  const form = useForm({ email: '', password: '', remember: true })
  const enviar = () => form.post('/login', { onFinish: () => form.reset('password') })
</script>

<template>
  <AuthLayout titulo="Ingresá" subtitulo="Descubrí lo que pasa en tu barrio">
    <form class="space-y-4" @submit.prevent="enviar">
      <CampoTexto v-model="form.email" etiqueta="Email" tipo="email" inputmode="email" autocomplete="email" required :error="form.errors.email" />
      <CampoTexto v-model="form.password" etiqueta="Contraseña" tipo="password" autocomplete="current-password" required :error="form.errors.password" />
      <label class="flex min-h-11 cursor-pointer items-center gap-3 font-semibold">
        <input v-model="form.remember" type="checkbox" class="size-5 accent-violeta-700" /> Mantener la sesión
      </label>
      <Boton tipo="submit" bloque :cargando="form.processing">Ingresar</Boton>
    </form>
    <Boton href="/forgot-password" variante="fantasma" chico bloque class="mt-2">Olvidé mi contraseña</Boton>

    <template #pie>
      <p class="mb-3 font-semibold text-niebla-600">¿Primera vez en Barriopoli?</p>
      <Boton href="/register" variante="secundario" bloque>Crear cuenta</Boton>
    </template>
  </AuthLayout>
</template>
