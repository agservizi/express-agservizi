<?php

declare(strict_types=1);

return [
    'fastweb' => [
        [
            'key' => 'fastweb_v1',
            'match' => [
                '/FASTWEB/i',
                '/Proposta\s+di\s+Abbonamento/i',
            ],
            'required' => ['iccid'],
            'patterns' => [
                'customer_fullname' => [
                    '/Cliente\s*[:\-]\s*([^\r\n]+)/i',
                ],
                'customer_tax_code' => [
                    '/Codice\s*Fiscale\s*[:\-]\s*([A-Z0-9]{11,16})/i',
                ],
                'customer_email' => [
                    '/Email\s*[:\-]\s*([^\s]+)/i',
                ],
                'customer_phone' => [
                    '/Telefono\s*[:\-]\s*([0-9+ ]{7,})/i',
                ],
                'customer_address' => [
                    '/Indirizzo\s*[:\-]\s*([^\r\n]+)/i',
                ],
                'plan' => [
                    '/Offerta\s*[:\-]\s*([^\r\n]+)/i',
                ],
                'price' => [
                    '/Totale\s*[:\-]\s*€?\s*([\d.,]+)/i',
                ],
                'iccid' => [
                    '/\b89\d{17,19}\b/',
                ],
                'msisdn' => [
                    '/\b3\d{8,10}\b/',
                ],
            ],
        ],
    ],
    'windtre' => [
        [
            'key' => 'windtre_v1',
            'match' => [
                '/WIND\s*TRE/i',
            ],
            'required' => ['iccid'],
            'patterns' => [
                'customer_fullname' => [
                    '/Intestatario\s*[:\-]\s*([^\r\n]+)/i',
                ],
                'customer_tax_code' => [
                    '/Codice\s*Fiscale\s*[:\-]\s*([A-Z0-9]{11,16})/i',
                ],
                'customer_email' => [
                    '/Email\s*[:\-]\s*([^\s]+)/i',
                ],
                'customer_phone' => [
                    '/Cellulare\s*[:\-]\s*([0-9+ ]{7,})/i',
                ],
                'customer_address' => [
                    '/Indirizzo\s*[:\-]\s*([^\r\n]+)/i',
                ],
                'plan' => [
                    '/Offerta\s*[:\-]\s*([^\r\n]+)/i',
                    '/Piano\s*[:\-]\s*([^\r\n]+)/i',
                ],
                'price' => [
                    '/Importo\s*[:\-]\s*€?\s*([\d.,]+)/i',
                ],
                'iccid' => [
                    '/\b89\d{17,19}\b/',
                ],
                'msisdn' => [
                    '/\b3\d{8,10}\b/',
                ],
            ],
        ],
    ],
    'generic' => [
        [
            'key' => 'generic_v1',
            'match' => [
                '/PDA/i',
            ],
            'required' => ['iccid'],
            'patterns' => [
                'customer_fullname' => [
                    '/Intestatario\s*[:\-]\s*([^\r\n]+)/i',
                ],
                'customer_tax_code' => [
                    '/Codice\s*Fiscale\s*[:\-]\s*([A-Z0-9]{11,16})/i',
                ],
                'customer_email' => [
                    '/Email\s*[:\-]\s*([^\s]+)/i',
                ],
                'customer_phone' => [
                    '/Telefono\s*[:\-]\s*([0-9+ ]{7,})/i',
                ],
                'customer_address' => [
                    '/Indirizzo\s*[:\-]\s*([^\r\n]+)/i',
                ],
                'plan' => [
                    '/Offerta\s*[:\-]\s*([^\r\n]+)/i',
                    '/Prodotto\s*[:\-]\s*([^\r\n]+)/i',
                ],
                'price' => [
                    '/Totale\s*[:\-]\s*€?\s*([\d.,]+)/i',
                    '/Importo\s*[:\-]\s*€?\s*([\d.,]+)/i',
                ],
                'iccid' => [
                    '/\b89\d{17,19}\b/',
                ],
                'msisdn' => [
                    '/\b3\d{8,10}\b/',
                ],
            ],
        ],
    ],
];
