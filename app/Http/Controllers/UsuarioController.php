<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Models\VerificacaoTelefone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    //LISTAR

    public function index()
    {
        $usuarios = Usuario::all();

        return response()->json([
            'usuarios' => $usuarios
        ], 200);
    }

    // CADASTRAR

    public function store(Request $request)
    {
    $dados = $request->validate([
        'nome' => 'required|string|max:90',
        'idade' => 'nullable|integer|min:0|max:130',
        'email' => 'required|email|max:90|unique:tb_usuarios,email',
        'telefone' => [
            'required',
            'string',
            'max:20',
            'unique:tb_usuarios,telefone',
            'regex:/^\+[1-9][0-9]{7,14}$/',
        ],
        'senha' => 'required|string|min:8|max:255',
    ], [
        'email.email' => 'Informe um e-mail válido.',
        'email.unique' => 'Este e-mail já está cadastrado.',
        'telefone.unique' => 'Este telefone já está cadastrado.',
        'telefone.regex' =>
            'Informe o telefone no formato internacional, como +5511999999999.',
        'senha.min' => 'A senha deve possuir pelo menos 8 caracteres.',
    ]);

    //Verificação do telefone 

    $verificacao = VerificacaoTelefone::where(
        'telefone',
        $dados['telefone']
    )
        ->where('verificado', true)
        ->where('expira_em', '>=', now())
        ->latest('cod')
        ->first();

    if (!$verificacao) {
        return response()->json([
            'mensagem' =>
                'O telefone precisa ser verificado antes do cadastro.'
        ], 422);
    }

    $dados['senha'] = Hash::make($dados['senha']);

    $dados['tipo'] = 'usuario';

    $usuario = DB::transaction(function () use (
        $dados,
        $verificacao
    ) {
        $usuario = \App\Models\Usuario::create($dados);

        $verificacao->delete();

        return $usuario;
    });

    return response()->json([
        'mensagem' => 'Usuário cadastrado com sucesso.',
        'usuario' => $usuario,
    ], 201);
}


// CADASTRAR FUNCIONÁRIO OU ADMINISTRADOR

