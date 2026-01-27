<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateEmailRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Models\User;

class SettingsController extends Controller
{
    public function edit() {
        $user = User::findOrFail(auth()->id());

        return view('web.settings.edit', compact('user'));
    }

    public function edit_password() {
        return view('web.settings.edit_password');
    }

    public function update_data(UpdateEmailRequest $request) {
        $user = User::findOrFail(auth()->id());

        $user->update($request->only('email'));

        $request->session()->flash('type', 'success');
        $request->session()->flash('message', trans('texts.Email successfully updated'));

        return back();
    }

    public function update_password(UpdatePasswordRequest $request) {
        $user = User::findOrFail(auth()->id());

        $user->password = bcrypt($request->password);
        $user->save();

        $request->session()->flash('type', 'success');
        $request->session()->flash('message', trans('texts.Password successfully updated'));

        return back();
    }
}
