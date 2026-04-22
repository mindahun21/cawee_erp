<?php

namespace App\Events\Finance;

use App\Models\Finance\PayrollSummary;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PayrollSummaryReadyForPosting
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly PayrollSummary $summary) {}
}
