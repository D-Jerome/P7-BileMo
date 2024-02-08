<?php

declare(strict_types=1);

namespace App\EntityListener;

use App\Entity\Customer;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsEntityListener(event: Events::prePersist, entity: Customer::class)]
#[AsEntityListener(event: Events::preUpdate, entity: Customer::class)]
class CustomerEntityListener
{
    public function __construct(
        private SluggerInterface $slugger,
    ) {
    }

    public function prePersist(Customer $customer): void
    {
        $customer->computeSlug($this->slugger);
    }

    public function preUpdate(Customer $customer): void
    {
        $customer->computeSlug($this->slugger);
    }
}
