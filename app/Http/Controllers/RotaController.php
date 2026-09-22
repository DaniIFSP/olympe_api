<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RotaController extends Controller
{
    public function calcular(Request $request)
    {
        $dados = $request->validate([
            'origin.latitude' => ['required', 'numeric', 'between:-90,90'],
            'origin.longitude' => ['required', 'numeric', 'between:-180,180'],

            'destination.latitude' => ['required', 'numeric', 'between:-90,90'],
            'destination.longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $origin = $dados['origin'];
        $destination = $dados['destination'];

        Log::info('alo mamae');

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Goog-Api-Key' => config('services.google.routes_api_key'),
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
                'google_error' => $response->json(),
            ], $response->status());
        }

        return response()->json([
            'routes' => $response->json('routes', []),
        ]);
    }
}