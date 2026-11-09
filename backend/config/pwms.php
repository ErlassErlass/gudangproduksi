<?php

/**
 * Erlass PWMS configurable domain defaults (agent_docs/02_SCOPE_AND_ASSUMPTIONS.md).
 * None of these values should be hardcoded in controllers or business services.
 */
return [
    'default_currency' => env('PWMS_CURRENCY', 'IDR'),
    'display_timezone' => env('PWMS_DISPLAY_TIMEZONE', 'Asia/Jakarta'),
    'idempotency_ttl_hours' => (int) env('PWMS_IDEMPOTENCY_TTL_HOURS', 48),

    'attachments' => [
        'disk' => env('PWMS_ATTACHMENT_DISK', 'local'),
        'max_kb' => (int) env('PWMS_ATTACHMENT_MAX_KB', 10_240),
        'allowed_mimes' => [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/webp',
            'text/csv',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ],
    ],

    /*
     * Document numbering formats (02_SCOPE_AND_ASSUMPTIONS.md).
     * Patterns support `{prefix}`, `{date}`, `{seq}`, and caller-supplied context tokens
     * such as `{product_code}` for production execution numbers (`1001-261107-01`).
     */
    'numbering' => [
        'INVENTORY_TRANSFER' => ['prefix' => 'TRF', 'date_format' => 'Ymd', 'reset_period' => 'DAILY', 'padding' => 4, 'pattern' => '{prefix}-{date}-{seq}'],
        'INVENTORY_ADJUSTMENT' => ['prefix' => 'ADJ', 'date_format' => 'Ymd', 'reset_period' => 'DAILY', 'padding' => 4, 'pattern' => '{prefix}-{date}-{seq}'],
        'STOCK_OPNAME' => ['prefix' => 'OPN', 'date_format' => 'Ymd', 'reset_period' => 'MONTHLY', 'padding' => 4, 'pattern' => '{prefix}-{date}-{seq}'],
        'PURCHASE_REQUISITION' => ['prefix' => 'PR', 'date_format' => 'Ymd', 'reset_period' => 'DAILY', 'padding' => 4, 'pattern' => '{prefix}-{date}-{seq}'],
        'PURCHASE_ORDER' => ['prefix' => 'PO', 'date_format' => 'Ymd', 'reset_period' => 'DAILY', 'padding' => 4, 'pattern' => '{prefix}-{date}-{seq}'],
        'GOODS_RECEIPT' => ['prefix' => 'GRN', 'date_format' => 'Ymd', 'reset_period' => 'DAILY', 'padding' => 4, 'pattern' => '{prefix}-{date}-{seq}'],
        'PRODUCTION_PLAN' => ['prefix' => 'PPL', 'date_format' => 'Ymd', 'reset_period' => 'DAILY', 'padding' => 4, 'pattern' => '{prefix}-{date}-{seq}'],
        'PRODUCTION_ORDER' => ['prefix' => '', 'date_format' => 'ymd', 'reset_period' => 'DAILY', 'padding' => 2, 'pattern' => '{product_code}-{date}-{seq}'],
        'WORK_ORDER' => ['prefix' => 'WO', 'date_format' => 'ymd', 'reset_period' => 'DAILY', 'padding' => 3, 'pattern' => '{prefix}-{date}-{seq}'],
        'MATERIAL_REQUEST' => ['prefix' => 'MRQ', 'date_format' => 'Ymd', 'reset_period' => 'DAILY', 'padding' => 4, 'pattern' => '{prefix}-{date}-{seq}'],
        'MATERIAL_ISSUE' => ['prefix' => 'MIS', 'date_format' => 'Ymd', 'reset_period' => 'DAILY', 'padding' => 4, 'pattern' => '{prefix}-{date}-{seq}'],
        'VENDOR_ORDER' => ['prefix' => 'GAP', 'date_format' => 'Ymd', 'reset_period' => 'DAILY', 'padding' => 4, 'pattern' => '{prefix}-{date}-{seq}'],
        'VENDOR_RECEIPT' => ['prefix' => 'VRC', 'date_format' => 'Ymd', 'reset_period' => 'DAILY', 'padding' => 4, 'pattern' => '{prefix}-{date}-{seq}'],
        'RENTAL_ORDER' => ['prefix' => 'PP', 'date_format' => 'ymd', 'reset_period' => 'DAILY', 'padding' => 2, 'pattern' => '{prefix}-{date}-{seq}'],
        'RENTAL_RETURN' => ['prefix' => 'RTN', 'date_format' => 'ymd', 'reset_period' => 'DAILY', 'padding' => 2, 'pattern' => '{prefix}-{date}-{seq}'],
        'QUALITY_INSPECTION' => ['prefix' => 'QC', 'date_format' => 'Ymd', 'reset_period' => 'DAILY', 'padding' => 4, 'pattern' => '{prefix}-{date}-{seq}'],
        'SHIPMENT' => ['prefix' => 'DO', 'date_format' => 'Ymd', 'reset_period' => 'DAILY', 'padding' => 4, 'pattern' => '{prefix}-{date}-{seq}'],
    ],
];
