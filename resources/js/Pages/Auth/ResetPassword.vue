<script setup>
import { useForm } from '@inertiajs/vue3'
import AuthLayout from '@/layouts/AuthLayout.vue'
import CampoTexto from '@/components/CampoTexto.vue'
import Boton from '@/components/ui/Boton.vue'

const props = defineProps({ email: String, token: String })
const form = useForm({ token: props.token, email: props.email, password: '', password_confirmation: '' })
const enviar = () => form.post('/reset-password', { onFinish: () => form.reset('password', 'password_confirmation') })
</script>

<template>
  <AuthLayout titulo="Nueva contraseña">
    <form class="space-y-4" @submit.prevent="enviar">
      <CampoTexto v-model="form.email" etiqueta="Email" tipo="email" autocomplete="email" required :error="form.errors.email" />
      <CampoTexto v-model="form.password" etiqueta="Contraseña nueva" tipo="password" autocomplete="new-password" required ayuda="Mínimo 8 caracteres, con letras y números." :error="form.errors.password" />
      <CampoTexto v-model="form.password_confirmation" etiqueta="Repetí la contraseña" tipo="password" autocomplete="new-password" required />
      <Boton tipo="submit" bloque :cargando="form.processing">Guardar</Boton>
    </form>
  </AuthLayout>
</template>
