<?php

namespace Paymenter\Extensions\Gateways\MCsets;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Helpers\ExtensionHelper;
use App\Classes\Extension\Gateway;
use Illuminate\Support\Facades\View;

class MCsets extends Gateway
{
    public function boot()
    {
        require __DIR__ . '/routes/web.php';
        View::addNamespace('extensions.gateways.mcsets', __DIR__ . '/views');
    }

    public function getConfig($values = [])
    {
        return [
            [
                'name'        => 'api_key',
                'label'       => 'Live API Key',
                'type'        => 'text',
                'description' => 'Your MCsets Enterprise live API key (starts with ent_live_).',
                'required'    => false,
            ],
            [
                'name'        => 'webhook_secret',
                'label'       => 'Webhook Secret',
                'type'        => 'text',
                'description' => 'Signing secret from your MCsets webhook. Webhook URL: https://your-domain.com/extensions/gateways/mcsets/webhook — subscribe to checkout.session.completed.',
                'required'    => true,
            ],
            [
                'name'        => 'test_mode',
                'label'       => 'Test Mode',
                'type'        => 'checkbox',
                'description' => 'When enabled, uses your test API key. Payments auto-succeed without charging real cards.',
                'required'    => false,
            ],
            [
                'name'        => 'test_api_key',
                'label'       => 'Test API Key',
                'type'        => 'text',
                'description' => 'Your MCsets Enterprise test API key (starts with ent_test_). Only needed when Test Mode is on.',
                'required'    => false,
            ],
        ];
    }

    public function pay($invoice, $total)
    {
        $apiKey = $this->config('test_mode')
            ? $this->config('test_api_key')
            : $this->config('api_key');

        $supportedCurrencies = ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'SEK', 'NOK', 'DKK', 'CHF', 'PLN'];
        $currency = strtoupper($invoice->currency_code);

        if (!in_array($currency, $supportedCurrencies)) {
            return view('extensions.gateways.mcsets::error', [
                'error' => 'MCsets does not support the currency "' . $currency . '". Supported: ' . implode(', ', $supportedCurrencies),
            ]);
        }

        $amountInCents = (int) round($total * 100);

        if ($amountInCents < 100) {
            return view('extensions.gateways.mcsets::error', [
                'error' => 'Minimum payment amount is 1.00 (100 cents).',
            ]);
        }

        $successUrl = route('invoices.show', ['invoice' => $invoice->id]);
        $cancelUrl  = route('invoices.show', ['invoice' => $invoice->id]);

        $client = new Client();

        try {
            $response = $client->post('https://mcsets.com/api/v1/enterprise/checkout/sessions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                ],
                'json' => [
                    'amount'         => $amountInCents,
                    'currency'       => $currency,
                    'name'           => 'Invoice #' . $invoice->id,
                    'description'    => 'Payment for Invoice #' . $invoice->id,
                    'success_url'    => $successUrl,
                    'cancel_url'     => $cancelUrl,
                    'customer_email' => $invoice->user->email ?? null,
                    'metadata'       => [
                        'invoice_id' => (string) $invoice->id,
                        'user_id'    => (string) $invoice->user->id,
                    ],
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            if (!isset($data['success']) || !$data['success'] || empty($data['data']['checkout_url'])) {
                throw new \Exception('MCsets returned an unexpected response: ' . json_encode($data));
            }

            return redirect($data['data']['checkout_url']);

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $body    = json_decode($e->getResponse()->getBody(), true);
            $message = $body['message'] ?? $body['error']['message'] ?? $e->getMessage();

            return view('extensions.gateways.mcsets::error', [
                'error' => 'Could not create payment session: ' . $message,
            ]);
        } catch (\Exception $e) {
            return view('extensions.gateways.mcsets::error', [
                'error' => 'An error occurred: ' . $e->getMessage(),
            ]);
        }
    }

    public function webhook(Request $request)
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('X-MCsets-Signature');
        $secret    = $this->config('webhook_secret');

        if (!preg_match('/t=(\d+),v1=([a-f0-9]+)/', $sigHeader ?? '', $matches)) {
            return response('Invalid signature format', 400);
        }

        $timestamp = $matches[1];
        $signature = $matches[2];

        if (abs(time() - (int) $timestamp) > 300) {
            return response('Timestamp too old', 400);
        }

        $signedPayload = $timestamp . '.' . $payload;
        $expectedSig   = hash_hmac('sha256', $signedPayload, $secret);

        if (!hash_equals($expectedSig, $signature)) {
            return response('Signature verification failed', 401);
        }

        $event = json_decode($payload, true);

        if (($event['type'] ?? '') !== 'checkout.session.completed') {
            return response('Event ignored', 200);
        }

        $session   = $event['data']['object'] ?? [];
        $invoiceId = $session['metadata']['invoice_id'] ?? null;
        $sessionId = $session['id'] ?? null;
        $amount    = isset($session['amount']) ? ($session['amount'] / 100) : 0;

        if (!$invoiceId) {
            return response('Missing invoice_id in session metadata', 400);
        }

        ExtensionHelper::addPayment((int) $invoiceId, 'MCsets', $amount, null, $sessionId);

        return response('OK', 200);
    }
}