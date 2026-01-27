<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(){
        if(env('APP_ENV') != 'production'){
            if (!User::where('username', 'admin')->count() > 0){
                DB::table('users')->insert([
                    'username' => 'admin',
                    'name' => 'admin',
                    'email' => 'admin',
                    'password' => bcrypt('admin'),
                    'created_at' => now()->format('Y-m-d H:i:s'),
                    'updated_at' => now()->format('Y-m-d H:i:s'),
                ]);
            }
        }
    }
}
