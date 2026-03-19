<?php

use Illuminate\Support\Facades\Route;
use Paymenter\Extensions\Gateways\MCsets\MCsets;

Route::post('/extensions/gateways/mcsets/webhook', function (\Illuminate\Http\Request $request) {
    $gateway = new MCsets();
    return $gateway->webhook($request);
})->name('extensions.gateways.mcsets.webhook');