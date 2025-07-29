<?php

// app/Models/LeaveApplication.php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'start_date',
        'end_date',
        'reason',
        'leave_type',
        'status',
        'day_type',
        'start_half_session',
        'end_half_session',
        'leave_days',
        'salary_deduction_days',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'leave_days' => 'float',
        'salary_deduction_days' => 'float',
    ];

    // Set default values for attributes
    protected $attributes = [
        'leave_type' => 'annual',
        'status' => 'pending',
    ];

    // RELATIONSHIPS
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // QUERY SCOPES
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', 'rejected');
    }

    public function scopeForUser(Builder $query, $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeInDateRange(Builder $query, $startDate, $endDate): Builder
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate])
              ->orWhere(function ($q2) use ($startDate, $endDate) {
                  $q2->where('start_date', '<=', $startDate)
                     ->where('end_date', '>=', $endDate);
              });
        });
    }

    public function scopeOverlapsWith(Builder $query, $startDate, $endDate): Builder
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->where('start_date', '<=', $endDate)
              ->where('end_date', '>=', $startDate);
        });
    }

    public function scopeCurrentYear(Builder $query): Builder
    {
        $currentYear = Carbon::now()->year;
        return $query->whereYear('start_date', $currentYear);
    }

    public function scopeByLeaveType(Builder $query, string $leaveType): Builder
    {
        return $query->where('leave_type', $leaveType);
    }

    public function scopeOrderByStatusPriority(Builder $query): Builder
    {
        return $query->orderByRaw("CASE status
            WHEN 'pending' THEN 1
            WHEN 'approved' THEN 2
            WHEN 'rejected' THEN 3
            ELSE 4
        END");
    }

    // HELPER METHODS
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isOverlappingWith($startDate, $endDate): bool
    {
        return $this->start_date <= $endDate && $this->end_date >= $startDate;
    }

    public function getDurationInDays(): float
    {
        return $this->leave_days ?? 0;
    }

    public function getLeaveTypeDisplayName(): string
    {
        return ucfirst($this->leave_type) . ' Leave';
    }
}