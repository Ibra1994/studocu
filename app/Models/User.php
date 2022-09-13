<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
    ];

    public function practices()
    {
        return $this->belongsToMany(
            Flashcard::class,
            'practices',
            'user_id',
            'flashcard_id',
            'id',
            'id'
        )->withPivot(['is_correct']);
    }

    public static function getRules(): array
    {
        return [
            'name' => 'required|string|unique|min:3|max:255',
        ];
    }
}
