<script setup>
import { computed, ref } from 'vue'
import { Link, router, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import CampoTexto from '@/components/CampoTexto.vue'

defineOptions({ layout: AppLayout })
defineProps({
  solicitud: { type: Object, default: null },
  tokens: { type: Array, default: () => [] },
})

const page = usePage()
const auth = computed(() => page.props.auth)
const fila = 'flex min-h-12 items-center rounded-lg px-3 hover:bg-gray-100'

// El token en texto plano llega una sola vez por flash.
const tokenNuevo = computed(() => page.flash?.token ?? null)
const copiado = ref(false)
const form = useForm({ nombre: '' })

function crearToken() {
  copiado.value = false
  form.post('/perfil/tokens', { preserveScroll: true, onSuccess: () => form.reset() })
}

async function copiar() {
  try {
    await navigator.clipboard.writeText(tokenNuevo.value)
    copiado.value = true
  } catch {
    copiado.value = false
  }
}

function revocar(token) {
  if (confirm(`¿Revocar "${token.nombre}"? Lo que lo use deja de funcionar.`)) {
    router.delete(`/perfil/tokens/${token.id}`, { preserveScroll: true })
  }
}

const fecha = (iso) => new Date(iso).toLocaleDateString('es-AR', { day: 'numeric', month: 'short', year: 'numeric' })
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

    <section v-if="auth.puede.publicar" class="mt-8 border-t border-gray-200 pt-6" aria-labelledby="titulo-api">
      <h2 id="titulo-api" class="text-lg font-bold">Acceso a la API</h2>
      <p class="mt-1 text-sm text-gray-600">
        Conectá tu sitio o sistema para publicar eventos sin entrar a la app. Cada token vence a los 90 días.
      </p>

      <div v-if="tokenNuevo" class="mt-4 rounded-lg bg-crema p-3" role="status">
        <p class="font-bold">Copialo ahora: no se vuelve a mostrar.</p>
        <code class="mt-2 block break-all rounded bg-white p-2 text-sm">{{ tokenNuevo }}</code>
        <button type="button" class="mt-2 min-h-12 w-full rounded-lg border border-violeta-oscuro font-bold text-violeta-oscuro" @click="copiar">
          {{ copiado ? 'Copiado' : 'Copiar token' }}
        </button>
      </div>

      <form class="mt-4 space-y-3" @submit.prevent="crearToken">
        <CampoTexto v-model="form.nombre" etiqueta="Nombre (p. ej. Mi sitio web)" required maxlength="60" :error="form.errors.nombre" />
        <button :disabled="form.processing" class="min-h-12 w-full rounded-lg bg-violeta-oscuro font-bold text-white">Crear token</button>
      </form>

      <ul v-if="tokens.length" class="mt-4 divide-y divide-gray-200">
        <li v-for="token in tokens" :key="token.id" class="flex items-center gap-3 py-3">
          <div class="min-w-0 flex-1">
            <p class="truncate font-bold">{{ token.nombre }}</p>
            <p class="text-sm text-gray-600">
              {{ token.ultimo_uso ? `Usado el ${fecha(token.ultimo_uso)}` : 'Sin usar' }}
              <template v-if="token.expira"> · vence el {{ fecha(token.expira) }}</template>
            </p>
          </div>
          <button type="button" class="min-h-12 rounded-lg px-3 font-bold text-red-700 hover:bg-red-50" @click="revocar(token)">Revocar</button>
        </li>
      </ul>
    </section>
  </div>
</template>
