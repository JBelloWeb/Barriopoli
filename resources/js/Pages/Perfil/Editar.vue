<script setup>
    import { computed } from 'vue';
    import { useForm, usePage } from '@inertiajs/vue3';
    import AppLayout from '@/layouts/AppLayout.vue';
    import CampoTexto from '@/components/CampoTexto.vue';
    import Boton from '@/components/ui/Boton.vue';
    import CampoArea from '@/components/ui/CampoArea.vue';
    import Icono from '@/components/ui/Icono.vue';
    import Tarjeta from '@/components/ui/Tarjeta.vue';

    defineOptions({ layout: AppLayout })
    const props = defineProps({
      emprendimiento: { type: String, default: null },
      cambioNombre: { type: Object, default: null },
    })

    const page = usePage()
    const usuario = computed(() => page.props.auth.user)

    const MENSAJES = {
      'profile-information-updated': 'Guardamos tus datos.',
      'password-updated': 'Cambiamos tu contraseña.',
    }
    const estado = computed(() => MENSAJES[page.props.status] ?? null)

    const perfil = useForm({ name: usuario.value.name, email: usuario.value.email })
    const emailAnterior = usuario.value.email
    const cambioEmail = computed(() => page.props.status === 'profile-information-updated' && !usuario.value.verificado)
    const guardarPerfil = () => perfil.put('/user/profile-information', { errorBag: 'updateProfileInformation', preserveScroll: true })

    const clave = useForm({ current_password: '', password: '', password_confirmation: '' })
    const guardarClave = () => clave.put('/user/password', {
      errorBag: 'updatePassword',
      preserveScroll: true,
      onFinish: () => clave.reset(),
    })

    const nombre = useForm({ nombre_emprendimiento: props.emprendimiento ?? '', motivo: '' })
    const pendiente = computed(() => props.cambioNombre?.estado === 'pendiente')
    const pedirNombre = () => nombre.post('/perfil/emprendimiento', { preserveScroll: true, onSuccess: () => nombre.reset('motivo') })
</script>

<template>
  <div class="mx-auto max-w-lg px-4 pb-10 pt-[max(1rem,env(safe-area-inset-top))]">
    <Boton href="/perfil" variante="fantasma" icono="atras" chico class="-ml-2">Perfil</Boton>
    <h1 class="titulo-marca mt-2 text-3xl text-violeta-700">Editar perfil</h1>
    <p class="mt-0.5 font-bold text-niebla-600">Tus datos de cuenta</p>

    <p v-if="estado" role="status" class="mt-4 flex items-center gap-3 rounded-2xl bg-durazno-100 p-4 font-semibold">
      <Icono nombre="check" class="size-5 text-violeta-700" /> {{ estado }}
    </p>
    <p v-if="cambioEmail" role="status" class="mt-3 rounded-2xl bg-durazno-100 p-4 font-semibold">
      Te mandamos un link a tu email nuevo. Hasta que lo confirmes no podés publicar.
    </p>

    <Tarjeta as="form" class="mt-4 space-y-5" aria-labelledby="titulo-datos" @submit.prevent="guardarPerfil">
      <h2 id="titulo-datos" class="text-lg font-bold">Datos</h2>
      <CampoTexto v-model="perfil.name" etiqueta="Nombre" autocomplete="name" required maxlength="255" :error="perfil.errors.name" />
      <CampoTexto
        v-model="perfil.email"
        etiqueta="Email"
        tipo="email"
        autocomplete="email"
        required
        :ayuda="perfil.email !== emailAnterior ? 'Si lo cambiás, vas a tener que verificarlo de nuevo.' : null"
        :error="perfil.errors.email"
      />
      <Boton tipo="submit" bloque :cargando="perfil.processing">Guardar datos</Boton>
    </Tarjeta>

    <Tarjeta as="form" class="mt-4 space-y-5" aria-labelledby="titulo-clave" @submit.prevent="guardarClave">
      <h2 id="titulo-clave" class="text-lg font-bold">Contraseña</h2>
      <CampoTexto v-model="clave.current_password" etiqueta="Contraseña actual" tipo="password" autocomplete="current-password" required :error="clave.errors.current_password" />
      <CampoTexto v-model="clave.password" etiqueta="Contraseña nueva" tipo="password" autocomplete="new-password" required ayuda="Mínimo 8 caracteres, con letras y números." :error="clave.errors.password" />
      <CampoTexto v-model="clave.password_confirmation" etiqueta="Repetí la contraseña nueva" tipo="password" autocomplete="new-password" required />
      <Boton tipo="submit" bloque :cargando="clave.processing">Cambiar contraseña</Boton>
    </Tarjeta>

    <Tarjeta v-if="emprendimiento" as="form" class="mt-4 space-y-5" aria-labelledby="titulo-emprendimiento" @submit.prevent="pedirNombre">
      <div>
        <h2 id="titulo-emprendimiento" class="text-lg font-bold">Nombre del emprendimiento</h2>
        <p class="mt-1 text-sm text-niebla-600">Se muestra en tu perfil público y en tus eventos. Un cambio de nombre lo revisa un moderador antes de publicarse.</p>
      </div>

      <p v-if="pendiente" role="status" class="flex items-start gap-3 rounded-2xl bg-durazno-100 p-4 font-semibold">
        <Icono nombre="reloj" class="mt-0.5 size-5 shrink-0 text-violeta-700" />
        <span>Pediste cambiarlo a <span class="font-bold">“{{ cambioNombre.nombre_emprendimiento }}”</span>. Está en revisión.</span>
      </p>
      <p v-else-if="cambioNombre?.estado === 'rechazada'" class="rounded-2xl bg-error/8 p-4 text-error">
        <span class="font-bold">Rechazamos “{{ cambioNombre.nombre_emprendimiento }}”:</span> {{ cambioNombre.motivo_rechazo }}
      </p>

      <template v-if="!pendiente">
        <CampoTexto v-model="nombre.nombre_emprendimiento" etiqueta="Nombre nuevo" required maxlength="120" :error="nombre.errors.nombre_emprendimiento" />
        <CampoArea v-model="nombre.motivo" etiqueta="¿Por qué lo cambiás? (opcional)" maxlength="500" :error="nombre.errors.motivo" />
        <Boton tipo="submit" variante="secundario" bloque :cargando="nombre.processing">Pedir cambio de nombre</Boton>
      </template>
    </Tarjeta>
  </div>
</template>
