<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RecruitmentPrintController extends Controller
{
    public function print(Request $request)
    {
        $key = $request->get('key');
        if (!$key || !Cache::has($key)) {
            abort(404, 'Print data expired or invalid.');
        }

        $payload = Cache::get($key);
        
        return view('exports.recruitment_print', $payload);
    }
}
