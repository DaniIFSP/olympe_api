<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    
    protected $table = 'tb_usuarios';

    protected $primaryKey = 'cod';

    public $timestamps = false;


    protected $fillable = [
        'nome',
        'idade',
        'email',
        'telefone',
        'senha',
        'tipo'
    ];

    protected $hidden = [
        'senha',
    ];

    public function getAuthPassword()
    {
        return $this->senha;
    }

    public function contatos()
    {
        return $this->hasMany(
            Contato::class,
            'cod_usuario',
            'cod'
        );
    }

    public function denuncias()
    {
        return $this->hasMany(
            Denuncia::class,
            'cod_usuario',
            'cod'
        );
    }

}
