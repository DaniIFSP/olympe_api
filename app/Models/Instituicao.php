<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Instituicao extends Model
{

    use HasFactory;

    protected $table = 'tb_instituicoes';

    protected $primaryKey = 'cod';

    public $timestamps = false;


    protected $fillable = [
        'nome',
        'imagem',
        'descricao'
    ];


    public function endereco()
    {
        return $this->hasMany(
            EnderecoInstituicao::class,
            'cod_inst',
            'cod'
        );
    }

}
