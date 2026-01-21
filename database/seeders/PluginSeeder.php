<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PluginSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('plugins')->insert([
            [
                'name' => 'EssentialsX',
                'slug' => 'essentialsx',
                'version' => '2.20.1',
                'description' => 'Essential commands for servers',
            ],
            [
                'name' => 'LuckPerms',
                'slug' => 'luckperms',
                'version' => '5.4',
                'description' => 'Permissions plugin',
            ],
            [
                'name' => 'WorldEdit',
                'slug' => 'worldedit',
                'version' => '7.3.0',
                'description' => 'In-game world editor',
            ],
        ]);
    }
}
