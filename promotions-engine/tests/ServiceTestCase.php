<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ServiceTestCase extends WebTestCase
{
    protected ContainerInterface $container;
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->container = $this->client->getContainer();
    }

    protected function withApiKey(array $server = []): array
    {
        return array_merge(['HTTP_Access_Token' => $_ENV['API_KEY']], $server);
    }
}
