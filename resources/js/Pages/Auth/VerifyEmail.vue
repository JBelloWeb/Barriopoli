<script setup>
import { computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import AuthLayout from '@/layouts/AuthLayout.vue'
import Boton from '@/components/ui/Boton.vue'

const form = useForm({})
const page = usePage()
const enviado = computed(() => page.props.status === 'verification-link-sent')
</script>

<template>
  <AuthLayout titulo="Verificá tu email">
    <p class="leading-relaxed">Te enviamos un enlace. Lo necesitás para solicitar ser emprendedor y publicar eventos.</p>
    <p v-if="enviado" role="status" class="mt-3 font-bold text-exito">Te mandamos un enlace nuevo.</p>
    <Boton bloque class="mt-5" :cargando="form.processing" @click="form.post('/email/verification-notification')">Reenviar email</Boton>

    <template #pie>
      <Boton href="/logout" method="post" as="button" variante="fantasma" icono="salir">Salir</Boton>
    </template>
  </AuthLayout>
</template>
