<?php
// Payment gateway config (store secrets in environment or update this file in dev only)
return [
    'paystack' => [
        'public' => getenv('PAYSTACK_PUBLIC') ?: 'pk_test_xxx',
        'secret' => getenv('PAYSTACK_SECRET') ?: 'sk_test_xxx',
        'webhook_secret' => getenv('PAYSTACK_WEBHOOK_SECRET') ?: 'whsec_xxx'
    ],
    'bank' => [
        'account_number' => getenv('SITE_BANK_ACCOUNT') ?: '0123456789',
        'bank_name' => getenv('SITE_BANK_NAME') ?: 'Your Bank',
        'account_name' => getenv('SITE_ACCOUNT_NAME') ?: 'HIGH Q SOLID ACADEMY'
    ]
];
