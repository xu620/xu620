<?php

namespace app\http\middleware;

use think\facade\Log;

class Check
{
    public function handle($request, \Closure $next)
    {
        Log::record('PARAM：'. var_export_short($request->param()),'param');

        return $next($request);
    }
}
