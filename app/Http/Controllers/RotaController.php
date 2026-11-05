<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RotaController extends Controller
{
    public function calcular(Request $request)
    {

        error_log('OLYMPE: endpoint de rotas foi chamado');

        $apiKey = config('services.google.routes_api_key');

        error_log(
        'OLYMPE: chave Google configurada? ' .
        (filled($apiKey) ? 'SIM' : 'NAO')
    );

        $dados = $request->validate([
            'origin.latitude' => ['required', 'numeric', 'between:-90,90'],
            'origin.longitude' => ['required', 'numeric', 'between:-180,180'],

            'destination.latitude' => ['required', 'numeric', 'between:-90,90'],
            'destination.longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $origin = $dados['origin'];
        $destination = $dados['destination'];

        $apiKey = config('services.google.routes_api_key');

        Log::info('Verificação Google Routes', [
            'chave_configurada' => !empty($apiKey),
            'tamanho_chave' => $apiKey ? strlen($apiKey) : 0,
        ]);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Goog-Api-Key' => $apiKey,
            'X-Goog-FieldMask' => implode(',', [
                'routes.distanceMeters',
                'routes.duration',
                'routes.polyline.encodedPolyline',
                'routes.routeLabels',
            ]),
        ])->post(
            'https://routes.googleapis.com/directions/v2:computeRoutes',
            [
                'origin' => [
                    'location' => [
                        'latLng' => [
                            'latitude' => $origin['latitude'],
                            'longitude' => $origin['longitude'],
                        ],
                    ],
                ],

                'destination' => [
                    'location' => [
                        'latLng' => [
                            'latitude' => $destination['latitude'],
                            'longitude' => $destination['longitude'],
                        ],
                    ],
                ],

                'travelMode' => 'WALK',

                'computeAlternativeRoutes' => true,

                'languageCode' => 'pt-BR',

                'units' => 'METRIC',
            ]
        );

        if ($response->failed()) {
        return response()->json([
            'message' => 'Não foi possível calcular a rota.',
            'google_key_configured' => filled($apiKey),
            'google_error' => $response->json(),
        ], $response->status());
        }

        return response()->json([
            'routes' => $response->json('routes', []),
        ]);
    }
}