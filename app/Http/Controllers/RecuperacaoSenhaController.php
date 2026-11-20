<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class RecuperacaoSenhaController extends Controller
{
    public function solicitar(Request $request)
    {
        $dados = $request->validate([
            'email' => 'required|email|max:90',
        ]);

        $email = $dados['email'];

        // Verifica se existe um usuário com esse e-mail
        $usuario = Usuario::where('email', $email)->first();

        // Resposta genérica para não revelar se o e-mail existe
        $mensagem = 'Se o e-mail estiver cadastrado, você receberá um código de recuperação.';

        if (!$usuario) {
            return response()->json([
                'mensagem' => $mensagem
            ], 200);
        }

        // Gera um código de 6 dígitos
        $codigo = str_pad(
            random_int(0, 999999),
            6,
            '0',
            STR_PAD_LEFT
        );

        // Salva o código com hash no banco
        DB::table('recuperacao_senha')->updateOrInsert(
            ['email' => $email],
            [
                'codigo' => Hash::make($codigo),
                'expira_em' => now()->addMinutes(10),
                'tentativas' => 0,
                'criado_em' => now(),
            ]
        );

        // Envia o código por e-mail
        Mail::raw(
            "Olá!\n\n"
            . "Você solicitou a recuperação da sua senha do Olympe for Safe.\n\n"
            . "Seu código de recuperação é: {$codigo}\n\n"
            . "Este código expira em 10 minutos.\n\n"
            . "Se você não solicitou essa recuperação, ignore este e-mail.",
            function ($message) use ($email) {
                $message->to($email)
                    ->subject('Recuperação de senha - Olympe for Safe');
            }
        );

        return response()->json([
            'mensagem' => $mensagem
        ], 200);
    }
}
