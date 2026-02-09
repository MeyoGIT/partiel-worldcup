<?php

namespace App\Controller;

use App\Repository\StadiumRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/stadiums')]
class StadiumController extends AbstractController
{
    public function __construct(
        private StadiumRepository $stadiumRepository
    ) {}

    #[Route('', name: 'api_stadiums_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $stadiums = $this->stadiumRepository->findAllOrderedByCapacity();

        return $this->json([
            'data' => array_map(fn($stadium) => $this->serializeStadium($stadium), $stadiums),
            'meta' => [
                'total' => count($stadiums)
            ]
        ]);
    }

    #[Route('/{id}', name: 'api_stadiums_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        $stadium = $this->stadiumRepository->find($id);

        if (!$stadium) {
            return $this->json(['error' => 'Stadium not found'], 404);
        }

        return $this->json([
            'data' => $this->serializeStadium($stadium)
        ]);
    }

    private function serializeStadium($stadium): array
    {
        return [
            'id' => $stadium->getId(),
            'name' => $stadium->getName(),
            'city' => $stadium->getCity(),
            'country' => $stadium->getCountry(),
            'capacity' => $stadium->getCapacity()
        ];
    }
}
