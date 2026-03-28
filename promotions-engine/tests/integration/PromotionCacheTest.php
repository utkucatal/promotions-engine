<?php

namespace App\Tests\integration;

use App\Cache\PromotionCache;
use App\Entity\Product;
use App\Entity\Promotion;
use App\Repository\PromotionRepository;
use App\Tests\ServiceTestCase;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Contracts\Cache\CacheInterface;

class PromotionCacheTest extends ServiceTestCase
{
    /**
     * @throws Exception
     */
    public function testCacheKeyIsProductSpecific(): void
    {
        $product = new Product();
        $reflection = new \ReflectionProperty(Product::class, 'id');
        $reflection->setValue($product, 42);

        $repositoryMock = $this->createMock(PromotionRepository::class);

        $promotion = new Promotion();
        $promotion->setName('Test Promotion');

        $repositoryMock->expects($this->once())
            ->method('findValidForProduct')
            ->willReturn([$promotion]);

        $cacheMock = $this->createMock(CacheInterface::class);
        $cacheMock->expects($this->once())
            ->method('get')
            ->willReturnCallback(function (string $key, callable $callback) {
                $this->assertSame('valid-for-product-42', $key);
                return $callback($this->createStub(\Symfony\Contracts\Cache\ItemInterface::class));
            });

        $cache = new PromotionCache($cacheMock, $repositoryMock);
        $result = $cache->findValidForProduct($product, '2026-02-12');

        $this->assertCount(1, $result);
        $this->assertSame('Test Promotion', $result[0]->getName());
    }
}