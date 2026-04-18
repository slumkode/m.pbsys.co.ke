<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function show(Request $request)
    {
        $this->requireActionPermission($request, 'profile', 'view');

        return view('admin.modules.profile', $this->baseViewData($request));
    }

    public function update(Request $request)
    {
        $this->requireActionPermission($request, 'profile', 'update');
        $authUser = $request->user();
        $oldValues = [
            'name' => $authUser->name,
            'username' => $authUser->username,
            'email' => $authUser->email,
        ];

        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email,'.$authUser->id,
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $authUser->name = trim($validatedData['name']);
        $authUser->username = trim($validatedData['username']);
        $authUser->email = trim($validatedData['email']);

        if (! empty($validatedData['password'])) {
            $authUser->password = Hash::make($validatedData['password']);
        }

        $authUser->save();
        $this->recordAudit($request, 'profile_updated', $authUser, $oldValues, [
            'name' => $authUser->name,
            'username' => $authUser->username,
            'email' => $authUser->email,
            'password_changed' => ! empty($validatedData['password']),
        ], [
            'page_name' => 'Profile',
        ]);

        return redirect()
            ->route('profile.show')
            ->with('status', 'Profile updated successfully.');
    }

}
