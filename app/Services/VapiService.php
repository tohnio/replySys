<?php

namespace App\Services;

use App\Models\OrdemServico;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VapiService
{
    /**
     * Aciona a API da Vapi para ligar para o cliente.
     */
    public function makeCall(OrdemServico $os)
    {
        $cliente = $os->cliente;
        if (!$cliente || empty($cliente->telefone)) {
            Log::info("VapiService: OS {$os->id} não possui telefone de cliente. Ignorando chamada e histórico.");
            return false;
        }

        Log::info("Mock: VapiService iniciou chamada para o cliente {$cliente->nome} no numero {$cliente->telefone} sobre a OS {$os->id}");
        
        // Quando for para produção:
        /*
        $response = Http::withToken(env('VAPI_API_KEY'))
            ->post('https://api.vapi.ai/call', [
                'assistantId' => env('VAPI_ASSISTANT_ID'),
                'customer' => [
                    'number' => $cliente->telefone,
                ],
                'assistantOverrides' => [
                    'variableValues' => [
                        'nome_cliente' => $cliente->nome,
                        'item_reparado' => $os->descricao_item
                    ]
                ]
            ]);
        */
        
        // Simular a criação do histórico
        $os->historicoLigacoes()->create([
            'status_ligacao' => 'pendente',
            'data_ligacao' => now(),
        ]);

        return true;
    }
}
