<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoricoLigacao extends Model
{
    use HasFactory;

    protected $fillable = [
        'ordem_servico_id', 'status_ligacao', 'duracao', 
        'transcricao_ia', 'data_ligacao', 'proxima_tentativa'
    ];

    public function ordemServico()
    {
        return $this->belongsTo(OrdemServico::class);
    }
}
