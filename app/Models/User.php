<?php

namespace App\Models;

use App\Notifications\DocumentsRequested;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'username',
        'name',
        'email',
        'country',
        'driver_id',
    ];

    protected $dates = ['deleted_at'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'remember_token',
        'password'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        //
    ];

    public function transports(){
        return $this->hasMany(Transport::class);
    }

    public function generateLink($password){
        //$link = route('login', [ 'email' => $this->email, 'password' => $password ]);
        $this->password = bcrypt($password);

        $token = null;
        do {
            $token = Str::random(32);
        } while (User::where('token', $token)->exists());
        $this->token = $token;
        
        $this->save();

        $link = route('transports.enter', [ 'token' => $token ]);

        return $link;
    }

}
