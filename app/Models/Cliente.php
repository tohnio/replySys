<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome', 'telefone', 'email', 'preferencias_notificacao'
    ];

    public function ordensServico()
    {
        return $this->hasMany(OrdemServico::class);
    }
}
