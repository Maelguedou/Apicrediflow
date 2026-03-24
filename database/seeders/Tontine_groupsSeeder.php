<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\tontine_group;

class Tontine_groupsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        tontine_group::create([
            'name'=>'Groupe_test',
            'contribution_amount'=>10000,
            'frequency'=>'daily',
            'max_members'=>5
        ]);
    }



}
