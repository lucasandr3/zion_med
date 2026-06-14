<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PlatformSettingsSeeder::class,
            OrganizationSeeder::class,
            FormTemplateSeeder::class,
            ReleaseNotesSeeder::class,
        ]);
    }
}
