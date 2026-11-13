<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })
defineProps({ solicitud: { type: Object, default: null } })

const page = usePage()
const auth = computed(() => page.props.auth)
const fila = 'flex min-h-12 items-center rounded-lg px-3 hover:bg-gray-100'
</script>

<template>
  <div class="mx-auto max-w-lg p-5">
    <h1 class="text-2xl font-bold">{{ auth.user.name }}</h1>
    <p class="text-gray-600">{{ auth.user.email }} · {{ auth.user.rol }}</p>

    <Link v-if="!auth.user.verificado" href="/email/verify" class="mt-4 block rounded-lg bg-crema p-3">
      Verificá tu email para poder publicar.
    </Link>

    <p v-if="solicitud?.estado === 'pendiente'" class="mt-4 rounded-lg bg-crema p-3">Tu solicitud está en revisión.</p>
    <p v-if="solicitud?.estado === 'rechazada'" class="mt-4 rounded-lg bg-red-50 p-3">
      Rechazamos tu solicitud: {{ solicitud.motivo_rechazo }}
    </p>

    <nav class="mt-6 flex flex-col">
      <Link v-if="auth.puede.publicar" href="/mis-eventos" :class="fila">Mis eventos</Link>
      <Link v-if="auth.user.rol === 'vecino' && solicitud?.estado !== 'pendiente'" href="/emprendedor/solicitud" :class="fila">Quiero publicar eventos</Link>
      <Link v-if="auth.puede.moderar" href="/moderacion" :class="fila">Moderación</Link>
      <Link href="/logout" method="post" as="button" :class="[fila, 'w-full text-red-700']">Salir</Link>
    </nav>
  </div>
</template>
