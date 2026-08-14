<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $dados = $request->validate([
            'email' => 'required|email',
            'senha' => 'required|string',
        ], [
            'email.email' => 'Informe um e-mail válido.'
        ]);

        $usuario = Usuario::where('email', $dados['email'])->first();

        if(!$usuario || !Hash::check($dados['senha'], $usuario->senha)) {
            return response()->json([
                'mensagem' => 'E-mail ou senha inválidos.'
            ], 401);
        }

        //apaga tokens antigos, para iniciar uma nova sessão.
        $usuario->tokens()->delete();
        
        $token = $usuario->createToken('olympe-app')->plainTextToken;

        return response()->json([
            'mensagem' => 'Login realizado com sucesso.',
            'token' => $token,
            'tipo_token' => 'Baerer',
            'usuario' => $usuario,
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'mensagem' => 'Logout realizado com sucesso.'
        ], 200);
    }

    public function me(Request $request)
    {
        return response()->json([
            'usuario' => $request->user(),
        ], 200);
    }
}
