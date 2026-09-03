<?php

namespace App\Http\Controllers;

use App\Models\Gift;
use App\Models\Guest;
use Illuminate\Http\Request;
use Exception;

class GiftRegistryController extends Controller
{
    public function show($guestId)
    {
        try {
            // Verifica que el invitado exista
            $guest = Guest::find($guestId);

            if (!$guest) {
                return response()->json([
                    'error' => "El invitado con ID {$guestId} no existe en la base de datos."
                ], 404);
            }
            
            // Carga los regalos y el nombre del invitado reservado
            $gifts = Gift::with('guest')->get();

            return response()->json([
                'guest' => $guest,
                'gifts' => $gifts,
            ]);

        } catch (Exception $e) {
            // Devuelve el error exacto para diagnosticar
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

        $gift = Gift::findOrFail($giftId);

        if ($gift->guest_id !== null) {
            return response()->json([
                'message' => 'Este regalo ya fue reservado por otro invitado.'
            ], 422);
        }

        $gift->update(['guest_id' => $request->guest_id]);

        return response()->json([
            'message' => '¡Regalo reservado con éxito!',
            'gift'    => $gift->load('guest')
        ]);
    }
}