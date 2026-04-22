<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserActivity;
use Carbon\Carbon;

class CleanupUserActivities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'useractivities:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete user activity records older than the first day of the current month';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $cutoff = Carbon::now()->firstOfMonth()->startOfDay();

        $deleted = UserActivity::where('created_at', '<', $cutoff)->delete();

        $this->info("Deleted {$deleted} user activity records older than {$cutoff->toDateTimeString()}");

        return 0;
    }
}
