<?php

// app/Models/LeaveApplication.php

namespace App\Models;

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
        'approved_by',
        'approved_at',
        'rejection_reason',
        'supporting_document_path',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'leave_days' => 'float',
    ];

    // Set default values for attributes
    protected $attributes = [
        'leave_type' => 'annual',
        'status' => 'pending',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
