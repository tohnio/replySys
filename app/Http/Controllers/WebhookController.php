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
        description: "Webhook chamado pelo fluxo do n8n para informar o resultado ou logs de diálogo da ligação.",
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
        
        $historico = null;
        $os = null;

        if ($externalCallId) {
            $historico = \App\Models\HistoricoLigacao::where('external_call_id', $externalCallId)->first();
            if ($historico) {
                $os = $historico->ordemServico;
            }
        }

        if (!$os && $osId) {
            $os = OrdemServico::find($osId);
        }

        if (!$os) {
            return response()->json(['status' => 'error', 'message' => 'OrdemServico not found or OrdemServico ID missing'], 404);
        }

        $status_ligacao = $payload['status_ligacao'] ?? null;
        $duracao = $payload['duracao'] ?? null;
        $transcricao_ia = $payload['transcricao_ia'] ?? null;
        $append = $payload['append'] ?? false;

        if (!$historico && $os) {
            if ($externalCallId) {
                $historico = $os->historicoLigacoes()->where('external_call_id', $externalCallId)->first();
            }
            if (!$historico) {
                // Tenta encontrar a última tentativa "pendente" para atualizar (fallback)
                $historico = $os->historicoLigacoes()->where('status_ligacao', 'pendente')->latest()->first();
            }
        }
        
        if ($historico) {
            $updateData = [
                'data_ligacao' => now()
            ];

            if ($status_ligacao !== null) {
                $updateData['status_ligacao'] = $status_ligacao;
            }
            if ($duracao !== null) {
                $updateData['duracao'] = $duracao;
            }
            if ($transcricao_ia !== null) {
                if ($append) {
                    $existing = $historico->transcricao_ia;
                    $updateData['transcricao_ia'] = $existing ? ($existing . "\n" . $transcricao_ia) : $transcricao_ia;
                } else {
                    $updateData['transcricao_ia'] = $transcricao_ia;
                }
            }
            
            // Garante que o external_call_id está salvo caso tenha caído no fallback
            if ($externalCallId) {
                $updateData['external_call_id'] = $externalCallId;
            }
            
            $historico->update($updateData);
        } else {
            // Fallback caso não exista pendente (cria um novo)
            $historico = $os->historicoLigacoes()->create([
                'external_call_id' => $externalCallId,
                'status_ligacao' => $status_ligacao ?? 'pendente',
                'duracao' => $duracao ?? 0,
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

    #[OA\Get(
        path: "/webhook/n8n/call-details/{externalCallId}",
        summary: "Busca detalhes do cliente e da OS para alimentar o n8n em tempo real",
        description: "Retorna o nome do cliente, item consertado e valor restante de orçamento pelo UUID do canal de chamada.",
        tags: ["Webhook"],
        parameters: [
            new OA\Parameter(
                name: "externalCallId",
                in: "path",
                required: true,
                description: "UUID de controle da chamada gerado no Laravel",
                schema: new OA\Schema(type: "string")
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Dados retornados com sucesso"),
            new OA\Response(response: 404, description: "Chamada ou OS não localizada")
        ]
    )]
    public function getCallDetails($externalCallId)
    {
        $historico = \App\Models\HistoricoLigacao::where('external_call_id', $externalCallId)->first();
        
        if (!$historico) {
            return response()->json(['status' => 'error', 'message' => 'Histórico de ligação não encontrado para o ID fornecido.'], 404);
        }

        $os = $historico->ordemServico;
        if (!$os) {
            return response()->json(['status' => 'error', 'message' => 'Ordem de serviço associada não encontrada.'], 404);
        }

        $cliente = $os->cliente;
        $valorRestante = (float) ($os->valor_orcamento - $os->valor_pago);

        return response()->json([
            'cliente_nome' => $cliente->nome ?? 'Cliente',
            'item_reparado' => $os->descricao_item ?? $os->modelo,
            'valor_restante' => number_format($valorRestante, 2, '.', ''),
        ]);
    }

    #[OA\Get(
        path: "/webhook/n8n/client-details/{phone}",
        summary: "Busca detalhes do cliente e da OS pelo número de telefone",
        description: "Retorna o nome do cliente, item consertado e valor restante de orçamento pelo número de telefone do cliente.",
        tags: ["Webhook"],
        parameters: [
            new OA\Parameter(
                name: "phone",
                in: "path",
                required: true,
                description: "Telefone do cliente",
                schema: new OA\Schema(type: "string")
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Dados retornados com sucesso"),
            new OA\Response(response: 404, description: "Cliente ou OS não localizado")
        ]
    )]
    public function getClientDetails($phone)
    {
        $cleanedPhone = preg_replace('/\D/', '', $phone);
        
        if (str_starts_with($cleanedPhone, '55')) {
            $cleanedPhone = substr($cleanedPhone, 2);
        }
        
        $cliente = null;
        if (!empty($cleanedPhone)) {
            $alternatives = [$cleanedPhone];
            if (strlen($cleanedPhone) === 10) {
                $ddd = substr($cleanedPhone, 0, 2);
                $numero = substr($cleanedPhone, 2);
                $alternatives[] = $ddd . '9' . $numero;
            } elseif (strlen($cleanedPhone) === 11) {
                $ddd = substr($cleanedPhone, 0, 2);
                $numero = substr($cleanedPhone, 2);
                if (str_starts_with($numero, '9')) {
                    $alternatives[] = $ddd . substr($numero, 1);
                }
            }
            
            $query = \App\Models\Cliente::query();
            $query->where(function($q) use ($alternatives) {
                foreach ($alternatives as $alt) {
                    $q->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(telefone, '(', ''), ')', ''), '-', ''), ' ', '') like ?", ["%{$alt}%"]);
                }
            });
            $cliente = $query->first();
        }
        
        if (!$cliente) {
            return response()->json(['status' => 'error', 'message' => 'Cliente não encontrado para o telefone fornecido.'], 404);
        }
        
        $os = $cliente->ordensServico()->whereIn('status', ['RECEBIDO', 'EM_REPARO', 'AGUARDANDO_PECA', 'REPARADO'])->latest()->first();
        
        if (!$os) {
            $os = $cliente->ordensServico()->latest()->first();
        }
        
        if (!$os) {
            return response()->json(['status' => 'error', 'message' => 'Nenhuma ordem de serviço encontrada.'], 404);
        }
        
        $valorRestante = (float) ($os->valor_orcamento - $os->valor_pago);
        
        return response()->json([
            'cliente_nome' => $cliente->nome ?? 'Cliente',
            'item_reparado' => $os->descricao_item ?? $os->modelo,
            'valor_restante' => number_format($valorRestante, 2, '.', ''),
        ]);
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
