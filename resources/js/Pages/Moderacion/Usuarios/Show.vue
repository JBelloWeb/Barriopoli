<script setup>
    import { computed } from 'vue';
    import { useForm, usePage } from '@inertiajs/vue3';
    import AppLayout from '@/layouts/AppLayout.vue';
    import CampoTexto from '@/components/CampoTexto.vue';
    import Boton from '@/components/ui/Boton.vue';
    import CampoSelect from '@/components/ui/CampoSelect.vue';
    import Insignia from '@/components/ui/Insignia.vue';
    import Tarjeta from '@/components/ui/Tarjeta.vue';
    import { ROLES } from '@/lib/roles';
    import { nombreLegible } from '@/lib/texto';

    defineOptions({ layout: AppLayout })
    const props = defineProps({
      usuario: { type: Object, required: true },
      eventos: { type: Array, default: () => [] },
      solicitudes: { type: Array, default: () => [] },
      barrios: { type: Array, default: () => [] },
    })

    const page = usePage()
    const esPropio = computed(() => page.props.auth.user.id === props.usuario.id)
    const opciones = Object.entries(ROLES).map(([valor, texto]) => ({ valor, texto }))
    const ESTADOS = { pendiente: 'Pendiente', publicado: 'Publicado', rechazado: 'Rechazado', aprobada: 'Aprobada', rechazada: 'Rechazada' }
    const TIPOS = { alta: 'Alta de emprendedor', cambio_nombre: 'Cambio de nombre' }

    const form = useForm({ rol: props.usuario.rol, emprendimiento: props.usuario.emprendimiento ?? '' })
    const cambio = computed(() => form.rol !== props.usuario.rol || (form.rol === 'emprendedor' && form.emprendimiento !== (props.usuario.emprendimiento ?? '')))
    const guardar = () => form.put(`/moderacion/usuarios/${props.usuario.id}/rol`, { preserveScroll: true })

    const fecha = (iso) => new Date(iso).toLocaleDateString('es-AR', { day: 'numeric', month: 'short', year: 'numeric' })
</script>

<template>
  <div class="mx-auto max-w-3xl px-4 pb-10 pt-[max(1rem,env(safe-area-inset-top))] md:px-8">
    <Boton href="/moderacion/usuarios" variante="fantasma" icono="atras" chico class="-ml-2">Usuarios</Boton>
    <h1 class="titulo-marca mt-2 text-3xl text-violeta-700">{{ usuario.nombre }}</h1>
    <p class="mt-1 flex flex-wrap items-center gap-2">
      <Insignia>{{ ROLES[usuario.rol] }}</Insignia>
      <span v-if="usuario.emprendimiento" class="font-semibold">{{ usuario.emprendimiento }}</span>
    </p>

    <Tarjeta as="section" class="mt-4" aria-labelledby="titulo-resumen">
      <h2 id="titulo-resumen" class="sr-only">Resumen</h2>
      <dl class="grid grid-cols-2 gap-x-4 gap-y-3 sm:grid-cols-3">
        <div class="col-span-2 sm:col-span-3"><dt class="text-sm font-bold text-niebla-600">Email</dt><dd class="break-all">{{ usuario.email }} <span v-if="!usuario.verificado" class="font-semibold text-alerta">(sin verificar)</span></dd></div>
        <div><dt class="text-sm font-bold text-niebla-600">Alta</dt><dd>{{ usuario.alta ? fecha(usuario.alta) : '—' }}</dd></div>
        <div><dt class="text-sm font-bold text-niebla-600">Eventos</dt><dd>{{ usuario.eventos }}</dd></div>
        <div><dt class="text-sm font-bold text-niebla-600">Agendados</dt><dd>{{ usuario.agendados }}</dd></div>
        <div class="col-span-2 sm:col-span-3">
          <dt class="text-sm font-bold text-niebla-600">Barrios favoritos ({{ usuario.favoritos }})</dt>
          <dd>{{ barrios.length ? barrios.map(nombreLegible).join(', ') : 'Ninguno' }}</dd>
        </div>
      </dl>
    </Tarjeta>

    <Tarjeta as="form" class="mt-4 space-y-5" aria-labelledby="titulo-rol" @submit.prevent="guardar">
      <h2 id="titulo-rol" class="text-lg font-bold">Rol</h2>
      <p v-if="esPropio" class="rounded-2xl bg-durazno-100 p-4 font-semibold">Es tu cuenta: tu rol lo cambia otro moderador.</p>
      <template v-else>
        <CampoSelect v-model="form.rol" etiqueta="Rol" :opciones="opciones" :error="form.errors.rol" />
        <CampoTexto
          v-if="form.rol === 'emprendedor'"
          v-model="form.emprendimiento"
          etiqueta="Nombre del emprendimiento"
          required
          maxlength="120"
          :error="form.errors.emprendimiento"
        />
        <p v-if="usuario.rol === 'emprendedor' && form.rol === 'vecino'" class="text-sm text-niebla-600">
          Sus eventos siguen publicados, pero ya no va a poder editarlos ni cargar nuevos.
        </p>
        <Boton tipo="submit" bloque :disabled="!cambio" :cargando="form.processing">Guardar rol</Boton>
      </template>
    </Tarjeta>

    <Tarjeta as="section" class="mt-4" aria-labelledby="titulo-eventos">
      <h2 id="titulo-eventos" class="text-lg font-bold">Últimos eventos</h2>
      <ul v-if="eventos.length" class="mt-3 divide-y divide-niebla-200">
        <li v-for="e in eventos" :key="e.id" class="flex items-center gap-3 py-3">
          <div class="min-w-0 flex-1">
            <p class="truncate font-bold">{{ e.titulo }}</p>
            <p class="text-sm text-niebla-600">{{ fecha(e.inicio) }}</p>
          </div>
          <Insignia>{{ e.cancelado ? 'Cancelado' : ESTADOS[e.estado] }}</Insignia>
        </li>
      </ul>
      <p v-else class="mt-2 text-niebla-600">No cargó eventos.</p>
    </Tarjeta>

    <Tarjeta as="section" class="mt-4" aria-labelledby="titulo-solicitudes">
      <h2 id="titulo-solicitudes" class="text-lg font-bold">Solicitudes</h2>
      <ul v-if="solicitudes.length" class="mt-3 divide-y divide-niebla-200">
        <li v-for="s in solicitudes" :key="s.id" class="py-3">
          <p class="flex flex-wrap items-center gap-2">
            <span class="font-bold">{{ s.nombre_emprendimiento }}</span>
            <Insignia>{{ ESTADOS[s.estado] }}</Insignia>
          </p>
          <p class="text-sm text-niebla-600">{{ TIPOS[s.tipo] }} · {{ fecha(s.created_at) }}</p>
          <p v-if="s.motivo_rechazo" class="mt-1 text-sm text-error">{{ s.motivo_rechazo }}</p>
        </li>
      </ul>
      <p v-else class="mt-2 text-niebla-600">Sin solicitudes.</p>
    </Tarjeta>
  </div>
</template>
