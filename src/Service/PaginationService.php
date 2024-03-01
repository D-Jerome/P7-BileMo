<?php

namespace App\Service;

use App\Request\DTO\PaginationDTO;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

class PaginationService
{
    /**
     * @param QueryBuilder|Query $query
     */
    public function paginate($query, PaginationDTO $paginationDTO): Paginator
    {
        $currentPage = $paginationDTO->page ?: 1;
        $limitPerPage = $paginationDTO->limit ?: 3;
        $paginator = new Paginator($query);
        $paginator
            ->getQuery()
            ->setFirstResult($limitPerPage * ($currentPage - 1))
            ->setMaxResults($limitPerPage);

        return $paginator;
    }

    public function lastPage(Paginator $paginator): int
    {
        return (int) ceil($paginator->count() / $paginator->getQuery()->getMaxResults());
    }

    public function total(Paginator $paginator): int
    {
        return $paginator->count();
    }
}
