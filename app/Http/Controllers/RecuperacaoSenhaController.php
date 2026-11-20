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


    public function verificar(Request $request)
    {
        $dados = $request->validate([
            'email' => 'required|email|max:90',
            'codigo' => 'required|string|size:6',
        ]);

        $email = $dados['email'];
        $codigo = $dados['codigo'];

        $recuperacao = DB::table('recuperacao_senha')
            ->where('email', $email)
            ->first();

        if (!$recuperacao) {
            return response()->json([
                'mensagem' => 'Solicite um novo código de recuperação.'
            ], 422);
        }

        // Verifica se o código expirou
        if (now()->greaterThan($recuperacao->expira_em)) {
            DB::table('recuperacao_senha')
                ->where('email', $email)
                ->delete();

            return response()->json([
                'mensagem' => 'O código expirou. Solicite um novo código.'
            ], 422);
        }

        // Verifica se já atingiu o limite de tentativas
        if ($recuperacao->tentativas >= 5) {
            DB::table('recuperacao_senha')
                ->where('email', $email)
                ->delete();

            return response()->json([
                'mensagem' => 'Limite de tentativas atingido. Solicite um novo código.'
            ], 429);
        }

        // Confere o código digitado com o hash armazenado
        if (!Hash::check($codigo, $recuperacao->codigo)) {

            DB::table('recuperacao_senha')
                ->where('email', $email)
                ->increment('tentativas');

            return response()->json([
                'mensagem' => 'Código incorreto. Verifique e tente novamente.'
            ], 422);
        }

        // Código correto: registra a validação
        DB::table('recuperacao_senha')
            ->where('email', $email)
            ->update([
                'codigo_validado_em' => now()
            ]);

        return response()->json([
            'mensagem' => 'Código verificado com sucesso!',
            'validado' => true
        ], 200);
    }

    public function redefinir(Request $request)
    {
        $dados = $request->validate([
            'email' => 'required|email|max:90',
            'senha' => 'required|string|min:8|confirmed',
        ]);

        $email = $dados['email'];

        $recuperacao = DB::table('recuperacao_senha')
            ->where('email', $email)
            ->first();

        if (!$recuperacao) {
            return response()->json([
                'mensagem' => 'Solicite uma nova recuperação de senha.'
            ], 422);
        }

        // Verifica se o código foi validado
        if (!$recuperacao->codigo_validado_em) {
            return response()->json([
                'mensagem' => 'Você precisa validar o código primeiro.'
            ], 422);
        }

        // Verifica se o código original ainda está dentro da validade
        if (now()->greaterThan($recuperacao->expira_em)) {
            DB::table('recuperacao_senha')
                ->where('email', $email)
                ->delete();

            return response()->json([
                'mensagem' => 'O código expirou. Solicite um novo código.'
            ], 422);
        }

        // Verifica se a validação do código foi recente (10 minutos)
        if (now()->greaterThan(
            \Carbon\Carbon::parse($recuperacao->codigo_validado_em)->addMinutes(10)
        )) {
            DB::table('recuperacao_senha')
                ->where('email', $email)
                ->delete();

            return response()->json([
                'mensagem' => 'A validação expirou. Solicite um novo código.'
            ], 422);
        }

        // Busca o usuário
        $usuario = Usuario::where('email', $email)->first();

        if (!$usuario) {
            DB::table('recuperacao_senha')
                ->where('email', $email)
                ->delete();

            return response()->json([
                'mensagem' => 'Usuário não encontrado.'
            ], 404);
        }

        // Salva a nova senha com hash
        $usuario->senha = Hash::make($dados['senha']);
        $usuario->save();

        // Revoga tokens antigos, caso existam
        $usuario->tokens()->delete();

        // Apaga o código de recuperação utilizado
        DB::table('recuperacao_senha')
            ->where('email', $email)
            ->delete();

        return response()->json([
            'mensagem' => 'Senha redefinida com sucesso! Você já pode fazer login.'
        ], 200);
    }

}
