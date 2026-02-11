<?php

namespace App\Controller;

use App\Entity\Game;
use App\Repository\GameRepository;
use App\Service\MatchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    public function __construct(
        private GameRepository $gameRepository,
        private MatchService $matchService
    ) {}

    #[Route('/matches', name: 'api_admin_matches', methods: ['GET'])]
    public function listMatches(Request $request): JsonResponse
    {
        $status = $request->query->get('status');
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(200, max(1, (int) $request->query->get('limit', 20)));

        $games = $this->gameRepository->findByFilters(null, null, $status, null, $page, $limit);
        $total = $this->gameRepository->countByFilters(null, null, $status, null);

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

    #[Route('/matches/{id}/start', name: 'api_admin_match_start', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function startMatch(int $id): JsonResponse
    {
        $game = $this->gameRepository->find($id);

        if (!$game) {
            return $this->json(['error' => 'Match non trouvé'], 404);
        }

        if ($game->getStatus() !== Game::STATUS_SCHEDULED) {
            return $this->json([
                'error' => 'Seuls les matchs programmés peuvent être démarrés',
                'currentStatus' => $game->getStatus()
            ], 400);
        }

        $game = $this->matchService->startMatch($game);

        return $this->json([
            'success' => true,
            'message' => 'Match démarré',
            'data' => $this->serializeGame($game)
        ]);
    }

    #[Route('/matches/{id}/score', name: 'api_admin_match_score', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    public function updateScore(int $id, Request $request): JsonResponse
    {
        $game = $this->gameRepository->find($id);

        if (!$game) {
            return $this->json(['error' => 'Match non trouvé'], 404);
        }

        if ($game->getStatus() !== Game::STATUS_LIVE) {
            return $this->json([
                'error' => 'Seuls les matchs en cours peuvent être modifiés',
                'currentStatus' => $game->getStatus()
            ], 400);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['homeScore']) || !isset($data['awayScore'])) {
            return $this->json([
                'error' => 'homeScore et awayScore sont requis'
            ], 400);
        }

        $homeScore = (int) $data['homeScore'];
        $awayScore = (int) $data['awayScore'];

        if ($homeScore < 0 || $awayScore < 0) {
            return $this->json([
                'error' => 'Les scores doivent être positifs'
            ], 400);
        }

        $game = $this->matchService->updateScore($game, $homeScore, $awayScore);

        return $this->json([
            'success' => true,
            'message' => 'Score mis à jour',
            'data' => $this->serializeGame($game)
        ]);
    }

    #[Route('/matches/{id}/finish', name: 'api_admin_match_finish', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function finishMatch(int $id, Request $request): JsonResponse
    {
        $game = $this->gameRepository->find($id);

        if (!$game) {
            return $this->json(['error' => 'Match non trouvé'], 404);
        }

        if ($game->getStatus() !== Game::STATUS_LIVE) {
            return $this->json([
                'error' => 'Seuls les matchs en cours peuvent être terminés',
                'currentStatus' => $game->getStatus()
            ], 400);
        }

        // Optionally accept final score in request body
        $data = json_decode($request->getContent(), true);
        $homeScore = isset($data['homeScore']) ? (int) $data['homeScore'] : $game->getHomeScore();
        $awayScore = isset($data['awayScore']) ? (int) $data['awayScore'] : $game->getAwayScore();

        $game = $this->matchService->finishMatch($game, $homeScore, $awayScore);

        return $this->json([
            'success' => true,
            'message' => 'Match terminé',
            'data' => $this->serializeGame($game)
        ]);
    }

    private function serializeGame(Game $game): array
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
