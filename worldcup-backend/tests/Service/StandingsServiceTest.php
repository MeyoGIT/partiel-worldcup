<?php

namespace App\Tests\Service;

use App\Entity\Game;
use App\Entity\Team;
use App\Repository\GameRepository;
use App\Repository\TeamRepository;
use App\Service\StandingsService;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour StandingsService.
 *
 * Utilise des stubs pour simuler les repositories sans toucher la BDD.
 * On crée des équipes et des matchs fictifs, puis on vérifie que
 * le calcul de classement (points, buts, tri) est correct.
 */
class StandingsServiceTest extends TestCase
{
    private Stub&GameRepository $gameRepository;
    private Stub&TeamRepository $teamRepository;
    private StandingsService $standingsService;

    protected function setUp(): void
    {
        $this->gameRepository = $this->createStub(GameRepository::class);
        $this->teamRepository = $this->createStub(TeamRepository::class);
        $this->standingsService = new StandingsService($this->gameRepository, $this->teamRepository);
    }

    /**
     * Crée une équipe fictive pour les tests.
     * Utilise la réflexion pour forcer l'ID (normalement géré par Doctrine).
     */
    private function createTeam(int $id, string $name, string $code, string $group): Team
    {
        $team = new Team();
        $team->setName($name);
        $team->setCode($code);
        $team->setGroupName($group);

        // Forcer l'ID via réflexion car il est normalement auto-généré par la BDD
        $ref = new \ReflectionProperty(Team::class, 'id');
        $ref->setValue($team, $id);

        return $team;
    }

    /**
     * Crée un match terminé fictif avec un score donné.
     */
    private function createFinishedGame(Team $home, Team $away, int $homeScore, int $awayScore, string $group): Game
    {
        $game = new Game();
        $game->setHomeTeam($home);
        $game->setAwayTeam($away);
        $game->setHomeScore($homeScore);
        $game->setAwayScore($awayScore);
        $game->setStatus(Game::STATUS_FINISHED);
        $game->setGroupName($group);

        return $game;
    }

    /**
     * Teste le classement quand aucun match n'a été joué.
     * Toutes les stats doivent être à 0 pour les 4 équipes.
     */
    public function testCalculateStandingsWithNoGames(): void
    {
        $teams = [
            $this->createTeam(1, 'France', 'FRA', 'A'),
            $this->createTeam(2, 'Allemagne', 'GER', 'A'),
            $this->createTeam(3, 'Brésil', 'BRA', 'A'),
            $this->createTeam(4, 'Argentine', 'ARG', 'A'),
        ];

        $this->teamRepository->method('findByGroup')->willReturn($teams);
        $this->gameRepository->method('findFinishedByGroup')->willReturn([]);

        $standings = $this->standingsService->calculateStandings('A');

        $this->assertCount(4, $standings);

        foreach ($standings as $standing) {
            $this->assertEquals(0, $standing['points']);
            $this->assertEquals(0, $standing['played']);
            $this->assertEquals(0, $standing['won']);
            $this->assertEquals(0, $standing['drawn']);
            $this->assertEquals(0, $standing['lost']);
            $this->assertEquals(0, $standing['goalsFor']);
            $this->assertEquals(0, $standing['goalsAgainst']);
            $this->assertEquals(0, $standing['goalDifference']);
        }
    }

    /**
     * Teste le classement avec une victoire à domicile (France 2-0 Allemagne).
     * France doit avoir 3 points, 2 buts marqués, +2 de différence.
     * Allemagne doit avoir 0 points, 2 buts encaissés, -2 de différence.
     */
    public function testCalculateStandingsWithHomeWin(): void
    {
        $france = $this->createTeam(1, 'France', 'FRA', 'A');
        $germany = $this->createTeam(2, 'Allemagne', 'GER', 'A');

        $teams = [$france, $germany];
        $games = [
            $this->createFinishedGame($france, $germany, 2, 0, 'A'),
        ];

        $this->teamRepository->method('findByGroup')->willReturn($teams);
        $this->gameRepository->method('findFinishedByGroup')->willReturn($games);

        $standings = $this->standingsService->calculateStandings('A');

        // France gagne : 3 points, 1V 0N 0D, 2 buts marqués, 0 encaissés
        $franceStanding = $this->findStandingByCode($standings, 'FRA');
        $this->assertEquals(3, $franceStanding['points']);
        $this->assertEquals(1, $franceStanding['won']);
        $this->assertEquals(0, $franceStanding['lost']);
        $this->assertEquals(2, $franceStanding['goalsFor']);
        $this->assertEquals(0, $franceStanding['goalsAgainst']);
        $this->assertEquals(2, $franceStanding['goalDifference']);

        // Allemagne perd : 0 points, 0V 0N 1D, 0 buts marqués, 2 encaissés
        $germanyStanding = $this->findStandingByCode($standings, 'GER');
        $this->assertEquals(0, $germanyStanding['points']);
        $this->assertEquals(0, $germanyStanding['won']);
        $this->assertEquals(1, $germanyStanding['lost']);
        $this->assertEquals(0, $germanyStanding['goalsFor']);
        $this->assertEquals(2, $germanyStanding['goalsAgainst']);
        $this->assertEquals(-2, $germanyStanding['goalDifference']);
    }

