<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdministradorMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $usuario = $rrequest->user();

        if(!$usuario || $usuario->tipo !== 'administrador') {
            return response()->json([
                'mensagem' => 'Acesso negado, somente administradores.'
            ], 403);
        }
    
        return $next($request);
    }
}
