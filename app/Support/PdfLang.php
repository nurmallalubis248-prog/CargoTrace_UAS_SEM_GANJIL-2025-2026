<?php

namespace App\Support;

use Illuminate\Http\Request;

/** The UI language lives in the browser, so PDF links pass ?lang=en|id and the server switches locale for that request. */
final class PdfLang
{
    public static function apply(Request $request): void
    {
        $lang = $request->query('lang');
        if (in_array($lang, ['en', 'id'], true)) {
            app()->setLocale($lang);
        }
    }
}
