<?php

namespace App\Repository;

use App\Entity\Product;
use App\Service\ServiceException;
use App\Service\ServiceExceptionData;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function findOrFail(int $id): Product
    {
        $product = $this->find($id);
        if (!$product) {
            throw new ServiceException(new ServiceExceptionData(404, 'Product Not Found'));
        }

        return $product;
    }

    public function add(Product $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush){
            $this->_em->flush();
        }
    }
}
