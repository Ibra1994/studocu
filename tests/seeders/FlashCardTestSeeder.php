<?php

namespace Tests\seeders;

use App\Models\Flashcard;

class FlashCardTestSeeder extends BaseSeeder
{
    public function run()
    {
        Flashcard::create([
            'question' => 'What is the smallest country in the world?',
            'answer' => 'Vatican'
        ]);
    }
}
