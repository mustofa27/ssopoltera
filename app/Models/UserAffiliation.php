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

    /**
     * Serialize this affiliation for API responses, including only the
     * organizational unit key(s) that are actually set on this record.
     */
    public function toApiArray(): array
    {
        $unit = array_filter([
            'department' => optional($this->department)->name,
            'program_study' => optional($this->programStudy)->name,
            'support_unit' => optional($this->supportUnit)->name,
        ], fn ($value) => $value !== null);

        return array_merge([
            'affiliation_type' => $this->affiliation_type,
            'is_primary' => $this->is_primary,
        ], $unit);
    }
}
