<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\Barrio;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class EventoController extends Controller
{
    /**
     * Muestra el formulario para crear un evento.
     */
    public function create()
    {
        return Inertia::render('Eventos/Create', [
            'barrios' => Barrio::select('id', 'nombre')->get(),
        ]);
    }

    /**
     * Guarda un nuevo evento en la base de datos.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'barrio_id' => 'required|exists:barrios,id',
            'titulo'    => 'required|string|max:255',
            'categoria' => 'required|string|max:255',
            'direccion' => 'required|string',
            'fecha'     => 'required|date',
        ]);

        $direccionCompleta = $validated['direccion'] . ', Buenos Aires, Argentina';

        $response = Http::withHeaders([
            'User-Agent' => 'BarriopoliApp/1.0 (contacto@tuapp.com)'
        ])->get('https://nominatim.openstreetmap.org/search', [
            'q'      => $direccionCompleta,
            'format' => 'json',
            'limit'  => 1,
        ]);

        $data = $response->json();

        if (!empty($data)) {
            $validated['latitud']  = $data[0]['lat'];
            $validated['longitud'] = $data[0]['lon'];
        }

        Evento::create($validated);

        return redirect()->route('barrios.show', Barrio::find($validated['barrio_id'])->slug)
            ->with('success', 'Evento guardado correctamente.');
    }

    /**
     * Muestra el formulario para editar un evento existente.
     */
    public function edit(Evento $evento)
    {
        return Inertia::render('Eventos/Edit', [
            'evento'  => $evento,
            'barrios' => Barrio::select('id', 'nombre')->get(),
        ]);
    }

    /**
     * Actualiza el evento en la base de datos.
     */
    public function update(Request $request, Evento $evento)
    {
        $validated = $request->validate([
            'barrio_id' => 'required|exists:barrios,id',
            'titulo'    => 'required|string|max:255',
            'categoria' => 'required|string|max:255',
            'direccion' => 'required|string',
            'fecha'     => 'required|date',
        ]);

        // Si cambió la dirección, consultar nuevamente a Nominatim
        if ($evento->direccion !== $validated['direccion']) {
            $direccionCompleta = $validated['direccion'] . ', Buenos Aires, Argentina';
            
            $response = Http::withHeaders([
                'User-Agent' => 'BarriopoliApp/1.0 (contacto@tuapp.com)'
            ])->get('https://nominatim.openstreetmap.org/search', [
                'q'      => $direccionCompleta,
                'format' => 'json',
                'limit'  => 1,
            ]);

            $data = $response->json();

            if (!empty($data)) {
                $validated['latitud']  = $data[0]['lat'];
                $validated['longitud'] = $data[0]['lon'];
            }
        }

        $evento->update($validated);

        return redirect()->back()->with('success', 'Evento actualizado correctamente.');
    }
}