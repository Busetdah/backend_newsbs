<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StreamStitchController extends Controller
{
    public function sendData(Request $request)
    {
        // Ini supaya response nya jadi streaming (SSE)
        return response()->stream(function () use ($request) {
            while (true) {
                // Ambil filter tanggal kalau ada
                $startDate = $request->query('start_date');
                $endDate = $request->query('end_date');

                // Query data dari tabel stitches
                $query = DB::table('stitches');

                if ($startDate && $endDate) {
                    $query->whereBetween('time', [$startDate, $endDate]);
                }

                $data = $query->get();

                // Buat summary data
                $summary = [
                    'good' => $data->sum('good'),
                    'bad' => $data->sum('bad'),
                    'total' => $data->sum('good') + $data->sum('bad'),
                ];

                // Bisa tambahin dailyData juga kalau mau, contoh grup harian
                $dailyData = $data->groupBy(function ($item) {
                    return \Carbon\Carbon::parse($item->created_at)->format('Y-m-d');
                })->map(function ($group) {
                    return [
                        'good' => $group->sum('good'),
                        'bad' => $group->sum('bad'),
                        'total' => $group->sum('good') + $group->sum('bad'),
                    ];
                });

                // Buat data dalam format SSE
                echo "data: " . json_encode([
                    'data' => $data,
                    'summary' => $summary,
                    'dailyData' => $dailyData,
                ]) . "\n\n";

                // Flush supaya data langsung dikirim
                ob_flush();
                flush();

                // Delay 1 detik
                sleep(1);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
        ]);
    }
}
