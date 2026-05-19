<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ReplySys - Financeiro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
            background: linear-gradient(135deg, #10b981 0%, #3b82f6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .card-hover { transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .card-hover:hover { transform: translateY(-2px); box-shadow: 0 12px 40px rgba(0,0,0,0.3); }
    </style>
</head>
<body class="bg-slate-900 text-slate-200 min-h-screen relative overflow-x-hidden">

    <!-- Background Decorators -->
    <div class="absolute top-0 left-0 w-full h-full overflow-hidden -z-10 pointer-events-none">
        <div class="absolute -top-[10%] -right-[10%] w-[40%] h-[40%] rounded-full bg-emerald-600/15 blur-[120px]"></div>
        <div class="absolute top-[60%] -left-[10%] w-[30%] h-[30%] rounded-full bg-blue-600/15 blur-[100px]"></div>
    </div>

    <!-- Navbar -->
    <nav class="glass-panel sticky top-0 z-50 px-6 py-4 flex justify-between items-center shadow-lg shadow-black/20">
        <div class="flex items-center gap-3">
            <div class="bg-gradient-to-tr from-emerald-500 to-blue-500 p-2 rounded-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <h1 class="text-2xl font-bold tracking-wide text-white">Reply<span class="text-emerald-400">Fin</span></h1>
        </div>
        <div class="flex items-center gap-4">
            <a href="{{ route('dashboard') }}" class="text-sm font-medium text-slate-300 hover:text-white transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Dashboard
            </a>
            <a href="{{ route('financeiro.despesas') }}" class="glass-panel px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-800 transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                Despesas
            </a>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">

        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <h2 class="text-3xl font-bold text-white">Financeiro</h2>
                <p class="text-slate-400 mt-1">Receitas, despesas e fluxo de caixa do negócio.</p>
            </div>
            <a href="{{ route('financeiro.despesas') }}" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-500 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition-all shadow-lg shadow-emerald-900/30">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Nova Despesa
            </a>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">

            <!-- Receita Semana -->
            <div class="glass-panel rounded-2xl p-6 card-hover relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4 opacity-10">
                    <svg class="w-16 h-16 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg>
                </div>
                <span class="text-xs font-semibold uppercase tracking-wider text-emerald-400/80">Receita da Semana</span>
                <p class="text-3xl font-bold text-white mt-1">R$ {{ number_format($receitaSemana, 2, ',', '.') }}</p>
                <div class="mt-3 text-sm text-slate-400">Serviços pagos esta semana</div>
            </div>

            <!-- Receita Mês -->
            <div class="glass-panel rounded-2xl p-6 card-hover relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4 opacity-10">
                    <svg class="w-16 h-16 text-blue-400" fill="currentColor" viewBox="0 0 20 20"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"></path></svg>
                </div>
                <span class="text-xs font-semibold uppercase tracking-wider text-blue-400/80">Receita do Mês</span>
                <p class="text-3xl font-bold text-white mt-1">R$ {{ number_format($receitaMes, 2, ',', '.') }}</p>
                <div class="mt-3 text-sm text-slate-400">Serviços pagos este mês</div>
            </div>

            <!-- Saldo Mês -->
            <div class="glass-panel rounded-2xl p-6 card-hover relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4 opacity-10">
                    <svg class="w-16 h-16 {{ $saldoMes >= 0 ? 'text-emerald-400' : 'text-red-400' }}" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 2a2 2 0 00-2 2v14l3.5-2 3.5 2 3.5-2 3.5 2V4a2 2 0 00-2-2H5zm4.707 3.707a1 1 0 00-1.414-1.414l-3 3a1 1 0 000 1.414l3 3a1 1 0 001.414-1.414L8.414 9H10a3 3 0 013 3v1a1 1 0 102 0v-1a5 5 0 00-5-5H8.414l1.293-1.293z" clip-rule="evenodd"></path></svg>
                </div>
                <span class="text-xs font-semibold uppercase tracking-wider {{ $saldoMes >= 0 ? 'text-emerald-400/80' : 'text-red-400/80' }}">Saldo do Mês</span>
                <p class="text-3xl font-bold {{ $saldoMes >= 0 ? 'text-emerald-300' : 'text-red-300' }} mt-1">
                    {{ $saldoMes >= 0 ? '' : '-' }}R$ {{ number_format(abs($saldoMes), 2, ',', '.') }}
                </p>
                <div class="mt-3 text-sm text-slate-400">Receitas − Despesas pagas</div>
            </div>

            <!-- Despesas Semana -->
            <div class="glass-panel rounded-2xl p-6 card-hover relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4 opacity-10">
                    <svg class="w-16 h-16 text-rose-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 2a2 2 0 00-2 2v14l3.5-2 3.5 2 3.5-2 3.5 2V4a2 2 0 00-2-2H5zm2 3a1 1 0 000 2h6a1 1 0 100-2H7zm3 4a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"></path></svg>
                </div>
                <span class="text-xs font-semibold uppercase tracking-wider text-rose-400/80">Despesas da Semana</span>
                <p class="text-3xl font-bold text-white mt-1">R$ {{ number_format($despesasSemana, 2, ',', '.') }}</p>
                <div class="mt-3 text-sm text-slate-400">Despesas pagas esta semana</div>
            </div>

            <!-- Despesas Mês -->
            <div class="glass-panel rounded-2xl p-6 card-hover relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4 opacity-10">
                    <svg class="w-16 h-16 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 100 4h12a2 2 0 100-4H4z"></path><path fill-rule="evenodd" d="M3 8h14v7a2 2 0 01-2 2H5a2 2 0 01-2-2V8zm5 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" clip-rule="evenodd"></path></svg>
                </div>
                <span class="text-xs font-semibold uppercase tracking-wider text-amber-400/80">Despesas do Mês</span>
                <p class="text-3xl font-bold text-white mt-1">R$ {{ number_format($despesasMes, 2, ',', '.') }}</p>
                <div class="mt-3 text-sm text-slate-400">Despesas pagas este mês</div>
            </div>

            <!-- A Pagar -->
            <div class="glass-panel rounded-2xl p-6 card-hover relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4 opacity-10">
                    <svg class="w-16 h-16 text-orange-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 2a8 8 0 100 16 8 8 0 000-16zm1 8a1 1 0 11-2 0V6a1 1 0 112 0v4zm-1 4a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path></svg>
                </div>
                <span class="text-xs font-semibold uppercase tracking-wider text-orange-400/80">A Pagar (Pendentes)</span>
                <p class="text-3xl font-bold text-orange-300 mt-1">R$ {{ number_format($totalPendente, 2, ',', '.') }}</p>
                <div class="mt-3 text-sm text-slate-400">{{ $despesasPendentes->count() }} despesa(s) pendente(s)</div>
            </div>

        </div>

        <!-- Chart + Categories -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Last 8 weeks chart -->
            <div class="glass-panel rounded-2xl p-6 lg:col-span-2">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold text-white">Últimas 8 Semanas</h3>
                    <div class="flex gap-4 text-xs">
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-emerald-500 inline-block"></span> Receita</span>
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-rose-500 inline-block"></span> Despesa</span>
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-blue-500 inline-block"></span> Saldo</span>
                    </div>
                </div>
                <canvas id="chartSemanas" height="90"></canvas>
            </div>

            <!-- Categories pie -->
            <div class="glass-panel rounded-2xl p-6">
                <h3 class="text-lg font-bold text-white mb-6">Despesas por Categoria</h3>
                @if($porCategoria->count() > 0)
                    <canvas id="chartCategorias" height="160"></canvas>
                    <div class="mt-4 space-y-2">
                        @foreach($porCategoria as $cat)
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-400 capitalize">{{ $cat->categoria }}</span>
                            <span class="font-medium text-white">R$ {{ number_format($cat->total, 2, ',', '.') }}</span>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center h-48 text-slate-500">
                        <svg class="w-12 h-12 mb-3 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <p class="text-sm">Sem dados este mês</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Pending expenses quick view -->
        @if($despesasPendentes->count() > 0)
        <div class="glass-panel rounded-2xl shadow-lg">
            <div class="p-6 border-b border-white/10 flex justify-between items-center">
                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                    <span class="flex h-2.5 w-2.5 relative">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-orange-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-orange-500"></span>
                    </span>
                    Despesas Pendentes
                </h3>
                <a href="{{ route('financeiro.despesas') }}" class="text-emerald-400 text-sm hover:text-emerald-300">Gerenciar todas</a>
            </div>
            <div class="divide-y divide-white/5">
                @foreach($despesasPendentes->take(5) as $d)
                <div class="flex items-center justify-between px-6 py-4 hover:bg-slate-800/40 transition-colors">
                    <div>
                        <p class="font-medium text-slate-200">{{ $d->descricao }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">
                            <span class="capitalize">{{ $d->categoria }}</span> &middot; vence {{ $d->data_vencimento->format('d/m/Y') }}
                            @if($d->data_vencimento->isPast())
                                <span class="text-red-400 ml-1 font-medium">• Vencida</span>
                            @elseif($d->data_vencimento->isToday())
                                <span class="text-amber-400 ml-1 font-medium">• Vence hoje</span>
                            @endif
                        </p>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="font-bold text-white">R$ {{ number_format($d->valor, 2, ',', '.') }}</span>
                        <form action="{{ route('financeiro.despesas.pagar', $d->id) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="px-3 py-1.5 text-xs font-semibold bg-emerald-600/20 text-emerald-300 border border-emerald-600/40 rounded-lg hover:bg-emerald-600/40 transition-colors">
                                Pagar
                            </button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

    </main>

    <script>
        // Chart Semanas
        const semanasData = @json($ultimasSemanas);
        const ctx1 = document.getElementById('chartSemanas').getContext('2d');
        new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: semanasData.map(s => s.label),
                datasets: [
                    {
                        label: 'Receita',
                        data: semanasData.map(s => s.receita),
                        backgroundColor: 'rgba(16, 185, 129, 0.5)',
                        borderColor: '#10b981',
                        borderWidth: 1.5,
                        borderRadius: 4,
                    },
                    {
                        label: 'Despesa',
                        data: semanasData.map(s => s.despesa),
                        backgroundColor: 'rgba(244, 63, 94, 0.5)',
                        borderColor: '#f43f5e',
                        borderWidth: 1.5,
                        borderRadius: 4,
                    },
                    {
                        label: 'Saldo',
                        data: semanasData.map(s => s.saldo),
                        type: 'line',
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#3b82f6',
                        pointRadius: 4,
                    },
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => `${ctx.dataset.label}: R$ ${ctx.parsed.y.toFixed(2).replace('.', ',')}`
                        }
                    }
                },
                scales: {
                    x: { ticks: { color: '#94a3b8', font: { size: 11 } }, grid: { color: 'rgba(255,255,255,0.05)' } },
                    y: {
                        ticks: {
                            color: '#94a3b8',
                            font: { size: 11 },
                            callback: v => 'R$ ' + v.toFixed(0)
                        },
                        grid: { color: 'rgba(255,255,255,0.05)' }
                    }
                }
            }
        });

        @if($porCategoria->count() > 0)
        // Chart Categorias
        const catData = @json($porCategoria);
        const catColors = ['#10b981','#3b82f6','#f59e0b','#f43f5e','#8b5cf6','#06b6d4','#ec4899'];
        const ctx2 = document.getElementById('chartCategorias').getContext('2d');
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: catData.map(c => c.categoria),
                datasets: [{
                    data: catData.map(c => parseFloat(c.total)),
                    backgroundColor: catColors.slice(0, catData.length),
                    borderWidth: 0,
                }]
            },
            options: {
                cutout: '70%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => `R$ ${parseFloat(ctx.parsed).toFixed(2).replace('.', ',')}`
                        }
                    }
                }
            }
        });
        @endif
    </script>

</body>
</html>
