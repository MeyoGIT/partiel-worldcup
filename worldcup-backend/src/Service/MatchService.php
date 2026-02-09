<?php

namespace App\Service;

use App\Entity\Game;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;

class MatchService
{
    public function __construct(
        private GameRepository $gameRepository,
        private EntityManagerInterface $entityManager
    ) {}

    public function updateScore(Game $game, int $homeScore, int $awayScore): Game
    {
        $game->setHomeScore($homeScore);
        $game->setAwayScore($awayScore);

        $this->entityManager->flush();

        return $game;
    }

    public function startMatch(Game $game): Game
    {
        $game->setStatus(Game::STATUS_LIVE);
        $game->setHomeScore(0);
        $game->setAwayScore(0);

        $this->entityManager->flush();

        return $game;
    }

    public function finishMatch(Game $game, int $homeScore, int $awayScore): Game
    {
        $game->setStatus(Game::STATUS_FINISHED);
        $game->setHomeScore($homeScore);
        $game->setAwayScore($awayScore);

        $this->entityManager->flush();

        return $game;
    }

    public function getUpcomingMatches(int $limit = 5): array
    {
        return $this->gameRepository->createQueryBuilder('g')
            ->leftJoin('g.homeTeam', 'ht')
            ->leftJoin('g.awayTeam', 'at')
            ->leftJoin('g.stadium', 's')
            ->leftJoin('g.phase', 'p')
            ->addSelect('ht', 'at', 's', 'p')
            ->andWhere('g.status = :status')
            ->andWhere('g.matchDate > :now')
            ->setParameter('status', Game::STATUS_SCHEDULED)
            ->setParameter('now', new \DateTime())
            ->orderBy('g.matchDate', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getRecentResults(int $limit = 5): array
    {
        return $this->gameRepository->createQueryBuilder('g')
            ->leftJoin('g.homeTeam', 'ht')
            ->leftJoin('g.awayTeam', 'at')
            ->leftJoin('g.stadium', 's')
            ->leftJoin('g.phase', 'p')
            ->addSelect('ht', 'at', 's', 'p')
            ->andWhere('g.status = :status')
            ->setParameter('status', Game::STATUS_FINISHED)
            ->orderBy('g.matchDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
