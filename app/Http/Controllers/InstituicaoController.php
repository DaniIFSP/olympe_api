<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Instituicao;
use App\Models\EnderecoInstituicao;
use Illuminate\Support\Facades\Storage;

class InstituicaoController extends Controller
{
    //LISTAR

    public function index()
    {
        $instituicoes = Instituicao::with('endereco')->get();

        $instituicoes->transform(function ($instituicao) {
            if ($instituicao->imagem) {
                $instituicao->imagem = asset (
                    'storage/' . $instituicao->imagem
                );
            }

            return $instituicao;
        });

        return response()->json([
            "instituicoes"=>$instituicoes
        ]);
    }

    //CADASTRAR

    public function store(Request $request)
    {

        $request->validate([
            'nome' => 'required|string|max:90',
            'descricao' => 'nullable|string|max:300',
            'imagem' => 'nullable|image|mimes|jpeg,png,jpg,webp|max:5120',
            'endereco' => 'nullable|array',
        ]);

        $caminhoImagem = null;

        if ($request->hasFile('imagem')) {
            $caminhoImagem = $request->file('imagem')
                ->store('instituicoes', 'public');
        }

        $instituicao = Instituicao::create([

            'nome'=>$request->nome,
            'imagem'=>$caminhoImagem,
            'descricao'=>$request->descricao

        ]);

        if($request->endereco)
        {
            $instituicao->endereco()->create(
                $request->endereco
            );
        }

        return response()->json([
            "mensagem"=>"Instituição cadastrada",
            "dados"=>$instituicao->load('endereco')
        ],201);
    }

    //MOSTRAR 
    
    public function show($id)
    {
        $instituicao = Instituicao::with('endereco')
            ->find($id);

        if(!$instituicao)
        {
            return response()->json([
                "erro"=>"Instituição não encontrada"
            ],404);
        }

        return response()->json([
            "cod"=>$instituicao->cod,
            "nome"=> $instituicao->nome,
            "imagem"=>$instituicao->imagem
                ? asset('storage/' . $instituicao->imagem)
                : null,
            "descricao"=>$instituicao->descricao,
            "enderecos"=>$instituicao->endereco->map(function($endereco){
                return [
                    "telefone"=>$endereco->telefone,
                    "endereco"=>
                    $endereco->logradouro . "," .
                    $endereco->numero . "-" . 
                    $endereco->bairro, 
                    "cidade"=>$endereco->cidade,
                    "uf"=>$endereco->uf,
                    "latitude"=>$endereco->latitude,
                    "longitude"=>$endereco->longitude,
                    "horario_inicio"=>$endereco->horario_inicio,
                    "horario_fim"=>$endereco->horario_fim
                ];
            })
        ]);
    }

    // ATUALIZAR 

    public function update(Request $request, string $id)
    {
        $instituicao = Instituicao::find($id);

        if(!$instituicao)
        {
            return response()->json([
                "erro"=>"Instituição não encontrada"
            ],404);
        }

        $request->validate([
            'nome' => 'sometimes|required|string|max:90',
            'descricao' => 'nullable|string|max:300',
            'imagem' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'endereco' => 'nullable|array',
        ]);

        $dados = [
            'nome' => $request->nome,
            'descricao' => $request->descricao
        ];

        if ($request->hasFile('imagem')) {

            if ($instituicao->imagem) {
                Storage::disk('public')->delete(
                    $instituicao->imagem
                );
            }

            $dados['imagem'] = $request->file('imagem')
                ->store('instituicoes', 'public');
        }

        $instituicao->update(dados);

        if($instituicao->endereco && $request->endereco) {
            $instituicao->endereco->update(
                $request->endereco
            );
        }
        
        return response()->json([
            "mensagem"=>"Atualizado com sucesso",
            "dados" => $instituicao->load('endereco')
        ]);
    }

    // EXCLUIR

    public function destroy($id)
    {
        $instituicao = Instituicao::find($id);

        if(!$instituicao)
        {
            return response()->json([
                "erro"=>"Instituição não encontrada"
            ],404);
        }

        if ($instituicao->imagem) {
            Storage::disk('public')->delete(
                $instituicao->imagem
            );
        }

        $instituicao->delete();

        return response()->json([
            "mensagem"=>"Excluída com sucesso"
        ]);
    }
}
