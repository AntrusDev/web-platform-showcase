<?php

/*
 * Controlador Inertia con minimización de datos.
 * Decisión: nunca se pasa el modelo completo a la vista; se seleccionan
 * solo las columnas que la página usa, también en las relaciones con
 * eager loading (evita N+1), y se ocultan los timestamps usados solo para SEO.
 */

namespace App\Http\Controllers;

use App\Models\Program;
use Inertia\Inertia;

class ProgramController extends Controller
{
    // Catálogo de programas activos
    public function index()
    {
        $programs = Program::query()
            ->where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get(['id', 'title', 'description', 'image_path', 'is_active']);

        // ...

        return Inertia::render('Programs/Index', [
            'programs' => $programs,
            // ...
        ]);
    }

    // Detalle de un programa activo (404 si no existe o está inactivo)
    public function show($id)
    {
        $program = Program::query()
            ->select(['id', 'title', 'description', 'image_path', 'youtube_embed', 'gallery', 'created_at', 'updated_at'])
            ->with([
                'variations:id,program_id,region_id,commune_id,requirements,documents',
                'variations.region:id,name',
                'variations.commune:id,name',
            ])
            ->where('is_active', true)
            ->findOrFail($id);

        // ... (metadatos SEO y schema.org a partir de created_at / updated_at)

        return Inertia::render('Programs/Show', [
            'program' => $program->makeHidden(['created_at', 'updated_at']),
            // ...
        ]);
    }
}
