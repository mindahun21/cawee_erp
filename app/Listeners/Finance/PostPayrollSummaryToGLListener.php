<?php

namespace App\Listeners\Finance;

use App\Events\Finance\PayrollSummaryReadyForPosting;
use App\Services\Finance\PayrollGLPostingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Automatically posts a PayrollSummary to the GL when the
 * PayrollSummaryReadyForPosting event is fired.
 *
 * Queue: runs async to avoid blocking the request.
 */
class PostPayrollSummaryToGLListener implements ShouldQueue
{
    public string $queue = 'finance';

    public function __construct(private readonly PayrollGLPostingService $service) {}

    public function handle(PayrollSummaryReadyForPosting $event): void
    {
        try {
            $this->service->postToGL($event->summary);
            Log::info('Finance: PayrollSummary posted to GL', ['id' => $event->summary->id]);
        } catch (\Throwable $e) {
            Log::error('Finance: PayrollSummary GL posting failed', [
                'id'    => $event->summary->id,
                'error' => $e->getMessage(),
            ]);
            throw $e; // let the queue retry
        }
    }
}
