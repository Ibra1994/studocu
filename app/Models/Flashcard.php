<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Flashcard extends Model
{
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'question',
        'answer'
    ];

    public function practices()
    {
        return $this->belongsToMany(
            User::class,
            'practices',
            'flashcard_id',
            'user_id',
            'id',
            'id'
        )->withPivot(['is_correct']);
    }

    public static function getRules(): array
    {
        return [
            'question' => 'required|string|min:1|max:255',
            'answer' => 'required|string|min:1|max:255',
        ];
    }
}
