<?php

namespace App\Http\Controllers;

use App\Models\Gift;
use App\Models\Guest;
use Illuminate\Http\Request;
use Exception;

class GiftRegistryController extends Controller
{
    public function show($identifier)
    {
        try {
            // Busca por ID si es numérico, o por nombre si es texto (ej: 'carolina')
            $guest = is_numeric($identifier)
                ? Guest::find($identifier)
                : Guest::where('name', $identifier)->first();

            if (!$guest) {
                return response()->json([
                    'error' => "El invitado '{$identifier}' no existe en la base de datos."
                ], 404);
            }
            
            $guestId = $guest->id;

            // Carga regalos con conteo de selecciones y evalúa estado para la vista
            $gifts = Gift::withCount('guests')->get()->map(function ($gift) use ($guestId) {
                $maxSelection = $gift->max_selection ?? 1;
                $selectionsCount = $gift->guests_count;

                return [
                    'id'                => $gift->id,
                    'name'              => $gift->name,
                    'image_url'         => $gift->image_url,
                    'max_selection'     => $maxSelection,
                    'selections_count'  => $selectionsCount,
                    'is_selected_by_me' => $gift->guests()->where('guest_id', $guestId)->exists(),
                    'is_full'           => $selectionsCount >= $maxSelection,
                ];
            });

            return response()->json([
                'guest' => $guest,
                'gifts' => $gifts,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine()
            ], 500);
        }
    }

    public function selectGift(Request $request, $giftId)
    {
        $request->validate([
            'guest_id' => 'required|exists:guests,id',
        ]);

        $guestId = $request->guest_id;
        $gift = Gift::withCount('guests')->findOrFail($giftId);
        $maxSelection = $gift->max_selection ?? 1;

        // Validar si el invitado ya reservó este regalo previamente
        if ($gift->guests()->where('guest_id', $guestId)->exists()) {
            return response()->json([
                'message' => 'Ya has reservado este regalo.'
            ], 422);
        }

        // Validar si ya se alcanzó el límite de selecciones
        if ($gift->guests_count >= $maxSelection) {
            return response()->json([
                'message' => 'Este regalo ya ha alcanzado el límite máximo de reservas.'
            ], 422);
        }

        // Registrar la selección en la tabla pivote
        $gift->guests()->attach($guestId);

        return response()->json([
            'message' => '¡Regalo reservado con éxito!',
            'gift'    => $gift
        ]);
    }
}
