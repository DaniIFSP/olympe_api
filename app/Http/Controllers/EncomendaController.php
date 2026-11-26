<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class Encomenda extends Controller
{
    public function store(Request $request)
    {
        // Validar os dados recebidos
        $dados = $request->validate([
            'nome' => 'required|string|max:150',
            'email' => 'required|email|max:255',
            'cidade' => 'required|string|max:100',
            'estado' => 'required|string|size:2',
            'quantidade' => 'required|integer|min:1|max:1000',
            'observacoes' => 'nullable|string|max:1000',
            'autorizacao' => 'required|accepted',
        ], [
            'nome.required' => 'Informe seu nome.',
            'email.required' => 'Informe seu e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'cidade.required' => 'Informe sua cidade.',
            'estado.required' => 'Informe seu estado.',
            'estado.size' => 'Informe a sigla do estado (UF).',
            'quantidade.required' => 'Informe a quantidade desejada.',
            'quantidade.integer' => 'A quantidade deve ser um número inteiro.',
            'quantidade.min' => 'A quantidade deve ser pelo menos 1.',
            'autorizacao.accepted' => 'É necessário autorizar o contato por e-mail.',
        ]);

        // Montar o conteúdo do e-mail
        $conteudo = "
NOVA MANIFESTAÇÃO DE INTERESSE - DISPOSITIVO OLYMPE

Nome: {$dados['nome']}

E-mail: {$dados['email']}

Cidade: {$dados['cidade']}

Estado: {$dados['estado']}

Quantidade desejada: {$dados['quantidade']}

Autorizou receber novidades: Sim

Observações:
" . ($dados['observacoes'] ?? 'Nenhuma observação informada.');

        try {

            // Enviar para o e-mail oficial do projeto
            Mail::raw($conteudo, function ($message) use ($dados) {

                $message->to('olympeforsafe@gmail.com')
                    ->subject('[Olympe] Nova manifestação de interesse pelo dispositivo')
                    ->replyTo($dados['email'], $dados['nome']);

            });

            return response()->json([
                'mensagem' => 'Manifestação de interesse enviada com sucesso!'
            ], 200);

        } catch (\Exception $erro) {

            // Registrar o erro no log do Laravel
            Log::error('Erro ao enviar interesse pelo dispositivo Olympe', [
                'erro' => $erro->getMessage()
            ]);

            return response()->json([
                'mensagem' => 'Não foi possível enviar sua manifestação no momento. Tente novamente mais tarde.'
            ], 500);
        }
    }
}
