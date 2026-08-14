<?php

namespace App\Http\Controllers;

use App\Models\VerificacaoTelefone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class VerificacaoTelefoneController extends Controller
{
    public function enviar(Request $request)
    {
        $dados = $request->validate([
            'telefone' => 'required|string|max:20',
        ]);

        VerificacaoTelefone::where(
            'telefone',
            $dados['telefone']
        )->delete();

        $codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        VerificacaoTelefone::create([
            'telefone' => $dados['telefone'],
            'codigo' => Hash::make($codigo),
            'expira_em' => now()->addMinutes(5),
            'verificado' => false,
        ]);

        return response()->json([
            'mensagem' => 'Código gerado com sucesso.',
            'codigo_teste' => $codigo,
        ]);
    }

    public function confirmar(Request $request)
    {
        $dados = $request->validate([
            'telefone' => 'required|string|max:20',
            'codigo' => 'required|string|digits:6',
        ]);

        $verificacao = VerificacaoTelefone::where(
            'telefone', $dados['telefone']
        )
            ->where('verificado', false)
            ->latest('cod')
            ->first();

        if (!$verificacao) {
            return response()->json([
                'mensagem' => 'Verificação falhou.',
            ], 404);
        }

        if ($verificacao->expira_em->isPast()) {
            return response()->json([
                'mensagem' => 'O código expirou.',
            ], 422);
        }

        if(!Hash::check (
            $dados['codigo'],
            $verificacao->codigo
        )) {
            return response()->json([
                'mensagem' => 'Código inválido.',
            ], 422);
        }

        $verificacao->update([
            'verificado' => true,
        ]);

        return response()->json([
            'mensagem' => 'Telefone verificado com sucesso.',
        ]);
    }
}
