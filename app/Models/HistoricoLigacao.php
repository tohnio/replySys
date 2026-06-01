<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoricoLigacao extends Model
{
    use HasFactory;

    protected $fillable = [
        'ordem_servico_id', 'external_call_id', 'status_ligacao', 'duracao', 
        'transcricao_ia', 'data_ligacao', 'proxima_tentativa', 'tentativas',
        'whatsapp_reply_count', 'last_whatsapp_message_id'
    ];

    protected $casts = [
        'data_ligacao' => 'datetime',
        'proxima_tentativa' => 'datetime',
        'tentativas' => 'integer',
        'whatsapp_reply_count' => 'integer',
    ];

    public function ordemServico()
    {
        return $this->belongsTo(OrdemServico::class);
    }
}
