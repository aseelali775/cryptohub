<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RepairNewsWithAI extends Command
{
    protected $signature = 'news:repair-ai';

    protected $description =
        'Repair previously processed news articles with missing AI editorial fields.';

    public function handle(): int
    {
        $this->info('🔧 Starting AI repair process...');

        $exitCode = $this->call('news:process-ai', [
            '--repair' => true,
        ]);

        if ($exitCode === self::SUCCESS) {
            $this->info('✅ AI repair cycle completed.');
        } else {
            $this->error('❌ AI repair cycle failed.');
        }

        return $exitCode;
    }
}