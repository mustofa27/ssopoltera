<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAffiliation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'department_id',
        'program_study_id',
        'support_unit_id',
        'affiliation_type',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function programStudy(): BelongsTo
    {
        return $this->belongsTo(ProgramStudy::class);
    }

    public function supportUnit(): BelongsTo
    {
        return $this->belongsTo(SupportUnit::class);
    }
}
