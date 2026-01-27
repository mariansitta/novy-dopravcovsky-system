<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Seeder;

class StatusesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if ( !Status::where('slug', 'uploaded')->count() > 0) {
            Status::create([
                'name_sk' => 'Nahraté',
                'name_en' => 'Uploaded',
                'slug' => 'uploaded',
            ]);
        }

        if ( !Status::where('slug', 'processed')->count() > 0) {
            Status::create([
                'name_sk' => 'Spracované',
                'name_en' => 'Processed',
                'slug' => 'processed',
            ]);
        }

        if ( !Status::where('slug', 'paid')->count() > 0) {
            Status::create([
                'name_sk' => 'Uhradené',
                'name_en' => 'Paid',
                'slug' => 'paid',
            ]);
        }
    }
}
