<script setup>
    import { computed } from 'vue'
    import { Link, usePage } from '@inertiajs/vue3'
    import AppLayout from '@/layouts/AppLayout.vue'
    import Onda from '@/components/marca/Onda.vue'
    import SiluetaBarrio from '@/components/marca/SiluetaBarrio.vue'
    import Boton from '@/components/ui/Boton.vue'
    import Icono from '@/components/ui/Icono.vue'
    import Insignia from '@/components/ui/Insignia.vue'
    import Tarjeta from '@/components/ui/Tarjeta.vue'
    import { ROLES } from '@/lib/roles'
    import { nombreLegible } from '@/lib/texto'

    defineOptions({ layout: AppLayout })
    defineProps({
      solicitud: { type: Object, default: null },
      emprendimiento: { type: String, default: null },
      miBarrio: { type: Object, default: null },
      cantidades: { type: Object, default: () => ({ favoritos: 0, agendados: 0 }) },
    })

    const page = usePage()
    const auth = computed(() => page.props.auth)
    const inicial = computed(() => auth.value.user.name.trim().charAt(0).toUpperCase())

    const fila = 'flex min-h-14 w-full items-center gap-3 px-4 text-left font-semibold hover:bg-violeta-50'
</script>

<template>
  <div class="pb-10">
    <header class="relative overflow-hidden rounded-b-[2rem] bg-violeta-600 px-4 pb-8 pt-[max(2.5rem,env(safe-area-inset-top))] text-papel">
      <Onda espejo class="pointer-events-none absolute -right-6 -top-6 w-40 text-violeta-400 md:w-56" />
      <div class="relative mx-auto flex max-w-lg items-center gap-4">
        <span class="grid size-16 shrink-0 place-items-center rounded-full bg-durazno-200 text-3xl font-bold text-violeta-700 ring-4 ring-violeta-400" aria-hidden="true">{{ inicial }}</span>
        <div class="min-w-0">
          <h1 class="titulo-marca truncate text-3xl text-durazno-200">{{ auth.user.name }}</h1>
          <p class="truncate text-sm font-semibold">{{ auth.user.email }}</p>
          <p class="mt-2 flex flex-wrap gap-1.5">
            <span class="pastilla px-2.5 py-0.5 text-xs">{{ ROLES[auth.user.rol] ?? auth.user.rol }}</span>
            <span v-if="emprendimiento" class="pastilla bg-papel! px-2.5 py-0.5 text-xs">{{ emprendimiento }}</span>
          </p>
        </div>
      </div>
    </header>

    <div class="mx-auto mt-5 max-w-lg space-y-4 px-4">
      <Link v-if="!auth.user.verificado" href="/email/verify" class="flex items-center gap-3 rounded-2xl bg-durazno-100 p-4 font-semibold">
        <Icono nombre="alerta" class="size-5 text-alerta" /> Verificá tu email para poder publicar.
        <Icono nombre="chevron" class="ml-auto size-5" />
      </Link>
      <p v-if="solicitud?.estado === 'pendiente'" class="flex items-center gap-3 rounded-2xl bg-durazno-100 p-4 font-semibold" role="status">
        <Icono nombre="reloj" class="size-5 text-violeta-700" /> Tu solicitud de emprendedor está en revisión.
      </p>
      <p v-if="solicitud?.estado === 'rechazada'" class="rounded-2xl bg-error/8 p-4 text-error">
        <span class="font-bold">Rechazamos tu solicitud:</span> {{ solicitud.motivo_rechazo }}
      </p>

      <!-- Mi barrio con su silueta: el recurso gráfico de la marca con datos reales. -->
      <Link v-if="miBarrio" :href="`/barrios/${miBarrio.slug}`" class="block">
        <Tarjeta class="flex items-center gap-4 transition hover:shadow-flotante motion-reduce:transition-none">
          <SiluetaBarrio :geojson="miBarrio.geojson" :contornos="3" :separacion="0.15" class="size-16 shrink-0" />
          <div class="min-w-0 flex-1">
            <p class="text-xs font-bold uppercase tracking-wider text-niebla-600">Mi barrio</p>
            <p class="titulo-marca truncate text-2xl text-violeta-700">{{ nombreLegible(miBarrio.nombre) }}</p>
            <p class="text-sm text-niebla-600">{{ cantidades.favoritos }} {{ cantidades.favoritos === 1 ? 'barrio guardado' : 'barrios guardados' }} · {{ cantidades.agendados }} en tu agenda</p>
          </div>
          <Icono nombre="chevron" class="size-5 text-violeta-500" />
        </Tarjeta>
      </Link>
      <Tarjeta v-else variante="suave" class="flex items-center gap-3">
        <Icono nombre="casa" class="size-6 text-violeta-700" />
        <p class="flex-1 font-semibold">Elegí tu barrio y la app abre directo ahí.</p>
        <Boton href="/barrios" variante="fantasma" chico>Elegir</Boton>
      </Tarjeta>

      <Tarjeta as="nav" sin-padding class="divide-y divide-niebla-200 overflow-hidden" aria-label="Opciones de cuenta">
        <Link href="/perfil/editar" :class="fila">
          <Icono nombre="editar" class="size-5 text-violeta-500" /> Editar perfil <Icono nombre="chevron" class="ml-auto size-5 text-niebla-500" />
        </Link>
        <Link v-if="auth.puede.publicar" href="/mis-eventos" :class="fila">
          <Icono nombre="calendario" class="size-5 text-violeta-500" /> Mis eventos <Icono nombre="chevron" class="ml-auto size-5 text-niebla-500" />
        </Link>
        <Link v-if="auth.user.rol === 'vecino' && solicitud?.estado !== 'pendiente'" href="/emprendedor/solicitud" :class="fila">
          <Icono nombre="tienda" class="size-5 text-violeta-500" /> Quiero publicar eventos <Icono nombre="chevron" class="ml-auto size-5 text-niebla-500" />
        </Link>
        <Link v-if="auth.puede.moderar" href="/moderacion" :class="fila">
          <Icono nombre="escudo" class="size-5 text-violeta-500" /> Moderación <Icono nombre="chevron" class="ml-auto size-5 text-niebla-500" />
        </Link>
        <Link href="/agenda" :class="fila">
          <Icono nombre="campana" class="size-5 text-violeta-500" /> Agenda y avisos <Icono nombre="chevron" class="ml-auto size-5 text-niebla-500" />
        </Link>
      </Tarjeta>

      <Tarjeta v-if="auth.puede.publicar" as="section" aria-labelledby="titulo-api">
        <h2 id="titulo-api" class="flex items-center gap-2 text-lg font-bold"><Icono nombre="llave" class="size-5 text-violeta-500" /> Acceso a la API</h2>
        <p class="mt-1 text-sm text-niebla-600">
          Podés cargar y editar eventos desde tu sitio o sistema con la API, usando tu email y contraseña (HTTP Basic).
          Los eventos nuevos pasan por moderación, igual que desde la app.
        </p>
        <code class="mt-3 block break-all rounded-xl bg-niebla-100 p-3 font-mono text-sm">POST /api/v1/eventos</code>
      </Tarjeta>

      <div class="text-center">
        <Boton href="/logout" method="post" as="button" variante="fantasma" icono="salir" class="text-error!">Salir</Boton>
      </div>

      <p v-if="auth.user.rol === 'emprendedor'" class="flex justify-center"><Insignia>Gracias por mostrar lo que pasa en tu barrio</Insignia></p>
    </div>
  </div>
</template>
