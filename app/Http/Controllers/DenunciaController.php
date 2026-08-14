<?php

namespace App\Http\Controllers;

use App\Models\Denuncia;
use Illuminate\Http\Request;

class DenunciaController extends Controller
{
    //LISTAR TODOS

    public function index()
    {
        $denuncias = Denuncia::with(
            'usuarios:cod,nome,email,telefone'
        )
            ->orderByDesc('data_denuncia')
            ->get();
        
            return response()->json([
                'denuncias' => $denuncias
            ], 200);
    }

    //CADASTRAR 

    public function store(Request $request)
{
    $dados = $request->validate([
        'latitude' => 'required|numeric|between:-90,90',
        'longitude' => 'required|numeric|between:-180,180',
        'tipo' => 'required|string|max:50',
        'descricao' => 'nullable|string|max:300',
    ]);

    $dados['cod_usuario'] = $request->user()->cod;
    $dados['status'] = 'Pendente';

    $denuncia = Denuncia::create($dados);

    return response()->json([
        'mensagem' => 'Denúncia enviada com sucesso.',
        'protocolo' => $denuncia->cod,
    ], 201);
}

    // BUSCAR

    public function show(string $id)
    {
        $denuncia = Denuncia::with(
            'usuario:cod,nome,email,telefone'
        )->find($id);

        if (!$denuncia) {
            return response()->json([
                'mensagem' => 'Denúncia não encontrada'
            ], 404);
        }

        return response()->json([
            'denuncia' => $denuncia
        ], 200);
    }

    //ATUALIZAR DENÚNCIA

    public function atualizarStatus(Request $request, string $id)
    {
    $denuncia = Denuncia::find($id);

    if (!$denuncia) {
        return response()->json([
            'mensagem' => 'Denúncia não encontrada.'
        ], 404);
    }

    $dados = $request->validate([
        'status' => 'required|in:Pendente,Em análise,Resolvida,Arquivada',
    ]);

    $denuncia->update([
        'status' => $dados['status']
    ]);

    return response()->json([
        'mensagem' => 'Status atualizado com sucesso.',
        'denuncia' => $denuncia
    ]);
    }

    //EXCLUIR DENÚNCIA

    public function destroy(string $id)
    {
        $denuncia = Denuncia::find($id);

        if(!$denuncia) {
            return response()->json([
                'mensagem' => 'Denúncia não encontrada.'
            ], 404);
        }

        $denunciaa->delete();

        return response()->json([
            'mensagem' => 'Denúnia excluída com sucesso.'
        ], 200);
    }
}
