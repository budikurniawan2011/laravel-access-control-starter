<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EnsureSetupIsComplete
{
    public function handle(Request $request, \Closure $next): Response
    {
        abort_unless(
            Schema::hasTable('installation_states'),
            503,
            'Run the database migrations before accessing the admin panel.',
        );

        if (! DB::table('installation_states')->where('id', 1)->value('setup_completed')) {
            return redirect('/setup');
        }

        return $next($request);
    }
}
