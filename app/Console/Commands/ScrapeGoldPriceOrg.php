<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ScrapeGoldPriceOrg extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:goldprice-org';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Mengambil harga emas via GoldAPI.io...');

        // 1. Daftar gratis di https://www.goldapi.io
        $apiKey = env('GOLDAPI_KEY');

        $response = Http::withHeaders([
            'x-access-token' => $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(30)->get('https://www.goldapi.io/api/XAU/USD');

        if (! $response->successful()) {
            $this->error('Gagal! Status: '.$response->status());
            $this->error('Body: '.$response->body());

            return Command::FAILURE;
        }

        $data = $response->json();
        $this->line(json_encode($data, JSON_PRETTY_PRINT));
        dd($data);

        // Response sudah include harga per gram langsung dalam IDR
        $pricePerOz = (float) $data['price'];           // per troy ounce
        $priceGram24k = (float) $data['price_gram_24k'];  // per gram 24k
        $priceGram22k = (float) $data['price_gram_22k'];  // per gram 22k
        $priceGram18k = (float) $data['price_gram_18k'];  // per gram 18k

        $this->table(
            ['Satuan', 'Harga (IDR)'],
            [
                ['Per Troy Ounce',  'Rp '.number_format($pricePerOz, 0, ',', '.')],
                ['Per Gram (24k)',  'Rp '.number_format($priceGram24k, 0, ',', '.')],
                ['Per Gram (22k)',  'Rp '.number_format($priceGram22k, 0, ',', '.')],
                ['Per Gram (18k)',  'Rp '.number_format($priceGram18k, 0, ',', '.')],
                ['Per 5 Gram (24k)', 'Rp '.number_format($priceGram24k * 5, 0, ',', '.')],
                ['Per 10 Gram (24k)', 'Rp '.number_format($priceGram24k * 10, 0, ',', '.')],
            ]
        );

        $this->info('Update: '.($data['timestamp'] ?? '-'));
        $this->info('Sumber: goldapi.io (XAU/IDR real-time)');

        return Command::SUCCESS;
    }
}
