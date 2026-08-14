<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Denuncia extends Model
{
    
    protected $table='tb_denuncias';

    protected $primaryKey='cod';

    public $timestamps=false;


    protected $fillable=[

        'cod_usuario',
        'latitude',
        'longitude',
        'tipo',
        'descricao',
        'status',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'data_denuncia' => 'datetime:d-m-Y H:i:s',
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
