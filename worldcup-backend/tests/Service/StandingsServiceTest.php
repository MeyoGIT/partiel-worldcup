<?php

namespace App\Tests\Service;

use App\Entity\Game;
use App\Entity\Team;
use App\Repository\GameRepository;
use App\Repository\TeamRepository;
use App\Service\StandingsService;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

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

    private function createTeam(int $id, string $name, string $code, string $group): Team
    {
        $team = new Team();
        $team->setName($name);
        $team->setCode($code);
        $team->setGroupName($group);

        $ref = new \ReflectionProperty(Team::class, 'id');
        $ref->setValue($team, $id);

        return $team;
    }

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

        // France gagne : 3 points
        $franceStanding = $this->findStandingByCode($standings, 'FRA');
        $this->assertEquals(3, $franceStanding['points']);
        $this->assertEquals(1, $franceStanding['won']);
        $this->assertEquals(0, $franceStanding['lost']);
        $this->assertEquals(2, $franceStanding['goalsFor']);
        $this->assertEquals(0, $franceStanding['goalsAgainst']);
        $this->assertEquals(2, $franceStanding['goalDifference']);

        // Allemagne perd : 0 points
        $germanyStanding = $this->findStandingByCode($standings, 'GER');
        $this->assertEquals(0, $germanyStanding['points']);
        $this->assertEquals(0, $germanyStanding['won']);
        $this->assertEquals(1, $germanyStanding['lost']);
        $this->assertEquals(0, $germanyStanding['goalsFor']);
        $this->assertEquals(2, $germanyStanding['goalsAgainst']);
        $this->assertEquals(-2, $germanyStanding['goalDifference']);
    }

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

        // Les deux équipes ont 1 point
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

    public function testCalculateStandingsSorting(): void
    {
        $france = $this->createTeam(1, 'France', 'FRA', 'A');
        $germany = $this->createTeam(2, 'Allemagne', 'GER', 'A');
        $brazil = $this->createTeam(3, 'Brésil', 'BRA', 'A');

        $teams = [$france, $germany, $brazil];
        $games = [
            // France bat Allemagne 3-0 => France 3pts
            $this->createFinishedGame($france, $germany, 3, 0, 'A'),
            // Brésil bat Allemagne 1-0 => Brésil 3pts
            $this->createFinishedGame($brazil, $germany, 1, 0, 'A'),
            // France et Brésil font match nul 0-0 => France 4pts, Brésil 4pts
            $this->createFinishedGame($france, $brazil, 0, 0, 'A'),
        ];

        $this->teamRepository->method('findByGroup')->willReturn($teams);
        $this->gameRepository->method('findFinishedByGroup')->willReturn($games);

        $standings = $this->standingsService->calculateStandings('A');

        // France : 4pts, GD=+3, GF=3
        // Brésil : 4pts, GD=+1, GF=1
        // Allemagne : 0pts, GD=-4, GF=0
        // Tri : points DESC, puis GD DESC, puis GF DESC
        $this->assertEquals(1, $standings[0]['position']);
        $this->assertEquals('FRA', $standings[0]['team']['code']);
        $this->assertEquals(4, $standings[0]['points']);

        $this->assertEquals(2, $standings[1]['position']);
        $this->assertEquals('BRA', $standings[1]['team']['code']);
        $this->assertEquals(4, $standings[1]['points']);

        $this->assertEquals(3, $standings[2]['position']);
        $this->assertEquals('GER', $standings[2]['team']['code']);
        $this->assertEquals(0, $standings[2]['points']);
    }

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
