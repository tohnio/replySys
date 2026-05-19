<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdemServico extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_id', 'descricao_item', 'defeito_relatado', 
        'valor_orcamento', 'status', 'data_entrada', 'data_reparo', 'tecnico_id',
        'modelo', 'data_pronto', 'status_pagamento', 'valor_pago', 'data_entregue'
    ];

    protected $casts = [
        'data_entrada' => 'datetime',
        'data_reparo' => 'datetime',
        'data_pronto' => 'date',
        'data_entregue' => 'datetime',
        'valor_orcamento' => 'decimal:2',
        'valor_pago' => 'decimal:2',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function historicoLigacoes()
    {
        return $this->hasMany(HistoricoLigacao::class);
    }

    /**
     * Calcula o valor total em caixa no período com base em:
     * - OS com status_pagamento total: valor pago adiantado (valor_pago ou valor_orcamento)
     * - OS com status_pagamento parcial: valor pago adiantado (valor_pago)
     * - OS com status ENTREGUE: restante do valor pago na entrega (valor_orcamento - valor_pago)
     */
    public static function getCashForPeriod($startDate, $endDate)
    {
        $orders = self::where(function($query) use ($startDate, $endDate) {
            $query->whereIn('status_pagamento', ['total', 'parcial'])
                  ->whereBetween('created_at', [$startDate, $endDate]);
        })->orWhere(function($query) use ($startDate, $endDate) {
            $query->where('status', 'ENTREGUE')
                  ->where(function($q) use ($startDate, $endDate) {
                      $q->whereBetween('data_entregue', [$startDate, $endDate])
                        ->orWhere(function($sq) use ($startDate, $endDate) {
                            $sq->whereNull('data_entregue')
                               ->whereBetween('updated_at', [$startDate, $endDate]);
                        });
                  });
        })->get();

        $total = 0.0;
        foreach ($orders as $order) {
            // 1. Parte paga de forma adiantada (total ou parcial) ocorrendo no período
            if (in_array($order->status_pagamento, ['total', 'parcial']) && $order->created_at->between($startDate, $endDate)) {
                $val = (float) $order->valor_pago;
                if ($order->status_pagamento === 'total' && $val <= 0) {
                    $val = (float) $order->valor_orcamento;
                }
                $total += $val;
            }

            // 2. Parte paga na entrega (status ENTREGUE) ocorrendo no período
            if ($order->status === 'ENTREGUE') {
                $deliveryDate = $order->data_entregue ?: $order->updated_at;
                if ($deliveryDate && $deliveryDate->between($startDate, $endDate)) {
                    $adv = (float) $order->valor_pago;
                    if ($order->status_pagamento === 'total' && $adv <= 0) {
                        $adv = (float) $order->valor_orcamento;
                    }
                    $remaining = max(0.0, (float)$order->valor_orcamento - $adv);
                    $total += $remaining;
                }
            }
        }

        return $total;
    }
}
