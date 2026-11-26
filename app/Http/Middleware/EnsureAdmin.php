<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class EnsureAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user('web');

        if ($user === null) {
            abort_if($request->expectsJson(), 401);

            if ($request->isMethod('GET')) {
                $request->session()->put('dashboard_admin.intended', $request->fullUrl());
            }

            return redirect()->route('admin.login');
        }

        abort_unless($user->is_admin, 403);
        $response = $next($request);
        $response->headers->set('Cache-Control', 'private, no-store');
        return $response;
    }
}
