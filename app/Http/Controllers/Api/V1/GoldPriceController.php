<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\GoldPriceResource;
use App\Models\GoldPrice;
use App\Models\Source;
use Illuminate\Http\JsonResponse;

class GoldPriceController extends Controller
{
    public function index(): JsonResponse
    {
        // 1. Ambil semua master source yang terdaftar
        $sources = Source::all();
        $responseRaw = [];

        foreach ($sources as $source) {
            // 2. Cari tanggal perekaman data terbaru untuk source ini
            $latestRecord = GoldPrice::where('source_id', $source->id)
                ->latest('recorded_at')
                ->first();

            if ($latestRecord) {
                // 3. Ambil semua data pecahan gramasi pada tanggal terbaru tersebut
                $prices = GoldPrice::where('source_id', $source->id)
                    ->whereDate('recorded_at', $latestRecord->recorded_at->toDateString())
                    ->orderBy('weight', 'asc')
                    ->get();

                // 4. Bungkus ke dalam format array berkelompok
                $responseRaw[] = [
                    'source_name' => $source->name,
                    'source_slug' => $source->slug,
                    'source_url' => $source->url,
                    'last_updated' => $latestRecord->recorded_at->toIso8601String(),
                    'prices' => GoldPriceResource::collection($prices),
                ];
            }
        }

        // 5. Return response JSON standar API masJUBEL
        return response()->json([
            'status' => 'success',
            'message' => 'Success fetch all latest gold prices data',
            'data' => $responseRaw,
        ], 200);
    }

    public function highlight(): JsonResponse
    {
        $sources = Source::all();
        $highlights = [];

        foreach ($sources as $source) {
            // 1. Ambil data harga 1 gram TERBARU (Hari ini)
            $latest = GoldPrice::where('source_id', $source->id)
                ->where('weight', 1.0) // 1 gram
                ->latest('recorded_at')
                ->first();

            if (! $latest) {
                continue;
            }

            // 2. Ambil data harga 1 gram SEBELUMNYA (H-1 atau data record terakhir sebelum hari ini)
            $previous = GoldPrice::where('source_id', $source->id)
                ->where('weight', 1.0)
                ->whereDate('recorded_at', '<', $latest->recorded_at->toDateString())
                ->latest('recorded_at')
                ->first();

            // 3. Kalkulasi Tren (Persentase Naik/Turun)
            $trendPercentage = 0;
            $isUp = true;

            if ($previous && $previous->base_price > 0) {
                $diff = $latest->base_price - $previous->base_price;
                $trendPercentage = round(($diff / $previous->base_price) * 100, 2);
                $isUp = $diff >= 0; // true jika naik/tetap, false jika turun
            }

            $highlights[] = [
                'source_name' => $source->name,
                'weight' => 1,
                'current_price' => $latest->base_price,
                'previous_price' => $previous ? $previous->base_price : null,
                'trend_percentage' => abs($trendPercentage), // Pakai absolute agar minusnya diwakilkan is_up
                'is_up' => $isUp,
                'last_updated' => $latest->recorded_at->toIso8601String(),
            ];

        }

        return response()->json([
            'status' => 'success',
            'message' => 'Success fetch highlight 1 gram gold price data',
            'data' => $highlights,
        ], 200);
    }

    public function history(Request $request): JsonResponse
    {
        $sourceSlug = $request->query('source', 'antam');
        $days = (int) $request->query('range', 7);

        $source = Source::where('source', $sourceSlug)->first();
        if (! $source) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gold resource not found',
            ], 404);
        }

        // Ambil data historis dari database
        $historyData = GoldPrice::where('source_id', $source->id)
            ->where('weight', 1.0)
            ->whereDate('recorded_at', '>=', now()->subDays($days))
            ->orderBy('recorded_at', 'asc')
            ->get();

        // 4. Format data khusus untuk kebutuhan Chart Frontend
        $formattedChart = $historyData->map(function ($item) {
            return [
                'date' => $item->recorded_at->format('Y-m-d'), // Format tanggal sumbu X
                'price' => $item->base_price,                   // Nilai sumbu Y
            ];
        })->unique('date')->values();

        return response()->json([
            'status' => 'success',
            'message' => "Succeed fetch history {$source->name} for {$days} last days",
            'data' => [
                'source_name' => $source->name,
                'range_days' => $days,
                'chart_data' => $formattedChart,
            ],
        ], 200);
    }
}
