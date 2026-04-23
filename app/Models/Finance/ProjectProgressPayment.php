<?php

namespace App\Models\Finance;

use App\Models\Currency;
use App\Models\Donor;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectProgressPayment extends Model
{
    protected $table = 'finance_project_progress_payments';

    protected $fillable = [
        'project_id', 'donor_id', 'payment_date', 'amount', 'currency_id',
        'description', 'invoice_reference', 'bank_account_id',
        'cumulative_received', 'status', 'journal_entry_id',
    ];

    protected function casts(): array
    {
        return [
            'payment_date'       => 'date',
            'amount'             => 'decimal:2',
            'cumulative_received'=> 'decimal:2',
        ];
    }

    public static function statuses(): array
    {
        return ['received' => 'Received', 'partially_spent' => 'Partially Spent', 'fully_utilized' => 'Fully Utilized'];
    }

    public function project(): BelongsTo      { return $this->belongsTo(Project::class); }
    public function donor(): BelongsTo        { return $this->belongsTo(Donor::class); }
    public function bankAccount(): BelongsTo  { return $this->belongsTo(BankAccount::class); }
    public function journalEntry(): BelongsTo { return $this->belongsTo(JournalEntry::class); }
}
