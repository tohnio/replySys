<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class WhatsAppNotificationJob implements ShouldQueue
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
    public function handle(\App\Services\N8nService $n8nService): void
    {
        $success = $n8nService->sendWhatsApp($this->os);
        if (!$success) {
            \Illuminate\Support\Facades\Log::info("WhatsAppNotificationJob: Falha ao enviar WhatsApp para OS {$this->os->id}. Iniciando fallback com ligação telefônica.");
            \App\Jobs\CallCustomerJob::dispatch($this->os);
        }
    }
}
