<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $translations = [
            ['locale' => 'en', 'group' => 'db', 'key' => 'Website Settings', 'value' => 'Website Settings'],
            ['locale' => 'en', 'group' => 'db', 'key' => 'These settings affect the public website only, not the admin panel.', 'value' => 'These settings affect the public website only, not the admin panel.'],
            ['locale' => 'en', 'group' => 'db', 'key' => 'Website Default Language', 'value' => 'Website Default Language'],
            ['locale' => 'en', 'group' => 'db', 'key' => 'Website RTL Languages', 'value' => 'Website RTL Languages'],
            ['locale' => 'en', 'group' => 'db', 'key' => 'Select languages that use right-to-left layout (e.g. Arabic, Urdu).', 'value' => 'Select languages that use right-to-left layout (e.g. Arabic, Urdu).'],
        ];

        foreach ($translations as $t) {
            if (!DB::table('translations')->where('locale', $t['locale'])->where('group', $t['group'])->where('key', $t['key'])->exists()) {
                DB::table('translations')->insert(array_merge($t, ['created_at' => now(), 'updated_at' => now()]));
            }
        }
    }

    public function down(): void
    {
        $keys = [
            'Website Settings',
            'These settings affect the public website only, not the admin panel.',
            'Website Default Language',
            'Website RTL Languages',
            'Select languages that use right-to-left layout (e.g. Arabic, Urdu).',
        ];
        DB::table('translations')->where('group', 'db')->whereIn('key', $keys)->delete();
    }
};
