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
    private int $productId;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var EntityManagerInterface $em */
        $em = $this->container->get(EntityManagerInterface::class);
        $this->em = $em;

        $product = new Product();
        $product->setPrice(1000);
        $this->em->persist($product);
        $this->em->flush();

        $this->productId = $product->getId();
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    protected function tearDown(): void
    {
        $product = $this->em->find(Product::class, $this->productId);
        if ($product) {
            $this->em->remove($product);
            $this->em->flush();
        }

        parent::tearDown();
    }

    public function testLowestPriceReturns200ForValidProduct(): void
    {
        $this->client->request(
            'POST',
            '/products/' . $this->productId . '/lowest-price',
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
    }
}