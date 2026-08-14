<?php

namespace App\Http\Controllers;

use App\Models\PontoRisco;
use Illuminate\Http\Request;

class PontoRiscoController extends Controller
{
    //LISTAR

    public function index()
    {
        $pontos = PontoRisco::orderByDesc(
            'data_denuncia'
        )->get();

        return response()->json([
            'pontos_risco' => $pontos
        ], 200);
    }

    //CADASTRAR

    public function store(Request $request)
    {
        $dados = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'tipo' => 'required|string|max:90',
            'descricao' => 'nullable|string|max:300',
            'data_denuncia' => 'nullable|date_format:m-d-Y',
        ]);

        $ponto = PontoRisco::create($dados);

        return response()->json([
            'mensagem' => 'Ponto de risco cadastrado com sucesso.',
            'ponto_risco' => $ponto
        ], 201);
    }

    //BUSCAR

    public function show(string $id)
    {
        $ponto = PontoRisco::find($id);

        if(!$ponto) {
            return response()->json([
                'mensagem' => 'Ponto de risco não encontrado.0'
            ], 404);
        }

        return response()->json([
            'ponto_risco' => $ponto
        ], 200);
    }

    //ATUALIZAR

    public function update(Request $request, string $id)
    {
        $ponto = PontoRisco::find($id);

        if(!$ponto) {
            return response()->json([
                'mensagem' => 'Ponto de risco não encontrado.0'
            ], 404);
        }

        $dados = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'tipo' => 'required|string|max:90',
            'descricao' => 'nullable|string|max:300',
            'data_denuncia' => 'nullable|date_format:m-d-Y',
        ]);

        $ponto->update($dados);

        return response()->json([
            'mensagem' => 'Ponto de risco atualizado com sucesso.',
            'ponto_risco' => $ponto
        ], 200);
    }

    //EXCLUIR 

    public function destroy(string $id)
    {
        $ponto = PontoRisco::find($id);

        if(!$ponto) {
            return response()->json([
                'mensagem' => 'Ponto de risco não encontrado.'
            ], 404);
        }

        $ponto->delete();

        return response()->json([
            'mensagem' => 'Ponto de risco excluído com sucesso.'
        ], 200);
    }
}
