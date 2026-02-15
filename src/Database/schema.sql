-- BackendDemo Database Schema
-- E-commerce DDD Demo

CREATE TABLE IF NOT EXISTS products (
    id           CHAR(36)     NOT NULL PRIMARY KEY,
    name         VARCHAR(255) NOT NULL,
    price_amount INT          NOT NULL DEFAULT 0,
    price_currency CHAR(3)    NOT NULL DEFAULT 'USD',
    description  TEXT         NOT NULL DEFAULT '',
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_products_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS orders (
    id             CHAR(36)     NOT NULL PRIMARY KEY,
    status         VARCHAR(20)  NOT NULL DEFAULT 'pending',
    customer_name  VARCHAR(255) NOT NULL,
    total_amount   INT          NOT NULL DEFAULT 0,
    total_currency CHAR(3)      NOT NULL DEFAULT 'USD',
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_orders_status (status),
    INDEX idx_orders_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS order_lines (
    order_id           CHAR(36)     NOT NULL,
    position           INT          NOT NULL DEFAULT 0,
    product_id         CHAR(36)     NOT NULL,
    product_name       VARCHAR(255) NOT NULL,
    unit_price_amount  INT          NOT NULL DEFAULT 0,
    unit_price_currency CHAR(3)     NOT NULL DEFAULT 'USD',
    quantity           INT          NOT NULL DEFAULT 1,

    PRIMARY KEY (order_id, position),
    CONSTRAINT fk_order_lines_order
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,

    INDEX idx_order_lines_product_id (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
