<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Denuncia extends Model
{
    protected $table = 'tb_denuncias';

    protected $primaryKey = 'cod';

    public $timestamps = false;

    protected $hidden = [
    'codigo_acesso'
    ];

    protected $fillable = [
        'cod_usuario',
        'latitude',
        'longitude',
        'tipo',
        'descricao',
        'status',
        'protocolo',
        'codigo_acesso',
        'local',
        'data_ocorrido'
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'data_denuncia' => 'datetime:d-m-Y H:i:s',
        'data_ocorrido' => 'date:d-m-Y'
    ];

    public function usuario()
    {
        return $this->belongsTo(
            Usuario::class,
            'cod_usuario',
            'cod'
        );
    }
}