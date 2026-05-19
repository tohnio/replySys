<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use OpenApi\Attributes as OA;

class WebhookController extends Controller
{
    #[OA\Post(
        path: "/webhook/vapi",
        summary: "Recebe atualizações de chamada da Vapi",
        description: "Webhook chamado pela IA da Vapi para informar o resultado da ligação.",
        tags: ["Webhook"],
        responses: [
            new OA\Response(response: 200, description: "Processado com sucesso")
        ]
    )]
    public function handleVapiWebhook(\Illuminate\Http\Request $request)
    {
        // A Vapi envia dados sobre a chamada.
        $payload = $request->all();
        $callData = $payload['message']['call'] ?? null;
        
        if (!$callData) {
            return response()->json(['status' => 'ignored']);
        }

        // Mock: pegamos o ID da OS pelo número ou variável enviada pela Vapi
        // Aqui simulo que o webhook enviou 'ordem_servico_id' em 'variables'
        $osId = $payload['message']['call']['assistantOverrides']['variableValues']['ordem_servico_id'] ?? null;
        
        if ($osId) {
            $os = \App\Models\OrdemServico::find($osId);
            if ($os) {
                $status_ligacao = $callData['status'] === 'completed' ? 'atendida' : 'caixa_postal';
                
                $os->historicoLigacoes()->create([
                    'status_ligacao' => $status_ligacao,
                    'duracao' => $callData['duration'] ?? 0,
                    'transcricao_ia' => $payload['message']['transcript'] ?? null,
                    'data_ligacao' => now()
                ]);

                if ($status_ligacao === 'caixa_postal') {
                    // Fallback para dia seguinte 10hs ou 19hs alternando.
                    $hora = rand(0, 1) ? '10:00:00' : '19:00:00';
                    $proxima = \Carbon\Carbon::tomorrow()->format('Y-m-d') . ' ' . $hora;
                    
                    \App\Jobs\CallCustomerJob::dispatch($os)->delay(\Carbon\Carbon::parse($proxima));
                }
            }
        }

        return response()->json(['status' => 'success']);
    }
}
