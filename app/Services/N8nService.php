<?php

namespace App\Services;

use App\Models\OrdemServico;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class N8nService
{
    /**
     * Aciona o webhook do n8n para iniciar o fluxo de comunicação com o cliente.
     */
    public function makeCall(OrdemServico $os): bool
    {
        // Só realiza chamadas se a OS ainda estiver no status REPARADO
        if ($os->status !== 'REPARADO') {
            Log::info("N8nService: OS {$os->id} não está mais no status REPARADO (Status atual: {$os->status}). Cancelando chamada.");
            return false;
        }

        $cliente = $os->cliente;
        if (!$cliente || empty($cliente->telefone)) {
            Log::info("N8nService: OS {$os->id} não possui telefone de cliente. Ignorando chamada.");
            return false;
        }

        $webhookUrl = config('services.n8n.webhook_url');

        // Formata o telefone para o padrão E.164 (+55...) para n8n/NVoIP
        $telefoneFormatado = $this->formatPhoneNumberToE164($cliente->telefone);

        // Gera um ID único para rastreamento da chamada
        $externalCallId = (string) Str::uuid();

        // Registra a tentativa no histórico (status: pendente)
        $historico = $os->historicoLigacoes()->create([
            'external_call_id' => $externalCallId,
            'status_ligacao' => 'pendente',
            'data_ligacao' => now(),
        ]);

        if (empty($webhookUrl)) {
            Log::warning("N8nService: N8N_WEBHOOK_URL não configurado no .env. Simulando chamada no ambiente de desenvolvimento.");
            return true;
        }

        try {
            $payload = [
                'external_call_id' => $externalCallId,
                'ordem_servico_id' => $os->id,
                'cliente' => [
                    'nome' => $cliente->nome,
                    'telefone' => $telefoneFormatado,
                ],
                'item_reparado' => $os->descricao_item ?? $os->modelo,
                'valor_orcamento' => (float) $os->valor_orcamento,
                'valor_pago' => (float) $os->valor_pago,
                'defeito_relatado' => $os->defeito_relatado,
                'callback_url' => url('/api/webhook/n8n'),
            ];

            Log::info("N8nService: Enviando requisição para n8n em {$webhookUrl} para notificar {$telefoneFormatado} (OS {$os->id})");

            $response = Http::withoutVerifying()->post($webhookUrl, $payload);

            if ($response->successful()) {
                Log::info("N8nService: Webhook do n8n disparado com sucesso para OS {$os->id}. External Call ID: {$externalCallId}");
                return true;
            } else {
                Log::error("N8nService: Erro ao disparar webhook do n8n para OS {$os->id}. Status: " . $response->status() . " | Resposta: " . $response->body());
                
                $historico->update([
                    'status_ligacao' => 'falhou'
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error("N8nService: Exceção ao chamar n8n para OS {$os->id}: " . $e->getMessage());
            $historico->update([
                'status_ligacao' => 'falhou'
            ]);
            return false;
        }
    }

    /**
     * Aciona o webhook do n8n para iniciar o fluxo de WhatsApp com o cliente.
     */
    public function sendWhatsApp(OrdemServico $os): bool
    {
        // Só realiza se a OS estiver no status REPARADO
        if ($os->status !== 'REPARADO') {
            Log::info("N8nService: OS {$os->id} não está no status REPARADO (Status atual: {$os->status}). Cancelando WhatsApp.");
            return false;
        }

        $cliente = $os->cliente;
        if (!$cliente || empty($cliente->telefone)) {
            Log::info("N8nService: OS {$os->id} não possui telefone de cliente. Ignorando WhatsApp.");
            return false;
        }

        $webhookUrl = config('services.n8n.whatsapp_webhook_url');

        // Formata o telefone para o padrão E.164 (+55...) para n8n/NVoIP
        $telefoneFormatado = $this->formatPhoneNumberToE164($cliente->telefone);

        // Gera um ID único para rastreamento da chamada/fluxo de conversa
        $externalCallId = (string) Str::uuid();

        // Registra a tentativa no histórico (status: pendente)
        $historico = $os->historicoLigacoes()->create([
            'external_call_id' => $externalCallId,
            'status_ligacao' => 'pendente',
            'data_ligacao' => now(),
        ]);

        if (empty($webhookUrl)) {
            Log::warning("N8nService: N8N_WHATSAPP_WEBHOOK_URL não configurado no .env. Simulando WhatsApp no desenvolvimento.");
            return true;
        }

        try {
            $payload = [
                'external_call_id' => $externalCallId,
                'ordem_servico_id' => $os->id,
                'cliente' => [
                    'nome' => $cliente->nome,
                    'telefone' => $telefoneFormatado,
                ],
                'item_reparado' => $os->descricao_item ?? $os->modelo,
                'valor_orcamento' => (float) $os->valor_orcamento,
                'valor_pago' => (float) $os->valor_pago,
                'defeito_relatado' => $os->defeito_relatado,
                'callback_url' => url('/api/webhook/n8n'),
            ];

            Log::info("N8nService: Enviando requisição para n8n em {$webhookUrl} para WhatsApp de {$telefoneFormatado} (OS {$os->id})");

            $response = Http::withoutVerifying()->post($webhookUrl, $payload);

            if ($response->successful()) {
                Log::info("N8nService: Webhook do n8n (WhatsApp) disparado com sucesso para OS {$os->id}. External Call ID: {$externalCallId}");
                return true;
            } else {
                Log::error("N8nService: Erro ao disparar webhook de WhatsApp do n8n para OS {$os->id}. Status: " . $response->status() . " | Resposta: " . $response->body());
                
                $historico->update([
                    'status_ligacao' => 'falhou'
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error("N8nService: Exceção ao chamar n8n (WhatsApp) para OS {$os->id}: " . $e->getMessage());
            $historico->update([
                'status_ligacao' => 'falhou'
            ]);
            return false;
        }
    }

    /**
     * Formata o telefone para o padrão E.164 do Brasil (+55...)
     */
    private function formatPhoneNumberToE164(string $phone): string
    {
        $cleaned = preg_replace('/\D/', '', $phone);
        
        if (strlen($cleaned) >= 12 && str_starts_with($cleaned, '55')) {
            return '+' . $cleaned;
        }
        
        if (strlen($cleaned) == 10 || strlen($cleaned) == 11) {
            return '+55' . $cleaned;
        }
        
        return '+' . (empty($cleaned) ? '00000000000' : $cleaned);
    }
}
