<?php

namespace App\Repository;

use App\Entity\Stadium;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Stadium>
 */
class StadiumRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Stadium::class);
    }

    /**
     * @return Stadium[]
     */
    public function findByCountry(string $country): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.country = :country')
            ->setParameter('country', $country)
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Stadium[]
     */
    public function findAllOrderedByCapacity(): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.capacity', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
