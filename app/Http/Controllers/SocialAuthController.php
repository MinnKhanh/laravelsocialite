<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Throwable;
use Socialite;
use App\Models\User;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite as FacadesSocialite;

class SocialAuthController extends Controller
{
    public function redirectToProvider($provider)
    {
        try {
            return FacadesSocialite::driver($provider)->redirect();
        } catch (Throwable $e) {
            return redirect()->route('login');
        }
    }

    public function handleProviderCallback($provider)
    {
        $user = self::createOrGetUser(FacadesSocialite::driver($provider));
        dd($user);
        if ($user) {
            Auth::login($user);
            return redirect()->route('dashboard');
        }
        return redirect()->route('login');
    }

    public function createOrGetUser(Provider $provider)
    {
        try {
            $providerUser = $provider->user();
            $providerName = class_basename($provider);
            $account      = SocialAccount::whereProvider($providerName)->whereProviderUserId($providerUser->getId())->first();
            if ($account) {
                return $account->user;
            } else {
                $account  = new SocialAccount([
                    'provider'         => $providerName,
                    'provider_user_id' => $providerUser->getId(),
                ]);
                $user = User::whereEmail($providerUser->getEmail())->first();
                if (!$user) {
                    $user = User::create([
                        'email'    => $providerUser->getEmail(),
                        'name'     => $providerUser->getName(),
                        'password' => encrypt('ManhDanBlogs')
                    ]);
                }
                $account->user()->associate($user);
                $account->save();
                return $user;
            }
        } catch (Throwable $e) {
            return false;
        }
    }
}