    /**
     * Teste le classement avec un match nul (France 1-1 Allemagne).
     * Les deux équipes doivent avoir 1 point chacune.
     */
    public function testCalculateStandingsWithDraw(): void
    {
        $france = $this->createTeam(1, 'France', 'FRA', 'A');
        $germany = $this->createTeam(2, 'Allemagne', 'GER', 'A');

        $teams = [$france, $germany];
        $games = [
            $this->createFinishedGame($france, $germany, 1, 1, 'A'),
        ];

        $this->teamRepository->method('findByGroup')->willReturn($teams);
        $this->gameRepository->method('findFinishedByGroup')->willReturn($games);

        $standings = $this->standingsService->calculateStandings('A');

        // Match nul : 1 point chacun, 0V 1N 0D
        $franceStanding = $this->findStandingByCode($standings, 'FRA');
        $this->assertEquals(1, $franceStanding['points']);
        $this->assertEquals(0, $franceStanding['won']);
        $this->assertEquals(1, $franceStanding['drawn']);
        $this->assertEquals(0, $franceStanding['lost']);

        $germanyStanding = $this->findStandingByCode($standings, 'GER');
        $this->assertEquals(1, $germanyStanding['points']);
        $this->assertEquals(0, $germanyStanding['won']);
        $this->assertEquals(1, $germanyStanding['drawn']);
        $this->assertEquals(0, $germanyStanding['lost']);
    }

    /**
     * Teste le tri du classement avec les critères FIFA.
     *
     * Scénario : France et Brésil ont 4 points chacun, mais France
     * a une meilleure différence de buts (+3 vs +1) → France 1ère.
     *
     * Résultats simulés :
     *   France 3-0 Allemagne → France 3pts
     *   Brésil 1-0 Allemagne → Brésil 3pts
     *   France 0-0 Brésil    → France 4pts, Brésil 4pts
     *
     * Classement attendu : France (4pts, +3) > Brésil (4pts, +1) > Allemagne (0pts)
     */
    public function testCalculateStandingsSorting(): void
    {
        $france = $this->createTeam(1, 'France', 'FRA', 'A');
        $germany = $this->createTeam(2, 'Allemagne', 'GER', 'A');
        $brazil = $this->createTeam(3, 'Brésil', 'BRA', 'A');

        $teams = [$france, $germany, $brazil];
        $games = [
            $this->createFinishedGame($france, $germany, 3, 0, 'A'),
            $this->createFinishedGame($brazil, $germany, 1, 0, 'A'),
            $this->createFinishedGame($france, $brazil, 0, 0, 'A'),
        ];

        $this->teamRepository->method('findByGroup')->willReturn($teams);
        $this->gameRepository->method('findFinishedByGroup')->willReturn($games);

        $standings = $this->standingsService->calculateStandings('A');

        // 1er : France (4pts, diff +3, 3 buts marqués)
        $this->assertEquals(1, $standings[0]['position']);
        $this->assertEquals('FRA', $standings[0]['team']['code']);
        $this->assertEquals(4, $standings[0]['points']);

        // 2e : Brésil (4pts, diff +1, 1 but marqué)
        $this->assertEquals(2, $standings[1]['position']);
        $this->assertEquals('BRA', $standings[1]['team']['code']);
        $this->assertEquals(4, $standings[1]['points']);

        // 3e : Allemagne (0pts)
        $this->assertEquals(3, $standings[2]['position']);
        $this->assertEquals('GER', $standings[2]['team']['code']);
        $this->assertEquals(0, $standings[2]['points']);
    }

    /**
     * Helper : retrouve une équipe dans le classement par son code FIFA.
     */
    private function findStandingByCode(array $standings, string $code): array
    {
        foreach ($standings as $standing) {
            if ($standing['team']['code'] === $code) {
                return $standing;
            }
        }
        $this->fail("Team with code $code not found in standings");
    }
}
