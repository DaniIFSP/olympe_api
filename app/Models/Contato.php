<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contato extends Model
{
    
    protected $table='tb_contatos';

    protected $primaryKey='cod';

    public $timestamps=false;


    protected $fillable=[

        'cod_usuario',
        'nome',
        'telefone',
        'parentesco'

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
