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

/**
 * Controller admin pour la gestion des matchs en direct.
 *
 * Protégé par 3 couches de sécurité :
 * 1. Firewall Symfony → l'utilisateur doit être authentifié (session)
 * 2. #[IsGranted('ROLE_ADMIN')] → l'utilisateur doit avoir le rôle admin
 * 3. CsrfTokenListener → les requêtes POST/PATCH doivent contenir un token CSRF valide
 *
 * Gère le cycle de vie d'un match : scheduled → live → finished
 */
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

    /**
     * Démarre un match programmé (scheduled → live).
     *
     * Initialise les scores à 0-0. Le match apparaît ensuite
     * dans /api/matches/live pour les spectateurs.
     */
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

    /**
     * Met à jour le score d'un match en cours.
     *
     * Méthode PATCH (modification partielle) : on ne modifie que le score,
     * pas les autres champs du match.
     *
     * Validation en cascade :
     * 1. Le match existe ? (sinon 404)
     * 2. Le match est en cours ? (sinon 400)
     * 3. Les scores sont fournis ? (sinon 400)
     * 4. Les scores sont positifs ? (sinon 400)
     */
    #[Route('/matches/{id}/score', name: 'api_admin_match_score', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    public function updateScore(int $id, Request $request): JsonResponse
    {
        $game = $this->gameRepository->find($id);

        // Vérification 1 : le match existe en BDD
        if (!$game) {
            return $this->json(['error' => 'Match non trouvé'], 404);
        }

        // Vérification 2 : le match doit être en cours (status = "live")
        if ($game->getStatus() !== Game::STATUS_LIVE) {
            return $this->json([
                'error' => 'Seuls les matchs en cours peuvent être modifiés',
                'currentStatus' => $game->getStatus()
            ], 400);
        }

        // Décoder le body JSON de la requête
        $data = json_decode($request->getContent(), true);

        // Vérification 3 : les deux scores doivent être présents
        if (!isset($data['homeScore']) || !isset($data['awayScore'])) {
            return $this->json([
                'error' => 'homeScore et awayScore sont requis'
            ], 400);
        }

        $homeScore = (int) $data['homeScore'];
        $awayScore = (int) $data['awayScore'];

        // Vérification 4 : les scores ne peuvent pas être négatifs
        if ($homeScore < 0 || $awayScore < 0) {
            return $this->json([
                'error' => 'Les scores doivent être positifs'
            ], 400);
        }

        // Déléguer la mise à jour au service métier
        $game = $this->matchService->updateScore($game, $homeScore, $awayScore);

        return $this->json([
            'success' => true,
            'message' => 'Score mis à jour',
            'data' => $this->serializeGame($game)
        ]);
    }

    /**
     * Termine un match en cours (live → finished).
     *
     * Le score final peut être passé dans le body, sinon le score
     * actuel est conservé. Une fois terminé, le match est pris en compte
     * par StandingsService pour le calcul du classement du groupe.
     */
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
