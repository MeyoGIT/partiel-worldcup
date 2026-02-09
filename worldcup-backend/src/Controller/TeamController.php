<?php

namespace App\Controller;

use App\Repository\TeamRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/teams')]
class TeamController extends AbstractController
{
    public function __construct(
        private TeamRepository $teamRepository
    ) {}

    #[Route('', name: 'api_teams_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $teams = $this->teamRepository->findAllOrderedByGroup();

        return $this->json([
            'data' => array_map(fn($team) => $this->serializeTeam($team), $teams),
            'meta' => [
                'total' => count($teams)
            ]
        ]);
    }

    #[Route('/{id}', name: 'api_teams_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        $team = $this->teamRepository->find($id);

        if (!$team) {
            return $this->json(['error' => 'Team not found'], 404);
        }

        return $this->json([
            'data' => $this->serializeTeam($team)
        ]);
    }

    #[Route('/group/{group}', name: 'api_teams_by_group', methods: ['GET'], requirements: ['group' => '[A-L]'])]
    public function byGroup(string $group): JsonResponse
    {
        $teams = $this->teamRepository->findByGroup(strtoupper($group));

        return $this->json([
            'data' => array_map(fn($team) => $this->serializeTeam($team), $teams),
            'meta' => [
                'total' => count($teams),
                'group' => strtoupper($group)
            ]
        ]);
    }

    private function serializeTeam($team): array
    {
        return [
            'id' => $team->getId(),
            'name' => $team->getName(),
            'code' => $team->getCode(),
            'flag' => $team->getFlag(),
            'groupName' => $team->getGroupName()
        ];
    }
}
