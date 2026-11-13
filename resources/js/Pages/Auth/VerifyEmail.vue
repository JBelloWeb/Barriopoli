<script setup>
import { computed } from 'vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import AuthLayout from '@/layouts/AuthLayout.vue'

const form = useForm({})
const page = usePage()
const enviado = computed(() => page.props.status === 'verification-link-sent')
</script>

<template>
  <AuthLayout titulo="Verificá tu email">
    <p>Te enviamos un enlace. Lo necesitás para solicitar ser emprendedor y publicar eventos.</p>
    <p v-if="enviado" class="mt-3 font-bold">Te mandamos un enlace nuevo.</p>
    <button :disabled="form.processing" class="mt-5 min-h-12 w-full rounded-lg bg-violeta-oscuro font-bold text-white" @click="form.post('/email/verification-notification')">
      Reenviar email
    </button>
    <Link href="/logout" method="post" as="button" class="mt-3 min-h-11 w-full underline">Salir</Link>
  </AuthLayout>
</template>
