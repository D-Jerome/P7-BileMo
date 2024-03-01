<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 *
 * @method Customer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Customer|null findOneBy(array $criteria, array $orderBy = null)
 * @method Customer[]    findAll()
 * @method Customer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * [Description for findAllWithPagination].
     */
    public function findAllQuery(): Query
    {
        return $this->createQueryBuilder('p')
            ->getQuery()
        ;
    }

    /**
     * [Description for findByWithPagination].
     */
    public function findByQuery(string $brand): Query
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.brand = :brand')
            ->setParameter('brand', $brand)
            ->orderBy('p.id', 'ASC')
            ->getQuery()
        ;
    }
}
