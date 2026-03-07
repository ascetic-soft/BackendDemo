<?php

declare(strict_types=1);

return [
    'tables' => [
        'products' => [
            'columns' => [
                'id' => ['type' => 'string', 'length' => 36, 'primaryKey' => true],
                'name' => ['type' => 'string', 'length' => 255],
                'price_amount' => ['type' => 'integer', 'default' => 0],
                'price_currency' => ['type' => 'string', 'length' => 3, 'default' => 'USD'],
                'description' => ['type' => 'text'],
                'created_at' => ['type' => 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
                'updated_at' => ['type' => 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
            ],
        ],
        'orders' => [
            'columns' => [
                'id' => ['type' => 'string', 'length' => 36, 'primaryKey' => true],
                'status' => ['type' => 'string', 'length' => 20, 'default' => 'pending'],
                'customer_name' => ['type' => 'string', 'length' => 255],
                'total_amount' => ['type' => 'integer', 'default' => 0],
                'total_currency' => ['type' => 'string', 'length' => 3, 'default' => 'USD'],
                'created_at' => ['type' => 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
                'updated_at' => ['type' => 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
            ],
        ],
        'order_lines' => [
            'columns' => [
                'order_id' => ['type' => 'string', 'length' => 36, 'primaryKey' => true],
                'position' => ['type' => 'integer', 'default' => 0, 'primaryKey' => true],
                'product_id' => ['type' => 'string', 'length' => 36],
                'product_name' => ['type' => 'string', 'length' => 255],
                'unit_price_amount' => ['type' => 'integer', 'default' => 0],
                'unit_price_currency' => ['type' => 'string', 'length' => 3, 'default' => 'USD'],
                'quantity' => ['type' => 'integer', 'default' => 1],
            ],
            'primaryKey' => ['order_id', 'position'],
        ],
    ],
];
