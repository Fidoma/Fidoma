<?php

namespace App\Features\Welcome\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

class InstallScriptController
{
    public function __invoke(): Response
    {
        return response(File::get(base_path('install.sh')), headers: [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
