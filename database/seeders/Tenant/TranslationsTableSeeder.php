<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class TranslationsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Step 1: Load existing translations from the database
        $existing = DB::table('translations')
            ->select('locale', 'group', 'key')
            ->get();

        $existingMap = [];

        foreach ($existing as $item) {
            $existingMap[$item->locale . '|' . ($item->group ?? 'db') . '|' . $item->key] = true;
        }

        // Step 2: Load all locale files
        $directory = database_path('seeders/translations');
        $files = glob($directory . '/*.php');

        $insertData = [];

        foreach ($files as $file) {
            $data = include $file;

            foreach ($data as $row) {
                $group = $row['group'] ?? 'db';
                $lookupKey = $row['locale'] . '|' . $group . '|' . $row['key'];

                if (!isset($existingMap[$lookupKey])) {
                    $insertData[] = [
                        'locale' => $row['locale'],
                        'group' => $group,
                        'key' => $row['key'],
                        'value' => $row['value'],
                        'created_at' => null,
                        'updated_at' => null,
                    ];
                }
            }
        }

        // Step 3: Insert new records in chunks
        if (!empty($insertData)) {
            $this->command->info('Found ' . count($insertData) . ' new translations to insert.');
            $chunks = collect($insertData)->chunk(1000);
            foreach ($chunks as $chunk) {
                DB::table('translations')->insert($chunk->toArray());
            }
            $this->command->info('Successfully inserted ' . count($insertData) . ' translations.');
            
            // Clear translation cache after inserting new translations
            \App\Models\Translation::forgetCachedTranslations();
        } else {
            $this->command->info('No new translations found. All translations already exist in database.');
        }

    }
}
