<?php

namespace App\Features\Authentication\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class LocaleController
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, ['de', 'en'], true), 404);

        $request->session()->put('locale', $locale);

        return redirect()->back();
    }
}
