<?php

namespace Tests\Feature;

use App\Models\Despesa;
use App\Models\OrdemServico;
use App\Models\Cliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Tests\TestCase;

class FinanceiroTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_access_financeiro_dashboard(): void
    {
        // Set fixed current date for testing predictability
        Carbon::setTestNow(Carbon::create(2026, 5, 20, 12, 0, 0));

        $cliente = Cliente::create(['nome' => 'Cliente Financeiro', 'telefone' => '11911111111']);
        
        // OS with total adiantado payment created in the range
        OrdemServico::create([
            'cliente_id' => $cliente->id,
            'descricao_item' => 'Celular',
            'status' => 'RECEBIDO',
            'defeito_relatado' => '',
            'valor_orcamento' => 150.00,
            'status_pagamento' => 'total',
            'valor_pago' => 150.00,
            'created_at' => Carbon::now()
        ]);

        // Paid expense
        Despesa::create([
            'descricao' => 'Energia',
            'categoria' => 'Luz',
            'valor' => 50.00,
            'data_vencimento' => Carbon::now()->subDays(5),
            'status' => 'pago',
            'data_pagamento' => Carbon::now()
        ]);

        // Pending expense
        Despesa::create([
            'descricao' => 'Aluguel',
            'categoria' => 'Infraestrutura',
            'valor' => 120.00,
            'data_vencimento' => Carbon::now()->addDays(5),
            'status' => 'pendente'
        ]);

        $response = $this->get(route('financeiro.dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('receitaSemana', 150.0);
        $response->assertViewHas('despesasSemana', 50.0);
        $response->assertViewHas('saldoSemana', 100.0);
        $response->assertViewHas('totalPendente', 120.0);
        
        Carbon::setTestNow(); // Reset test time
    }

    public function test_can_list_despesas(): void
    {
        Despesa::create([
            'descricao' => 'Aluguel Comercial',
            'categoria' => 'Aluguel',
            'valor' => 1000.00,
            'data_vencimento' => '2026-05-10',
            'status' => 'pendente'
        ]);

        $response = $this->get(route('financeiro.despesas'));

        $response->assertStatus(200);
        $response->assertViewHas('despesas');
        $response->assertSee('Aluguel Comercial');
    }

    public function test_can_store_despesa(): void
    {
        $payload = [
            'descricao' => 'Internet Fibra',
            'categoria' => 'Tecnologia',
            'valor' => 150.00,
            'data_vencimento' => '2026-05-20',
            'observacao' => 'Link dedicado'
        ];

        $response = $this->post(route('financeiro.despesas.store'), $payload);

        $response->assertRedirect(route('financeiro.despesas'));
        $this->assertDatabaseHas('despesas', [
            'descricao' => 'Internet Fibra',
            'categoria' => 'Tecnologia',
            'valor' => 150.00,
            'status' => 'pendente'
        ]);
    }

    public function test_store_despesa_validation(): void
    {
        $payload = [
            'descricao' => '', // missing
            'categoria' => 'Tecnologia',
            'valor' => -10.00, // invalid negative value
            'data_vencimento' => 'not-a-date' // invalid date
        ];

        $response = $this->post(route('financeiro.despesas.store'), $payload);

        $response->assertSessionHasErrors(['descricao', 'valor', 'data_vencimento']);
    }

    public function test_can_pay_despesa(): void
    {
        $despesa = Despesa::create([
            'descricao' => 'Serviços Terceirizados',
            'categoria' => 'Serviços',
            'valor' => 200.00,
            'data_vencimento' => '2026-05-15',
            'status' => 'pendente'
        ]);

        $response = $this->patch(route('financeiro.despesas.pagar', $despesa), [
            'data_pagamento' => '2026-05-18'
        ]);

        $response->assertRedirect();
        $this->assertEquals('pago', $despesa->fresh()->status);
        $this->assertEquals('2026-05-18', $despesa->fresh()->data_pagamento->toDateString());
    }

    public function test_can_delete_despesa(): void
    {
        $despesa = Despesa::create([
            'descricao' => 'Taxa Bancária',
            'categoria' => 'Taxas',
            'valor' => 15.00,
            'data_vencimento' => '2026-05-01',
            'status' => 'pendente'
        ]);

        $response = $this->delete(route('financeiro.despesas.destroy', $despesa));

        $response->assertRedirect(route('financeiro.despesas'));
        $this->assertDatabaseMissing('despesas', [
            'id' => $despesa->id
        ]);
    }

    public function test_get_cash_for_period_calculation(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 20, 12, 0, 0));
        
        $startDate = Carbon::create(2026, 5, 18, 0, 0, 0); // Monday
        $endDate = Carbon::create(2026, 5, 24, 23, 59, 59); // Sunday

        $cliente = Cliente::create(['nome' => 'Cliente Teste', 'telefone' => '11900000000']);

        // 1. Paid adiantado total created in period (value_orcamento = 200.00, value_pago = 200.00)
        OrdemServico::create([
            'cliente_id' => $cliente->id,
            'descricao_item' => 'Celular 1',
            'defeito_relatado' => '',
            'valor_orcamento' => 200.00,
            'status' => 'RECEBIDO',
            'status_pagamento' => 'total',
            'valor_pago' => 200.00,
        ]);

        // 2. Paid adiantado parcial created in period (value_orcamento = 300.00, value_pago = 100.00)
        OrdemServico::create([
            'cliente_id' => $cliente->id,
            'descricao_item' => 'Celular 2',
            'defeito_relatado' => '',
            'valor_orcamento' => 300.00,
            'status' => 'RECEBIDO',
            'status_pagamento' => 'parcial',
            'valor_pago' => 100.00,
        ]);

        // 3. Paid adiantado total created out of period (created before)
        $os3 = new OrdemServico([
            'cliente_id' => $cliente->id,
            'descricao_item' => 'Celular 3',
            'defeito_relatado' => '',
            'valor_orcamento' => 500.00,
            'status' => 'RECEBIDO',
            'status_pagamento' => 'total',
            'valor_pago' => 500.00,
        ]);
        $os3->created_at = Carbon::create(2026, 5, 10, 10, 0, 0);
        $os3->updated_at = Carbon::create(2026, 5, 10, 10, 0, 0);
        $os3->save();

        // 4. Delivered inside period (status ENTREGUE, data_entregue inside period)
        // budget = 400.00, prepaid = 150.00. Remaining 250.00 collected on delivery.
        // Created outside the period, delivered inside.
        $os4 = new OrdemServico([
            'cliente_id' => $cliente->id,
            'descricao_item' => 'Celular 4',
            'defeito_relatado' => '',
            'valor_orcamento' => 400.00,
            'status' => 'ENTREGUE',
            'status_pagamento' => 'parcial',
            'valor_pago' => 150.00,
            'data_entregue' => Carbon::create(2026, 5, 21, 14, 0, 0),
        ]);
        $os4->created_at = Carbon::create(2026, 5, 5, 10, 0, 0);
        $os4->updated_at = Carbon::create(2026, 5, 21, 14, 0, 0);
        $os4->save();

        // 5. Total adiantado where valor_pago was 0 (should fall back to valor_orcamento)
        OrdemServico::create([
            'cliente_id' => $cliente->id,
            'descricao_item' => 'Celular 5',
            'defeito_relatado' => '',
            'valor_orcamento' => 80.00,
            'status' => 'RECEBIDO',
            'status_pagamento' => 'total',
            'valor_pago' => 0.00,
        ]);

        // Expected calculation:
        // OS 1: 200.00 (adiantado total)
        // OS 2: 100.00 (adiantado parcial)
        // OS 3: 0.00 (adiantado total outside period)
        // OS 4: 250.00 (remaining of delivered: 400 - 150)
        // OS 5: 80.00 (adiantado total using fallback)
        // Total = 200 + 100 + 250 + 80 = 630.00

        $cash = OrdemServico::getCashForPeriod($startDate, $endDate);
        
        $this->assertEquals(630.00, $cash);

        Carbon::setTestNow();
    }
}
