<?php

namespace App\Http\Controllers;

use App\Models\Denuncia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class DenunciaController extends Controller
{
    //LISTAR TODOS

    public function index()
{
    $denuncias = Denuncia::orderByDesc('data_denuncia')
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
        'local' => 'nullable|string|max:255',
        'data_ocorrido' => 'nullable|date',
        'email' => 'required|email|max:255',
    ]);

    // Gera protocolo único
    do {
        $protocolo = 'OLY-' . strtoupper(Str::random(8));
    } while (
        Denuncia::where('protocolo', $protocolo)->exists()
    );

    // Gera código de acesso
    $caracteres = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    $codigoAcesso = '';

    for ($i = 0; $i < 10; $i++) {
        $codigoAcesso .= $caracteres[
            random_int(0, strlen($caracteres) - 1)
        ];
    }

    // Salva denúncia anônima
    $denuncia = Denuncia::create([
        'cod_usuario' => null,
        'latitude' => $dados['latitude'],
        'longitude' => $dados['longitude'],
        'tipo' => $dados['tipo'],
        'descricao' => $dados['descricao'] ?? null,
        'local' => $dados['local'] ?? null,
        'data_ocorrido' => $dados['data_ocorrido'] ?? null,
        'status' => 'Pendente',
        'protocolo' => $protocolo,
        'codigo_acesso' => Hash::make($codigoAcesso),
    ]);

    // Tenta enviar o e-mail
    $emailEnviado = true;

    try {
        Mail::raw(
            "Olá!\n\n"
            . "Seu relato foi registrado com sucesso no Olympe for Safe.\n\n"
            . "Protocolo: {$protocolo}\n"
            . "Código de acesso: {$codigoAcesso}\n\n"
            . "Guarde essas informações para consultar o andamento do seu relato.\n\n"
            . "O e-mail informado foi utilizado exclusivamente para o envio "
            . "dessas informações e não foi armazenado no banco de dados de denúncias.\n\n"
            . "Este e-mail é automático. Não responda.\n\n"
            . "Olympe for Safe",
            function ($message) use ($dados) {
                $message->to($dados['email'])
                    ->subject('Protocolo do seu relato - Olympe for Safe');
            }
        );

    } catch (\Throwable $e) {

        $emailEnviado = false;

        Log::error('Erro ao enviar e-mail da denúncia', [
            'erro' => $e->getMessage(),
        ]);
    }

    // Retorna resultado para o frontend
    return response()->json([
        'mensagem' => $emailEnviado
            ? 'Relato enviado com sucesso! O protocolo e código foram enviados para seu e-mail.'
            : 'Relato registrado, mas não foi possível enviar o e-mail. Guarde os dados abaixo.',
        'protocolo' => $denuncia->protocolo,
        'codigo_acesso' => $codigoAcesso,
        'email_enviado' => $emailEnviado,
    ], 201);
}

public function consultar(Request $request)
{
    $dados = $request->validate([
        'protocolo' => 'required|string|max:20',
        'codigo_acesso' => 'required|string|max:20',
    ]);

    $denuncia = Denuncia::where(
        'protocolo',
        strtoupper(trim($dados['protocolo']))
    )->first();

    if (
    !$denuncia ||
    !$denuncia->codigo_acesso ||
    !Hash::check(
        strtoupper(trim($dados['codigo_acesso'])),
        $denuncia->codigo_acesso
    )) {
        return response()->json([
            'mensagem' => 'Protocolo ou código de acesso inválido.'
        ], 403);
    }

    return response()->json([
        'denuncia' => [
            'protocolo' => $denuncia->protocolo,
            'tipo' => $denuncia->tipo,
            'descricao' => $denuncia->descricao,
            'local' => $denuncia->local,
            'latitude' => $denuncia->latitude,
            'longitude' => $denuncia->longitude,
            'data_ocorrido' => $denuncia->data_ocorrido,
            'data_denuncia' => $denuncia->data_denuncia,
            'status' => $denuncia->status,
        ]
    ]);
}


    // BUSCAR

    public function show(string $id)
{
    $denuncia = Denuncia::find($id);

    if (!$denuncia) {
        return response()->json([
            'mensagem' => 'Denúncia não encontrada.'
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

        $denuncia->delete();

        return response()->json([
            'mensagem' => 'Denúncia excluída com sucesso.'
        ], 200);
    }
}
