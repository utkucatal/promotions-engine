<?php

namespace App\Tests\integration;

use App\Entity\Promotion;
use App\Tests\ServiceTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuthenticatorTest extends ServiceTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();
        $this->em = $this->container->get(EntityManagerInterface::class);
    }

    public function testWriteEndpointWithoutKeyReturns401(): void
    {
        $this->client->request('POST', '/promotions', [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testWriteEndpointWithWrongKeyReturns401(): void
    {
        $this->client->request('POST', '/promotions', [], [], [
            'CONTENT_TYPE'   => 'application/json',
            'HTTP_Access_Token' => 'wrong-key',
        ], '{}');
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function testWriteEndpointWithCorrectKeyPasses(): void
    {
        $this->client->request('POST', '/promotions', [], [], [
            'CONTENT_TYPE'   => 'application/json',
            'HTTP_Access_Token' => $_ENV['API_KEY'],
        ], json_encode([
            'name'       => 'Auth Test',
            'type'       => 'date_range_multiplier',
            'adjustment' => 0.9,
            'criteria'   => ['from' => '2026-01-01', 'to' => '2026-12-31'],
        ]));

        $this->assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $promotion = $this->em->find(Promotion::class, $data['id']);
        $this->em->remove($promotion);
        $this->em->flush();
    }

    public function testReadEndpointWithoutKeyReturns200(): void
    {
        $this->client->request('GET', '/promotions');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

}