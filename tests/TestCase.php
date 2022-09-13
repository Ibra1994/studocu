<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\seeders\FlashCardTestSeeder;
use Tests\seeders\UserTestSeeder;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
        $this->seed(UserTestSeeder::class);
        $this->seed(FlashCardTestSeeder::class);
    }
}
