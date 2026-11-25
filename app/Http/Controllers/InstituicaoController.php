<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Instituicao;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class InstituicaoController extends Controller
{
    // LISTAR

    public function index()
    {
        $instituicoes = Instituicao::with('endereco')->get();

        $instituicoes->transform(function ($instituicao) {

            if ($instituicao->imagem) {
                $instituicao->imagem = asset(
                    'storage/' . $instituicao->imagem
                );
            }

            return $instituicao;
        });

        return response()->json([
            "instituicoes" => $instituicoes
        ]);
    }


    // CADASTRAR

    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:90',
            'descricao' => 'nullable|string|max:300',
            'imagem' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',

            'enderecos' => 'nullable|array',

            'enderecos.*.telefone' => 'nullable|string|max:30',
            'enderecos.*.cep' => 'nullable|string|max:9',
            'enderecos.*.logradouro' => 'nullable|string|max:255',
            'enderecos.*.numero' => 'nullable|string|max:20',
            'enderecos.*.complemento' => 'nullable|string|max:100',
            'enderecos.*.bairro' => 'nullable|string|max:100',
            'enderecos.*.cidade' => 'nullable|string|max:100',
            'enderecos.*.uf' => 'nullable|string|max:2',
            'enderecos.*.dia_semana' => 'nullable|string|max:100',
            'enderecos.*.horario_inicio' => 'nullable|string|max:10',
            'enderecos.*.horario_fim' => 'nullable|string|max:10',
        ]);

        $caminhoImagem = null;

        if ($request->hasFile('imagem')) {
            $caminhoImagem = $request->file('imagem')
                ->store('instituicoes', 'public');
        }

        $instituicao = Instituicao::create([
            'nome' => $request->nome,
            'imagem' => $caminhoImagem,
            'descricao' => $request->descricao
        ]);

        // Salvar os vários endereços

        if ($request->has('enderecos') && is_array($request->enderecos)) {

            foreach ($request->enderecos as $enderecoData) {

                // Remove campos totalmente vazios

                $enderecoData = array_filter(
                    $enderecoData,
                    function ($valor) {
                        return $valor !== null && $valor !== '';
                    }
                );

                if (!empty($enderecoData)) {
                    $instituicao->endereco()->create($enderecoData);
                }
            }
        }

        return response()->json([
            "mensagem" => "Instituição cadastrada com sucesso",
            "dados" => $instituicao->load('endereco')
        ], 201);
    }


    // MOSTRAR

    public function show($id)
    {
        $instituicao = Instituicao::with('endereco')
            ->find($id);

        if (!$instituicao) {
            return response()->json([
                "erro" => "Instituição não encontrada"
            ], 404);
        }

        return response()->json([
            "cod" => $instituicao->cod,
            "nome" => $instituicao->nome,

            "imagem" => $instituicao->imagem
                ? asset('storage/' . $instituicao->imagem)
                : null,

            "descricao" => $instituicao->descricao,

            "enderecos" => $instituicao->endereco->map(function ($endereco) {

                return [
                    "cod" => $endereco->cod,
                    "telefone" => $endereco->telefone,
                    "cep" => $endereco->cep,
                    "logradouro" => $endereco->logradouro,
                    "numero" => $endereco->numero,
                    "complemento" => $endereco->complemento,
                    "bairro" => $endereco->bairro,
                    "cidade" => $endereco->cidade,
                    "uf" => $endereco->uf,
                    "dia_semana" => $endereco->dia_semana,
                    "latitude" => $endereco->latitude,
                    "longitude" => $endereco->longitude,
                    "horario_inicio" => $endereco->horario_inicio,
                    "horario_fim" => $endereco->horario_fim
                ];
            })
        ]);
    }


    // ATUALIZAR

    public function update(Request $request, string $id)
    {
        $instituicao = Instituicao::with('endereco')
            ->find($id);

        if (!$instituicao) {
            return response()->json([
                "erro" => "Instituição não encontrada"
            ], 404);
        }

        $request->validate([
            'nome' => 'sometimes|required|string|max:90',
            'descricao' => 'nullable|string|max:300',
            'imagem' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',

            'enderecos' => 'nullable|array',

            'enderecos.*.cod' => 'nullable|integer',
            'enderecos.*.telefone' => 'nullable|string|max:30',
            'enderecos.*.cep' => 'nullable|string|max:9',
            'enderecos.*.logradouro' => 'nullable|string|max:255',
            'enderecos.*.numero' => 'nullable|string|max:20',
            'enderecos.*.complemento' => 'nullable|string|max:100',
            'enderecos.*.bairro' => 'nullable|string|max:100',
            'enderecos.*.cidade' => 'nullable|string|max:100',
            'enderecos.*.uf' => 'nullable|string|max:2',
            'enderecos.*.dia_semana' => 'nullable|string|max:100',
            'enderecos.*.horario_inicio' => 'nullable|string|max:10',
            'enderecos.*.horario_fim' => 'nullable|string|max:10',
        ]);

        // Atualizar nome e descrição

        $dados = [];

        if ($request->has('nome')) {
            $dados['nome'] = $request->nome;
        }

        if ($request->has('descricao')) {
            $dados['descricao'] = $request->descricao;
        }

        // Atualizar imagem, se uma nova foi enviada

        if ($request->hasFile('imagem')) {

            if ($instituicao->imagem) {
                Storage::disk('public')->delete(
                    $instituicao->imagem
                );
            }

            $dados['imagem'] = $request->file('imagem')
                ->store('instituicoes', 'public');
        }

        if (!empty($dados)) {
            $instituicao->update($dados);
        }

        // Atualizar endereços, caso tenham sido enviados

        if ($request->has('enderecos') && is_array($request->enderecos)) {

            $enderecosRecebidos = $request->enderecos;

            // IDs dos endereços que continuam existindo

            $codigosMantidos = [];

            foreach ($enderecosRecebidos as $enderecoData) {

                $codigoEndereco = $enderecoData['cod'] ?? null;

                // Remove o código dos dados que serão salvos

                unset($enderecoData['cod']);

                // Remove campos vazios

                $enderecoData = array_filter(
                    $enderecoData,
                    function ($valor) {
                        return $valor !== null && $valor !== '';
                    }
                );

                if (empty($enderecoData)) {
                    continue;
                }

                if ($codigoEndereco) {

                    // Atualiza endereço existente pertencente à instituição

                    $endereco = $instituicao->endereco()
                        ->where('cod', $codigoEndereco)
                        ->first();

                    if ($endereco) {
                        $endereco->update($enderecoData);

                        $codigosMantidos[] = $codigoEndereco;
                    } else {
                        // Código não pertence à instituição:
                        // cadastra como um novo endereço

                        $novoEndereco = $instituicao->endereco()
                            ->create($enderecoData);

                        $codigosMantidos[] = $novoEndereco->cod;
                    }

                } else {

                    // Cadastrar endereço novo

                    $novoEndereco = $instituicao->endereco()
                        ->create($enderecoData);

                    $codigosMantidos[] = $novoEndereco->cod;
                }
            }

            // Remover endereços que não foram enviados

            $instituicao->endereco()
                ->whereNotIn('cod', $codigosMantidos)
                ->delete();
        }

        return response()->json([
            "mensagem" => "Instituição atualizada com sucesso",
            "dados" => $instituicao->load('endereco')
        ]);
    }


    // EXCLUIR

    public function destroy($id)
    {
        $instituicao = Instituicao::find($id);

        if (!$instituicao) {
            return response()->json([
                "erro" => "Instituição não encontrada"
            ], 404);
        }

        if ($instituicao->imagem) {
            Storage::disk('public')->delete(
                $instituicao->imagem
            );
        }

        // Excluir os endereços vinculados

        $instituicao->endereco()->delete();

        $instituicao->delete();

        return response()->json([
            "mensagem" => "Instituição excluída com sucesso"
        ]);
    }
}