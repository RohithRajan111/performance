<?php
// app/Models/Task.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

  protected $fillable = [
        'name',
        'description',
        'project_id',
        'assigned_to_id',
        'status', // <-- CRUCIAL: Add 'status' to this array
        'due_date',
    ];
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }
}