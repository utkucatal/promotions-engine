<?php

namespace App\Tests\integration;

use App\Entity\Product;
use App\Tests\ServiceTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\HttpFoundation\Response;

class ProductsControllerTest extends ServiceTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var EntityManagerInterface $em */
        $em = $this->container->get(EntityManagerInterface::class);
        $this->em = $em;
    }

    private function createProduct(): Product
    {
        $product = new Product();
        $product->setPrice(1000);
        $this->em->persist($product);
        $this->em->flush();
        return $product;
    }

    private function deleteProduct(Product $product): void
    {
        $this->em->remove($product);
        $this->em->flush();
    }

    public function testLowestPriceReturns200ForValidProduct(): void
    {
        $product = $this->createProduct();
        $this->client->request(
            'POST',
            '/products/' . $product->getId() . '/lowest-price',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'quantity' => 5,
                'request_date' => '2026-02-12',
                'voucher_code' => 'OU812'
            ])
        );

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('discounted_price', $data);
        $this->assertArrayHasKey('promotion_name', $data);

        $this->deleteProduct($product);
    }

    public function testLowestPriceReturns404ForNonExistentProduct(): void
    {
        $this->client->request(
            'POST',
            '/products/9999999/lowest-price',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'quantity' => 5,
                'request_date' => '2026-02-12',
                'voucher_code' => 'OU812'
            ])
        );

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testLowestPriceReturns422WhenQuantityIsInvalid(): void
    {
        $product = $this->createProduct();
        $this->client->request(
            'POST',
            '/products/'.$product->getId().'/lowest-price',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'quantity' => -5,
                'request_date' => '2026-02-12',
                'voucher_code' => 'OU812'
            ])
        );

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    public function testLowestPriceReturns422WhenRequestDateIsMissing(): void
    {
        $product = $this->createProduct();
        $this->client->request(
            'POST',
            '/products/'.$product->getId().'/lowest-price',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'quantity' => 5,
                'voucher_code' => 'OU812'
            ])
        );

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }
}