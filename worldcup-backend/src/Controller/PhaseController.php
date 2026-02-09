<?php

namespace App\Controller;

use App\Repository\PhaseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/phases')]
class PhaseController extends AbstractController
{
    public function __construct(
        private PhaseRepository $phaseRepository
    ) {}

    #[Route('', name: 'api_phases_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $phases = $this->phaseRepository->findAllOrdered();

        return $this->json([
            'data' => array_map(fn($phase) => $this->serializePhase($phase), $phases),
            'meta' => [
                'total' => count($phases)
            ]
        ]);
    }

    #[Route('/{id}', name: 'api_phases_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        $phase = $this->phaseRepository->find($id);

        if (!$phase) {
            return $this->json(['error' => 'Phase not found'], 404);
        }

        return $this->json([
            'data' => $this->serializePhase($phase)
        ]);
    }

    private function serializePhase($phase): array
    {
        return [
            'id' => $phase->getId(),
            'name' => $phase->getName(),
            'code' => $phase->getCode(),
            'displayOrder' => $phase->getDisplayOrder()
        ];
    }
}
