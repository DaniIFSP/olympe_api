<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PontoRisco extends Model
{
    
    protected $table='tb_pontos_risco';

    protected $primaryKey='cod';

    public $timestamps=false;


    protected $fillable=[

        'latitude',
        'longitude',
        'tipo',
        'descricao',
        'nivel_risco',
        'data_denuncia'

    ];

    protected $casts = [
        'data_denuncia' => 'date:d-m-Y',
    ];

}
