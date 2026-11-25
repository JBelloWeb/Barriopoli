<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\EventoController as WebEventoController;
use App\Http\Resources\EmprendimientoResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EmprendimientoController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $datos = $request->validate(['per_page' => ['nullable', 'integer', 'between:1,50']]);

        $emprendimientos = $this->emprendimientos()
            ->orderBy('emprendimiento')
            ->paginate($datos['per_page'] ?? 15, ['id', 'emprendimiento'])
            ->withQueryString();

        return EmprendimientoResource::collection($emprendimientos);
    }

    public function show(int $emprendedor): EmprendimientoResource
    {
        $emprendimiento = $this->emprendimientos()
            ->with(['eventos' => fn ($query) => $query->publicados()->vigentes()
                ->with(WebEventoController::RELACIONES)
                ->orderBy('inicio')
                ->limit(50)])
            ->findOrFail($emprendedor, ['id', 'emprendimiento']);

        return EmprendimientoResource::make($emprendimiento);
    }

    private function emprendimientos(): Builder
    {
        return User::emprendimientosPublicos()
            ->withCount(['eventos' => fn (Builder $query) => $query->publicados()->vigentes()]);
    }
}
