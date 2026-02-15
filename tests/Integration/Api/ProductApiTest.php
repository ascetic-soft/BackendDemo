<?php

declare(strict_types=1);

namespace Tests\Integration\Api;

use PHPUnit\Framework\Attributes\Test;
use Tests\Integration\IntegrationTestCase;

final class ProductApiTest extends IntegrationTestCase
{
    // ------------------------------------------------------------------
    // POST /api/products — Create
    // ------------------------------------------------------------------

    #[Test]
    public function createProductReturns201(): void
    {
        $response = $this->post('/api/products', [
            'name' => 'Widget',
            'price_amount' => 1999,
            'price_currency' => 'USD',
            'description' => 'A nice widget',
        ]);

        self::assertSame(201, $response->getStatusCode());

        $body = $this->json($response);
        self::assertSame('Product created.', $body['message']);
    }

    #[Test]
    public function createProductPersistsToDatabase(): void
    {
        $this->post('/api/products', [
            'name' => 'Gadget',
            'price_amount' => 4500,
            'price_currency' => 'EUR',
            'description' => 'A fancy gadget',
        ]);

        $response = $this->get('/api/products');
        $products = $this->json($response);

        self::assertCount(1, $products);
        self::assertSame('Gadget', $products[0]['name']);
        self::assertSame(4500, $products[0]['price']['amount']);
        self::assertSame('EUR', $products[0]['price']['currency']);
        self::assertSame('A fancy gadget', $products[0]['description']);
    }

