<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\RedirectResponse;

class LoginController extends Controller
{


    protected array $providers = [
        'github', 'facebook', 'google', 'twitter'
    ];


    public function redirectToProvider($driver)
    {
        if (!$this->isProviderAllowed($driver)) {
            return $this->sendFailedResponse("{$driver} is not currently supported");
        }

        try {
            return Socialite::driver($driver)->redirect();
        } catch (Exception $e) {
            flash()->addWarning('Something went wrong1');
            return $this->sendFailedResponse($e->getMessage());
        }
    }

    private function isProviderAllowed($driver)
    {
        return in_array($driver, $this->providers, true) && config()->has("services.{$driver}");
    }

    protected function sendFailedResponse($msg = null)
    {
        dd('mistake');

        return to_route('login')->withErrors([
            'email' => $msg ?: 'Unable to login, try with another provider to login.'
        ]);
    }

    public function handleProviderCallback($driver): RedirectResponse
    {
        try {
            $user = Socialite::driver($driver)->user();
        } catch (Exception $e) {

            dd($e);
            flash()->addWarning('Something went wrong2');
            return $this->sendFailedResponse($e->getMessage());
        }

        return empty($user->email)
            ? $this->sendFailedResponse("No email id returned from {$driver} provider.")
            : $this->loginOrCreateAccount($user, $driver);
    }

    protected function loginOrCreateAccount($providerUser, $driver): RedirectResponse
    {



      $user=  User::query()->updateOrCreate(
            [
                'email' => $providerUser->getEmail()
            ],
            [
                'name' => $providerUser->getName(),
                'provider' => $driver,
                'provider_id' => $providerUser->id,
                'access_token' => $providerUser->token

            ]
        );


        Auth::login($user, true);

        return $this->sendSuccessResponse();
    }

    protected function sendSuccessResponse(): RedirectResponse
    {
        flash()->addSuccess('Logged in successfully using socialite');
        return to_route('dashboard');
    }
}
