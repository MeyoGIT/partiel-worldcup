<?php

namespace App\Repository;

use App\Entity\Game;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Game>
 */
class GameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }

    /**
     * @return Game[]
     */
    public function findByFilters(
        ?string $phase = null,
        ?string $group = null,
        ?string $status = null,
        ?string $date = null,
        int $page = 1,
        int $limit = 20
    ): array {
        $qb = $this->createQueryBuilder('g')
            ->leftJoin('g.homeTeam', 'ht')
            ->leftJoin('g.awayTeam', 'at')
            ->leftJoin('g.stadium', 's')
            ->leftJoin('g.phase', 'p')
            ->addSelect('ht', 'at', 's', 'p');

        if ($phase !== null) {
            $qb->andWhere('p.code = :phase')
               ->setParameter('phase', $phase);
        }

        if ($group !== null) {
            $qb->andWhere('g.groupName = :group')
               ->setParameter('group', $group);
        }

        if ($status !== null) {
            $qb->andWhere('g.status = :status')
               ->setParameter('status', $status);
        }

        if ($date !== null) {
            $dateObj = new \DateTime($date);
            $nextDay = (clone $dateObj)->modify('+1 day');
            $qb->andWhere('g.matchDate >= :dateStart')
               ->andWhere('g.matchDate < :dateEnd')
               ->setParameter('dateStart', $dateObj)
               ->setParameter('dateEnd', $nextDay);
        }

        $qb->orderBy('g.matchDate', 'ASC')
           ->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function countByFilters(
        ?string $phase = null,
        ?string $group = null,
        ?string $status = null,
        ?string $date = null
    ): int {
        $qb = $this->createQueryBuilder('g')
            ->select('COUNT(g.id)')
            ->leftJoin('g.phase', 'p');

        if ($phase !== null) {
            $qb->andWhere('p.code = :phase')
               ->setParameter('phase', $phase);
        }

        if ($group !== null) {
            $qb->andWhere('g.groupName = :group')
               ->setParameter('group', $group);
        }

        if ($status !== null) {
            $qb->andWhere('g.status = :status')
               ->setParameter('status', $status);
        }

        if ($date !== null) {
            $dateObj = new \DateTime($date);
            $nextDay = (clone $dateObj)->modify('+1 day');
            $qb->andWhere('g.matchDate >= :dateStart')
               ->andWhere('g.matchDate < :dateEnd')
               ->setParameter('dateStart', $dateObj)
               ->setParameter('dateEnd', $nextDay);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return Game[]
     */
    public function findLiveGames(): array
    {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.homeTeam', 'ht')
            ->leftJoin('g.awayTeam', 'at')
            ->leftJoin('g.stadium', 's')
            ->leftJoin('g.phase', 'p')
            ->addSelect('ht', 'at', 's', 'p')
            ->andWhere('g.status = :status')
            ->setParameter('status', Game::STATUS_LIVE)
            ->orderBy('g.matchDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Game[]
     */
    public function findTodayGames(): array
    {
        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        $tomorrow = (clone $today)->modify('+1 day');

        return $this->createQueryBuilder('g')
            ->leftJoin('g.homeTeam', 'ht')
            ->leftJoin('g.awayTeam', 'at')
            ->leftJoin('g.stadium', 's')
            ->leftJoin('g.phase', 'p')
            ->addSelect('ht', 'at', 's', 'p')
            ->andWhere('g.matchDate >= :today')
            ->andWhere('g.matchDate < :tomorrow')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->orderBy('g.matchDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Game[]
     */
    public function findByPhaseCode(string $phaseCode): array
    {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.homeTeam', 'ht')
            ->leftJoin('g.awayTeam', 'at')
            ->leftJoin('g.stadium', 's')
            ->leftJoin('g.phase', 'p')
            ->addSelect('ht', 'at', 's', 'p')
            ->andWhere('p.code = :code')
            ->setParameter('code', $phaseCode)
            ->orderBy('g.matchDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Game[]
     */
    public function findByGroup(string $groupName): array
    {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.homeTeam', 'ht')
            ->leftJoin('g.awayTeam', 'at')
            ->leftJoin('g.stadium', 's')
            ->leftJoin('g.phase', 'p')
            ->addSelect('ht', 'at', 's', 'p')
            ->andWhere('g.groupName = :group')
            ->setParameter('group', $groupName)
            ->orderBy('g.matchDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Game[]
     */
    public function findFinishedByGroup(string $groupName): array
    {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.homeTeam', 'ht')
            ->leftJoin('g.awayTeam', 'at')
            ->addSelect('ht', 'at')
            ->andWhere('g.groupName = :group')
            ->andWhere('g.status = :status')
            ->setParameter('group', $groupName)
            ->setParameter('status', Game::STATUS_FINISHED)
            ->orderBy('g.matchDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
