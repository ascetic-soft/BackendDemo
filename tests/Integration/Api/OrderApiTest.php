<?php

declare(strict_types=1);

namespace Tests\Integration\Api;

use PHPUnit\Framework\Attributes\Test;
use Tests\Integration\IntegrationTestCase;

final class OrderApiTest extends IntegrationTestCase
{
    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * Create a product and return its ID.
     */
    private function createProduct(string $name = 'Test Product', int $price = 1000, string $currency = 'USD'): string
    {
        $this->post('/api/products', [
            'name' => $name,
            'price_amount' => $price,
            'price_currency' => $currency,
        ]);

        $products = $this->json($this->get('/api/products'));

        // Find by name to avoid timestamp ordering issues
        foreach ($products as $product) {
            if ($product['name'] === $name) {
                return $product['id'];
            }
        }

        self::fail("Product '{$name}' not found after creation.");
    }

    // ------------------------------------------------------------------
    // POST /api/orders — Place order
    // ------------------------------------------------------------------

    #[Test]
    public function placeOrderReturns201(): void
    {
        $productId = $this->createProduct();

        $response = $this->post('/api/orders', [
            'customer_name' => 'John Doe',
            'lines' => [
                ['product_id' => $productId, 'quantity' => 2],
            ],
        ]);

        self::assertSame(201, $response->getStatusCode());

        $body = $this->json($response);
        self::assertSame('Order placed.', $body['message']);
    }

    #[Test]
    public function placeOrderPersistsToDatabase(): void
    {
        $productId = $this->createProduct('Widget', 1500, 'EUR');

        $this->post('/api/orders', [
            'customer_name' => 'Jane Smith',
            'lines' => [
                ['product_id' => $productId, 'quantity' => 3],
            ],
        ]);

        $orders = $this->json($this->get('/api/orders'));
        self::assertCount(1, $orders);
        self::assertSame('Jane Smith', $orders[0]['customer_name']);
        self::assertSame('pending', $orders[0]['status']);
        // Total = 1500 * 3 = 4500
        self::assertSame(4500, $orders[0]['total']['amount']);
        self::assertSame('EUR', $orders[0]['total']['currency']);
    }

    #[Test]
    public function placeOrderWithMultipleLines(): void
    {
        $productA = $this->createProduct('Product A', 1000, 'USD');
        $productB = $this->createProduct('Product B', 2000, 'USD');

        $this->post('/api/orders', [
            'customer_name' => 'Multi-line Customer',
            'lines' => [
                ['product_id' => $productA, 'quantity' => 2],
                ['product_id' => $productB, 'quantity' => 1],
            ],
        ]);

        $orders = $this->json($this->get('/api/orders'));
        self::assertCount(1, $orders);

        // Get full order with lines
        $order = $this->json($this->get("/api/orders/{$orders[0]['id']}"));
        self::assertCount(2, $order['lines']);

        // Total = (1000*2) + (2000*1) = 4000
        self::assertSame(4000, $order['total']['amount']);
    }

