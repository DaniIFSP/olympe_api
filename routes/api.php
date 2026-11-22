<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InstituicaoController;
use App\Http\Controllers\ContatoController;
use App\Http\Controllers\DenunciaController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\PontoRiscoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\VerificacaoTelefoneController;
use App\Http\Controllers\RotaController;
use App\Http\Controllers\RecuperacaoSenhaController;


// ROTAS PÚBLICAS


// GERAÇÃO DE ROTAS
Route::post('/rotas', [RotaController::class, 'calcular']);


// VERIFICAÇÃO DE TELEFONE
Route::post(
    '/verificacoes/telefone/enviar',
    [VerificacaoTelefoneController::class, 'enviar']
);

Route::post(
    '/verificacoes/telefone/confirmar',
    [VerificacaoTelefoneController::class, 'confirmar']
);


// INSTITUIÇÕES
Route::get('/instituicoes', [InstituicaoController::class, 'index']);


// CONTATOS
Route::apiResource('contatos', ContatoController::class);


// USUÁRIOS
Route::post('/usuarios', [UsuarioController::class, 'store']);


// LOGIN
Route::post('/login', [AuthController::class, 'login']);


// RECUPERAÇÃO DE SENHA
Route::post('/recuperar-senha', [RecuperacaoSenhaController::class, 'solicitar']);

Route::post('/verificar-codigo', [RecuperacaoSenhaController::class, 'verificar']);

Route::post('/redefinir-senha', [RecuperacaoSenhaController::class, 'redefinir']);


// DENÚNCIAS ANÔNIMAS

Route::post('/denuncias', [DenunciaController::class, 'store']);
Route::post('/consultar-denuncia', [DenunciaController::class, 'consultar']);



// PONTOS DE RISCO
Route::get('/pontos-risco', [PontoRiscoController::class, 'index']);
Route::get('/pontos-risco/{id}', [PontoRiscoController::class, 'show']);


// ACORDAR API
Route::get('/acordar', function () {
    return response()->json([
        'conexao' => 'ok'
    ]);
});


// TESTE DE E-MAIL

Route::post('/teste-email', function () {

    \Illuminate\Support\Facades\Mail::raw(
        'Este é um e-mail de teste do Olympe for Safe.',
        function ($message) {
            $message->to('olympeforsafe@gmail.com')
                ->subject('Teste de envio - Olympe for Safe');
        }
    );

    return response()->json([
        'mensagem' => 'E-mail enviado com sucesso!'
    ]);
});


// ROTAS AUTENTICADAS 

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/usuario', [UsuarioController::class, 'updateMe']);

});

// ROTAS DA EQUIPE OLYMPE FOR SAFE
// Administradores e funcionários

Route::middleware(['auth:sanctum', 'equipe'])->group(function () {

    // INSTITUIÇÕES

    Route::post('/instituicoes', [InstituicaoController::class, 'store']);

    Route::put('/instituicoes/{id}', [InstituicaoController::class, 'update']);

    Route::patch('/instituicoes/{id}', [InstituicaoController::class, 'update']);


    // DENÚNCIAS

    Route::get('/denuncias', [DenunciaController::class, 'index']);

    Route::get('/denuncias/{id}', [DenunciaController::class, 'show']);

    Route::patch(
        '/denuncias/{id}/status',
        [DenunciaController::class, 'atualizarStatus']
    );

});


// ROTAS EXCLUSIVAS DO ADMINISTRADOR

Route::middleware(['auth:sanctum', 'admin'])->group(function () {

    // GERENCIAR EQUIPE

    Route::post('/funcionarios', [UsuarioController::class, 'cadastrarEquipe']);

    // EXCLUIR INSTITUIÇÕES

    Route::delete('/instituicoes/{id}', [InstituicaoController::class, 'destroy']);

    // EXCLUIR DENÚNCIAS

    Route::delete('/denuncias/{id}', [DenunciaController::class, 'destroy']);

    // PONTOS DE RISCO

    Route::post('/pontos-risco', [PontoRiscoController::class, 'store']);

    Route::put('/pontos-risco/{id}', [PontoRiscoController::class, 'update']);

    Route::patch('/pontos-risco/{id}', [PontoRiscoController::class, 'update']);

    Route::delete('/pontos-risco/{id}', [PontoRiscoController::class, 'destroy']);

});