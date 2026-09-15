<?php

namespace App\Actions\Fortify;

use App\Models\InviteCode;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => $this->passwordRules(),
            'invite_code' => ['required', 'string'],
        ])->validate();

        $invite = InviteCode::query()
            ->available()
            ->where('code', $input['invite_code'])
            ->first();

        if (! $invite) {
            throw ValidationException::withMessages([
                'invite_code' => "Ce code d'invitation est invalide ou a déjà été utilisé.",
            ]);
        }

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'], // hashed automatically (cast on User::password)
        ]);

        $invite->recordUse($user);

        return $user;
    }
}
