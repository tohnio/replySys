<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use App\Models\OrdemServico;
use App\Models\HistoricoLigacao;
use App\Jobs\CallCustomerJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    #[OA\Post(
        path: "/webhook/n8n",
        summary: "Recebe atualizações de chamada do n8n",
        description: "Webhook chamado pelo fluxo do n8n para informar o resultado da ligação.",
        tags: ["Webhook"],
        responses: [
            new OA\Response(response: 200, description: "Processado com sucesso")
        ]
    )]
    public function handleN8nWebhook(Request $request)
    {
        $payload = $request->all();
        $externalCallId = $payload['external_call_id'] ?? null;
        $osId = $payload['ordem_servico_id'] ?? null;
        
        if (!$osId) {
            return response()->json(['status' => 'ignored', 'message' => 'OrdemServico ID missing']);
        }

        $os = OrdemServico::find($osId);
        if (!$os) {
            return response()->json(['status' => 'error', 'message' => 'OrdemServico not found'], 404);
        }

        $status_ligacao = $payload['status_ligacao'] ?? 'falhou';
        $duracao = $payload['duracao'] ?? 0;
        $transcricao_ia = $payload['transcricao_ia'] ?? null;

        $historico = null;

        if ($externalCallId) {
            $historico = $os->historicoLigacoes()->where('external_call_id', $externalCallId)->first();
        }

        if (!$historico) {
            // Tenta encontrar a última tentativa "pendente" para atualizar (fallback)
            $historico = $os->historicoLigacoes()->where('status_ligacao', 'pendente')->latest()->first();
        }
        
        if ($historico) {
            $historico->update([
                'status_ligacao' => $status_ligacao,
                'duracao' => $duracao,
                'transcricao_ia' => $transcricao_ia,
                'data_ligacao' => now()
            ]);
            // Garante que o external_call_id está salvo caso tenha caído no fallback
            if ($externalCallId && empty($historico->external_call_id)) {
                $historico->update(['external_call_id' => $externalCallId]);
            }
        } else {
            // Fallback caso não exista pendente (cria um novo)
            $historico = $os->historicoLigacoes()->create([
                'external_call_id' => $externalCallId,
                'status_ligacao' => $status_ligacao,
                'duracao' => $duracao,
                'transcricao_ia' => $transcricao_ia,
                'data_ligacao' => now()
            ]);
        }

        // Se não atendeu e a OS ainda está REPARADO, agenda retry (até 4 chamadas no total)
        if ($status_ligacao === 'caixa_postal' && $os->status === 'REPARADO') {
            $tentativas = $os->historicoLigacoes()->count();
            
            if ($tentativas < 4) {
                $proxima = $this->calcularProximoHorarioLigacao(now());
                
                $historico->update([
                    'proxima_tentativa' => $proxima
                ]);

                CallCustomerJob::dispatch($os)->delay($proxima);
                
                Log::info("Webhook n8n: Chamada não atendida (OS {$os->id}). Tentativa {$tentativas}/4 falhou. Reagendada para {$proxima->toDateTimeString()}");
            } else {
                Log::warning("Webhook n8n: Chamada não atendida (OS {$os->id}). Limite máximo de 4 tentativas atingido. Parando retentativas.");
            }
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Calcula o próximo horário válido para a ligação dentro do intervalo comercial das 09:00 às 18:00.
     */
    private function calcularProximoHorarioLigacao(Carbon $fromTime)
    {
        $proxima = $fromTime->copy()->addHour();

        if ($proxima->hour < 9) {
            $proxima->hour(9)->minute(0)->second(0);
        } elseif ($proxima->hour >= 18) {
            $proxima->addDay()->hour(9)->minute(0)->second(0);
        }

        return $proxima;
    }
}