    #[Test]
    public function createProductWithEmptyNameReturns400(): void
    {
        $response = $this->post('/api/products', [
            'name' => '',
            'price_amount' => 100,
        ]);

        self::assertSame(400, $response->getStatusCode());

        $body = $this->json($response);
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function createProductWithNegativePriceReturns400(): void
    {
        $response = $this->post('/api/products', [
            'name' => 'Bad Price',
            'price_amount' => -1,
        ]);

        self::assertSame(400, $response->getStatusCode());
    }

    // ------------------------------------------------------------------
    // GET /api/products — List
    // ------------------------------------------------------------------

    #[Test]
    public function listProductsReturnsEmptyArrayWhenNoProducts(): void
    {
        $response = $this->get('/api/products');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame([], $this->json($response));
    }

    #[Test]
    public function listProductsReturnsAllCreatedProducts(): void
    {
        $this->post('/api/products', [
            'name' => 'Product A',
            'price_amount' => 1000,
        ]);
        $this->post('/api/products', [
            'name' => 'Product B',
            'price_amount' => 2000,
        ]);

        $response = $this->get('/api/products');
        $products = $this->json($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertCount(2, $products);
    }

    #[Test]
    public function listProductsRespectsPagination(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->post('/api/products', [
                'name' => "Product {$i}",
                'price_amount' => $i * 100,
            ]);
        }

        $response = $this->get('/api/products', ['limit' => '2', 'offset' => '0']);
        $page1 = $this->json($response);
        self::assertCount(2, $page1);

        $response = $this->get('/api/products', ['limit' => '2', 'offset' => '2']);
        $page2 = $this->json($response);
        self::assertCount(2, $page2);

        $response = $this->get('/api/products', ['limit' => '2', 'offset' => '4']);
        $page3 = $this->json($response);
        self::assertCount(1, $page3);
    }

    // ------------------------------------------------------------------
    // GET /api/products/{id} — Show
    // ------------------------------------------------------------------

    #[Test]
    public function showProductReturnsProductById(): void
    {
        $this->post('/api/products', [
            'name' => 'SingleProduct',
            'price_amount' => 3000,
            'price_currency' => 'GBP',
            'description' => 'Single item',
        ]);

        // Retrieve the product ID from the list
        $products = $this->json($this->get('/api/products'));
        $id = $products[0]['id'];

        $response = $this->get("/api/products/{$id}");

        self::assertSame(200, $response->getStatusCode());

        $product = $this->json($response);
        self::assertSame($id, $product['id']);
        self::assertSame('SingleProduct', $product['name']);
        self::assertSame(3000, $product['price']['amount']);
        self::assertSame('GBP', $product['price']['currency']);
        self::assertSame('Single item', $product['description']);
        self::assertArrayHasKey('created_at', $product);
        self::assertArrayHasKey('updated_at', $product);
    }

    #[Test]
    public function showProductReturns404ForNonExistentId(): void
    {
        $fakeId = '00000000-0000-0000-0000-000000000000';

        $response = $this->get("/api/products/{$fakeId}");

        self::assertSame(404, $response->getStatusCode());

        $body = $this->json($response);
        self::assertArrayHasKey('error', $body);
    }

    // ------------------------------------------------------------------
    // PUT /api/products/{id} — Update
    // ------------------------------------------------------------------

    #[Test]
    public function updateProductChangesName(): void
    {
        $this->post('/api/products', [
            'name' => 'OldName',
            'price_amount' => 1000,
        ]);

        $products = $this->json($this->get('/api/products'));
        $id = $products[0]['id'];

        $response = $this->put("/api/products/{$id}", [
            'name' => 'NewName',
        ]);

        self::assertSame(200, $response->getStatusCode());

        $updated = $this->json($this->get("/api/products/{$id}"));
        self::assertSame('NewName', $updated['name']);
        // Price should remain unchanged
        self::assertSame(1000, $updated['price']['amount']);
    }

    #[Test]
    public function updateProductChangesPrice(): void
    {
        $this->post('/api/products', [
            'name' => 'PriceTest',
            'price_amount' => 500,
            'price_currency' => 'USD',
        ]);

        $products = $this->json($this->get('/api/products'));
        $id = $products[0]['id'];

        $response = $this->put("/api/products/{$id}", [
            'price_amount' => 999,
            'price_currency' => 'EUR',
        ]);

        self::assertSame(200, $response->getStatusCode());

        $updated = $this->json($this->get("/api/products/{$id}"));
        self::assertSame(999, $updated['price']['amount']);
        self::assertSame('EUR', $updated['price']['currency']);
        // Name should remain unchanged
        self::assertSame('PriceTest', $updated['name']);
    }

    #[Test]
    public function updateProductChangesDescription(): void
    {
        $this->post('/api/products', [
            'name' => 'DescTest',
            'price_amount' => 100,
            'description' => 'old description',
        ]);

        $products = $this->json($this->get('/api/products'));
        $id = $products[0]['id'];

        $this->put("/api/products/{$id}", [
            'description' => 'new description',
        ]);

        $updated = $this->json($this->get("/api/products/{$id}"));
        self::assertSame('new description', $updated['description']);
    }

    #[Test]
    public function updateNonExistentProductReturns404(): void
    {
        $fakeId = '00000000-0000-0000-0000-000000000000';

        $response = $this->put("/api/products/{$fakeId}", [
            'name' => 'Ghost',
        ]);

        self::assertSame(404, $response->getStatusCode());
    }

    // ------------------------------------------------------------------
    // Full lifecycle
    // ------------------------------------------------------------------

    #[Test]
    public function fullProductLifecycle(): void
    {
        // 1. Create
        $createResponse = $this->post('/api/products', [
            'name' => 'Lifecycle Item',
            'price_amount' => 2500,
            'price_currency' => 'USD',
            'description' => 'For lifecycle test',
        ]);
        self::assertSame(201, $createResponse->getStatusCode());

        // 2. List — should appear
        $products = $this->json($this->get('/api/products'));
        self::assertCount(1, $products);
        $id = $products[0]['id'];

        // 3. Show
        $product = $this->json($this->get("/api/products/{$id}"));
        self::assertSame('Lifecycle Item', $product['name']);

        // 4. Update
        $this->put("/api/products/{$id}", [
            'name' => 'Updated Lifecycle',
            'price_amount' => 3500,
            'description' => 'Updated description',
        ]);

        // 5. Verify update
        $updated = $this->json($this->get("/api/products/{$id}"));
        self::assertSame('Updated Lifecycle', $updated['name']);
        self::assertSame(3500, $updated['price']['amount']);
        self::assertSame('Updated description', $updated['description']);
    }
}
