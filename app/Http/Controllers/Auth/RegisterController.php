<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegistrationRequest;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Testing\Fluent\Concerns\Has;

class RegisterController extends Controller
{

    use RegistersUsers;

    protected $redirectTo = RouteServiceProvider::HOME;

    public function __construct() {
        $this->middleware(['guest'])->except('logout');
    }

    protected function validator(array $data) {
        return Validator::make($data, [
            'email' => ['required', 'string', 'email', 'max:150', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'hash' => ['required', 'string', 'exists:users,hash']
        ]);
    }

    protected function create(array $data)
    {
        $user = User::where('hash', $data['hash'])->first();
        $user->password = Hash::make($data['password']);
        $user->email = $data['email'];
        $user->save();
        return $user;
    }

    public function showRegistrationForm() {
        return view('auth.register');
    }





}
