<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Instituicao;
use App\Models\EnderecoInstituicao;

class InstituicaoController extends Controller
{
    //LISTAR

    public function index()
    {
        $instituicoes = Instituicao::with('endereco')->get();

        return response()->json([
            "instituicoes"=>$instituicoes
        ]);
    }

    //CADASTRAR

    public function store(Request $request)
    {
        $instituicao = Instituicao::create([

            'nome'=>$request->nome,
            'imagem'=>$request->imagem,
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
            "imagem"=>$instituicao->imagem,
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

        $instituicao->update([
            'nome'=>$request->nome,
            'imagem'=>$request->imagem,
            'descricao'=>$request->descricao
        ]);

        if($instituicao->endereco && $request->endereco)
        {
            $instituicao->endereco->update(
                $request->endereco
            );
        }
        
        return response()->json([
            "mensagem"=>"Atualizado com sucesso"
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

        $instituicao->delete();

        return response()->json([
            "mensagem"=>"Excluída com sucesso"
        ]);
    }
}
