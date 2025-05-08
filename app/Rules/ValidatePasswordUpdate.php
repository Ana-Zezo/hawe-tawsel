<?php

namespace App\Rules;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidatePasswordUpdate implements ValidationRule
{
    protected string $guard;
    public function __construct(string $guard)
    {
        $this->guard = $guard;
    }
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!isset($this->guard) || !is_string($this->guard)) {
            $fail('Authentication guard is not set correctly.');
            return;
        }

        $user = Auth::guard($this->guard)->user();

        if (!$user) {
            $fail('User is not authenticated.');
            return;
        }

        if ($attribute === 'current_password' && !is_null($value)) {
            if (!is_string($value)) {
                $fail('The current password must be a string.');
                return;
            }

            if (!Hash::check($value, $user->password)) {
                $fail('The current password is incorrect.');
                return;
            }

            if (empty(request()->password)) {
                $fail('The new password field is required when changing the password.');
                return;
            }
        }

        if ($attribute === 'password' && !is_null($value)) {
            if (!is_string($value)) {
                $fail('The password must be a string.');
                return;
            }

            if (strlen($value) < 8) {
                $fail('The password must be at least 8 characters.');
                return;
            }

            if ($value !== request()->password_confirmation) {
                $fail('The password confirmation does not match.');
                return;
            }

            if (empty(request()->current_password) || !Hash::check(request()->current_password, $user->password)) {
                $fail('The current password is required and must be correct when updating the password.');
                return;
            }
        }
    }

    // protected string $guard;

    // public function __construct(string $guard)
    // {
    //     $this->guard = $guard;
    // }
    // /**
    //  * Run the validation rule.
    //  *
    //  * @param  string  $attribute
    //  * @param  mixed  $value
    //  * @param  Closure  $fail
    //  */
    // public function validate(string $attribute, mixed $value, Closure $fail): void
    // {
    //     $user = Auth::guard($this->guard)->user();
    //     if ($attribute === 'current_password') {
    //         if (!is_null($value)) {
    //             if (!is_string($value)) {
    //                 $fail('The current password must be a string.');
    //                 return;
    //             }
    //             $validator = Validator::make(
    //                 ['current_password' => $value],
    //                 ['current_password' => "current_password:$user"]
    //             );

    //             if ($validator->fails()) {
    //                 $fail('The current password is incorrect.');
    //                 return;
    //             }
    //             if (empty(request()->password)) {
    //                 $fail('The password field is required when current password is provided.');
    //                 return;
    //             }
    //         }
    //     }

    //     // If the attribute is `password`
    //     if ($attribute === 'password') {
    //         // Validate that `password` is a string, nullable, min:8, and confirmed
    //         if (!is_null($value)) {
    //             if (!is_string($value)) {
    //                 $fail('The password must be a string.');
    //                 return;
    //             }

    //             if (strlen($value) < 8) {
    //                 $fail('The password must be at least 8 characters.');
    //                 return;
    //             }

    //             if ($value !== request()->password_confirmation) {
    //                 $fail('The password confirmation does not match.');
    //                 return;
    //             }

    //             // If `password` is provided, ensure `current_password` is also provided
    //             if (empty(request()->current_password)) {
    //                 $fail('The current password field is required when updating the password.');
    //                 return;
    //             }
    //         }
    //     }
    // }

}