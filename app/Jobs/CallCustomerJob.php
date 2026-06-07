<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CallCustomerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $os;

    /**
     * Create a new job instance.
     */
    public function __construct(\App\Models\OrdemServico $os)
    {
        $this->os = $os;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $cliente = $this->os->cliente;
        if (!$cliente || empty($cliente->telefone)) {
            \Illuminate\Support\Facades\Log::info("CallCustomerJob: OS {$this->os->id} não possui telefone de cliente. Cancelando chamada.");
            return;
        }

        // Verifica se já existe uma notificação concluída para esta OS
        $historico = $this->os->historicoLigacoes()->first();
        if ($historico && in_array($historico->status_ligacao, ['whatsapp', 'atendida'])) {
            \Illuminate\Support\Facades\Log::info("CallCustomerJob: OS {$this->os->id} já possui notificação concluída ({$historico->status_ligacao}).");
            return;
        }

        $telefone = $cliente->telefone;
        $nome = $cliente->nome ?? 'Cliente';
        $desc = $this->os->descricao_item ?? $this->os->modelo ?? 'aparelho';
        $valorRestante = (float) ($this->os->valor_orcamento - $this->os->valor_pago);
        $valor = number_format($valorRestante, 2, ',', '');

        // Gera um ID único para rastreamento da chamada se não existir histórico
        $externalCallId = $historico ? $historico->external_call_id : (string) \Illuminate\Support\Str::uuid();
        if (!$externalCallId) {
            $externalCallId = (string) \Illuminate\Support\Str::uuid();
        }

        // Registra a tentativa no histórico (status: pendente)
        if ($historico) {
            $isVoiceRetry = in_array($historico->status_ligacao, ['pendente', 'caixa_postal', 'falhou']);
            $novaTentativa = $isVoiceRetry ? ($historico->tentativas + 1) : 1;

            $historico->update([
                'external_call_id' => $externalCallId,
                'status_ligacao' => 'pendente',
                'data_ligacao' => now(),
                'duracao' => null,
                'transcricao_ia' => null,
                'proxima_tentativa' => null,
                'tentativas' => $novaTentativa,
            ]);
        } else {
            $historico = $this->os->historicoLigacoes()->create([
                'external_call_id' => $externalCallId,
                'status_ligacao' => 'pendente',
                'data_ligacao' => now(),
                'tentativas' => 1,
            ]);
        }

        // Executa o make_call.py
        $scriptPath = base_path('make_call.py');
        $command = [
            'python3',
            $scriptPath,
            '--to', $telefone,
            '--name', $nome,
            '--desc', $desc,
            '--value', $valor,
            '--external-id', $externalCallId
        ];

        \Illuminate\Support\Facades\Log::info("CallCustomerJob: Disparando make_call.py para OS {$this->os->id} | Telefone: {$telefone}");

        $process = new \Symfony\Component\Process\Process($command);
        $process->run();

        if (!$process->isSuccessful()) {
            \Illuminate\Support\Facades\Log::error("CallCustomerJob: Erro ao executar make_call.py: " . $process->getErrorOutput());
            $historico->update([
                'status_ligacao' => 'falhou'
            ]);
        } else {
            \Illuminate\Support\Facades\Log::info("CallCustomerJob: Chamada disparada com sucesso via make_call.py: " . $process->getOutput());
        }
    }
}
