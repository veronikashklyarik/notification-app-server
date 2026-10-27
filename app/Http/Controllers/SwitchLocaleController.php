<?php

namespace App\Http\Controllers;

use App\Enums\Locale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class SwitchLocaleController extends Controller
{
    /**
     * Remember a guest's language choice from the public pages.
     */
    public function __invoke(Request $request, Locale $locale): RedirectResponse
    {
        $request->session()->put('locale', $locale->value);
        Cookie::queue('app_locale', $locale->value, 60 * 24 * 365);

        return redirect()->back(fallback: route('landing'));
    }
}
