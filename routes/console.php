<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('replysys:test-call {telefone=51985408560} {--nome=Cliente Teste} {--item=Sapato Derby} {--orcamento=150.00} {--pago=50.00}', function ($telefone) {
    $nome = $this->option('nome');
    $item = $this->option('item');
    $orcamento = (float) $this->option('orcamento');
    $pago = (float) $this->option('pago');

    $this->info("Iniciando fluxo de teste de chamada...");
    $this->info("Telefone: {$telefone}");
    $this->info("Cliente: {$nome}");
    $this->info("Item: {$item}");
    $this->info("Orçamento: R$ " . number_format($orcamento, 2, ',', '.'));
    $this->info("Valor Pago: R$ " . number_format($pago, 2, ',', '.'));
    $this->info("Valor Restante: R$ " . number_format($orcamento - $pago, 2, ',', '.'));

    // Busca ou cria o cliente
    $cliente = \App\Models\Cliente::firstOrCreate(
        ['telefone' => $telefone],
        ['nome' => $nome]
    );

    // Cria uma OS de teste no status REPARADO
    $os = \App\Models\OrdemServico::create([
        'cliente_id' => $cliente->id,
        'modelo' => $item,
        'descricao_item' => $item,
        'status' => 'REPARADO',
        'valor_orcamento' => $orcamento,
        'valor_pago' => $pago,
        'status_pagamento' => $pago >= $orcamento ? 'total' : ($pago > 0 ? 'parcial' : 'pendente'),
        'defeito_relatado' => 'Teste de chamada automatizada',
        'data_entrada' => now(),
        'data_reparo' => now(),
    ]);

    $this->info("Ordem de Serviço de teste criada com ID: {$os->id}");

    $n8nService = new \App\Services\N8nService();
    $result = $n8nService->makeCall($os);

    if ($result) {
        $historico = $os->historicoLigacoes()->latest()->first();
        $this->info("Sucesso! Webhook do n8n disparado.");
        $this->info("External Call ID gerado: " . ($historico ? $historico->external_call_id : 'N/A'));
    } else {
        $this->error("Erro ao disparar chamada no n8n. Verifique os logs e a URL no .env.");
    }
})->purpose('Dispara uma chamada de teste para o n8n com dados de reparo customizaveis');

