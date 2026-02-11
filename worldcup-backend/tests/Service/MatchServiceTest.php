<?php

namespace App\Tests\Service;

use App\Entity\Game;
use App\Repository\GameRepository;
use App\Service\MatchService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour MatchService.
 *
 * Utilise des mocks/stubs pour isoler le service de la BDD :
 * - GameRepository est un stub (pas d'attentes, juste des retours simulés)
 * - EntityManager est un mock (on vérifie que flush() est bien appelé)
 *
 * Chaque test vérifie une transition d'état du match.
 */
class MatchServiceTest extends TestCase
{
    private Stub&GameRepository $gameRepository;
    private MockObject&EntityManagerInterface $entityManager;
    private MatchService $matchService;

    /**
     * Initialisation avant chaque test : crée les stubs/mocks
     * et instancie le service avec ces fausses dépendances.
     */
    protected function setUp(): void
    {
        $this->gameRepository = $this->createStub(GameRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->matchService = new MatchService($this->gameRepository, $this->entityManager);
    }

    /**
     * Teste startMatch() : scheduled → live.
     * Vérifie que le status passe à "live", les scores à 0-0,
     * et que flush() est appelé pour persister en BDD.
     */
    public function testStartMatch(): void
    {
        $game = new Game();
        $game->setStatus(Game::STATUS_SCHEDULED);

        // Vérifie que flush() est appelé exactement une fois
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->matchService->startMatch($game);

        $this->assertSame(Game::STATUS_LIVE, $result->getStatus());
        $this->assertSame(0, $result->getHomeScore());
        $this->assertSame(0, $result->getAwayScore());
    }

    /**
     * Test unitaire : modification du score d'un match en cours.
     * Vérifie que les scores sont mis à jour et persistés en BDD.
     */
    public function testUpdateScore(): void
    {
        // Créer un match fictif en mémoire (pas en BDD), déjà en cours avec score 0-0
        $game = new Game();
        $game->setStatus(Game::STATUS_LIVE);
        $game->setHomeScore(0);
        $game->setAwayScore(0);

        // Vérifier que flush() sera appelé exactement 1 fois
        $this->entityManager->expects($this->once())->method('flush');

        // Appeler la méthode testée : passer le score à 2-1
        $result = $this->matchService->updateScore($game, 2, 1);

        // Vérifier que l'objet Game retourné a les bons scores
        $this->assertSame(2, $result->getHomeScore());
        $this->assertSame(1, $result->getAwayScore());
    }

    /**
     * Teste finishMatch() : live → finished.
     * Vérifie que le status passe à "finished" avec le score final.
     */
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
