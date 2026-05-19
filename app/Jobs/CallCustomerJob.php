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
    public function handle(\App\Services\VapiService $vapiService): void
    {
        $vapiService->makeCall($this->os);
    }
}
