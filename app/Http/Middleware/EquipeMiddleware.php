<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EquipeMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if (
            !$usuario ||
            !in_array($usuario->tipo, ['administrador', 'funcionario'])
        ) {
            return response()->json([
                'mensagem' => 'Acesso negado. Somente membros da equipe podem acessar.'
            ], 403);
        }

        return $next($request);
    }
}