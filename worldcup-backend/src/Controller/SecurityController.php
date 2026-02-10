<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Entity\User;

class SecurityController extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(#[CurrentUser] ?User $user): JsonResponse
    {
        // This method is handled by json_login in security.yaml
        // It will never be called if authentication fails
        if (null === $user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Identifiants manquants',
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Connexion réussie',
            'user' => [
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ],
        ]);
    }

    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(): void
    {
        // This method is intercepted by the logout key on the firewall
        throw new \LogicException('This method should never be reached.');
    }

    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(#[CurrentUser] ?User $user): JsonResponse
    {
        if (null === $user) {
            return new JsonResponse([
                'authenticated' => false,
                'user' => null,
            ]);
        }

        return new JsonResponse([
            'authenticated' => true,
            'user' => [
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ],
        ]);
    }
}
