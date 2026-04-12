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
#[AsEntityListener(event: Events::preRemove, entity: Product::class)]
class ElasticsearchSyncListener
{
    private array $pendingDeletes = [];

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

    public function preRemove(Product $entity): void
    {
        $this->pendingDeletes[spl_object_id($entity)] = $entity->getId();
    }

    /**
     * @throws ClientResponseException
     * @throws ServerResponseException
     * @throws MissingParameterException|InvalidArgumentException
     */
    public function postRemove(Product $entity): void
    {
        $id = $this->pendingDeletes[spl_object_id($entity)] ?? null;
        unset($this->pendingDeletes[spl_object_id($entity)]);
        if ($id !== null) {
            $this->es->delete('products', $id);
            $this->cache->invalidateTags(['search-products']);
        }
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
