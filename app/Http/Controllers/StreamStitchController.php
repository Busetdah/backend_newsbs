<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;

class StreamStitchController extends Controller
{
    private $allData = [];
    private $goodTotal = 0;
    private $badTotal = 0;
    private $startValue = 890; // Starting value at 15:00 WIB

    public function sendData(Request $request)
    {
        // Set timezone to Asia/Jakarta
        date_default_timezone_set('Asia/Jakarta');
        Carbon::setLocale('id');

        // Handle CORS preflight requests
        if ($request->isMethod('OPTIONS')) {
            return response('', 200)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With');
        }

        // Ambil filter tanggal kalau ada
        $startDate = $request->query('start_date') ? Carbon::parse($request->query('start_date'), 'Asia/Jakarta') : Carbon::now('Asia/Jakarta')->subDays(30);
        $endDate = $request->query('end_date') ? Carbon::parse($request->query('end_date'), 'Asia/Jakarta') : Carbon::now('Asia/Jakarta');

        // Ensure we have a valid date range
        if ($startDate->greaterThan($endDate)) {
            $temp = $startDate;
            $startDate = $endDate;
            $endDate = $temp;
        }

        // Get the current Jakarta time
        $now = Carbon::now('Asia/Jakarta');
        $hour = $now->hour;
        $minute = $now->minute;
        $second = $now->second;

        // Calculate seconds elapsed since 15:00 if current time is later
        if ($hour > 15 || ($hour == 15 && ($minute > 0 || $second > 0))) {
            $secondsAfter15 = ($hour - 15) * 3600 + $minute * 60 + $second;

            // Distribute the increments between good and bad
            for ($i = 0; $i < $secondsAfter15; $i++) {
                if (rand(0, 100) > 30) { // 70% chance of being good
                    $this->goodTotal++;
                } else {
                    $this->badTotal++;
                }
            }

            // Generate initial data up to current time
            $this->generateInitialData($secondsAfter15);
        }

        // Set up SSE response with CORS headers
        return response()->stream(function () use ($startDate, $endDate) {
            $lastTimeStamp = Carbon::now('Asia/Jakarta')->timestamp;

            while (true) {
                // Get current Jakarta time
                $now = Carbon::now('Asia/Jakarta');
                $currentTimestamp = $now->timestamp;

                // Check if a second has passed
                if ($currentTimestamp > $lastTimeStamp) {
                    // Update the last timestamp
                    $lastTimeStamp = $currentTimestamp;

                    // Create a data point for today with current time
                    $dataTime = Carbon::today('Asia/Jakarta')->setTime($now->hour, $now->minute, $now->second);

                    // Randomly decide if this increment is good or bad
                    $isGood = (rand(0, 100) > 30); // 70% chance of being good

                    // Update total counters
                    if ($isGood) {
                        $this->goodTotal++;
                    } else {
                        $this->badTotal++;
                    }

                    // Create the new data point with ACCUMULATED totals
                    $newData = (object)[
                        'id' => count($this->allData) + 1,
                        'time' => $dataTime->format('Y-m-d H:i:s'),
                        'good' => $this->goodTotal,
                        'bad' => $this->badTotal,
                        'created_at' => $dataTime->format('Y-m-d H:i:s'),
                        'updated_at' => $dataTime->format('Y-m-d H:i:s'),
                    ];

                    // Add to our collection
                    $this->allData[] = $newData;

                    // Calculate summary - now just using the current totals
                    $summary = [
                        'good' => $this->goodTotal,
                        'bad' => $this->badTotal,
                        'total' => $this->goodTotal + $this->badTotal,
                    ];

                    // Group by day for daily data
                    $dailyData = collect($this->allData)
                        ->groupBy(function ($item) {
                            return Carbon::parse($item->time, 'Asia/Jakarta')->format('Y-m-d');
                        })
                        ->map(function ($group) {
                            // Get the last record for each day to get the accumulated totals
                            $lastRecord = $group->last();
                            return [
                                'good' => $lastRecord->good,
                                'bad' => $lastRecord->bad,
                                'total' => $lastRecord->good + $lastRecord->bad,
                            ];
                        });

                    // Format in SSE format
                    echo "data: " . json_encode([
                        'data' => $this->allData,
                        'summary' => $summary,
                        'dailyData' => $dailyData,
                    ]) . "\n\n";

                    // Flush to send data immediately
                    ob_flush();
                    flush();
                }

                // Small delay to prevent CPU overuse
                usleep(10000); // 10ms
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no', // For NGINX
            'Access-Control-Allow-Origin' => '*', // Allow access from any origin
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, X-Requested-With',
        ]);
    }

    /**
     * Generate initial data points for the elapsed seconds
     *
     * @param int $seconds Number of seconds to generate data for
     */
    private function generateInitialData($seconds)
    {
        // Same method as before...
        // Your implementation here
    }
}
// namespace App\Http\Controllers;

// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\DB;

// class StreamStitchController extends Controller
// {
//     public function sendData(Request $request)
//     {
//         // Ini supaya response nya jadi streaming (SSE)
//         return response()->stream(function () use ($request) {
//             while (true) {
//                 // Ambil filter tanggal kalau ada
//                 $startDate = $request->query('start_date');
//                 $endDate = $request->query('end_date');

//                 // Query data dari tabel stitches
//                 $query = DB::table('stitches');

//                 if ($startDate && $endDate) {
//                     $query->whereBetween('time', [$startDate, $endDate]);
//                 }

//                 $data = $query->get();

//                 // Buat summary data
//                 $summary = [
//                     'good' => $data->sum('good'),
//                     'bad' => $data->sum('bad'),
//                     'total' => $data->sum('good') + $data->sum('bad'),
//                 ];

//                 // Bisa tambahin dailyData juga kalau mau, contoh grup harian
//                 $dailyData = $data->groupBy(function ($item) {
//                     return \Carbon\Carbon::parse($item->created_at)->format('Y-m-d');
//                 })->map(function ($group) {
//                     return [
//                         'good' => $group->sum('good'),
//                         'bad' => $group->sum('bad'),
//                         'total' => $group->sum('good') + $group->sum('bad'),
//                     ];
//                 });

//                 // Buat data dalam format SSE
//                 echo "data: " . json_encode([
//                     'data' => $data,
//                     'summary' => $summary,
//                     'dailyData' => $dailyData,
//                 ]) . "\n\n";

//                 // Flush supaya data langsung dikirim
//                 ob_flush();
//                 flush();

//                 // Delay 1 detik
//                 sleep(1);
//             }
//         }, 200, [
//             'Content-Type' => 'text/event-stream',
//             'Cache-Control' => 'no-cache',
//             'Connection' => 'keep-alive',
//         ]);
//     }
// }
