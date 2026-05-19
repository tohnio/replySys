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
                <button class="glass-panel px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-800 transition-all shadow-md flex items-center gap-2">
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
                                <div class="flex items-center justify-between p-3 rounded-lg hover:bg-slate-800/50 transition-colors border border-transparent hover:border-white/5">
                                    <div class="flex items-center gap-4">
                                        <div class="h-10 w-10 rounded-full bg-slate-800 flex items-center justify-center text-slate-300 font-bold border border-slate-700">
                                            #{{ $os->id }}
                                        </div>
                                        <div>
                                            <p class="font-medium text-slate-200">{{ $os->descricao_item }}</p>
                                            <p class="text-sm text-slate-400">{{ $os->cliente->nome ?? 'Cliente Desconhecido' }}</p>
                                        </div>
                                    </div>
                                    <div>
                                        @if($os->status == 'RECEBIDO')
                                            <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-slate-800 text-slate-300 border border-slate-600">Recebido</span>
                                        @elseif($os->status == 'EM_REPARO')
                                            <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-indigo-900/50 text-indigo-300 border border-indigo-700/50">Em Reparo</span>
                                        @elseif($os->status == 'AGUARDANDO_PECA')
                                            <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-amber-900/50 text-amber-300 border border-amber-700/50">Aguard. Peça</span>
                                        @elseif($os->status == 'REPARADO')
                                            <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-emerald-900/50 text-emerald-300 border border-emerald-700/50">Reparado</span>
                                        @elseif($os->status == 'ENTREGUE')
                                            <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-blue-900/50 text-blue-300 border border-blue-700/50">Entregue</span>
                                        @endif
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
                                        <div class="h-10 w-10 rounded-full {{ $ligacao->status_ligacao == 'atendida' ? 'bg-emerald-900/40 text-emerald-400 border-emerald-700/50' : ($ligacao->status_ligacao == 'pendente' ? 'bg-slate-800 text-slate-300 border-slate-700' : 'bg-amber-900/40 text-amber-400 border-amber-700/50') }} flex items-center justify-center border">
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

</body>
</html>
