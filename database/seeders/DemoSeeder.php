<?php

namespace Database\Seeders;

use App\Enums\EstadoEvento;
use App\Enums\Rol;
use App\Models\Barrio;
use App\Models\Categoria;
use App\Models\Evento;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Datos de demostración (solo local): emprendedores, vecina y eventos en los barrios
 * del "Recorrido" del lanzamiento. Idempotente: borra y recrea todo lo @demo.barriopoli.test.
 *
 *   php artisan db:seed --class=DemoSeeder
 *
 * Cuentas (contraseña "password"): vecina@ · teatro@ · feria@ demo.barriopoli.test
 */
class DemoSeeder extends Seeder
{
    private const DOMINIO = '@demo.barriopoli.test';

    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new RuntimeException('DemoSeeder solo corre en local.');
        }

        DB::transaction(function () {
            $this->limpiar();

            $teatro = $this->usuario('teatro', 'Lucía Ferreyra', Rol::Emprendedor, 'Teatro del Pasaje');
            $feria = $this->usuario('feria', 'Martín Ocampo', Rol::Emprendedor, 'Feria Jacarandá');
            $vecina = $this->usuario('vecina', 'Sofía Paz', Rol::Vecino);

            $categoria = Categoria::pluck('id', 'slug');
            $eventos = [];

            foreach ($this->eventos() as $datos) {
                [$autor, $slugCategoria, $titulo, $descripcion, $direccion, $lat, $lng, $dias, $hora, $duracion] = $datos;
                $estado = $datos[10] ?? EstadoEvento::Publicado;

                $barrio = Barrio::queContiene($lat, $lng)->first();
                if (! $barrio) {
                    throw new RuntimeException("El punto de \"{$titulo}\" no cae en ningún barrio.");
                }

                $inicio = Carbon::today()->addDays($dias)->setTimeFromTimeString($hora);
                $evento = new Evento([
                    'titulo' => $titulo,
                    'descripcion' => $descripcion,
                    'categoria_id' => $categoria[$slugCategoria],
                    'direccion' => $direccion,
                    'latitud' => $lat,
                    'longitud' => $lng,
                    'inicio' => $inicio,
                    'fin' => $duracion ? $inicio->copy()->addHours($duracion) : null,
                    'barrio_id' => $barrio->id,
                ]);
                $evento->forceFill([
                    'user_id' => ($autor === 'teatro' ? $teatro : $feria)->id,
                    'estado' => $estado,
                    'motivo_rechazo' => $estado === EstadoEvento::Rechazado ? 'Falta indicar si la entrada es libre o con costo.' : null,
                ])->save();

                $eventos[$titulo] = $evento;
            }

            // Agenda y favoritos de la vecina: Palermo es "Mi barrio".
            $favoritos = Barrio::whereIn('slug', ['palermo', 'boca', 'villa-del-parque', 'san-telmo'])->pluck('id', 'slug');
            $vecina->barriosFavoritos()->sync($favoritos->values());
            $vecina->forceFill(['barrio_propio_id' => $favoritos['palermo']])->save();
            $vecina->eventosAgendados()->sync(collect([
                'Feria Jacarandá en Plaza Serrano', 'Noche de tango en Caminito', 'Recorrido por la casa Cayetana', 'Lectura bajo los jacarandás',
            ])->map(fn ($t) => $eventos[$t]->id));

            // Uno agendado y después cancelado: aparece con la etiqueta "Cancelado".
            $eventos['Lectura bajo los jacarandás']->delete();
        });
    }

    private function limpiar(): void
    {
        $ids = User::where('email', 'like', '%'.self::DOMINIO)->pluck('id');
        Evento::withTrashed()->whereIn('user_id', $ids)->forceDelete();
        DB::table('notifications')->where('notifiable_type', User::class)->whereIn('notifiable_id', $ids)->delete();
        User::whereIn('id', $ids)->delete();
    }

    private function usuario(string $alias, string $nombre, Rol $rol, ?string $emprendimiento = null): User
    {
        $usuario = User::create(['name' => $nombre, 'email' => $alias.self::DOMINIO, 'password' => 'password']);
        $usuario->forceFill(['rol' => $rol, 'emprendimiento' => $emprendimiento, 'email_verified_at' => now()])->save();

        return $usuario;
    }

    /** [autor, categoría, título, descripción, dirección, lat, lng, en N días, hora, duración h, estado?] */
    private function eventos(): array
    {
        return [
            ['feria', 'feria', 'Feria Jacarandá en Plaza Serrano', "Diseño independiente, ilustración y objetos hechos en el barrio.\nMás de 40 puestos.", 'Plaza Serrano, Palermo', -34.5886, -58.4302, 2, '11:00', 8],
            ['teatro', 'musica', 'Concierto al atardecer en el Rosedal', 'Cuarteto de cuerdas con repertorio de tango y folklore. Llevá tu manta.', 'Paseo del Rosedal', -34.5717, -58.4171, 5, '18:30', 2],
            ['feria', 'gastronomia', 'Ruta de cafés de especialidad', 'Cinco cafeterías del barrio con descuento presentando la app.', 'Honduras y Gurruchaga', -34.5878, -58.4268, 9, '09:00', 6],
            ['teatro', 'teatro', 'Noche de tango en Caminito', 'Milonga abierta con orquesta en vivo y clase para principiantes.', 'Caminito, La Boca', -34.6393, -58.3628, 3, '20:00', 3],
            ['teatro', 'arte', 'Muestra en la Fundación Proa', 'Visita guiada por la muestra temporaria con artistas del sur de la ciudad.', 'Av. Pedro de Mendoza 1929', -34.6350, -58.3633, 12, '15:00', 2],
            ['feria', 'patrimonio', 'Recorrido por la casa Cayetana', 'Historia de las casonas de Villa del Parque contada por sus vecinos.', 'Cuenca y Marcos Sastre', -34.6040, -58.4910, 6, '10:30', 2],
            ['feria', 'comunidad', 'Huerta comunitaria abierta', 'Jornada de siembra de otoño. Traé semillas para intercambiar.', 'Plaza Arenales, Villa Devoto', -34.6011, -58.5129, 4, '10:00', 4],
            ['teatro', 'cine', 'Cine al aire libre en Parque Centenario', 'Clásicos del cine argentino en pantalla gigante.', 'Parque Centenario', -34.6067, -58.4351, 7, '20:30', 2],
            ['feria', 'feria', 'Feria del libro usado de Parque Rivadavia', 'Puestos históricos de libros, revistas y discos.', 'Parque Rivadavia', -34.6185, -58.4345, 1, '10:00', 9],
            ['feria', 'gastronomia', 'Sabores del Barrio Chino', 'Recorrido gastronómico guiado por Arribeños.', 'Arribeños y Mendoza', -34.5604, -58.4469, 10, '12:00', 3],
            ['teatro', 'taller', 'Taller de grabado para chicos', 'De 6 a 12 años. Materiales incluidos.', 'Plaza Manuel Belgrano', -34.5622, -58.4566, 8, '16:00', 2],
            ['teatro', 'musica', 'Orquesta juvenil en el CCK', 'Concierto gratuito de la orquesta juvenil del sur.', 'Sarmiento 151', -34.6034, -58.3697, 14, '19:00', 2],
            ['feria', 'feria', 'Feria de San Telmo', 'Antigüedades, artesanías y música en la calle Defensa.', 'Plaza Dorrego, San Telmo', -34.6206, -58.3717, 0, '10:00', 8],
            ['teatro', 'arte', 'Lectura bajo los jacarandás', 'Lecturas de poesía porteña en la plaza.', 'Plaza Italia, Palermo', -34.5807, -58.4209, 11, '17:00', 2],
            ['teatro', 'teatro', 'Estreno: Historias de conventillo', 'Obra sobre la inmigración en La Boca. Entrada a la gorra.', 'Necochea 1200', -34.6370, -58.3640, 16, '21:00', 2, EstadoEvento::Pendiente],
            ['feria', 'feria', 'Mercado de pulgas nocturno', 'Feria de objetos vintage con música en vivo.', 'Dorrego 1650', -34.5873, -58.4413, 13, '19:00', 5, EstadoEvento::Rechazado],
            ['feria', 'patrimonio', 'Visita a la Casa de los Ezeiza', 'Recorrido histórico por la casona.', 'Defensa 1179', -34.6209, -58.3718, -10, '11:00', 2],
        ];
    }
}
