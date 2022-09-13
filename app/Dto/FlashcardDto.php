<?php

namespace App\Dto;

class FlashcardDto
{
    public $question;
    public $answer;

    public static function fromParameters(string $question, string $answer): FlashcardDto
    {
        $self = new self();
        $self->question = $question;
        $self->answer = $answer;

        return $self;
    }
}
