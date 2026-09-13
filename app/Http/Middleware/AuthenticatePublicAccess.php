<?php

namespace App\Http\Middleware;

use App\Application\System\Queries\GetPublicAccessUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticatePublicAccess
{
    public function __construct(private readonly GetPublicAccessUser $getPublicAccessUser) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('login') && $request->boolean('admin')) {
            if ($request->session()->pull('public_access_authenticated', false)) {
                Auth::logout();
            }

            $request->session()->put('public_access_bypass', true);

            return $next($request);
        }

        $publicUser = $this->getPublicAccessUser->handle();

        if ($request->session()->get('public_access_authenticated')) {
            if ($publicUser === null || Auth::id() !== $publicUser->id) {
                Auth::logout();
                $request->session()->forget('public_access_authenticated');
            }
        }

        if (! Auth::check() && ! $request->session()->get('public_access_bypass') && $publicUser !== null) {
            Auth::login($publicUser);
            $request->session()->put('public_access_authenticated', true);
        }

        return $next($request);
    }
}
