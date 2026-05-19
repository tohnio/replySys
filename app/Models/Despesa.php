<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Despesa extends Model
{
    use HasFactory;

    protected $fillable = [
        'descricao', 'categoria', 'valor', 'data_vencimento',
        'status', 'data_pagamento', 'observacao'
    ];

    protected $casts = [
        'data_vencimento' => 'date',
        'data_pagamento' => 'date',
        'valor' => 'decimal:2',
    ];

    public function scopePendente($query)
    {
        return $query->where('status', 'pendente');
    }

    public function scopePago($query)
    {
        return $query->where('status', 'pago');
    }

    public function scopePagosHoje($query)
    {
        return $query->where('status', 'pago')->whereDate('data_pagamento', Carbon::today());
    }

    public function scopePagosSemana($query)
    {
        return $query->where('status', 'pago')
                     ->whereBetween('data_pagamento', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
    }

    public function scopePagosMes($query)
    {
        return $query->where('status', 'pago')
                     ->whereMonth('data_pagamento', Carbon::now()->month)
                     ->whereYear('data_pagamento', Carbon::now()->year);
    }
}
