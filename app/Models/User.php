<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // --- ADD THIS METHOD ---
    /**
     * Get the leave applications for the user.
     */
    public function leaveApplications()
    {
        return $this->hasMany(LeaveApplication::class);
    }
    public function timeLogs()
    {
        return $this->hasMany(TimeLog::class);
    }
    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_user', 'user_id', 'team_id');
    }
    public function unreadNotifications()
    {
        return $this->notifications()->whereNull('read_at');
    }

    public function markNotificationAsRead($notificationId)
    {
        return $this->notifications()->where('id', $notificationId)->update(['read_at' => now()]);
    }

    public function markAllNotificationsAsRead()
    {
        return $this->unreadNotifications()->update(['read_at' => now()]);
    }
}