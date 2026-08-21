<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\InstituicaoController;
use App\Http\Controllers\ContatoController;
use App\Http\Controllers\DenunciaController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\PontoRiscoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\VerificacaoTelefoneController;



//ROTAS PÚBLICAS (de acesso do cliente)

//VERIFICAÇÃO - TELEFONE

Route::post(
    '/verificacoes/telefone/enviar',
    [VerificacaoTelefoneController::class, 'enviar']
);

Route::post(
    '/verificacoes/telefone/confirmar',
    [VerificacaoTelefoneController::class, 'confirmar']
);

//INSTITUIÇÕES

Route::get('/instituicoes', [InstituicaoController::class, 'index']);

//CONTATOS
Route::apiResource(
    'contatos',
    ContatoController::class
);

//USUÁRIOS

Route::post('/usuarios', [UsuarioController::class, 'store']);

//LOGIN E LOGOUT 

Route::post('/login', [AuthController::class, 'login']);

//DENÚNCIAS

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/denuncias', [DenunciaController::class, 'store']);
});


//PONTOS DE RISCO

Route::get('/pontos-risco', [PontoRiscoController::class, 'index']);
Route::get('/pontos-risco/{id}', [PontoRiscoController::class, 'show']);


//ROTAS PRIVADAS (apenas administradores)

//INSTITUIÇÕES 

Route::get('/instituicoes/{id}', [InstituicaoController::class, 'show']);

//DENÚNCIAS
Route::middleware(['auth:sanctum', 'admin'])->group(function() {
    Route::post('/instituicoes', [InstituicaoController::class, 'store']);
    Route::put('/instituicoes/{id}', [InstituicaoController::class, 'update']);
    Route::patch('/instituicoes/{id}', [InstituicaoController::class, 'update']);
    Route::delete('/instituicoes/{id}', [InstituicaoController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/denuncias', [DenunciaController::class, 'index']);
    Route::get('/denuncias/{id}', [DenunciaController::class, 'show']);

    Route::patch(
        '/denuncias/{id}/status',
        [DenunciaController::class, 'atualizarStatus']
    );
});

//PONTOS DE RISCO

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('/pontos-risco', [PontoRiscoController::class, 'store']);
    Route::put('/pontos-risco/{id}', [PontoRiscoController::class, 'update']);
    Route::patch('/pontos-risco/{id}', [PontoRiscoController::class, 'update']);
    Route::delete('/pontos-risco/{id}', [PontoRiscoController::class, 'destroy']);
});

//LOGIN E LGOUT

Route::middleware('auth:sanctum')->group(function() {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/usuario', [UsuarioController::class,'updateMe',]);
});



Route::get('/teste-banco', function(){

    return response()->json([
        'conexao'=> 'ok'
    ]);
});