    #[Test]
    public function placeOrderWithNonExistentProductReturns404(): void
    {
        $fakeProductId = '00000000-0000-0000-0000-000000000000';

        $response = $this->post('/api/orders', [
            'customer_name' => 'Ghost Customer',
            'lines' => [
                ['product_id' => $fakeProductId, 'quantity' => 1],
            ],
        ]);

        self::assertSame(404, $response->getStatusCode());

        $body = $this->json($response);
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function placeOrderWithEmptyCustomerNameSucceeds(): void
    {
        $productId = $this->createProduct();

        $response = $this->post('/api/orders', [
            'customer_name' => '',
            'lines' => [
                ['product_id' => $productId, 'quantity' => 1],
            ],
        ]);

        // Domain does not validate customer name — order is placed successfully
        self::assertSame(201, $response->getStatusCode());
    }

    #[Test]
    public function placeOrderWithEmptyLinesReturns400(): void
    {
        $response = $this->post('/api/orders', [
            'customer_name' => 'No Lines Customer',
            'lines' => [],
        ]);

        self::assertSame(400, $response->getStatusCode());
    }

    // ------------------------------------------------------------------
    // GET /api/orders — List
    // ------------------------------------------------------------------

    #[Test]
    public function listOrdersReturnsEmptyArrayWhenNoOrders(): void
    {
        $response = $this->get('/api/orders');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame([], $this->json($response));
    }

    #[Test]
    public function listOrdersReturnsAllOrders(): void
    {
        $productId = $this->createProduct();

        $this->post('/api/orders', [
            'customer_name' => 'Customer A',
            'lines' => [['product_id' => $productId, 'quantity' => 1]],
        ]);
        $this->post('/api/orders', [
            'customer_name' => 'Customer B',
            'lines' => [['product_id' => $productId, 'quantity' => 2]],
        ]);

        $orders = $this->json($this->get('/api/orders'));
        self::assertCount(2, $orders);
    }

    #[Test]
    public function listOrdersRespectsPagination(): void
    {
        $productId = $this->createProduct();

        for ($i = 1; $i <= 5; $i++) {
            $this->post('/api/orders', [
                'customer_name' => "Customer $i",
                'lines' => [['product_id' => $productId, 'quantity' => 1]],
            ]);
        }

        $page1 = $this->json($this->get('/api/orders', ['limit' => '2', 'offset' => '0']));
        self::assertCount(2, $page1);

        $page2 = $this->json($this->get('/api/orders', ['limit' => '2', 'offset' => '2']));
        self::assertCount(2, $page2);

        $page3 = $this->json($this->get('/api/orders', ['limit' => '2', 'offset' => '4']));
        self::assertCount(1, $page3);
    }

    // ------------------------------------------------------------------
    // GET /api/orders/{id} — Show
    // ------------------------------------------------------------------

    #[Test]
    public function showOrderReturnsFullOrderWithLines(): void
    {
        $productId = $this->createProduct('Show Test', 800, 'GBP');

        $this->post('/api/orders', [
            'customer_name' => 'Detail Customer',
            'lines' => [
                ['product_id' => $productId, 'quantity' => 5],
            ],
        ]);

        $orders = $this->json($this->get('/api/orders'));
        $id = $orders[0]['id'];

        $response = $this->get("/api/orders/{$id}");
        self::assertSame(200, $response->getStatusCode());

        $order = $this->json($response);
        self::assertSame($id, $order['id']);
        self::assertSame('pending', $order['status']);
        self::assertSame('Detail Customer', $order['customer_name']);
        self::assertSame(4000, $order['total']['amount']); // 800*5
        self::assertSame('GBP', $order['total']['currency']);
        self::assertArrayHasKey('created_at', $order);
        self::assertArrayHasKey('updated_at', $order);

        // Verify lines
        self::assertCount(1, $order['lines']);
        $line = $order['lines'][0];
        self::assertSame($productId, $line['product_id']);
        self::assertSame('Show Test', $line['product_name']);
        self::assertSame(800, $line['unit_price']['amount']);
        self::assertSame('GBP', $line['unit_price']['currency']);
        self::assertSame(5, $line['quantity']);
        self::assertSame(4000, $line['line_total']); // 800*5
    }

    #[Test]
    public function showOrderReturns404ForNonExistentId(): void
    {
        $fakeId = '00000000-0000-0000-0000-000000000000';

        $response = $this->get("/api/orders/{$fakeId}");

        self::assertSame(404, $response->getStatusCode());

        $body = $this->json($response);
        self::assertArrayHasKey('error', $body);
    }

    // ------------------------------------------------------------------
    // POST /api/orders/{id}/cancel — Cancel
    // ------------------------------------------------------------------

    #[Test]
    public function cancelOrderReturns200(): void
    {
        $productId = $this->createProduct();

        $this->post('/api/orders', [
            'customer_name' => 'Cancel Customer',
            'lines' => [['product_id' => $productId, 'quantity' => 1]],
        ]);

        $orders = $this->json($this->get('/api/orders'));
        $id = $orders[0]['id'];

        $response = $this->post("/api/orders/{$id}/cancel");

        self::assertSame(200, $response->getStatusCode());

        $body = $this->json($response);
        self::assertSame('Order cancelled.', $body['message']);
    }

    #[Test]
    public function cancelOrderChangesStatusToCancelled(): void
    {
        $productId = $this->createProduct();

        $this->post('/api/orders', [
            'customer_name' => 'Status Check',
            'lines' => [['product_id' => $productId, 'quantity' => 1]],
        ]);

        $orders = $this->json($this->get('/api/orders'));
        $id = $orders[0]['id'];

        // Verify initial status
        $order = $this->json($this->get("/api/orders/{$id}"));
        self::assertSame('pending', $order['status']);

        // Cancel
        $this->post("/api/orders/{$id}/cancel");

        // Verify cancelled status
        $order = $this->json($this->get("/api/orders/{$id}"));
        self::assertSame('cancelled', $order['status']);
    }

    #[Test]
    public function cancelNonExistentOrderReturns404(): void
    {
        $fakeId = '00000000-0000-0000-0000-000000000000';

        $response = $this->post("/api/orders/{$fakeId}/cancel");

        self::assertSame(404, $response->getStatusCode());
    }

    #[Test]
    public function cancelAlreadyCancelledOrderReturns422(): void
    {
        $productId = $this->createProduct();

        $this->post('/api/orders', [
            'customer_name' => 'Double Cancel',
            'lines' => [['product_id' => $productId, 'quantity' => 1]],
        ]);

        $orders = $this->json($this->get('/api/orders'));
        $id = $orders[0]['id'];

        // First cancel — OK
        $response = $this->post("/api/orders/{$id}/cancel");
        self::assertSame(200, $response->getStatusCode());

        // Second cancel — should fail (logic error)
        $response = $this->post("/api/orders/{$id}/cancel");
        self::assertSame(422, $response->getStatusCode());
    }

    // ------------------------------------------------------------------
    // Full lifecycle
    // ------------------------------------------------------------------

    #[Test]
    public function fullOrderLifecycle(): void
    {
        // 1. Create products
        $productA = $this->createProduct('Laptop', 100000, 'USD');
        $productB = $this->createProduct('Mouse', 2500, 'USD');

        // 2. Place order
        $createResponse = $this->post('/api/orders', [
            'customer_name' => 'Lifecycle Buyer',
            'lines' => [
                ['product_id' => $productA, 'quantity' => 1],
                ['product_id' => $productB, 'quantity' => 2],
            ],
        ]);
        self::assertSame(201, $createResponse->getStatusCode());

        // 3. List orders
        $orders = $this->json($this->get('/api/orders'));
        self::assertCount(1, $orders);
        $orderId = $orders[0]['id'];

        // 4. Show order with details
        $order = $this->json($this->get("/api/orders/{$orderId}"));
        self::assertSame('Lifecycle Buyer', $order['customer_name']);
        self::assertSame('pending', $order['status']);
        self::assertCount(2, $order['lines']);
        // Total = 100000 + (2500*2) = 105000
        self::assertSame(105000, $order['total']['amount']);

        // 5. Cancel order
        $cancelResponse = $this->post("/api/orders/{$orderId}/cancel");
        self::assertSame(200, $cancelResponse->getStatusCode());

        // 6. Verify cancelled
        $cancelled = $this->json($this->get("/api/orders/{$orderId}"));
        self::assertSame('cancelled', $cancelled['status']);
    }
}
