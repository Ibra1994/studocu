<?php

namespace Tests\seeders;

use App\Models\User;

class UserTestSeeder extends BaseSeeder
{
    public function run()
    {
        User::create([
            'name' => $this->faker->userName()
        ]);
    }
}
