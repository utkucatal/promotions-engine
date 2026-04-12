<?php

namespace App\EventListener;

use App\Entity\Product;
use App\Search\ElasticsearchService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsEntityListener(event: Events::postPersist, entity: Product::class)]
#[AsEntityListener(event: Events::postUpdate, entity: Product::class)]
#[AsEntityListener(event: Events::postRemove, entity: Product::class)]
readonly class ElasticsearchSyncListener
{
    public function __construct(
        private ElasticsearchService   $es,
        private TagAwareCacheInterface $cache,
    ){}

    /**
     * @throws InvalidArgumentException
     */
    public function postPersist(Product $entity): void
    {
        $this->indexEntity($entity);
        $this->cache->invalidateTags(['search-products']);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function postUpdate(Product $entity): void
    {
        $this->indexEntity($entity);
        $this->cache->invalidateTags(['search-products']);
    }

    /**
     * @throws ClientResponseException
     * @throws ServerResponseException
     * @throws MissingParameterException|InvalidArgumentException
     */
    public function postRemove(Product $entity): void
    {
        $this->es->delete('products', $entity->getId());
        $this->cache->invalidateTags(['search-products']);
    }

    private function indexEntity(Product $entity): void
    {
        $this->es->index('products', $entity->getId(), [
            'id' => $entity->getId(),
            'name' => $entity->getName(),
            'sku' => $entity->getSku(),
            'description' => $entity->getDescription(),
            'price' => $entity->getPrice(),
        ]);
    }
}
