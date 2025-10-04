<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /**
     * @OA\Get(
     * path="/api/services",
     * summary="Obtener el catálogo completo de servicios",
     * security={{"sanctum":{}}},
     * @OA\Response(response=200, description="Lista de servicios obtenidos"),
     * @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function index()
    {
        // 1. Obtener todos los servicios desde la base de datos
        $services = Service::all();

        // 2. Devolver la respuesta en formato JSON
        return response()->json([
            'message' => 'Catálogo de servicios obtenido exitosamente.',
            'services' => $services
        ], 200);
    }

    public function show($id)
    {
        // 1. Buscar el servicio por su ID
        $service = Service::find($id);

        // 2. Verificar si el servicio existe
        if (!$service) {
            return response()->json(['message' => 'Servicio no encontrado.'], 404);
        }

        // 3. Devolver la respuesta en formato JSON
        return response()->json([
            'message' => 'Servicio obtenido exitosamente.',
            'service' => $service
        ], 200);
    }

    function store (Request $request) {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'duration' => 'nullable|integer|min:0',
            'is_domicilio' => 'nullable|boolean',
        ]);

        $service = Service::create([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'duration' => $request->duration,
            'is_domicilio' => $request->is_domicilio ?? false,
        ]);

        return response()->json([
            'message' => 'Servicio creado exitosamente.',
            'service' => $service
        ], 201);
    }

    function delete ($id) {
        $service = Service::find($id);

        if (!$service) {
            return response()->json(['message' => 'Servicio no encontrado.'], 404);
        }

        $service->delete();

        return response()->json(['message' => 'Servicio eliminado exitosamente.'], 200);
    }

    function update (Request $request, $id) {
        $service = Service::find($id);

        if (!$service) {
            return response()->json(['message' => 'Servicio no encontrado.'], 404);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'duration' => 'sometimes|nullable|integer|min:0',
            'is_domicilio' => 'sometimes|nullable|boolean',
        ]);

        $service->update($request->only(['name', 'description', 'price', 'duration', 'is_domicilio']));

        return response()->json([
            'message' => 'Servicio actualizado exitosamente.',
            'service' => $service
        ], 200);
    }
}