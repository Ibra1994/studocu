<?php

namespace Tests;

use App\Dto\FlashcardDto;
use App\Dto\FlashcardStatsDto;
use App\Models\Flashcard;
use App\Models\User;
use App\Services\FlashcardService;

class FlashcardServiceTest extends TestCase
{
    private $flashcardService;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::first();
        $this->assertNotNull($user);

        $this->flashcardService = new FlashcardService($user);
    }

    /**
     * @return void
     */
    public function test_GetFlashcards()
    {
        $flashcards = $this->flashcardService->getFlashcards();
        $this->assertDatabaseCount(Flashcard::class, 1);
        $this->assertCount(1, $flashcards);
        $flashcard = $flashcards[0];

        $this->assertEquals('What is the smallest country in the world?', $flashcard->question);
        $this->assertEquals('Vatican', $flashcard->answer);
    }

    /**
     * @return void
     */
    public function test_CreateFlashcards()
    {
        $flashcards = $this->flashcardService->getFlashcards();
        $this->assertCount(1, $flashcards);

        $dto = FlashcardDto::fromParameters('How old are you?', 28);

        $flashcard = $this->flashcardService->create($dto);

        $flashcards = $this->flashcardService->getFlashcards();
        $this->assertCount(2, $flashcards);
        $this->assertDatabaseCount(Flashcard::class, 2);

        $this->assertEquals('How old are you?', $flashcard->question);
        $this->assertEquals(28, $flashcard->answer);
    }

    /**
     * @return void
     */
    public function test_AnswerFlashcardCorrect()
    {
        $flashcardProgress = $this->flashcardService->getPracticeProgress();
        $this->assertCount(1, $flashcardProgress);
        $flashcard = $flashcardProgress[0];
        $this->assertEquals('What is the smallest country in the world?', $flashcard->question);
        $this->assertEquals('Vatican', $flashcard->answer);
        $this->assertNull($flashcard->is_correct);

        $isCorrect = $this->flashcardService->answer($flashcard, 'vatican');
        $this->assertTrue($isCorrect);

        $flashcardProgress = $this->flashcardService->getPracticeProgress();
        $this->assertCount(1, $flashcardProgress);
        $flashcard = $flashcardProgress[0];
        $this->assertEquals('What is the smallest country in the world?', $flashcard->question);
        $this->assertEquals('Vatican', $flashcard->answer);
        $this->assertEquals(1, $flashcard->is_correct);
    }

    /**
     * @return void
     */
    public function test_AnswerFlashcardIncorrect()
    {
        $flashcardProgress = $this->flashcardService->getPracticeProgress();
        $this->assertCount(1, $flashcardProgress);
        $flashcard = $flashcardProgress[0];
        $this->assertEquals('What is the smallest country in the world?', $flashcard->question);
        $this->assertEquals('Vatican', $flashcard->answer);
        $this->assertNull($flashcard->is_correct);

        $isCorrect = $this->flashcardService->answer($flashcard, 'Khujand');
        $this->assertFalse($isCorrect);

        $flashcardProgress = $this->flashcardService->getPracticeProgress();
        $this->assertCount(1, $flashcardProgress);
        $flashcard = $flashcardProgress[0];
        $this->assertEquals('What is the smallest country in the world?', $flashcard->question);
        $this->assertEquals('Vatican', $flashcard->answer);
        $this->assertEquals(0, $flashcard->is_correct);
    }

    /**
     * @return void
     */
    public function test_GetFlashcardStats()
    {
        $dto = FlashcardDto::fromParameters('How old are you?', 28);
        $this->flashcardService->create($dto);
        $flashcardProgress = $this->flashcardService->getPracticeProgress();
        $this->assertCount(2, $flashcardProgress);
        foreach ($flashcardProgress as $progress) {
            $this->assertNull($progress->is_correct);
        }

        $statsBefore = $this->flashcardService->getStats();
        $this->assertInstanceOf(FlashcardStatsDto::class, $statsBefore);
        $this->assertEquals(2, $statsBefore->total);
        $this->assertEquals(0, $statsBefore->totalAnsweredPercent);
        $this->assertEquals(0, $statsBefore->totalCorrectPercent);

        $this->assertTrue($this->flashcardService->answer($flashcardProgress[1], 28));

        $statsAfter = $this->flashcardService->getStats();
        $this->assertInstanceOf(FlashcardStatsDto::class, $statsAfter);
        $this->assertEquals(2, $statsAfter->total);
        $this->assertEquals(50, $statsAfter->totalAnsweredPercent);
        $this->assertEquals(50, $statsAfter->totalCorrectPercent);
    }
}
