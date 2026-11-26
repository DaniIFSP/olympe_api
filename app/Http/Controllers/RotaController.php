<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\PontoRisco;

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
        $pontosRisco = PontoRisco::all();

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

                $rotas = $response->json('routes', []);

        foreach ($rotas as &$rota) {

            $polyline = $rota['polyline']['encodedPolyline'] ?? '';

            $coordenadas = $this->decodificarPolyline($polyline);

            $analiseRisco = $this->analisarRiscosRota(
                $coordenadas,
                $pontosRisco
            );

            $rota['pontuacao_risco'] = $analiseRisco['pontuacao_risco'];

            $rota['quantidade_pontos_risco'] =
                $analiseRisco['quantidade_pontos_risco'];

            $rota['pontos_risco_proximos'] =
                $analiseRisco['pontos_risco_proximos'];
        }

        unset($rota);

        return response()->json([
            'routes' => $rotas,
        ]);
    }

    private function distanciaPontoSegmento($ponto, $inicio, $fim)
    {
        $latitudeMedia = deg2rad($ponto['latitude']);

        $metrosPorGrauLatitude = 111320;
        $metrosPorGrauLongitude = 111320 * cos($latitudeMedia);

        $px = $ponto['longitude'] * $metrosPorGrauLongitude;
        $py = $ponto['latitude'] * $metrosPorGrauLatitude;

        $ax = $inicio['longitude'] * $metrosPorGrauLongitude;
        $ay = $inicio['latitude'] * $metrosPorGrauLatitude;

        $bx = $fim['longitude'] * $metrosPorGrauLongitude;
        $by = $fim['latitude'] * $metrosPorGrauLatitude;

        $dx = $bx - $ax;
        $dy = $by - $ay;

        if ($dx == 0 && $dy == 0) {
            return sqrt(pow($px - $ax, 2) + pow($py - $ay, 2));
        }

        $t = (($px - $ax) * $dx + ($py - $ay) * $dy)
            / ($dx * $dx + $dy * $dy);

        $t = max(0, min(1, $t));

        $projecaoX = $ax + $t * $dx;
        $projecaoY = $ay + $t * $dy;

        return sqrt(
            pow($px - $projecaoX, 2) +
            pow($py - $projecaoY, 2)
        );
    }

    private function decodificarPolyline($encoded)
    {
        $coordenadas = [];
        $index = 0;
        $latitude = 0;
        $longitude = 0;

        $length = strlen($encoded);

        while ($index < $length) {
            $resultado = 0;
            $shift = 0;

            do {
                $b = ord($encoded[$index++]) - 63;
                $resultado |= ($b & 0x1f) << $shift;
                $shift += 5;
            } while ($b >= 0x20);

            $deltaLatitude = ($resultado & 1)
                ? ~($resultado >> 1)
                : ($resultado >> 1);

            $latitude += $deltaLatitude;

            $resultado = 0;
            $shift = 0;

            do {
                $b = ord($encoded[$index++]) - 63;
                $resultado |= ($b & 0x1f) << $shift;
                $shift += 5;
            } while ($b >= 0x20);

            $deltaLongitude = ($resultado & 1)
                ? ~($resultado >> 1)
                : ($resultado >> 1);

            $longitude += $deltaLongitude;

            $coordenadas[] = [
                'latitude' => $latitude / 100000,
                'longitude' => $longitude / 100000,
            ];
        }

        return $coordenadas;
    }


    private function analisarRiscosRota($coordenadas, $pontosRisco)
    {
        $raioSeguranca = 50;
        $riscosEncontrados = [];
        $pontuacaoRisco = 0;

        foreach ($pontosRisco as $pontoRisco) {

            $ponto = [
                'latitude' => (float) $pontoRisco->latitude,
                'longitude' => (float) $pontoRisco->longitude,
            ];

            $menorDistancia = PHP_FLOAT_MAX;

            for ($i = 0; $i < count($coordenadas) - 1; $i++) {

                $inicio = $coordenadas[$i];
                $fim = $coordenadas[$i + 1];

                $distancia = $this->distanciaPontoSegmento(
                    $ponto,
                    $inicio,
                    $fim
                );

                if ($distancia < $menorDistancia) {
                    $menorDistancia = $distancia;
                }
            }

            if ($menorDistancia <= $raioSeguranca) {

                $nivel = (int) $pontoRisco->nivel_risco;

                $pontuacaoRisco += $nivel;

                $riscosEncontrados[] = [
                    'cod' => $pontoRisco->cod,
                    'tipo' => $pontoRisco->tipo,
                    'nivel_risco' => $nivel,
                    'distancia_metros' => round($menorDistancia, 2),
                ];
            }
        }

        return [
            'pontuacao_risco' => $pontuacaoRisco,
            'quantidade_pontos_risco' => count($riscosEncontrados),
            'pontos_risco_proximos' => $riscosEncontrados,
        ];
    }
}