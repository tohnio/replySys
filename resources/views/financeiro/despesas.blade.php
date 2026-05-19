<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ReplySys - Despesas</title>
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
        .input-field {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #e2e8f0;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .input-field:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
        }
    </style>
</head>
<body class="bg-slate-900 text-slate-200 min-h-screen relative overflow-x-hidden">

    <!-- Background -->
    <div class="absolute top-0 left-0 w-full h-full overflow-hidden -z-10 pointer-events-none">
        <div class="absolute -top-[10%] -right-[10%] w-[40%] h-[40%] rounded-full bg-emerald-600/10 blur-[120px]"></div>
        <div class="absolute top-[60%] -left-[10%] w-[30%] h-[30%] rounded-full bg-rose-600/10 blur-[100px]"></div>
    </div>

    <!-- Navbar -->
    <nav class="glass-panel sticky top-0 z-50 px-6 py-4 flex justify-between items-center shadow-lg shadow-black/20">
        <div class="flex items-center gap-3">
            <div class="bg-gradient-to-tr from-emerald-500 to-blue-500 p-2 rounded-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
            </div>
            <h1 class="text-2xl font-bold tracking-wide text-white">Reply<span class="text-emerald-400">Fin</span> <span class="text-slate-400 font-normal text-lg">/ Despesas</span></h1>
        </div>
        <div class="flex items-center gap-4">
            <a href="{{ route('financeiro.dashboard') }}" class="text-sm font-medium text-slate-300 hover:text-white transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Financeiro
            </a>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">

        <!-- Flash messages -->
        @if(session('success'))
        <div class="flex items-center gap-3 bg-emerald-900/40 border border-emerald-600/40 text-emerald-300 px-5 py-4 rounded-xl text-sm font-medium">
            <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
            {{ session('success') }}
        </div>
        @endif

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">

            <!-- Form: Nova Despesa -->
            <div class="xl:col-span-1">
                <div class="glass-panel rounded-2xl p-6 sticky top-24">
                    <h3 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Cadastrar Despesa
                    </h3>

                    <form action="{{ route('financeiro.despesas.store') }}" method="POST" class="space-y-4">
                        @csrf

                        <div>
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Descrição *</label>
                            <input type="text" name="descricao" value="{{ old('descricao') }}" placeholder="Ex: Aluguel do imóvel" required
                                class="input-field w-full px-4 py-2.5 rounded-xl text-sm">
                            @error('descricao')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Categoria *</label>
                            <select name="categoria" required class="input-field w-full px-4 py-2.5 rounded-xl text-sm">
                                <option value="">Selecione...</option>
                                @foreach(['aluguel' => 'Aluguel', 'energia' => 'Energia', 'agua' => 'Água', 'salario' => 'Salário', 'material' => 'Material/Peça', 'telefone' => 'Telefone/Internet', 'imposto' => 'Imposto', 'outros' => 'Outros'] as $val => $label)
                                    <option value="{{ $val }}" {{ old('categoria') == $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('categoria')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Valor (R$) *</label>
                            <input type="number" name="valor" value="{{ old('valor') }}" step="0.01" min="0.01" placeholder="0,00" required
                                class="input-field w-full px-4 py-2.5 rounded-xl text-sm">
                            @error('valor')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Vencimento *</label>
                            <input type="date" name="data_vencimento" value="{{ old('data_vencimento') }}" required
                                class="input-field w-full px-4 py-2.5 rounded-xl text-sm">
                            @error('data_vencimento')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Observação</label>
                            <textarea name="observacao" rows="2" placeholder="Detalhes adicionais..."
                                class="input-field w-full px-4 py-2.5 rounded-xl text-sm resize-none">{{ old('observacao') }}</textarea>
                        </div>

                        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-semibold py-2.5 rounded-xl transition-all shadow-lg shadow-emerald-900/30 flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Cadastrar Despesa
                        </button>
                    </form>
                </div>
            </div>

            <!-- List: Despesas -->
            <div class="xl:col-span-2">
                <div class="glass-panel rounded-2xl shadow-lg">
                    <div class="p-6 border-b border-white/10 flex justify-between items-center">
                        <h3 class="text-lg font-bold text-white">Todas as Despesas</h3>
                        <span class="text-sm text-slate-400">{{ $despesas->total() }} registros</span>
                    </div>

                    @if($despesas->count() > 0)
                    <div class="divide-y divide-white/5">
                        @foreach($despesas as $d)
                        <div class="flex items-center justify-between px-6 py-4 hover:bg-slate-800/30 transition-colors group">
                            <div class="flex items-start gap-4 flex-1 min-w-0">
                                <!-- Category badge -->
                                <div class="flex-shrink-0 mt-0.5">
                                    @php
                                        $catColor = match($d->categoria) {
                                            'aluguel'   => 'bg-purple-900/40 text-purple-300 border-purple-700/40',
                                            'energia'   => 'bg-amber-900/40 text-amber-300 border-amber-700/40',
                                            'agua'      => 'bg-blue-900/40 text-blue-300 border-blue-700/40',
                                            'salario'   => 'bg-indigo-900/40 text-indigo-300 border-indigo-700/40',
                                            'material'  => 'bg-teal-900/40 text-teal-300 border-teal-700/40',
                                            'imposto'   => 'bg-red-900/40 text-red-300 border-red-700/40',
                                            default     => 'bg-slate-800 text-slate-300 border-slate-600',
                                        };
                                    @endphp
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full border {{ $catColor }} capitalize">
                                        {{ $d->categoria }}
                                    </span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="font-medium text-slate-200 truncate">{{ $d->descricao }}</p>
                                    <p class="text-xs text-slate-400 mt-0.5">
                                        Vence: {{ $d->data_vencimento->format('d/m/Y') }}
                                        @if($d->status === 'pago')
                                            &middot; Pago em {{ $d->data_pagamento->format('d/m/Y') }}
                                        @elseif($d->data_vencimento->isPast())
                                            <span class="text-red-400 font-medium ml-1">• Vencida</span>
                                        @elseif($d->data_vencimento->isToday())
                                            <span class="text-amber-400 font-medium ml-1">• Vence hoje</span>
                                        @endif
                                    </p>
                                    @if($d->observacao)
                                        <p class="text-xs text-slate-500 truncate mt-0.5">{{ $d->observacao }}</p>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center gap-3 ml-4 flex-shrink-0">
                                <span class="font-bold text-white text-sm">R$ {{ number_format($d->valor, 2, ',', '.') }}</span>

                                @if($d->status === 'pendente')
                                    <span class="px-2.5 py-1 text-xs rounded-full bg-amber-900/40 text-amber-300 border border-amber-700/40">Pendente</span>
                                    <form action="{{ route('financeiro.despesas.pagar', $d->id) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" title="Marcar como pago" class="p-1.5 rounded-lg bg-emerald-600/20 text-emerald-400 border border-emerald-600/30 hover:bg-emerald-600/40 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        </button>
                                    </form>
                                @else
                                    <span class="px-2.5 py-1 text-xs rounded-full bg-emerald-900/40 text-emerald-300 border border-emerald-700/40">Pago</span>
                                @endif

                                <form action="{{ route('financeiro.despesas.destroy', $d->id) }}" method="POST"
                                    onsubmit="return confirm('Deseja remover esta despesa?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Remover" class="p-1.5 rounded-lg text-slate-500 hover:text-red-400 hover:bg-red-900/20 transition-colors opacity-0 group-hover:opacity-100">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div class="p-4 border-t border-white/5">
                        {{ $despesas->links() }}
                    </div>
                    @else
                    <div class="flex flex-col items-center justify-center py-20 text-slate-500">
                        <svg class="w-14 h-14 mb-4 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                        <p class="text-lg font-medium">Nenhuma despesa cadastrada.</p>
                        <p class="text-sm mt-1">Use o formulário ao lado para adicionar a primeira.</p>
                    </div>
                    @endif
                </div>
            </div>

        </div>
    </main>

</body>
</html>
