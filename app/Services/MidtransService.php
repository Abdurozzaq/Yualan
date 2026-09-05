<?php
namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Http;

class MidtransService
{
    protected $serverKey;
    protected $clientKey;
    protected $merchantId;
    protected $isProduction;

    public function __construct(Tenant $tenant)
    {
        $this->serverKey = $tenant->midtrans_server_key;
        $this->clientKey = $tenant->midtrans_client_key;
        $this->merchantId = $tenant->midtrans_merchant_id;
        $this->isProduction = (bool) $tenant->midtrans_is_production;
    }

    protected function getBaseUrl(): string
    {
        return $this->isProduction
            ? 'https://api.midtrans.com/v2/'
            : 'https://api.sandbox.midtrans.com/v2/';
    }

    public function createTransaction(array $params)
    {
        $url = $this->getBaseUrl() . 'charge';
        $response = Http::withBasicAuth($this->serverKey, '')
            ->post($url, $params);
        return $response->json();
    }

    public function verifySignature($orderId, $statusCode, $grossAmount)
    {
        $signature = hash('sha512', $orderId . $statusCode . $grossAmount . $this->serverKey);
        return $signature;
    }

        /**
         * Create a Snap transaction using Midtrans Snap API.
         * @param array $params
         * @return array
         */
        public function createSnapTransaction(array $params)
        {
            $url = $this->isProduction
                ? 'https://app.midtrans.com/snap/v1/transactions'
                : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

            if (empty($this->serverKey)) {
                throw new \Exception('Midtrans server key belum dikonfigurasi.');
            }

            $payload = [
                'transaction_details' => [
                    'order_id' => $params['order_id'],
                    'gross_amount' => $params['gross_amount'],
                ],
                'item_details' => $params['items'] ?? [],
                'customer_details' => $params['customer_details'] ?? [],
                'callbacks' => [
                    'finish' => $params['callback_url'] ?? null,
                ],
            ];

            \Log::info('Midtrans Snap Request', [
                'url' => $url,
                'method' => 'POST',
                'payload' => $payload,
            ]);

            $response = Http::withBasicAuth($this->serverKey, '')
                ->post($url, $payload);

            \Log::info('Midtrans Snap Response', [
                'url' => $url,
                'method' => 'POST',
                'payload' => $payload,
                'response' => $response->json(),
            ]);

            return $response->json();
        }
}
