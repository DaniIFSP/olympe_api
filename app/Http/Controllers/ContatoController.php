<?php

namespace App\Http\Controllers;

use App\Models\Contato;
use Illuminate\Http\Request;

class ContatoController extends Controller
{
    // LISTAR OS CONTATOS DO USUÁRIO AUTENTICADO

    public function index(Request $request)
    {
        $contatos = Contato::where(
            'cod_usuario',
            $request->user()->cod
        )->get();

        return response()->json([
            'contatos' => $contatos
        ], 200);
    }

    // CADASTRAR

    public function store(Request $request)
    {
        $dados = $request->validate([
            'nome' => 'required|string|max:90',
            'telefone' => 'required|string|max:15',
            'parentesco' => 'nullable|string|max:30',
        ]);

        // O código vem do token, não do JSON enviado pelo cliente.
        $dados['cod_usuario'] = $request->user()->cod;

        $contato = Contato::create($dados);

        return response()->json([
            'mensagem' => 'Contato cadastrado com sucesso.',
            'contato' => $contato
        ], 201);
    }

    // MOSTRAR UM CONTATO DO USUÁRIO AUTENTICADO

    public function show(Request $request, string $id)
    {
        $contato = Contato::where('cod', $id)
            ->where('cod_usuario', $request->user()->cod)
            ->first();

        if (!$contato) {
            return response()->json([
                'mensagem' => 'Contato não encontrado.'
            ], 404);
        }

        return response()->json([
            'contato' => $contato
        ], 200);
    }

    // ATUALIZAR UM CONTATO DO USUÁRIO AUTENTICADO

    public function update(Request $request, string $id)
    {
        $contato = Contato::where('cod', $id)
            ->where('cod_usuario', $request->user()->cod)
            ->first();

        if (!$contato) {
            return response()->json([
                'mensagem' => 'Contato não encontrado.'
            ], 404);
        }

        $dados = $request->validate([
            'nome' => 'sometimes|required|string|max:90',
            'telefone' => 'sometimes|required|string|max:15',
            'parentesco' => 'sometimes|nullable|string|max:30',
        ]);

        $contato->update($dados);

        return response()->json([
            'mensagem' => 'Contato atualizado com sucesso.',
            'contato' => $contato
        ], 200);
    }

    // EXCLUIR UM CONTATO DO USUÁRIO AUTENTICADO

    public function destroy(Request $request, string $id)
    {
        $contato = Contato::where('cod', $id)
            ->where('cod_usuario', $request->user()->cod)
            ->first();

        if (!$contato) {
            return response()->json([
                'mensagem' => 'Contato não encontrado.'
            ], 404);
        }

        $contato->delete();

        return response()->json([
            'mensagem' => 'Contato excluído com sucesso.'
        ], 200);
    }
}
