<?php

namespace App\Http\Controllers;

use App\Models\OrdemServico;
use App\Models\HistoricoLigacao;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $totalOs = OrdemServico::count();
        $osReparadas = OrdemServico::where('status', 'REPARADO')->count();
        $osEmReparo = OrdemServico::where('status', 'EM_REPARO')->count();
        $osAguardandoPeca = OrdemServico::where('status', 'AGUARDANDO_PECA')->count();
        $osRecebidas = OrdemServico::where('status', 'RECEBIDO')->count();
        $osEntregues = OrdemServico::where('status', 'ENTREGUE')->count();

        $totalClientes = Cliente::count();

        $ligacoesPendentes = HistoricoLigacao::where('status_ligacao', 'pendente')->count();
        $ligacoesAtendidas = HistoricoLigacao::where('status_ligacao', 'atendida')->count();
        $ligacoesCaixaPostal = HistoricoLigacao::where('status_ligacao', 'caixa_postal')->count();

        $ultimasOs = OrdemServico::with('cliente')->orderBy('created_at', 'desc')->take(6)->get();
        $ultimasLigacoes = HistoricoLigacao::with('ordemServico.cliente')->orderBy('created_at', 'desc')->take(6)->get();

        // Métricas de Caixa
        $now = Carbon::now();
        $caixaHoje = OrdemServico::getCashForPeriod($now->copy()->startOfDay(), $now->copy()->endOfDay());
        $caixaSemana = OrdemServico::getCashForPeriod($now->copy()->startOfWeek(), $now->copy()->endOfWeek());

        return view('dashboard', compact(
            'totalOs', 'osReparadas', 'osEmReparo', 'osAguardandoPeca', 'osRecebidas', 'osEntregues', 'totalClientes',
            'ligacoesPendentes', 'ligacoesAtendidas', 'ligacoesCaixaPostal',
            'ultimasOs', 'ultimasLigacoes',
            'caixaHoje', 'caixaSemana'
        ));
    }
}
