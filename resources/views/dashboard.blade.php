<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ReplySys - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .glass-panel {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .gradient-text {
            background: linear-gradient(135deg, #a78bfa 0%, #3b82f6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body class="bg-slate-900 text-slate-200 min-h-screen relative overflow-x-hidden selection:bg-indigo-500 selection:text-white">
    
    <!-- Background Decorators -->
    <div class="absolute top-0 left-0 w-full h-full overflow-hidden -z-10 pointer-events-none">
        <div class="absolute -top-[10%] -right-[10%] w-[40%] h-[40%] rounded-full bg-indigo-600/20 blur-[120px]"></div>
        <div class="absolute top-[60%] -left-[10%] w-[30%] h-[30%] rounded-full bg-purple-600/20 blur-[100px]"></div>
    </div>

    <!-- Navbar -->
    <nav class="glass-panel sticky top-0 z-50 px-6 py-4 flex justify-between items-center shadow-lg shadow-black/20">
        <div class="flex items-center gap-3">
            <div class="bg-gradient-to-tr from-indigo-500 to-purple-500 p-2 rounded-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            </div>
            <h1 class="text-2xl font-bold tracking-wide text-white">Reply<span class="text-indigo-400">Sys</span></h1>
        </div>
        <div class="flex items-center gap-4">
            <a href="{{ url('/api/documentation') }}" class="text-sm font-medium text-slate-300 hover:text-white transition-colors duration-200">API Docs</a>
            <div class="h-8 w-8 rounded-full bg-gradient-to-r from-blue-500 to-indigo-600 border border-indigo-400 flex items-center justify-center font-bold text-sm shadow-md">
                A
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
        
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <h2 class="text-3xl font-bold text-white">Visão Geral</h2>
                <p class="text-slate-400 mt-1">Acompanhe o status das manutenções e o desempenho das ligações IA.</p>
            </div>
            <div class="flex gap-3">
                <button onclick="openCreateOsModal()" class="glass-panel px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-600/20 hover:border-indigo-500/30 hover:text-white transition-all shadow-md flex items-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Nova OS
                </button>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
            <!-- Stat 1 -->
            <div class="glass-panel rounded-2xl p-6 shadow-lg relative overflow-hidden group">
                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                    <svg class="w-16 h-16" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"></path></svg>
                </div>
                <h3 class="text-slate-400 text-sm font-medium mb-1">Total de OS</h3>
                <p class="text-4xl font-bold text-white">{{ $totalOs }}</p>
                <div class="mt-4 flex items-center text-sm">
                    <span class="text-indigo-400 font-medium">{{ $osEmReparo }} em reparo</span>
                </div>
            </div>

            <!-- Stat 2 -->
            <div class="glass-panel rounded-2xl p-6 shadow-lg relative overflow-hidden group">
                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                    <svg class="w-16 h-16 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                </div>
                <h3 class="text-slate-400 text-sm font-medium mb-1">Reparados (Aguardando Retirada)</h3>
                <p class="text-4xl font-bold text-white">{{ $osReparadas }}</p>
                <div class="mt-4 flex items-center text-sm">
                    <span class="text-emerald-400 font-medium">{{ $osEntregues }} já entregues</span>
                </div>
            </div>

            <!-- Stat 3 -->
            <div class="glass-panel rounded-2xl p-6 shadow-lg relative overflow-hidden group">
                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                    <svg class="w-16 h-16 text-blue-400" fill="currentColor" viewBox="0 0 20 20"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"></path></svg>
                </div>
                <h3 class="text-slate-400 text-sm font-medium mb-1">Ligações IA Atendidas</h3>
                <p class="text-4xl font-bold text-white">{{ $ligacoesAtendidas }}</p>
                <div class="mt-4 flex items-center text-sm">
                    <span class="text-blue-400 font-medium">{{ $ligacoesPendentes }} na fila</span>
                </div>
            </div>

            <!-- Stat 4 -->
            <div class="glass-panel rounded-2xl p-6 shadow-lg relative overflow-hidden group">
                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                    <svg class="w-16 h-16 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 2a8 8 0 100 16 8 8 0 000-16zm1 8a1 1 0 11-2 0V6a1 1 0 112 0v4zm-1 4a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path></svg>
                </div>
                <h3 class="text-slate-400 text-sm font-medium mb-1">Caixa Postal (Reagendados)</h3>
                <p class="text-4xl font-bold text-white">{{ $ligacoesCaixaPostal }}</p>
                <div class="mt-4 flex items-center text-sm">
                    <span class="text-amber-400 font-medium">Ligarão amanhã</span>
                </div>
            </div>

            <!-- Stat 5: Caixa -->
            <a href="{{ route('financeiro.dashboard') }}" class="glass-panel rounded-2xl p-6 shadow-lg relative overflow-hidden group block transition-all hover:-translate-y-1 hover:shadow-emerald-900/30 hover:border-emerald-500/30" style="border-color: rgba(16,185,129,0.2)">
                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-25 transition-opacity">
                    <svg class="w-16 h-16 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg>
                </div>
                <div class="flex items-center gap-1.5 mb-1">
                    <h3 class="text-emerald-400/80 text-sm font-medium">Caixa</h3>
                    <svg class="w-3.5 h-3.5 text-emerald-400/60 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </div>
                <p class="text-3xl font-bold text-emerald-300">R$ {{ number_format($caixaHoje, 2, ',', '.') }}</p>
                <div class="mt-4 flex items-center text-sm">
                    <span class="text-slate-400 font-medium">Semana: <span class="text-emerald-400">R$ {{ number_format($caixaSemana, 2, ',', '.') }}</span></span>
                </div>
            </a>
        </div>


        <!-- Tables Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-8">
            
            <!-- Últimas OS -->
            <div class="glass-panel rounded-2xl shadow-lg flex flex-col h-full">
                <div class="p-6 border-b border-white/10 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-white">Últimas Ordens de Serviço</h3>
                    <a href="#" class="text-indigo-400 text-sm hover:text-indigo-300">Ver todas</a>
                </div>
                <div class="p-6 flex-1">
                    @if($ultimasOs->count() > 0)
                        <div class="space-y-4">
                            @foreach($ultimasOs as $os)
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 rounded-xl hover:bg-slate-800/40 transition-all border border-white/5 hover:border-white/10 gap-4">
                                    <div class="flex items-start gap-4">
                                        <div class="h-12 w-12 rounded-xl bg-slate-800 border border-slate-700 flex flex-col items-center justify-center text-slate-300 font-bold shrink-0 shadow-inner">
                                            <span class="text-[10px] text-slate-500 font-normal">OS</span>
                                            <span class="text-sm -mt-1">#{{ $os->id }}</span>
                                        </div>
                                        <div class="space-y-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="font-semibold text-slate-100">{{ $os->cliente->nome ?? 'Cliente Desconhecido' }}</p>
                                                @if($os->cliente && $os->cliente->telefone)
                                                    <span class="text-xs text-slate-400 bg-slate-800/80 px-2 py-0.5 rounded border border-white/5">{{ $os->cliente->telefone }}</span>
                                                @endif
                                            </div>
                                            <p class="text-sm text-slate-300">
                                                <span class="text-slate-500 font-medium">Aparelho:</span> {{ $os->modelo ?? $os->descricao_item }}
                                            </p>
                                            @if($os->defeito_relatado)
                                                <p class="text-xs text-slate-400 italic max-w-[280px] sm:max-w-md truncate" title="{{ $os->defeito_relatado }}">
                                                    <span class="text-slate-500 font-medium not-italic">Defeito:</span> {{ $os->defeito_relatado }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="flex flex-col sm:items-end justify-center gap-1.5 text-xs">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span class="text-slate-500">Entrada:</span>
                                            <span class="text-slate-300 font-medium">{{ $os->data_entrada ? $os->data_entrada->format('d/m/Y') : '--/--/----' }}</span>
                                            <span class="text-slate-600">|</span>
                                            <span class="text-slate-500">Previsão:</span>
                                            <span class="text-slate-300 font-medium">{{ $os->data_pronto ? \Carbon\Carbon::parse($os->data_pronto)->format('d/m/Y') : '--/--/----' }}</span>
                                        </div>
                                        
                                        <div>
                                            @php
                                                $valorFormatado = number_format($os->valor_orcamento, 2, ',', '.');
                                                if ($os->status_pagamento === 'total') {
                                                    $paymentText = "R$ {$valorFormatado} (PAGO)";
                                                    $paymentClass = "text-emerald-400 font-semibold";
                                                } elseif ($os->status_pagamento === 'parcial') {
                                                    $deve = max(0, $os->valor_orcamento - $os->valor_pago);
                                                    $deveFormatado = number_format($deve, 2, ',', '.');
                                                    $paymentText = "R$ {$valorFormatado} (DEVE R$ {$deveFormatado})";
                                                    $paymentClass = "text-amber-400 font-medium";
                                                } else {
                                                    $paymentText = "R$ {$valorFormatado} (PENDENTE)";
                                                    $paymentClass = "text-slate-400 font-medium";
                                                }
                                            @endphp
                                            <span class="text-slate-500">Financeiro:</span>
                                            <span class="{{ $paymentClass }}">{{ $paymentText }}</span>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center gap-2 self-end sm:self-center shrink-0">
                                        <!-- Status Badge -->
                                        <div>
                                            @if($os->status == 'RECEBIDO')
                                                <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full bg-slate-800 text-slate-300 border border-slate-600">Recebido</span>
                                            @elseif($os->status == 'EM_REPARO')
                                                <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full bg-indigo-950/60 text-indigo-300 border border-indigo-700/50">Em Reparo</span>
                                            @elseif($os->status == 'AGUARDANDO_PECA')
                                                <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full bg-amber-950/60 text-amber-300 border border-amber-700/50">Aguard. Peça</span>
                                            @elseif($os->status == 'REPARADO')
                                                <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full bg-emerald-950/60 text-emerald-300 border border-emerald-700/50">Reparado</span>
                                            @elseif($os->status == 'ENTREGUE')
                                                <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full bg-blue-950/60 text-blue-300 border border-blue-700/50">Entregue</span>
                                            @endif
                                        </div>
                                        
                                        <!-- Actions -->
                                        <div class="flex items-center gap-1">
                                            <!-- Edit Button -->
                                            <button onclick="openEditOsModal({{ json_encode($os->load('cliente')) }})" class="p-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition-all shadow-sm cursor-pointer" title="Editar OS">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                            </button>
                                            
                                            @if($os->status !== 'REPARADO' && $os->status !== 'ENTREGUE')
                                                <button onclick="updateOsStatus({{ $os->id }}, 'REPARADO')" class="px-2 py-1 rounded-lg text-[10px] font-medium bg-emerald-600 hover:bg-emerald-500 text-white transition-all shadow-sm flex items-center gap-0.5 cursor-pointer">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                    Concluir
                                                </button>
                                            @endif
                                            
                                            @if($os->status === 'REPARADO')
                                                <button onclick="updateOsStatus({{ $os->id }}, 'ENTREGUE')" class="px-2 py-1 rounded-lg text-[10px] font-medium bg-blue-600 hover:bg-blue-500 text-white transition-all shadow-sm flex items-center gap-0.5 cursor-pointer">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 01.707.293h3.172a1 1 0 01.707-.293l2.414-2.414A1 1 0 0017.414 13H20"></path></svg>
                                                    Entregar
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="h-full flex flex-col items-center justify-center text-slate-500 py-10">
                            <svg class="w-12 h-12 mb-3 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                            <p>Nenhuma ordem de serviço cadastrada.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Últimas Ligações IA -->
            <div class="glass-panel rounded-2xl shadow-lg flex flex-col h-full">
                <div class="p-6 border-b border-white/10 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                        Atividade da IA Vapi 
                        <span class="flex h-3 w-3 relative ml-2">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-3 w-3 bg-indigo-500"></span>
                        </span>
                    </h3>
                    <a href="#" class="text-indigo-400 text-sm hover:text-indigo-300">Histórico completo</a>
                </div>
                <div class="p-6 flex-1">
                    @if($ultimasLigacoes->count() > 0)
                        <div class="space-y-4">
                            @foreach($ultimasLigacoes as $ligacao)
                                <div class="flex items-center justify-between p-3 rounded-lg hover:bg-slate-800/50 transition-colors border border-transparent hover:border-white/5">
                                    <div class="flex items-center gap-4">
                                        <div class="h-10 w-10 rounded-full {{ $ligacao->status_ligacao == 'atendida' ? 'bg-emerald-900/40 text-emerald-400 border-emerald-700/50' : ($ligacao->status_ligacao == 'pendente' ? 'bg-slate-800 text-slate-300 border-slate-700' : 'bg-amber-900/40 text-amber-400 border-amber-700/50') }} flex items-center justify-center border animate-pulse">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"></path></svg>
                                        </div>
                                        <div>
                                            <p class="font-medium text-slate-200">OS #{{ $ligacao->ordem_servico_id }} - {{ $ligacao->ordemServico->cliente->nome ?? 'Cliente' }}</p>
                                            <p class="text-xs text-slate-400">{{ $ligacao->created_at->diffForHumans() }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        @if($ligacao->status_ligacao == 'pendente')
                                            <span class="text-sm font-medium text-slate-400">Pendente</span>
                                        @elseif($ligacao->status_ligacao == 'atendida')
                                            <span class="text-sm font-medium text-emerald-400">Atendida</span>
                                            @if($ligacao->duracao)
                                                <p class="text-xs text-slate-500">{{ gmdate("i:s", $ligacao->duracao) }}</p>
                                            @endif
                                        @elseif($ligacao->status_ligacao == 'caixa_postal')
                                            <span class="text-sm font-medium text-amber-400">Caixa Postal</span>
                                        @else
                                            <span class="text-sm font-medium text-red-400">Falha</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="h-full flex flex-col items-center justify-center text-slate-500 py-10">
                            <svg class="w-12 h-12 mb-3 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path></svg>
                            <p>Nenhuma ligação registrada pela IA ainda.</p>
                        </div>
                    @endif
                </div>
            </div>
            
        </div>
    </main>

    <!-- OS Modal (Create & Edit) -->
    <div id="os-modal" class="fixed inset-0 z-[100] hidden flex items-center justify-center p-4">
        <!-- Overlay -->
        <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm" onclick="closeOsModal()"></div>
        
        <!-- Modal Content -->
        <div class="glass-panel w-full max-w-xl rounded-2xl shadow-2xl z-10 border border-white/10 flex flex-col max-h-[90vh] overflow-hidden">
            <!-- Header -->
            <div class="p-6 border-b border-white/10 flex justify-between items-center bg-slate-900/50">
                <h3 id="modal-title" class="text-xl font-bold text-white">Nova Ordem de Serviço</h3>
                <button onclick="closeOsModal()" class="text-slate-400 hover:text-white transition-colors cursor-pointer">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <!-- Form Content -->
            <form id="os-form" onsubmit="submitOsForm(event)" class="p-6 overflow-y-auto space-y-4">
                <input type="hidden" id="os-id" name="id">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Cliente Nome -->
                    <div class="flex flex-col gap-1">
                        <label for="os-nome" class="text-xs font-semibold text-slate-300 uppercase tracking-wider">Nome do Cliente <span class="text-rose-500">*</span></label>
                        <input type="text" id="os-nome" required placeholder="Nome completo" class="bg-slate-800/80 border border-white/10 rounded-xl px-4 py-2 text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all text-sm">
                    </div>
                    
                    <!-- Cliente Telefone -->
                    <div class="flex flex-col gap-1">
                        <label for="os-telefone" class="text-xs font-semibold text-slate-300 uppercase tracking-wider">Telefone</label>
                        <input type="text" id="os-telefone" placeholder="(XX) XXXXX-XXXX" class="bg-slate-800/80 border border-white/10 rounded-xl px-4 py-2 text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all text-sm">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Modelo -->
                    <div class="flex flex-col gap-1">
                        <label for="os-modelo" class="text-xs font-semibold text-slate-300 uppercase tracking-wider">Modelo do Aparelho</label>
                        <input type="text" id="os-modelo" placeholder="Ex: iPhone 13 Pro" class="bg-slate-800/80 border border-white/10 rounded-xl px-4 py-2 text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all text-sm">
                    </div>
                    
                    <!-- Previsão de Entrega (Pronto para) -->
                    <div class="flex flex-col gap-1">
                        <label for="os-previsao" class="text-xs font-semibold text-slate-300 uppercase tracking-wider">Previsão de Entrega</label>
                        <select id="os-previsao" class="bg-slate-800/80 border border-white/10 rounded-xl px-4 py-2 text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all text-sm cursor-pointer">
                            <!-- Populated dynamically via JS -->
                        </select>
                    </div>
                </div>
                
                <!-- Defeito Relatado -->
                <div class="flex flex-col gap-1">
                    <label for="os-defeito" class="text-xs font-semibold text-slate-300 uppercase tracking-wider">Descrição do Defeito / Reparo</label>
                    <textarea id="os-defeito" rows="3" placeholder="Descreva o defeito relatado..." class="bg-slate-800/80 border border-white/10 rounded-xl px-4 py-2 text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all resize-none text-sm"></textarea>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Valor Orçamento -->
                    <div class="flex flex-col gap-1">
                        <label for="os-valor" class="text-xs font-semibold text-slate-300 uppercase tracking-wider">Valor Total (R$)</label>
                        <input type="text" id="os-valor" placeholder="0,00" class="bg-slate-800/80 border border-white/10 rounded-xl px-4 py-2 text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all text-sm">
                    </div>
                    
                    <!-- Status de Pagamento -->
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-semibold text-slate-300 uppercase tracking-wider">Situação do Pagamento</label>
                        <div class="flex items-center gap-4 py-2">
                            <label class="flex items-center gap-1.5 cursor-pointer text-xs text-slate-300 hover:text-white transition-colors">
                                <input type="radio" name="status_pagamento" value="pendente" checked onchange="toggleValorPagoField()" class="accent-indigo-500">
                                Pendente
                            </label>
                            <label class="flex items-center gap-1.5 cursor-pointer text-xs text-slate-300 hover:text-white transition-colors">
                                <input type="radio" name="status_pagamento" value="parcial" onchange="toggleValorPagoField()" class="accent-indigo-500">
                                Parcial
                            </label>
                            <label class="flex items-center gap-1.5 cursor-pointer text-xs text-slate-300 hover:text-white transition-colors">
                                <input type="radio" name="status_pagamento" value="total" onchange="toggleValorPagoField()" class="accent-indigo-500">
                                Total
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Valor Pago (Adiantamento) -->
                <div id="valor-pago-container" class="flex flex-col gap-1 hidden">
                    <label for="os-valor-pago" class="text-xs font-semibold text-slate-300 uppercase tracking-wider">Valor Pago (Adiantado / Sinal)</label>
                    <input type="text" id="os-valor-pago" placeholder="0,00" class="bg-slate-800/80 border border-white/10 rounded-xl px-4 py-2 text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all text-sm">
                </div>
                
                <!-- Buttons -->
                <div class="flex gap-3 pt-4 border-t border-white/10">
                    <button type="button" onclick="closeOsModal()" class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl py-2.5 border border-white/5 font-semibold transition-all text-sm cursor-pointer">
                        Cancelar
                    </button>
                    <button id="save-button" type="submit" class="flex-1 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white rounded-xl py-2.5 font-semibold transition-all shadow-lg shadow-indigo-500/20 text-sm cursor-pointer">
                        Salvar Serviço
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Script integrations -->
    <script>
        function generatePrevisaoOptions(initialDate = null) {
            const options = [];
            const weekdayNamesPT = {
                1: 'Segunda-feira',
                2: 'Terça-feira',
                3: 'Quarta-feira',
                4: 'Quinta-feira',
                5: 'Sexta-feira'
            };
            
            const now = new Date();
            const todayStr = formatDateISO(now);
            
            // Find Monday of the current week
            const currentDay = now.getDay(); // 0 is Sunday, 1 is Monday, etc.
            const mondayDiff = currentDay === 0 ? -6 : 1 - currentDay;
            const monday = new Date(now);
            monday.setDate(now.getDate() + mondayDiff);
            
            // Upper limit is Friday of the next week (Monday + 11 days)
            const limit = new Date(monday);
            limit.setDate(monday.getDate() + 11);
            
            const currentIter = new Date(now);
            const seenCounts = {};
            
            let iterations = 0;
            while (currentIter <= limit && iterations < 30) {
                iterations++;
                const dayOfWeekNum = currentIter.getDay(); // 0-6
                
                if (dayOfWeekNum !== 0 && dayOfWeekNum !== 6) { // Skip Sat/Sun
                    const dateStr = formatDateISO(currentIter);
                    const isToday = (dateStr === todayStr);
                    
                    if (!seenCounts[dayOfWeekNum]) {
                        seenCounts[dayOfWeekNum] = 0;
                    }
                    seenCounts[dayOfWeekNum]++;
                    
                    let label = '';
                    if (isToday) {
                        label = 'Hoje';
                    } else {
                        const dayName = weekdayNamesPT[dayOfWeekNum];
                        if (seenCounts[dayOfWeekNum] > 1) {
                            label = `${dayName} (Semana seguinte)`;
                        } else {
                            label = dayName;
                        }
                    }
                    
                    options.push({ label, val: dateStr });
                }
                currentIter.setDate(currentIter.getDate() + 1);
            }
            
            // If initialDate is passed and not in options, format and prepend it
            if (initialDate) {
                const datePart = initialDate.split(' ')[0]; // Handle date-time strings
                const found = options.some(opt => opt.val === datePart);
                if (!found) {
                    const parts = datePart.split('-');
                    if (parts.length === 3) {
                        const [y, m, d] = parts;
                        const formatted = `${d}/${m}/${y}`;
                        options.unshift({ label: formatted, val: datePart });
                    } else {
                        options.unshift({ label: datePart, val: datePart });
                    }
                }
            }
            
            return options;
        }
        
        function formatDateISO(date) {
            const y = date.getFullYear();
            const m = String(date.getMonth() + 1).padStart(2, '0');
            const d = String(date.getDate()).padStart(2, '0');
            return `${y}-${m}-${d}`;
        }

        function formatPhoneJS(digits) {
            if (digits.length === 0) return '';
            if (digits.length <= 2) return digits;
            if (digits.length <= 6) return `(${digits.substring(0, 2)}) ${digits.substring(2)}`;
            if (digits.length <= 10) return `(${digits.substring(0, 2)}) ${digits.substring(2, 6)}-${digits.substring(6)}`;
            return `(${digits.substring(0, 2)}) ${digits.substring(2, 7)}-${digits.substring(7)}`;
        }

        document.getElementById('os-telefone').addEventListener('input', function(e) {
            const digits = e.target.value.replace(/\D/g, '').substring(0, 11);
            e.target.value = formatPhoneJS(digits);
        });

        function toggleValorPagoField() {
            const statusPagamento = document.querySelector('input[name="status_pagamento"]:checked').value;
            const container = document.getElementById('valor-pago-container');
            if (statusPagamento === 'parcial') {
                container.classList.remove('hidden');
            } else {
                container.classList.add('hidden');
            }
        }

        function populatePrevisaoSelect(selectedVal = null) {
            const select = document.getElementById('os-previsao');
            select.innerHTML = '';
            
            const options = generatePrevisaoOptions(selectedVal);
            options.forEach(opt => {
                const option = document.createElement('option');
                option.value = opt.val;
                option.textContent = opt.label;
                if (selectedVal && opt.val === selectedVal.split(' ')[0]) {
                    option.selected = true;
                }
                select.appendChild(option);
            });
        }

        function getNumericValue(elementId) {
            const raw = document.getElementById(elementId).value;
            if (!raw) return 0;
            const clean = raw.replace(',', '.').replace(/[^0-9.]/g, '');
            return parseFloat(clean) || 0.0;
        }

        function openCreateOsModal() {
            document.getElementById('modal-title').textContent = 'Nova Ordem de Serviço';
            document.getElementById('os-id').value = '';
            document.getElementById('os-form').reset();
            
            document.querySelector('input[name="status_pagamento"][value="pendente"]').checked = true;
            toggleValorPagoField();
            
            populatePrevisaoSelect();
            
            document.getElementById('os-modal').classList.remove('hidden');
        }

        function openEditOsModal(os) {
            document.getElementById('modal-title').textContent = 'Editar Ordem de Serviço';
            document.getElementById('os-id').value = os.id;
            document.getElementById('os-nome').value = os.cliente ? os.cliente.nome : '';
            
            const rawPhone = os.cliente && os.cliente.telefone ? os.cliente.telefone.replace(/\D/g, '') : '';
            document.getElementById('os-telefone').value = formatPhoneJS(rawPhone);
            
            document.getElementById('os-modelo').value = os.modelo || os.descricao_item || '';
            document.getElementById('os-defeito').value = os.defeito_relatado || '';
            
            const valStr = parseFloat(os.valor_orcamento).toFixed(2).replace('.', ',');
            document.getElementById('os-valor').value = valStr;
            
            const payStatus = os.status_pagamento || 'pendente';
            document.querySelector(`input[name="status_pagamento"][value="${payStatus}"]`).checked = true;
            toggleValorPagoField();
            
            if (payStatus === 'parcial') {
                const payValStr = parseFloat(os.valor_pago).toFixed(2).replace('.', ',');
                document.getElementById('os-valor-pago').value = payValStr;
            }
            
            populatePrevisaoSelect(os.data_pronto);
            
            document.getElementById('os-modal').classList.remove('hidden');
        }

        function closeOsModal() {
            document.getElementById('os-modal').classList.add('hidden');
        }

        function submitOsForm(event) {
            event.preventDefault();
            
            const saveBtn = document.getElementById('save-button');
            const originalText = saveBtn.textContent;
            saveBtn.disabled = true;
            saveBtn.textContent = 'Enviando...';
            
            const id = document.getElementById('os-id').value;
            const nome = document.getElementById('os-nome').value;
            const telefone = document.getElementById('os-telefone').value;
            const modelo = document.getElementById('os-modelo').value;
            const defeito_relatado = document.getElementById('os-defeito').value;
            const data_pronto = document.getElementById('os-previsao').value;
            const valor = getNumericValue('os-valor');
            const status_pagamento = document.querySelector('input[name="status_pagamento"]:checked').value;
            const valor_pago = status_pagamento === 'total' ? valor : (status_pagamento === 'parcial' ? getNumericValue('os-valor-pago') : 0.0);
            
            const payload = {
                nome,
                telefone,
                modelo,
                descricao_item: modelo || 'Equipamento',
                defeito_relatado,
                data_pronto,
                valor,
                status_pagamento,
                valor_pago
            };
            
            const url = id ? `/api/ordens-servico/${id}` : '/api/ordens-servico';
            const method = id ? 'PUT' : 'POST';
            
            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro ao salvar ordem de serviço');
                }
                return response.json();
            })
            .then(data => {
                window.location.reload();
            })
            .catch(err => {
                console.error(err);
                alert('Ocorreu um erro ao salvar a Ordem de Serviço.');
                saveBtn.disabled = false;
                saveBtn.textContent = originalText;
            });
        }

        function updateOsStatus(id, newStatus) {
            if (!confirm(`Deseja alterar o status desta OS para ${newStatus}?`)) {
                return;
            }
            
            fetch(`/api/ordens-servico/${id}/status`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status: newStatus })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro ao atualizar status');
                }
                return response.json();
            })
            .then(data => {
                window.location.reload();
            })
            .catch(err => {
                console.error(err);
                alert('Ocorreu um erro ao alterar o status da Ordem de Serviço.');
            });
        }
    </script>

</body>
</html>
