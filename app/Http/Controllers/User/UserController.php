<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::all();

        return response()->json(['data' => $users], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
        ];

        $this->validate($request, $rules);

        $data = $request->all();
        $data['password'] = bcrypt($request->password);
        $data['verified'] = User::UNVERIFIED_USER;
        $data['verification_token'] = User::generateVerificationCode();
        $data['admin'] = User::REGULAR_USER;

        $user = User::create($data);

        return response()->json(['data' => $user], 201); // 201 => data created successfuly.
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return response()->json(['data' => $user], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $rules = [
            'email' => 'email|unique:users,email,' . $user->id, // to except this user email to not be unique, in case if he dont want to change his email. 
            'password' => 'min:6|confirmed',
            'admin' => 'in:' . User::ADMIN_USER . ',' . User::REGULAR_USER,
        ];

        $this->validate($request, $rules);

        if ($request->has('name')) {
            $user->name = $request->name;
        }

        if ($request->has('email') && $user->email != $request->email) { // if the user want to change his email, he will have to verifiy the new email
            $user->verified = User::UNVERIFIED_USER; // mark the user as UNVERIFIED_USER.
            $user->verification_token = User::generateVerificationCode(); // generate a new verification code.
            $user->email = $request->email;
        }

        if ($request->has('password')) {
            $user->password = bcrypt($request->password);
        }

        if ($request->has('admin')) {
            if (!$user->isVerified()) {
                return response()->json([
                'error' => 'Only verified users can modify the admin field',
                'code' => 409
                ], 409); // 409 Conflict code
            }

            $user->admin = $request->admin;
        }

        if (!$user->isDirty()) { // ->isDirty() means the user has changed (one argument at least in the user model has changed).
            return response()->json([
                'error' => 'You need to specify a different value to update', 
                'code' => 422
            ], 422); // 422 Unprocessable Content
        }

        $user->save();

        return response()->json(['data' => $user], 200);
    }
    

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
