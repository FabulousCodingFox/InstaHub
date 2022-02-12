<?php

namespace App\Http\Controllers\Auth;

use App\Facades\RequestHub;
use App\Helpers\HubHelper;
use App\Http\Controllers\Controller;
use App\Models\Photo;
use App\Models\User;
use App\Notifications\NewUser;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Storage;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after login / registration.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
        $hub = new HubHelper(); //this Controller runs before HubHelper in AppServiceProvider, so we force changing db
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'username' => 'required|max:255|unique:users',
            'name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|min:6|confirmed',
            'bio' => 'nullable|max:500',
            'gender' => 'nullable',
            'birthday_birthDay' => 'nullable|date_format:Y-m-d',
            'city' => 'nullable|string',
            'country' => 'nullable|string',
            'centimeters' => 'nullable|numeric',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return User
     */
    protected function create(array $data)
    {
        if (array_key_exists('avatar', $data)) {
            if ($data['avatar']) {
                $url = Storage::putFile('avatars', $data['avatar']);
            } else {
                $url = 'avatar.png';
            }
        } else {
            $url = 'avatar.png';
        }

        $role = 1;
        if (! RequestHub::isHub()) {
            $role = 3;
        }

        $user = User::create([
            'username' => $data['username'],
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'bio' => Arr::has($data, 'bio') ? $data['bio'] : null,
            'gender' => Arr::has($data, 'gender') ? $data['gender'] : null,
            'birthday' => Arr::has($data, 'birthday') ? $data['birthday'] : null,
            'city' => Arr::has($data, 'city') ? $data['city'] : null,
            'country' => Arr::has($data, 'country') ? $data['country'] : null,
            'centimeters' => Arr::has($data, 'centimeters') ? $data['centimeters'] : null,
            'avatar' => $url,
            'role' => $role,
        ]);

        if (env('APP_ENV') == 'local') {
            $user->is_active = 1;
        }

        $user->save();

        //send message to admin if teacher apply for account in root
        if (! RequestHub::isHub() && env('APP_ENV') != 'local') {
            User::where('role', '=', 'admin')->first()->notify(new NewUser($user, $data['messageToAdmin']));
        }

        return $user;
    }
}
