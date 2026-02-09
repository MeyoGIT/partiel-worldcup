<?php

namespace App\Controller;

use App\Repository\GameRepository;
use App\Service\StandingsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class GameController extends AbstractController
{
    public function __construct(
        private GameRepository $gameRepository,
        private StandingsService $standingsService
    ) {}

    #[Route('/matches', name: 'api_matches_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $phase = $request->query->get('phase');
        $group = $request->query->get('group');
        $status = $request->query->get('status');
        $date = $request->query->get('date');
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, max(1, (int) $request->query->get('limit', 20)));

        $games = $this->gameRepository->findByFilters($phase, $group, $status, $date, $page, $limit);
        $total = $this->gameRepository->countByFilters($phase, $group, $status, $date);

        return $this->json([
            'data' => array_map(fn($game) => $this->serializeGame($game), $games),
            'meta' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }

    #[Route('/matches/{id}', name: 'api_matches_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        $game = $this->gameRepository->find($id);

        if (!$game) {
            return $this->json(['error' => 'Match not found'], 404);
        }

        return $this->json([
            'data' => $this->serializeGame($game)
        ]);
    }

    #[Route('/matches/phase/{phaseCode}', name: 'api_matches_by_phase', methods: ['GET'])]
    public function byPhase(string $phaseCode): JsonResponse
    {
        $games = $this->gameRepository->findByPhaseCode($phaseCode);

        return $this->json([
            'data' => array_map(fn($game) => $this->serializeGame($game), $games),
            'meta' => [
                'total' => count($games),
                'phase' => $phaseCode
            ]
        ]);
    }

    #[Route('/matches/live', name: 'api_matches_live', methods: ['GET'])]
    public function live(): JsonResponse
    {
        $games = $this->gameRepository->findLiveGames();

        return $this->json([
            'data' => array_map(fn($game) => $this->serializeGame($game), $games),
            'meta' => [
                'total' => count($games)
            ]
        ]);
    }

    #[Route('/matches/today', name: 'api_matches_today', methods: ['GET'])]
    public function today(): JsonResponse
    {
        $games = $this->gameRepository->findTodayGames();

        return $this->json([
            'data' => array_map(fn($game) => $this->serializeGame($game), $games),
            'meta' => [
                'total' => count($games),
                'date' => (new \DateTime())->format('Y-m-d')
            ]
        ]);
    }

    #[Route('/standings/{group}', name: 'api_standings', methods: ['GET'], requirements: ['group' => '[A-L]'])]
    public function standings(string $group): JsonResponse
    {
        $standings = $this->standingsService->calculateStandings(strtoupper($group));

        return $this->json([
            'data' => $standings,
            'meta' => [
                'group' => strtoupper($group)
            ]
        ]);
    }

    private function serializeGame($game): array
    {
        return [
            'id' => $game->getId(),
            'homeTeam' => [
                'id' => $game->getHomeTeam()->getId(),
                'name' => $game->getHomeTeam()->getName(),
                'code' => $game->getHomeTeam()->getCode(),
                'flag' => $game->getHomeTeam()->getFlag()
            ],
            'awayTeam' => [
                'id' => $game->getAwayTeam()->getId(),
                'name' => $game->getAwayTeam()->getName(),
                'code' => $game->getAwayTeam()->getCode(),
                'flag' => $game->getAwayTeam()->getFlag()
            ],
            'stadium' => [
                'id' => $game->getStadium()->getId(),
                'name' => $game->getStadium()->getName(),
                'city' => $game->getStadium()->getCity()
            ],
            'phase' => [
                'id' => $game->getPhase()->getId(),
                'name' => $game->getPhase()->getName(),
                'code' => $game->getPhase()->getCode()
            ],
            'matchDate' => $game->getMatchDate()->format('c'),
            'homeScore' => $game->getHomeScore(),
            'awayScore' => $game->getAwayScore(),
            'status' => $game->getStatus(),
            'groupName' => $game->getGroupName()
        ];
    }
}
