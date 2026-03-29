<?php

namespace App\Tests\integration;

use App\Entity\Promotion;
use App\Tests\ServiceTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class PromotionsControllerTest extends ServiceTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();
        $this->em = $this->container->get(EntityManagerInterface::class);
    }

    private function createPromotion(): Promotion
    {
        $promotion = new Promotion();
        $promotion->setName('Test Promotion');
        $promotion->setType('date_range_multiplier');
        $promotion->setAdjustment(0.5);
        $promotion->setCriteria(['from' => '2026-01-01', 'to' => '2026-12-31']);
        $this->em->persist($promotion);
        $this->em->flush();
        return $promotion;
    }

    private function deletePromotion(Promotion $promotion): void
    {
        $this->em->remove($promotion);
        $this->em->flush();
    }

    // LIST

    public function testListReturns200(): void
    {
        $this->client->request('GET', '/promotions');

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testListReturnsPromotionResponseFormat(): void
    {
        $promotion = $this->createPromotion();

        $this->client->request('GET', '/promotions');

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $found = array_filter($data, fn($p) => $p['id'] === $promotion->getId());
        $item = array_values($found)[0];

        $this->assertArrayHasKey('id', $item);
        $this->assertArrayHasKey('name', $item);
        $this->assertArrayHasKey('type', $item);
        $this->assertArrayHasKey('adjustment', $item);
        $this->assertArrayHasKey('criteria', $item);
        $this->assertArrayNotHasKey('product_promotions', $item);

        $this->deletePromotion($promotion);
    }

    // SHOW

    public function testShowReturns200ForValidPromotion(): void
    {
        $promotion = $this->createPromotion();

        $this->client->request('GET', '/promotions/' . $promotion->getId());

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->deletePromotion($promotion);
    }

    public function testShowReturns404ForNonExistentPromotion(): void
    {
        $this->client->request('GET', '/promotions/9999999');

        $this->assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    // CREATE

    public function testCreateReturns201WithValidBody(): void
    {
        $this->client->request('POST', '/promotions', [], [], $this->withApiKey(['CONTENT_TYPE' => 'application/json']), json_encode([
            'name'       => 'New Promotion',
            'type'       => 'date_range_multiplier',
            'adjustment' => 0.7,
            'criteria'   => ['from' => '2026-01-01', 'to' => '2026-12-31'],
        ]));

        $this->assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);

        $promotion = $this->em->find(Promotion::class, $data['id']);
        $this->deletePromotion($promotion);
    }

    public function testCreateReturns422WithMissingFields(): void
    {
        $this->client->request('POST', '/promotions', [], [], $this->withApiKey(['CONTENT_TYPE' => 'application/json']), json_encode([
            'name' => 'Incomplete Promotion',
        ]));

        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
    }

    // UPDATE

    public function testUpdateReturns200ForValidPromotion(): void
    {
        $promotion = $this->createPromotion();

        $this->client->request('PUT', '/promotions/' . $promotion->getId(), [], [], $this->withApiKey(['CONTENT_TYPE' => 'application/json']), json_encode([
            'name' => 'Updated Promotion',
        ]));

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->em->refresh($promotion);
        $this->assertSame('Updated Promotion', $promotion->getName());

        $this->deletePromotion($promotion);
    }

    public function testUpdateOnlyChangesProvidedFields(): void
    {
        $promotion = $this->createPromotion();
        $originalType = $promotion->getType();

        $this->client->request('PUT', '/promotions/' . $promotion->getId(), [], [], $this->withApiKey(['CONTENT_TYPE' => 'application/json']), json_encode([
            'name' => 'Partially Updated',
        ]));

        $this->em->refresh($promotion);
        $this->assertSame('Partially Updated', $promotion->getName());
        $this->assertSame($originalType, $promotion->getType());

        $this->deletePromotion($promotion);
    }

    public function testUpdateReturns404ForNonExistentPromotion(): void
    {
        $this->client->request('PUT', '/promotions/9999999', [], [], $this->withApiKey(['CONTENT_TYPE' => 'application/json']), json_encode([
            'name' => 'Ghost',
        ]));

        $this->assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    // DELETE

    public function testDeleteReturns204ForValidPromotion(): void
    {
        $promotion = $this->createPromotion();

        $this->client->request('DELETE', '/promotions/' . $promotion->getId(), [], [], $this->withApiKey());

        $this->assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }

    public function testDeleteReturns404ForNonExistentPromotion(): void
    {
        $this->client->request('DELETE', '/promotions/9999999', [], [], $this->withApiKey());

        $this->assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
