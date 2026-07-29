<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_time',
        'end_time',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Check if current time falls within shift bounds
     */
    public function isWithinShift(): bool
    {
        $now = Carbon::now();
        $start = Carbon::createFromTimeString($this->start_time);
        $end = Carbon::createFromTimeString($this->end_time);

        // Overnight shift case (e.g. 22:00 to 06:00)
        if ($end->lessThanOrEqualTo($start)) {
            return $now->greaterThanOrEqualTo($start) || $now->lessThanOrEqualTo($end);
        }

        return $now->greaterThanOrEqualTo($start) && $now->lessThanOrEqualTo($end);
    }

    public function getFormattedShiftAttribute(): string
    {
        $start = Carbon::createFromTimeString($this->start_time)->format('h:i A');
        $end = Carbon::createFromTimeString($this->end_time)->format('h:i A');
        return "{$this->name} ({$start} - {$end})";
    }
}
