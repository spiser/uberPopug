<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SSOController extends Controller
{
    public function login(Request $request)
    {
        $request->session()->put('state', $state = Str::random(40));

        $query = http_build_query([
            'client_id' => '9b854e3b-ffd0-445d-a85b-9f87ba0ce7e1',
            'redirect_uri' => 'http://localhost:8012/sso/callback',
            'response_type' => 'code',
            'scope' => '',
            'state' => $state,
            // 'prompt' => '', // "none", "consent", or "login"
        ]);

        return redirect('http://localhost:8010/oauth/authorize?'.$query);
    }

    public function callback(Request $request)
    {
        $state = $request->session()->pull('state');

        throw_unless(
            strlen($state) > 0 && $state === $request->state,
            InvalidArgumentException::class,
            'Invalid state value.'
        );

        $response = Http::asForm()->post('http://laravel_auth-service/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => '9b854e3b-ffd0-445d-a85b-9f87ba0ce7e1',
            'client_secret' => 'boFOGJARt66lTsJWt6JMaRCFauZWXOLSAJShegrY',
            'redirect_uri' => 'http://localhost:8012/sso/callback',
            'code' => $request->code,
        ]);

        $request->session()->put($response->json());

        return redirect(route('sso.userInfo'));
    }

    public function userInfo(Request $request)
    {
        $accessToken = $request->session()->pull('access_token');

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken
        ])->get('http://laravel_auth-service/api/user');

        $userArray = $response->json();

        try {
            $user = User::query()
                ->where("public_id", $userArray['public_id'])
                ->firstOrFail();

            Auth::login($user);

            switch ($user->role) {
                case UserRole::worker:
                    return redirect('/worker/view');
                default:
                    return redirect('/dashboard');
            }
        } catch (\Throwable $e) {
            throw new InvalidArgumentException($e->getMessage());
        }
    }
}
