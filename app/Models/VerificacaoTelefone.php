<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VerificacaoTelefone extends Model
{
    protected $table = 'verificacoes_telefone';

    public $timestamps = false;

    protected $primaryKey = 'cod';

    protected $fillable = [
        'telefone',
        'codigo',
        'expira_em',
        'verificado',
    ];

    protected $casts = [
        'expira_em' => 'datetime',
        'verificado' => 'boolean',
    ];
}
