<?php

namespace Tests\seeders;

use Faker\Factory;
use Illuminate\Database\Seeder;

abstract class BaseSeeder extends Seeder
{
    /**
     * @var Factory
     */
    protected $faker;

    /**
     * BaseSeeder constructor.
     *
     * @param Factory $faker
     */
    public function __construct(Factory $faker)
    {
        $this->faker = $faker::create('en_US');
    }
}
