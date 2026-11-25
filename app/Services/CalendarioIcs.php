<?php

namespace App\Services;

use App\Models\Evento;
use DateTimeInterface;
use Illuminate\Support\Carbon;

class CalendarioIcs
{
    private const HORAS_POR_DEFECTO = 2;

    /** @param iterable<Evento> $eventos */
    public function generar(iterable $eventos): string
    {
        $lineas = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Barriopoli//Agenda//ES', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH'];

        foreach ($eventos as $evento) {
            array_push($lineas, ...$this->lineasDe($evento));
        }

        $lineas[] = 'END:VCALENDAR';

        return implode("\r\n", array_map($this->plegar(...), $lineas))."\r\n";
    }

    private function lineasDe(Evento $evento): array
    {
        $fin = $evento->fin ?? $evento->inicio->copy()->addHours(self::HORAS_POR_DEFECTO);

        return [
            'BEGIN:VEVENT',
            "UID:evento-{$evento->id}@barriopoli",
            'DTSTAMP:'.$this->enUtc($evento->updated_at),
            'SEQUENCE:'.$evento->updated_at->getTimestamp(),
            'DTSTART:'.$this->enUtc($evento->inicio),
            'DTEND:'.$this->enUtc($fin),
            'SUMMARY:'.$this->escapar($evento->titulo),
            'LOCATION:'.$this->escapar("{$evento->direccion}, {$evento->barrio->nombre}, CABA"),
            'DESCRIPTION:'.$this->escapar((string) $evento->descripcion),
            sprintf('GEO:%.6F;%.6F', $evento->latitud, $evento->longitud),
            'URL:'.route('eventos.show', $evento->id),
            'BEGIN:VALARM',
            'ACTION:DISPLAY',
            'DESCRIPTION:'.$this->escapar($evento->titulo),
            'TRIGGER:-PT2H',
            'END:VALARM',
            'END:VEVENT',
        ];
    }

    private function enUtc(DateTimeInterface $fecha): string
    {
        return Carbon::instance($fecha)->utc()->format('Ymd\THis\Z');
    }

    private function escapar(string $texto): string
    {
        return str_replace(['\\', ';', ',', "\r\n", "\n", "\r"], ['\\\\', '\;', '\,', '\n', '\n', ''], $texto);
    }

    private function plegar(string $linea): string
    {
        $salida = [];
        $prefijo = '';

        while (strlen($prefijo.$linea) > 75) {
            $corte = mb_strcut($linea, 0, 75 - strlen($prefijo), 'UTF-8');
            $salida[] = $prefijo.$corte;
            $linea = substr($linea, strlen($corte));
            $prefijo = ' ';
        }

        $salida[] = $prefijo.$linea;

        return implode("\r\n", $salida);
    }
}
