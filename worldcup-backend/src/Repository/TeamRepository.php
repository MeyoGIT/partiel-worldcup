<?php

namespace App\Repository;

use App\Entity\Team;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Team>
 */
class TeamRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Team::class);
    }

    /**
     * @return Team[]
     */
    public function findByGroup(string $groupName): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.groupName = :group')
            ->setParameter('group', $groupName)
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Team[]
     */
    public function findAllOrderedByGroup(): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.groupName', 'ASC')
            ->addOrderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
