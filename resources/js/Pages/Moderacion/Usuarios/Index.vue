<script setup>
    import { reactive, watch } from 'vue';
    import { Link, router } from '@inertiajs/vue3';
    import AppLayout from '@/layouts/AppLayout.vue';
    import CampoTexto from '@/components/CampoTexto.vue';
    import Boton from '@/components/ui/Boton.vue';
    import EstadoVacio from '@/components/ui/EstadoVacio.vue';
    import Icono from '@/components/ui/Icono.vue';
    import Insignia from '@/components/ui/Insignia.vue';
    import Tarjeta from '@/components/ui/Tarjeta.vue';
    import { debounce, sinVacios } from '@/lib/funciones';
    import { ROLES } from '@/lib/roles';

    defineOptions({ layout: AppLayout })
    const props = defineProps({
      usuarios: { type: Object, required: true },
      filtros: { type: Object, required: true },
    })

    const opcionesRol = [{ valor: '', texto: 'Todos' }, ...Object.entries(ROLES).map(([valor, texto]) => ({ valor, texto }))]

    const filtros = reactive({ q: props.filtros.q, rol: props.filtros.rol })

    const buscar = debounce(() => router.get('/moderacion/usuarios', sinVacios(filtros), {
      preserveState: true,
      preserveScroll: true,
      replace: true,
      only: ['usuarios', 'filtros'],
    }), 300)
    watch(() => [filtros.q, filtros.rol], buscar)
</script>

<template>
  <div class="mx-auto max-w-3xl px-4 pb-10 pt-[max(1rem,env(safe-area-inset-top))] md:px-8">
    <Boton href="/moderacion" variante="fantasma" icono="atras" chico class="-ml-2">Moderación</Boton>
    <h1 class="titulo-marca mt-2 text-3xl text-violeta-700 md:text-4xl">Usuarios</h1>
    <p class="mt-0.5 font-bold text-niebla-600">{{ usuarios.total }} {{ usuarios.total === 1 ? 'cuenta' : 'cuentas' }}</p>

    <div class="mt-4 space-y-3">
      <CampoTexto v-model="filtros.q" etiqueta="Buscar" tipo="search" placeholder="Nombre, email o emprendimiento" maxlength="80" />
      <div class="-mx-4 flex gap-2 overflow-x-auto px-4 [scrollbar-width:none]" role="radiogroup" aria-label="Filtrar por rol">
        <button
          v-for="o in opcionesRol"
          :key="o.valor"
          type="button"
          role="radio"
          :aria-checked="filtros.rol === o.valor"
          class="min-h-11 shrink-0 rounded-full border-2 px-4 font-semibold transition motion-reduce:transition-none"
          :class="filtros.rol === o.valor ? 'border-violeta-700 bg-violeta-700 text-white' : 'border-niebla-200 bg-white text-tinta hover:border-violeta-300'"
          @click="filtros.rol = o.valor"
        >{{ o.texto }}</button>
      </div>
    </div>

    <ul v-if="usuarios.data.length" class="mt-5 space-y-3">
      <li v-for="u in usuarios.data" :key="u.id">
        <Link :href="`/moderacion/usuarios/${u.id}`" class="block">
          <Tarjeta class="flex items-center gap-3 transition hover:shadow-flotante motion-reduce:transition-none">
            <div class="min-w-0 flex-1">
              <p class="flex flex-wrap items-center gap-2">
                <span class="truncate text-lg font-bold">{{ u.nombre }}</span>
                <Insignia>{{ ROLES[u.rol] }}</Insignia>
                <span v-if="!u.verificado" class="text-sm font-semibold text-alerta">Sin verificar</span>
              </p>
              <p class="truncate text-sm text-niebla-600">{{ u.email }}<template v-if="u.emprendimiento"> · {{ u.emprendimiento }}</template></p>
              <p class="mt-1 text-sm text-niebla-600">{{ u.eventos }} eventos · {{ u.favoritos }} barrios · {{ u.agendados }} agendados</p>
            </div>
            <Icono nombre="chevron" class="size-5 shrink-0 text-violeta-500" />
          </Tarjeta>
        </Link>
      </li>
    </ul>
    <EstadoVacio v-else titulo="Sin resultados" texto="No hay cuentas con esos filtros." />

    <nav v-if="usuarios.last_page > 1" class="mt-6 flex items-center justify-between gap-3" aria-label="Paginación">
      <Boton v-if="usuarios.prev_page_url" :href="usuarios.prev_page_url" variante="secundario" icono="atras" chico>Anterior</Boton>
      <span v-else />
      <span class="text-sm font-semibold text-niebla-600">Página {{ usuarios.current_page }} de {{ usuarios.last_page }}</span>
      <Boton v-if="usuarios.next_page_url" :href="usuarios.next_page_url" variante="secundario" chico>Siguiente</Boton>
      <span v-else />
    </nav>
  </div>
</template>
