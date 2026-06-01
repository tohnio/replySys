<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\OrdemServico;
use App\Models\HistoricoLigacao;
use App\Jobs\CallCustomerJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Carbon\Carbon;
use Tests\TestCase;

class N8nTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.n8n.webhook_url' => 'https://n8n.test-url.com/webhook/123',
        ]);
    }

    public function test_n8n_service_sends_correct_http_payload(): void
    {
        Http::fake([
            'n8n.test-url.com/webhook/*' => Http::response(['status' => 'success'], 200)
        ]);

        $cliente = Cliente::create(['nome' => 'Maria Silva', 'telefone' => '(11) 99999-8888']);
        $os = OrdemServico::create([
            'cliente_id' => $cliente->id,
            'descricao_item' => 'Smartphone G8',
            'status' => 'REPARADO',
            'valor_orcamento' => 150.00,
            'status_pagamento' => 'pendente',
            'defeito_relatado' => ''
        ]);

        $n8nService = new \App\Services\N8nService();
        $result = $n8nService->makeCall($os);

        $this->assertTrue($result);

        Http::assertSent(function ($request) use ($os, $cliente) {
            return $request->url() === 'https://n8n.test-url.com/webhook/123' &&
                   $request['ordem_servico_id'] === $os->id &&
                   $request['cliente']['nome'] === 'Maria Silva' &&
                   $request['cliente']['telefone'] === '+5511999998888' &&
                   $request['item_reparado'] === 'Smartphone G8' &&
                   $request['valor_orcamento'] === 150.00 &&
                   isset($request['external_call_id']) &&
                   $request['callback_url'] === url('/api/webhook/n8n');
        });

        $this->assertDatabaseHas('historico_ligacaos', [
            'ordem_servico_id' => $os->id,
            'status_ligacao' => 'pendente'
        ]);
    }

    public function test_n8n_service_skips_call_if_not_reparado(): void
    {
        $cliente = Cliente::create(['nome' => 'Maria Silva', 'telefone' => '11999998888']);
        $os = OrdemServico::create([
            'cliente_id' => $cliente->id,
            'descricao_item' => 'Smartphone G8',
            'status' => 'RECEBIDO', // not REPARADO
            'valor_orcamento' => 150.00,
            'status_pagamento' => 'pendente',
            'defeito_relatado' => ''
        ]);

        $n8nService = new \App\Services\N8nService();
        $result = $n8nService->makeCall($os);

        $this->assertFalse($result);
        $this->assertDatabaseMissing('historico_ligacaos', [
            'ordem_servico_id' => $os->id
        ]);
    }

    public function test_n8n_webhook_handles_answered_call(): void
    {
        $cliente = Cliente::create(['nome' => 'João', 'telefone' => '11988887777']);
        $os = OrdemServico::create([
            'cliente_id' => $cliente->id,
            'descricao_item' => 'Notebook',
            'status' => 'REPARADO',
            'valor_orcamento' => 200.00,
            'status_pagamento' => 'pendente',
            'defeito_relatado' => ''
        ]);

        $historico = HistoricoLigacao::create([
            'ordem_servico_id' => $os->id,
            'external_call_id' => 'call-uuid-123',
            'status_ligacao' => 'pendente',
            'data_ligacao' => now()
        ]);

        $payload = [
            'external_call_id' => 'call-uuid-123',
            'ordem_servico_id' => $os->id,
            'status_ligacao' => 'atendida',
            'duracao' => 45,
            'transcricao_ia' => 'Olá! Sim, meu notebook está pronto.'
        ];

        $response = $this->postJson('/api/webhook/n8n', $payload);

        $response->assertStatus(200);
        
        $historicoAtualizado = $historico->fresh();
        $this->assertEquals('atendida', $historicoAtualizado->status_ligacao);
        $this->assertEquals(45, $historicoAtualizado->duracao);
        $this->assertEquals('Olá! Sim, meu notebook está pronto.', $historicoAtualizado->transcricao_ia);
    }

    public function test_n8n_webhook_handles_no_answer_and_schedules_retry(): void
    {
        Queue::fake();
        Carbon::setTestNow(Carbon::create(2026, 5, 20, 14, 0, 0)); // 14:00 (inside 09-20 range)

        $cliente = Cliente::create(['nome' => 'João', 'telefone' => '11988887777']);
        $os = OrdemServico::create([
            'cliente_id' => $cliente->id,
            'descricao_item' => 'Notebook',
            'status' => 'REPARADO',
            'valor_orcamento' => 200.00,
            'status_pagamento' => 'pendente',
            'defeito_relatado' => ''
        ]);

        $historico = HistoricoLigacao::create([
            'ordem_servico_id' => $os->id,
            'external_call_id' => 'call-uuid-123',
            'status_ligacao' => 'pendente',
            'data_ligacao' => now()
        ]);

        $payload = [
            'external_call_id' => 'call-uuid-123',
            'ordem_servico_id' => $os->id,
            'status_ligacao' => 'caixa_postal',
            'duracao' => 0,
            'transcricao_ia' => ''
        ];

        $response = $this->postJson('/api/webhook/n8n', $payload);

        $response->assertStatus(200);

        // Verify status updated to caixa_postal
        $this->assertEquals('caixa_postal', $historico->fresh()->status_ligacao);

        // Assert proxima_tentativa is set to 1 hour later (15:00)
        $expectedProxima = Carbon::create(2026, 5, 20, 15, 0, 0);
        $this->assertEquals($expectedProxima->toDateTimeString(), Carbon::parse($historico->fresh()->proxima_tentativa)->toDateTimeString());

        // Assert job was dispatched with delay
        Queue::assertPushed(CallCustomerJob::class, function ($job) use ($os) {
            return $job->os->id === $os->id;
        });

        Carbon::setTestNow();
    }

    public function test_n8n_webhook_schedules_next_day_if_out_of_range(): void
    {
        Queue::fake();
        Carbon::setTestNow(Carbon::create(2026, 5, 20, 17, 30, 0)); // 17:30 + 1 hour is 18:30 (outside 09-18 range)

        $cliente = Cliente::create(['nome' => 'João', 'telefone' => '11988887777']);
        $os = OrdemServico::create([
            'cliente_id' => $cliente->id,
            'descricao_item' => 'Notebook',
            'status' => 'REPARADO',
            'valor_orcamento' => 200.00,
            'status_pagamento' => 'pendente',
            'defeito_relatado' => ''
        ]);

        $historico = HistoricoLigacao::create([
            'ordem_servico_id' => $os->id,
            'external_call_id' => 'call-uuid-123',
            'status_ligacao' => 'pendente',
            'data_ligacao' => now()
        ]);

        $payload = [
            'external_call_id' => 'call-uuid-123',
            'ordem_servico_id' => $os->id,
            'status_ligacao' => 'caixa_postal',
            'duracao' => 0,
            'transcricao_ia' => ''
        ];

        $response = $this->postJson('/api/webhook/n8n', $payload);

        $response->assertStatus(200);

        // Assert proxima_tentativa is shifted to tomorrow at 09:00
        $expectedProxima = Carbon::create(2026, 5, 21, 9, 0, 0);
        $this->assertEquals($expectedProxima->toDateTimeString(), Carbon::parse($historico->fresh()->proxima_tentativa)->toDateTimeString());

        Carbon::setTestNow();
    }

    public function test_n8n_webhook_does_not_retry_beyond_four_attempts(): void
    {
        Queue::fake();

        $cliente = Cliente::create(['nome' => 'João', 'telefone' => '11988887777']);
        $os = OrdemServico::create([
            'cliente_id' => $cliente->id,
            'descricao_item' => 'Notebook',
            'status' => 'REPARADO',
            'valor_orcamento' => 200.00,
            'status_pagamento' => 'pendente',
            'defeito_relatado' => ''
        ]);

        // Cria o único registro de histórico com tentativas = 4 e status pendente
        $historico = HistoricoLigacao::create([
            'ordem_servico_id' => $os->id,
            'external_call_id' => 'call-uuid-123',
            'status_ligacao' => 'pendente',
            'data_ligacao' => now(),
            'tentativas' => 4
        ]);

        $payload = [
            'external_call_id' => 'call-uuid-123',
            'ordem_servico_id' => $os->id,
            'status_ligacao' => 'caixa_postal',
            'duracao' => 0
        ];

        $response = $this->postJson('/api/webhook/n8n', $payload);

        $response->assertStatus(200);

        // Total attempts is 4. No more retries should be pushed.
        Queue::assertNotPushed(CallCustomerJob::class);
        $this->assertNull($historico->fresh()->proxima_tentativa);
    }

    public function test_n8n_webhook_can_append_transcription_logs(): void
    {
        $cliente = Cliente::create(['nome' => 'Renato', 'telefone' => '11977776666']);
        $os = OrdemServico::create([
            'cliente_id' => $cliente->id,
            'descricao_item' => 'Tênis de Corrida',
            'status' => 'REPARADO',
            'valor_orcamento' => 180.00,
            'status_pagamento' => 'pendente',
            'defeito_relatado' => ''
        ]);

        $historico = HistoricoLigacao::create([
            'ordem_servico_id' => $os->id,
            'external_call_id' => 'call-uuid-interactive-789',
            'status_ligacao' => 'pendente',
            'transcricao_ia' => 'IA: Olá!',
            'data_ligacao' => now()
        ]);

        // First append: Cliente text
        $payload1 = [
            'external_call_id' => 'call-uuid-interactive-789',
            'transcricao_ia' => 'Cliente: Olá! Onde fica a loja?',
            'append' => true
        ];
        $response1 = $this->postJson('/api/webhook/n8n', $payload1);
        $response1->assertStatus(200);
        $this->assertEquals("IA: Olá!\nCliente: Olá! Onde fica a loja?", $historico->fresh()->transcricao_ia);

        // Second append: IA response
        $payload2 = [
            'external_call_id' => 'call-uuid-interactive-789',
            'transcricao_ia' => 'IA: Fica na Otto Niemeyer, 3210.',
            'append' => true
        ];
        $response2 = $this->postJson('/api/webhook/n8n', $payload2);
        $response2->assertStatus(200);
        $this->assertEquals("IA: Olá!\nCliente: Olá! Onde fica a loja?\nIA: Fica na Otto Niemeyer, 3210.", $historico->fresh()->transcricao_ia);

        // Check that call status remained 'pendente'
        $this->assertEquals('pendente', $historico->fresh()->status_ligacao);
    }

    public function test_n8n_get_call_details_endpoint(): void
    {
        $cliente = Cliente::create(['nome' => 'Carla', 'telefone' => '11966665555']);
        $os = OrdemServico::create([
            'cliente_id' => $cliente->id,
            'descricao_item' => 'Sapato Social',
            'status' => 'REPARADO',
            'valor_orcamento' => 200.00,
            'valor_pago' => 50.00,
            'status_pagamento' => 'parcial',
            'defeito_relatado' => ''
        ]);

        $historico = HistoricoLigacao::create([
            'ordem_servico_id' => $os->id,
            'external_call_id' => 'call-uuid-details-abc',
            'status_ligacao' => 'pendente',
            'data_ligacao' => now()
        ]);

        $response = $this->getJson('/api/webhook/n8n/call-details/call-uuid-details-abc');

        $response->assertStatus(200)
                 ->assertJson([
                     'cliente_nome' => 'Carla',
                     'item_reparado' => 'Sapato Social',
                     'valor_restante' => '150.00'
                 ]);
    }

    public function test_n8n_get_call_details_returns_404_if_not_found(): void
    {
        $response = $this->getJson('/api/webhook/n8n/call-details/non-existent-uuid');
        $response->assertStatus(404);
    }

    public function test_n8n_get_client_details_endpoint(): void
    {
        $cliente = Cliente::create(['nome' => 'Bruno', 'telefone' => '11955554444']);
        $os = OrdemServico::create([
            'cliente_id' => $cliente->id,
            'descricao_item' => 'Cinto de Couro',
            'status' => 'REPARADO',
            'valor_orcamento' => 80.00,
            'valor_pago' => 0.00,
            'status_pagamento' => 'pendente',
            'defeito_relatado' => ''
        ]);

        $response = $this->getJson('/api/webhook/n8n/client-details/+5511955554444');

        $response->assertStatus(200)
                 ->assertJson([
                     'cliente_nome' => 'Bruno',
                     'item_reparado' => 'Cinto de Couro',
                     'valor_restante' => '80.00'
                 ]);
    }

    public function test_n8n_get_client_details_returns_404_if_not_found(): void
    {
        $response = $this->getJson('/api/webhook/n8n/client-details/5599999999999');
        $response->assertStatus(404);
    }

    public function test_whatsapp_notification_job_triggers_fallback_on_immediate_failure(): void
    {
        Queue::fake();
        Http::fake([
            'n8n.test-url.com/whatsapp/*' => Http::response(['error' => 'Unauthorized'], 401)
        ]);

        config([
            'services.n8n.whatsapp_webhook_url' => 'https://n8n.test-url.com/whatsapp/123',
        ]);

        $cliente = Cliente::create(['nome' => 'Luiza', 'telefone' => '11911112222']);
        $os = OrdemServico::create([
            'cliente_id' => $cliente->id,
            'descricao_item' => 'Sandália',
            'status' => 'REPARADO',
            'valor_orcamento' => 90.00,
            'status_pagamento' => 'pendente',
            'defeito_relatado' => ''
        ]);

        $n8nService = new \App\Services\N8nService();
        $job = new \App\Jobs\WhatsAppNotificationJob($os);
        $job->handle($n8nService);

        // Assert WhatsApp call was recorded as erro ao executar chamada
        $this->assertDatabaseHas('historico_ligacaos', [
            'ordem_servico_id' => $os->id,
            'status_ligacao' => 'erro ao executar chamada',
            'tentativas' => 1
        ]);

        // Assert CallCustomerJob was dispatched as a fallback
        Queue::assertPushed(\App\Jobs\CallCustomerJob::class, function ($job) use ($os) {
            return $job->os->id === $os->id;
        });
    }

    public function test_n8n_whatsapp_flow_sends_correct_payload_and_records_status(): void
    {
        Http::fake([
            'n8n.test-url.com/whatsapp/*' => Http::response(['status' => 'success'], 200)
        ]);

        config([
            'services.n8n.whatsapp_webhook_url' => 'https://n8n.test-url.com/whatsapp/123',
        ]);

        $cliente = Cliente::create(['nome' => 'Luiza', 'telefone' => '11911112222']);
        $os = OrdemServico::create([
            'cliente_id' => $cliente->id,
            'descricao_item' => 'Sandália',
            'status' => 'REPARADO',
            'valor_orcamento' => 90.00,
            'status_pagamento' => 'pendente',
            'defeito_relatado' => ''
        ]);

        $n8nService = new \App\Services\N8nService();
        $result = $n8nService->sendWhatsApp($os);

        $this->assertTrue($result);

        Http::assertSent(function ($request) use ($os) {
            return $request->url() === 'https://n8n.test-url.com/whatsapp/123' &&
                   $request['ordem_servico_id'] === $os->id &&
                   $request['cliente']['nome'] === 'Luiza' &&
                   $request['cliente']['telefone'] === '+5511911112222';
        });

        $this->assertDatabaseHas('historico_ligacaos', [
            'ordem_servico_id' => $os->id,
            'status_ligacao' => 'chamada por whatsapp pendente',
            'tentativas' => 1
        ]);
    }

    public function test_whatsapp_reply_endpoint_returns_should_reply_false_when_phone_not_found(): void
    {
        $response = $this->postJson('/api/webhook/n8n/whatsapp-reply', [
            'phone' => '11999999999',
            'text' => 'Olá',
            'message_id' => 'msg-1'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'should_reply' => false
                 ]);
    }

    public function test_whatsapp_reply_endpoint_returns_correct_responses(): void
    {
        $cliente = Cliente::create(['nome' => 'Luiza', 'telefone' => '11911112222']);
        $os = OrdemServico::create([
            'cliente_id' => $cliente->id,
            'descricao_item' => 'Sandália',
            'status' => 'REPARADO',
            'valor_orcamento' => 90.00,
            'status_pagamento' => 'pendente',
            'defeito_relatado' => ''
        ]);

        $historico = HistoricoLigacao::create([
            'ordem_servico_id' => $os->id,
            'status_ligacao' => 'chamada por whatsapp pendente',
            'tentativas' => 1
        ]);

        // Test Horário
        $response = $this->postJson('/api/webhook/n8n/whatsapp-reply', [
            'phone' => '5511911112222',
            'text' => 'Qual o horario de atendimento de vcs?',
            'message_id' => 'msg-horario'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'should_reply' => true,
                     'reply' => "Nosso horário de atendimento é de segunda a sexta-feira, das 09:00 às 18:00 (não abrimos aos sábados e domingos)."
                 ]);

        // Verify reply count incremented and message id saved
        $historico = $historico->fresh();
        $this->assertEquals(1, $historico->whatsapp_reply_count);
        $this->assertEquals('msg-horario', $historico->last_whatsapp_message_id);

        // Test Endereço
        $response2 = $this->postJson('/api/webhook/n8n/whatsapp-reply', [
            'phone' => '5511911112222',
            'text' => 'qual o endereço da loja?',
            'message_id' => 'msg-endereco'
        ]);

        $response2->assertStatus(200)
                  ->assertJson([
                      'should_reply' => true,
                      'reply' => "Nosso endereço é Avenida Otto Niemeyer, 3210 - Cavalhada, Porto Alegre - RS."
                  ]);

        $historico = $historico->fresh();
        $this->assertEquals(2, $historico->whatsapp_reply_count);
        $this->assertEquals('msg-endereco', $historico->last_whatsapp_message_id);
    }

    public function test_whatsapp_reply_endpoint_limits_responses_to_two(): void
    {
        $cliente = Cliente::create(['nome' => 'Luiza', 'telefone' => '11911112222']);
        $os = OrdemServico::create([
            'cliente_id' => $cliente->id,
            'descricao_item' => 'Sandália',
            'status' => 'REPARADO',
            'valor_orcamento' => 90.00,
            'status_pagamento' => 'pendente',
            'defeito_relatado' => ''
        ]);

        HistoricoLigacao::create([
            'ordem_servico_id' => $os->id,
            'status_ligacao' => 'chamada por whatsapp pendente',
            'tentativas' => 1,
            'whatsapp_reply_count' => 2 // already replied twice
        ]);

        $response = $this->postJson('/api/webhook/n8n/whatsapp-reply', [
            'phone' => '5511911112222',
            'text' => 'Qual o endereço?',
            'message_id' => 'msg-3'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'should_reply' => false
                 ]);
    }

    public function test_whatsapp_reply_endpoint_prevents_duplicate_message_processing(): void
    {
        $cliente = Cliente::create(['nome' => 'Luiza', 'telefone' => '11911112222']);
        $os = OrdemServico::create([
            'cliente_id' => $cliente->id,
            'descricao_item' => 'Sandália',
            'status' => 'REPARADO',
            'valor_orcamento' => 90.00,
            'status_pagamento' => 'pendente',
            'defeito_relatado' => ''
        ]);

        HistoricoLigacao::create([
            'ordem_servico_id' => $os->id,
            'status_ligacao' => 'chamada por whatsapp pendente',
            'tentativas' => 1,
            'whatsapp_reply_count' => 0,
            'last_whatsapp_message_id' => 'msg-dup-123'
        ]);

        $response = $this->postJson('/api/webhook/n8n/whatsapp-reply', [
            'phone' => '5511911112222',
            'text' => 'qual o horário de atendimento?',
            'message_id' => 'msg-dup-123'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'should_reply' => false
                 ]);
    }

    public function test_whatsapp_reply_endpoint_combines_responses(): void
    {
        $cliente = Cliente::create(['nome' => 'Luiza', 'telefone' => '11911112222']);
        $os = OrdemServico::create([
            'cliente_id' => $cliente->id,
            'descricao_item' => 'Sandália',
            'status' => 'REPARADO',
            'valor_orcamento' => 90.00,
            'status_pagamento' => 'pendente',
            'defeito_relatado' => ''
        ]);

        $historico = HistoricoLigacao::create([
            'ordem_servico_id' => $os->id,
            'status_ligacao' => 'chamada por whatsapp pendente',
            'tentativas' => 1
        ]);

        $response = $this->postJson('/api/webhook/n8n/whatsapp-reply', [
            'phone' => '5511911112222',
            'text' => 'Certo, vou passar amanhã. fecha ao meio dia? qual o endereço da loja?',
            'message_id' => 'msg-combined'
        ]);

        $expectedReply = "Nosso horário de atendimento é de segunda a sexta-feira, das 09:00 às 18:00 (não abrimos aos sábados e domingos).\nNosso endereço é Avenida Otto Niemeyer, 3210 - Cavalhada, Porto Alegre - RS.";

        $response->assertStatus(200)
                 ->assertJson([
                     'should_reply' => true,
                     'reply' => $expectedReply
                 ]);

        $this->assertEquals(1, $historico->fresh()->whatsapp_reply_count);
    }

    public function test_whatsapp_reply_endpoint_returns_default_for_unknown(): void
    {
        $cliente = Cliente::create(['nome' => 'Luiza', 'telefone' => '11911112222']);
        $os = OrdemServico::create([
            'cliente_id' => $cliente->id,
            'descricao_item' => 'Sandália',
            'status' => 'REPARADO',
            'valor_orcamento' => 90.00,
            'status_pagamento' => 'pendente',
            'defeito_relatado' => ''
        ]);

        HistoricoLigacao::create([
            'ordem_servico_id' => $os->id,
            'status_ligacao' => 'chamada por whatsapp pendente',
            'tentativas' => 1
        ]);

        $response = $this->postJson('/api/webhook/n8n/whatsapp-reply', [
            'phone' => '5511911112222',
            'text' => 'Qual o valor total para consertar?',
            'message_id' => 'msg-unknown'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'should_reply' => true,
                     'reply' => 'Desculpe, não sei dizer no momento.'
                 ]);
    }
}
