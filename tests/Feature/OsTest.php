<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\OrdemServico;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_ordens_servico(): void
    {
        $cliente = Cliente::create(['nome' => 'Cliente Teste', 'telefone' => '11999999999']);
        OrdemServico::create([
            'cliente_id' => $cliente->id,
            'descricao_item' => 'Celular',
            'status' => 'RECEBIDO',
            'valor_orcamento' => 150.00,
            'status_pagamento' => 'pendente',
            'defeito_relatado' => ''
        ]);

        $response = $this->getJson('/api/ordens-servico');

        $response->assertStatus(200)
                 ->assertJsonCount(1);
    }

    public function test_can_create_ordem_servico(): void
    {
        $payload = [
            'nome' => 'Novo Cliente',
            'telefone' => '11988888888',
            'modelo' => 'Samsung S22',
            'defeito_relatado' => 'Tela Quebrada',
            'data_pronto' => '2026-05-25',
            'valor' => 300.00,
            'status_pagamento' => 'parcial',
            'valor_pago' => 100.00
        ];

        $response = $this->postJson('/api/ordens-servico', $payload);

        $response->assertStatus(201)
                 ->assertJsonPath('os.status', 'RECEBIDO')
                 ->assertJsonPath('os.valor_orcamento', '300.00')
                 ->assertJsonPath('os.valor_pago', '100.00');

        $this->assertDatabaseHas('clientes', [
            'nome' => 'Novo Cliente',
            'telefone' => '11988888888'
        ]);

        $this->assertDatabaseHas('ordem_servicos', [
            'modelo' => 'Samsung S22',
            'defeito_relatado' => 'Tela Quebrada',
            'valor_orcamento' => 300.00,
            'status_pagamento' => 'parcial',
            'valor_pago' => 100.00
        ]);
    }

    public function test_can_update_ordem_servico(): void
    {
        $cliente = Cliente::create(['nome' => 'Cliente Antigo', 'telefone' => '11977777777']);
        $os = OrdemServico::create([
            'cliente_id' => $cliente->id,
            'descricao_item' => 'Celular',
            'modelo' => 'iPhone XR',
            'status' => 'RECEBIDO',
            'valor_orcamento' => 150.00,
            'status_pagamento' => 'pendente',
            'defeito_relatado' => ''
        ]);

        $payload = [
            'nome' => 'Cliente Editado',
            'telefone' => '11966666666',
            'modelo' => 'iPhone 11',
            'defeito_relatado' => 'Bateria estufada',
            'data_pronto' => '2026-05-28',
            'valor' => 250.00,
            'status_pagamento' => 'total',
            'valor_pago' => 250.00
        ];

        $response = $this->putJson("/api/ordens-servico/{$os->id}", $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('clientes', [
            'id' => $cliente->id,
            'nome' => 'Cliente Editado',
            'telefone' => '11966666666'
        ]);

        $this->assertDatabaseHas('ordem_servicos', [
            'id' => $os->id,
            'modelo' => 'iPhone 11',
            'defeito_relatado' => 'Bateria estufada',
            'valor_orcamento' => 250.00,
            'status_pagamento' => 'total',
            'valor_pago' => 250.00
        ]);
    }

    public function test_can_update_status(): void
    {
        $cliente = Cliente::create(['nome' => 'Cliente Status', 'telefone' => '11955555555']);
        $os = OrdemServico::create([
            'cliente_id' => $cliente->id,
            'descricao_item' => 'Celular',
            'modelo' => 'Motorola G50',
            'status' => 'RECEBIDO',
            'valor_orcamento' => 100.00,
            'status_pagamento' => 'pendente',
            'defeito_relatado' => ''
        ]);

        $response = $this->putJson("/api/ordens-servico/{$os->id}/status", [
            'status' => 'REPARADO'
        ]);

        $response->assertStatus(200);
        $this->assertEquals('REPARADO', $os->fresh()->status);
        $this->assertNotNull($os->fresh()->data_reparo);
    }

    public function test_create_ordem_servico_validation_fails(): void
    {
        $payload = [
            'nome' => '', // Required name is missing/empty
            'status_pagamento' => 'invalid-status' // Invalid value
        ];

        $response = $this->postJson('/api/ordens-servico', $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['nome', 'status_pagamento']);
    }

    public function test_update_status_validation_fails(): void
    {
        $cliente = Cliente::create(['nome' => 'Cliente Status', 'telefone' => '11955555555']);
        $os = OrdemServico::create([
            'cliente_id' => $cliente->id,
            'descricao_item' => 'Celular',
            'modelo' => 'Motorola G50',
            'status' => 'RECEBIDO',
            'valor_orcamento' => 100.00,
            'status_pagamento' => 'pendente',
            'defeito_relatado' => ''
        ]);

        $response = $this->putJson("/api/ordens-servico/{$os->id}/status", [
            'status' => 'CONCLUIDO' // Not a valid enum status
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['status']);
    }

    public function test_create_ordem_servico_reuses_existing_cliente(): void
    {
        $existingCliente = Cliente::create([
            'nome' => 'Original Name',
            'telefone' => '11912345678'
        ]);

        $payload = [
            'nome' => 'New Name (ignored for matching client)',
            'telefone' => '11912345678', // Same phone
            'modelo' => 'iPhone 13',
            'defeito_relatado' => 'Broken screen',
            'valor' => 450.00,
            'status_pagamento' => 'pendente'
        ];

        $response = $this->postJson('/api/ordens-servico', $payload);

        $response->assertStatus(201);
        
        // Assert that the created OS is associated with the existing client
        $osId = $response->json('os.id');
        $os = OrdemServico::find($osId);
        $this->assertEquals($existingCliente->id, $os->cliente_id);

        // Assert that a new client was NOT created
        $this->assertEquals(1, Cliente::where('telefone', '11912345678')->count());
    }

    public function test_create_ordem_servico_creates_client_without_phone(): void
    {
        $payload = [
            'nome' => 'Client Without Phone',
            'telefone' => '', // empty
            'modelo' => 'Tablet',
            'defeito_relatado' => 'Battery replacement'
        ];

        $response = $this->postJson('/api/ordens-servico', $payload);

        $response->assertStatus(201);
        
        $osId = $response->json('os.id');
        $os = OrdemServico::find($osId);
        $this->assertNotNull($os->cliente_id);
        
        $cliente = $os->cliente;
        $this->assertEquals('Client Without Phone', $cliente->nome);
        $this->assertEquals('', $cliente->telefone);
    }

    public function test_update_status_to_reparado_dispatches_whatsapp_job(): void
    {
        \Illuminate\Support\Facades\Queue::fake();

        $cliente = Cliente::create(['nome' => 'Cliente Status', 'telefone' => '11955555555']);
        $os = OrdemServico::create([
            'cliente_id' => $cliente->id,
            'descricao_item' => 'Celular',
            'modelo' => 'Motorola G50',
            'status' => 'RECEBIDO',
            'valor_orcamento' => 100.00,
            'status_pagamento' => 'pendente',
            'defeito_relatado' => ''
        ]);

        $response = $this->putJson("/api/ordens-servico/{$os->id}/status", [
            'status' => 'REPARADO'
        ]);

        $response->assertStatus(200);

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\WhatsAppNotificationJob::class, function ($job) use ($os) {
            return $job->os->id === $os->id;
        });
    }

    public function test_update_status_to_reparado_does_not_dispatch_job_if_no_phone(): void
    {
        \Illuminate\Support\Facades\Queue::fake();

        $cliente = Cliente::create(['nome' => 'Cliente Sem Telefone', 'telefone' => '']);
        $os = OrdemServico::create([
            'cliente_id' => $cliente->id,
            'descricao_item' => 'Celular',
            'status' => 'RECEBIDO',
            'valor_orcamento' => 100.00,
            'status_pagamento' => 'pendente',
            'defeito_relatado' => ''
        ]);

        $response = $this->putJson("/api/ordens-servico/{$os->id}/status", [
            'status' => 'REPARADO'
        ]);

        $response->assertStatus(200);

        \Illuminate\Support\Facades\Queue::assertNotPushed(\App\Jobs\WhatsAppNotificationJob::class);
    }

    public function test_update_status_to_entregue_sets_data_entregue(): void
    {
        $cliente = Cliente::create(['nome' => 'Cliente Status', 'telefone' => '11955555555']);
        $os = OrdemServico::create([
            'cliente_id' => $cliente->id,
            'descricao_item' => 'Celular',
            'status' => 'REPARADO',
            'valor_orcamento' => 100.00,
            'status_pagamento' => 'pendente',
            'defeito_relatado' => ''
        ]);

        $this->assertNull($os->data_entregue);

        $response = $this->putJson("/api/ordens-servico/{$os->id}/status", [
            'status' => 'ENTREGUE'
        ]);

        $response->assertStatus(200);
        $this->assertNotNull($os->fresh()->data_entregue);
    }

    public function test_manual_redial_triggers_call_job(): void
    {
        \Illuminate\Support\Facades\Queue::fake();

        $cliente = Cliente::create(['nome' => 'Cliente Redial', 'telefone' => '11944443333']);
        $os = OrdemServico::create([
            'cliente_id' => $cliente->id,
            'descricao_item' => 'Sapato',
            'status' => 'REPARADO',
            'valor_orcamento' => 120.00,
            'status_pagamento' => 'pendente',
            'defeito_relatado' => ''
        ]);

        $response = $this->postJson("/api/ordens-servico/{$os->id}/redial");

        $response->assertStatus(200)
                 ->assertJsonPath('status', 'success');

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\CallCustomerJob::class, function ($job) use ($os) {
            return $job->os->id === $os->id;
        });
    }

    public function test_manual_redial_fails_if_not_reparado(): void
    {
        \Illuminate\Support\Facades\Queue::fake();

        $cliente = Cliente::create(['nome' => 'Cliente Redial', 'telefone' => '11944443333']);
        $os = OrdemServico::create([
            'cliente_id' => $cliente->id,
            'descricao_item' => 'Sapato',
            'status' => 'EM_REPARO',
            'valor_orcamento' => 120.00,
            'status_pagamento' => 'pendente',
            'defeito_relatado' => ''
        ]);

        $response = $this->postJson("/api/ordens-servico/{$os->id}/redial");

        $response->assertStatus(400)
                 ->assertJsonPath('status', 'error');

        \Illuminate\Support\Facades\Queue::assertNotPushed(\App\Jobs\CallCustomerJob::class);
    }

    public function test_manual_redial_fails_if_no_phone(): void
    {
        \Illuminate\Support\Facades\Queue::fake();

        $cliente = Cliente::create(['nome' => 'Cliente Sem Fone', 'telefone' => '']);
        $os = OrdemServico::create([
            'cliente_id' => $cliente->id,
            'descricao_item' => 'Sapato',
            'status' => 'REPARADO',
            'valor_orcamento' => 120.00,
            'status_pagamento' => 'pendente',
            'defeito_relatado' => ''
        ]);

        $response = $this->postJson("/api/ordens-servico/{$os->id}/redial");

        $response->assertStatus(400)
                 ->assertJsonPath('status', 'error');

        \Illuminate\Support\Facades\Queue::assertNotPushed(\App\Jobs\CallCustomerJob::class);
    }
}

