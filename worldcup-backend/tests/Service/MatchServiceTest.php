<?php

namespace App\Tests\Service;

use App\Entity\Game;
use App\Repository\GameRepository;
use App\Service\MatchService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

class MatchServiceTest extends TestCase
{
    private Stub&GameRepository $gameRepository;
    private MockObject&EntityManagerInterface $entityManager;
    private MatchService $matchService;

    protected function setUp(): void
    {
        $this->gameRepository = $this->createStub(GameRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->matchService = new MatchService($this->gameRepository, $this->entityManager);
    }

    public function testStartMatch(): void
    {
        $game = new Game();
        $game->setStatus(Game::STATUS_SCHEDULED);

        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->matchService->startMatch($game);

        $this->assertSame(Game::STATUS_LIVE, $result->getStatus());
        $this->assertSame(0, $result->getHomeScore());
        $this->assertSame(0, $result->getAwayScore());
    }

    public function testUpdateScore(): void
    {
        $game = new Game();
        $game->setStatus(Game::STATUS_LIVE);
        $game->setHomeScore(0);
        $game->setAwayScore(0);

        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->matchService->updateScore($game, 2, 1);

        $this->assertSame(2, $result->getHomeScore());
        $this->assertSame(1, $result->getAwayScore());
    }

    public function testFinishMatch(): void
    {
        $game = new Game();
        $game->setStatus(Game::STATUS_LIVE);
        $game->setHomeScore(1);
        $game->setAwayScore(1);

        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->matchService->finishMatch($game, 3, 2);

        $this->assertSame(Game::STATUS_FINISHED, $result->getStatus());
        $this->assertSame(3, $result->getHomeScore());
        $this->assertSame(2, $result->getAwayScore());
    }
}
