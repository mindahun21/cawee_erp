<?php

namespace App\Console\Commands;

use App\Services\DonationService;
use Illuminate\Console\Command;

class ProcessRecurringDonations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'donations:process-recurring';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process recurring donations that are due';

    /**
     * Execute the console command.
     */
    public function handle(DonationService $service)
    {
        $this->info('Starting recurring donation processing...');

        $result = $service->processRecurringDonations();

        $this->info("Processed: {$result['processed_count']}");
        $this->info("Errors: {$result['error_count']}");

        if ($result['error_count'] > 0) {
            $this->warn("Encountered {$result['error_count']} errors:");
            foreach ($result['errors'] as $error) {
                $this->error("Donation ID {$error['donation_id']}: {$error['error']}");
            }
        }

        $this->info('Recurring donation processing complete.');

        return Command::SUCCESS;
    }
}
