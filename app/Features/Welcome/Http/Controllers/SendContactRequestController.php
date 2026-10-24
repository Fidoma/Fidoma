<?php

namespace App\Features\Welcome\Http\Controllers;

use App\Features\Welcome\Actions\SendContactRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SendContactRequestController
{
    public function __invoke(Request $request, SendContactRequest $sendContactRequest): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email:rfc', 'max:254'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $sendContactRequest->execute($validated['email'], $validated['message']);

        return redirect(route('pricing').'#kontakt')
            ->with('contact_sent', true);
    }
}
