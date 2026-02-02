<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CheckClosingYear
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $inputDateType = null): Response
    {
        if ($inputDateType == null) {
            // Ambil tahun dari inputan user
            $inputYear = $request->input('year') ?? date('Y', strtotime($request->input('date')));
        } else {
            $inputYear = Carbon::parse($request->input($inputDateType))->format('Y');
        }

        // Cek apakah tahun tersebut sudah pernah diclosing
        $isYearClosed = DB::connection('finance')->table('closing_entries')
            ->where('year', $inputYear)
            ->exists();

        // Jika tahun sudah diclosing, tolak permintaan insert
        if ($isYearClosed) {
            return response()->json([
                'status' => true,
                'message' => "Data untuk tahun $inputYear tidak dapat ditambahkan karena sudah di-closing."
            ], 400);
        }

        // Jika belum diclosing, lanjutkan proses
        return $next($request);
    }
}
