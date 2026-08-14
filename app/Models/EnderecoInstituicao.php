<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnderecoInstituicao extends Model
{
    use HasFactory;    

    protected $table = 'tb_enderecos_instituicao';

    protected $primaryKey = 'cod';

    public $timestamps = false;


    protected $fillable = [

        'telefone',
        'cep',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'uf',
        'latitude',
        'longitude',
        'dia_semana',
        'horario_inicio',
        'horario_fim'

    ];


    public function instituicao()
    {
        return $this->belongsTo(
            Instituicao::class,
            'cod_inst',
            'cod'
        );
    }

}
