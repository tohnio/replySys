<?php

namespace App\Http\Controllers;

use App\Models\Despesa;
use App\Models\OrdemServico;
use Illuminate\Http\Request;
use Carbon\Carbon;

class FinanceiroController extends Controller
{
    public function dashboard()
    {
        $now = Carbon::now();

        // ── Receitas (Métricas de Caixa unificadas) ───────────────────────────────
        $receitaSemana = OrdemServico::getCashForPeriod($now->copy()->startOfWeek(), $now->copy()->endOfWeek());
        $receitaMes = OrdemServico::getCashForPeriod($now->copy()->startOfMonth(), $now->copy()->endOfMonth());

        // ── Despesas ─────────────────────────────────────────────────────────────
        $despesasSemana = Despesa::pagosSemana()->sum('valor');
        $despesasMes    = Despesa::pagosMes()->sum('valor');

        // ── Saldos ───────────────────────────────────────────────────────────────
        $saldoSemana = $receitaSemana - $despesasSemana;
        $saldoMes    = $receitaMes - $despesasMes;

        // ── Relatório das últimas 8 semanas ───────────────────────────────────────
        $ultimasSemanas = collect();
        for ($i = 7; $i >= 0; $i--) {
            $semanaRef = $now->copy()->subWeeks($i);
            $inicio    = $semanaRef->copy()->startOfWeek();
            $fim       = $semanaRef->copy()->endOfWeek();

            $receita = OrdemServico::getCashForPeriod($inicio, $fim);

            $despesa = Despesa::where('status', 'pago')
                ->whereBetween('data_pagamento', [$inicio->toDateString(), $fim->toDateString()])
                ->sum('valor');

            $ultimasSemanas->push([
                'label'   => $inicio->format('d/m') . ' - ' . $fim->format('d/m'),
                'receita' => (float) $receita,
                'despesa' => (float) $despesa,
                'saldo'   => (float) ($receita - $despesa),
            ]);
        }

        // ── Despesas pendentes ────────────────────────────────────────────────────
        $despesasPendentes = Despesa::pendente()->orderBy('data_vencimento')->get();
        $totalPendente     = $despesasPendentes->sum('valor');

        // ── Por categoria (mês atual) ─────────────────────────────────────────────
        $porCategoria = Despesa::pagosMes()
            ->selectRaw('categoria, SUM(valor) as total')
            ->groupBy('categoria')
            ->get();

        return view('financeiro.dashboard', compact(
            'receitaSemana', 'receitaMes',
            'despesasSemana', 'despesasMes',
            'saldoSemana', 'saldoMes',
            'ultimasSemanas',
            'despesasPendentes', 'totalPendente',
            'porCategoria'
        ));
    }

    // ── DESPESAS CRUD ─────────────────────────────────────────────────────────────

    public function despesas(Request $request)
    {
        $despesas = Despesa::orderBy('data_vencimento', 'desc')->paginate(20);
        return view('financeiro.despesas', compact('despesas'));
    }

    public function storeDespesa(Request $request)
    {
        $validated = $request->validate([
            'descricao'       => 'required|string|max:255',
            'categoria'       => 'required|string',
            'valor'           => 'required|numeric|min:0.01',
            'data_vencimento' => 'required|date',
            'observacao'      => 'nullable|string|max:1000',
        ]);

        Despesa::create($validated);

        return redirect()->route('financeiro.despesas')
            ->with('success', 'Despesa cadastrada com sucesso!');
    }

    public function pagarDespesa(Request $request, Despesa $despesa)
    {
        $despesa->update([
            'status'          => 'pago',
            'data_pagamento'  => $request->input('data_pagamento', now()->toDateString()),
        ]);

        return redirect()->back()->with('success', 'Despesa marcada como paga!');
    }

    public function destroyDespesa(Despesa $despesa)
    {
        $despesa->delete();
        return redirect()->route('financeiro.despesas')
            ->with('success', 'Despesa removida.');
    }
}
