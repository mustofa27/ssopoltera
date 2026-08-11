<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DirectionBoard extends Model
{
    use HasFactory;

    protected $table = 'direction_board';

    protected $fillable = [
        'director_user_id',
        'vice_director_1_user_id',
        'vice_director_2_user_id',
        'vice_director_3_user_id',
    ];

    public function director(): BelongsTo
    {
        return $this->belongsTo(User::class, 'director_user_id');
    }

    public function viceDirector1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vice_director_1_user_id');
    }

    public function viceDirector2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vice_director_2_user_id');
    }

    public function viceDirector3(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vice_director_3_user_id');
    }
}
