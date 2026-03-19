# MCsets Payment Gateway for Paymenter

A payment gateway extension for [Paymenter](https://paymenter.org) that integrates [MCsets Enterprise](https://mcsets.com) for hosted checkout payments.

![Paymenter](https://img.shields.io/badge/Paymenter-v1.x-4B32C3.svg)
![License](https://img.shields.io/badge/License-MIT-blue.svg)


---

## How It Works

1. Customer clicks **Pay** on a Paymenter invoice
2. Extension creates a checkout session via the MCsets API
3. Customer is redirected to the **MCsets hosted checkout page**
4. After payment, customer is sent back to their invoice
5. MCsets fires a webhook → invoice is automatically marked as paid

---

## Requirements

- Paymenter v1.x
- PHP 8.3+
- MCsets Enterprise account → [mcsets.com](https://mcsets.com)
- HTTPS on your domain

---

## Installation

```bash
git clone https://github.com/probablysubeditor69204/Mcsets-Paymenter /var/www/paymenter/extensions/Gateways/MCsets
```

---

## Setup

### 1. Exclude webhook from CSRF

Open `bootstrap/app.php` and add inside `->withMiddleware`:

```php
$middleware->validateCsrfTokens(except: [
    '/extensions/gateways/mcsets/webhook',
]);
```

### 2. Create webhook in MCsets dashboard

- **URL:** `https://your-domain.com/extensions/gateways/mcsets/webhook`
- **Event:** `checkout.session.completed`
- **Save the secret** — shown only once

### 3. Enable in Paymenter

Go to **Admin → Gateways → New Gateway → MCsets** and fill in:

| Field | Description |
|---|---|
| Live API Key | Your `ent_live_xxxx` key |
| Webhook Secret | Secret from step 2 |
| Test Mode | Enable to test without real charges |
| Test API Key | Your `ent_test_xxxx` key |

### 4. Clear cache

```bash
cd /var/www/paymenter && php artisan optimize:clear
```

---

## Supported Currencies

`USD` `EUR` `GBP` `CAD` `AUD` `SEK` `NOK` `DKK` `CHF` `PLN`

Minimum payment: **$1.00**

---

## Fees

MCsets charges **7% + $0.50** per transaction, deducted before settlement.

| Payment | Fee | You Receive |
|---|---|---|
| $10.00 | $1.20 | $8.80 |
| $50.00 | $4.00 | $46.00 |
| $100.00 | $7.50 | $92.50 |

---

## File Structure

```
extensions/Gateways/MCsets/
├── MCsets.php
├── routes/
│   └── web.php
├── views/
│   └── error.blade.php
└── README.md
```

---

## Security

- HMAC-SHA256 signature verification on every webhook
- Constant-time comparison to prevent timing attacks
- 5-minute timestamp window to prevent replay attacks
- No card data ever touches your server

---
Copyright (c) 2026 suBwAy
