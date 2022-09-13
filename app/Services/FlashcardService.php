<?php

namespace App\Services;

use App\Dto\FlashcardDto;
use App\Dto\FlashcardStatsDto;
use App\Models\Flashcard;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FlashcardService
{
    private User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function create(FlashcardDto $flashcardDto): Flashcard
    {
        return Flashcard::create([
            'question' => $flashcardDto->question,
            'answer' => $flashcardDto->answer
        ]);
    }

    public function answer(Flashcard $flashcard, $answer): bool
    {
        $isCorrect = Str::upper(trim($flashcard->answer)) == Str::upper(trim($answer));
        $flashcard->practices()->syncWithoutDetaching([$this->user->id => ['is_correct' => $isCorrect]]);

        return $isCorrect;
    }

    public function resetProgress(): void
    {
        $this->user->practices()->detach();
    }

    public function getFlashcards(): Collection
    {
        return Flashcard::all();
    }

    public function getPracticeProgress(): Collection
    {
        return Flashcard::selectRaw('flashcards.id, flashcards.question, flashcards.answer, is_correct')
            ->leftJoin('practices', function ($join) {
                $join->on('flashcards.id', 'practices.flashcard_id')
                    ->on('practices.user_id', DB::raw($this->user->id));
            })
            ->get();
    }

    public function getStats(): FlashcardStatsDto
    {
        $statsDB = Flashcard::selectRaw("COUNT(flashcards.id) total, SUM(IF(is_correct IS NULL,0,1)) as total_answers, SUM(IF(is_correct = 1, 1, 0)) as total_correct")
            ->leftJoin('practices', function ($join) {
                $join->on('flashcards.id', 'practices.flashcard_id')
                    ->on('practices.user_id', DB::raw($this->user->id));
            })
            ->first();

        $stats = new FlashcardStatsDto();
        $stats->total = $statsDB->total;
        $stats->totalAnsweredPercent = $statsDB->total > 0 ? round(100 * $statsDB->total_answers / $statsDB->total) : 0;
        $stats->totalCorrectPercent = $statsDB->total > 0 ? round(100 * $statsDB->total_correct / $statsDB->total) : 0;

        return $stats;
    }
}
