<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use App\Models\UserContact;
use App\Models\UserWorkHistory;
use App\Models\UserSkill;
use App\Models\UserLanguage;
use App\Models\UserCertification;
use App\Models\UserWorkTotal;

class User extends \TCG\Voyager\Models\User
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'user_create',
        // 'total_workcount',
        // 'total_worktime',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'profile_photo_url',
    ];
    
    public function contact()
    {
        return $this->hasOne(UserContact::class);
    }

    public function workHistories()
    {
        return $this->hasMany(UserWorkHistory::class);
    }

    public function skills()
    {
        return $this->hasMany(UserSkill::class);
    }

    public function educations()
    {
        return $this->hasMany(UserEducation::class);
    }

    public function languages()
    {
        return $this->hasMany(UserLanguage::class);
    }

    public function certifications()
    {
        return $this->hasMany(UserCertification::class);
    }

    public function work()
    {
        return $this->hasOne(UserWorkTotal::class);
    }
}
