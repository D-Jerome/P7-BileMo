<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Customer;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository <User>
 *
 * @method Customer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Customer|null findOneBy(array $criteria, array $orderBy = null)
 * @method Customer[]    findAll()
 * @method Customer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * [Description for findAll].
     */
    public function findAllQuery(): Query
    {
        $qb = $this->createQueryBuilder('u');

        return $qb->getQuery();
    }

    /**
     * [Description for findBy].
     *
     * @param array<string,Customer> $customer
     */
    public function findByQuery(array $customer): Query
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.customer = :customer')
            ->setParameter('customer', $customer)
            ->orderBy('u.id', 'ASC')
            ->getQuery()
        ;
    }
}
