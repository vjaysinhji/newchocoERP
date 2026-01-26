<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Translation;

class FixTranslations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translations:fix';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix translations by ensuring all have group column set to db and clear cache';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Fixing translations...');

        // Update translations where group is NULL or empty to 'db'
        $updated = DB::table('translations')
            ->whereNull('group')
            ->orWhere('group', '')
            ->update(['group' => 'db']);

        $this->info("Updated {$updated} translations with missing group column.");

        // Clear all translation caches
        Translation::forgetCachedTranslations();
        $this->info('Cleared all translation caches.');

        // Also clear Laravel cache
        $this->call('cache:clear');
        $this->call('config:clear');
        $this->call('view:clear');

        $this->info('All caches cleared successfully!');
        $this->info('Translations should now work correctly.');

        return 0;
    }
}
