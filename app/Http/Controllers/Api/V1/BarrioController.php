<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BarrioResource;
use App\Models\Barrio;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BarrioController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return BarrioResource::collection(Barrio::orderBy('nombre')->get(['id', 'nombre', 'slug', 'comuna']));
    }

    public function show(Barrio $barrio): BarrioResource
    {
        return BarrioResource::make($barrio);
    }
}
