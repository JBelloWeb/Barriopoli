<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoriaResource;
use App\Models\Categoria;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoriaController extends Controller
{
    public function __invoke(): AnonymousResourceCollection
    {
        return CategoriaResource::collection(Categoria::activas()->get());
    }
}