public function cadastrarEquipe(Request $request)
{
    $dados = $request->validate([
        'nome' => 'required|string|max:90',
        'idade' => 'nullable|integer|min:0|max:130',
        'email' => 'required|email|max:90|unique:tb_usuarios,email',
        'telefone' => [
            'required',
            'string',
            'max:20',
            'unique:tb_usuarios,telefone',
            'regex:/^\+[1-9][0-9]{7,14}$/',
        ],
        'senha' => 'required|string|min:8|max:255',
        'tipo' => 'required|in:funcionario,administrador',
    ], [
        'email.unique' => 'Este e-mail já está cadastrado.',
        'telefone.unique' => 'Este telefone já está cadastrado.',
        'telefone.regex' => 'Informe o telefone no formato internacional.',
        'senha.min' => 'A senha deve possuir pelo menos 8 caracteres.',
        'tipo.in' => 'O perfil selecionado é inválido.',
    ]);

    $dados['senha'] = Hash::make($dados['senha']);

    $usuario = Usuario::create($dados);

    return response()->json([
        'mensagem' => 'Membro da equipe cadastrado com sucesso.',
        'usuario' => $usuario,
    ], 201);
}


    // LISTAR MEMBROS DA EQUIPE
    public function listarEquipe()
    {
        $usuarios = Usuario::whereIn('tipo', [
            'funcionario',
            'administrador'
        ])
        ->select([
            'cod',
            'nome',
            'email',
            'telefone',
            'idade',
            'tipo'
        ])
        ->orderBy('nome')
        ->get();

        return response()->json($usuarios);
    }


    // EDITAR MEMBRO DA EQUIPE
    public function atualizarEquipe(Request $request, $id)
    {
        $usuario = Usuario::whereIn('tipo', [
            'funcionario',
            'administrador'
        ])->findOrFail($id);

        $dados = $request->validate([
            'nome' => 'required|string|max:90',
            'idade' => 'nullable|integer|min:0|max:130',
            'email' => 'required|email|max:90|unique:tb_usuarios,email,' . $usuario->cod . ',cod',
            'telefone' => [
                'required',
                'string',
                'max:20',
                'unique:tb_usuarios,telefone,' . $usuario->cod . ',cod',
                'regex:/^\+[1-9][0-9]{7,14}$/',
            ],
            'tipo' => 'required|in:funcionario,administrador',
        ], [
            'email.unique' => 'Este e-mail já está cadastrado.',
            'telefone.unique' => 'Este telefone já está cadastrado.',
            'telefone.regex' => 'Informe o telefone no formato internacional.',
            'tipo.in' => 'O perfil selecionado é inválido.',
        ]);

        // Impede que o administrador altere seu próprio perfil de acesso
        if (
            $usuario->cod == $request->user()->cod &&
            $dados['tipo'] !== $usuario->tipo
        ) {
            return response()->json([
                'mensagem' => 'Você não pode alterar seu próprio perfil de acesso.'
            ], 403);
        }

        $usuario->nome = $dados['nome'];
        $usuario->idade = $dados['idade'] ?? null;
        $usuario->email = $dados['email'];
        $usuario->telefone = $dados['telefone'];
        $usuario->tipo = $dados['tipo'];

        $usuario->save();

        return response()->json([
            'mensagem' => 'Membro da equipe atualizado com sucesso.',
            'usuario' => $usuario->only([
                'cod',
                'nome',
                'idade',
                'email',
                'telefone',
                'tipo',
                'ativo'
            ]),
        ]);
    }


    // ATIVAR OU DESATIVAR MEMBRO DA EQUIPE
    public function alterarSituacaoEquipe(Request $request, $id)
    {
        $usuario = Usuario::whereIn('tipo', [
            'funcionario',
            'administrador'
        ])->findOrFail($id);

        $dados = $request->validate([
            'ativo' => 'required|boolean',
        ]);

        // Impede o administrador de desativar a própria conta
        if (
            $usuario->cod == $request->user()->cod &&
            !$dados['ativo']
        ) {
            return response()->json([
                'mensagem' => 'Você não pode desativar sua própria conta.'
            ], 403);
        }

        $usuario->ativo = $dados['ativo'];
        $usuario->save();

        // Se desativar, encerra as sessões existentes
        if (!$usuario->ativo) {
            $usuario->tokens()->delete();
        }

        return response()->json([
            'mensagem' => $usuario->ativo
                ? 'Membro da equipe ativado com sucesso.'
                : 'Membro da equipe desativado com sucesso.',
            'usuario' => $usuario->only([
                'cod',
                'nome',
                'email',
                'telefone',
                'tipo',
                'ativo'
            ]),
        ]);
    }


    //LISTAR UM 

    public function show(string $id)
    {
        $usuario = Usuario::with('contatos')->find($id);

        if(!$usuario) {
            return response()->json([
                'mensagem' => 'Uusário não encontrado.'
            ], 404);
        }

        return response()->json([
            'usuario' => $usuario
        ], 200);
    }

    //ATUALIZAR

    public function updateMe(Request $request)
{
    $usuario = $request->user();

    $dados = $request->validate([
        'nome' => 'sometimes|required|string|max:90',

        'email' => [
            'sometimes',
            'required',
            'email',
            'max:90',
            Rule::unique('tb_usuarios', 'email')
                ->ignore($usuario->cod, 'cod'),
        ],

        'telefone' => [
            'sometimes',
            'required',
            'string',
            'max:20',
            Rule::unique('tb_usuarios', 'telefone')
                ->ignore($usuario->cod, 'cod'),
        ],

        'senha' => 'sometimes|required|string|min:8|max:255',
    ]);

    if (isset($dados['senha'])) {
        $dados['senha'] = Hash::make($dados['senha']);
    }

    $usuario->update($dados);

    return response()->json([
        'mensagem' => 'Dados atualizados com sucesso.',
        'usuario' => $usuario
    ]);
}

    public function destroy(string $id)
    {
        $usuario = Usuario::find($id);

        if(!$usuario) {
            return response()->json([
                'mensagem' => 'Usuário não encontrado.'
            ], 404);
        }

        $usuario->delete();

        return response()->json([
            'mensagem' => 'Usuário excluído com sucesso.'
        ], 200);
    }

}
