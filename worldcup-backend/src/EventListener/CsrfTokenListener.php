<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Listener de protection CSRF pour les endpoints admin.
 *
 * S'exécute automatiquement avant chaque requête grâce au pattern Observer de Symfony.
 * Intercepte les requêtes POST/PATCH/DELETE vers /api/admin/* et vérifie
 * que le header X-CSRF-Token contient un token valide.
 *
 * Sans cette protection, un site malveillant pourrait exploiter le cookie
 * de session d'un admin connecté pour envoyer des requêtes à son insu
 * (attaque Cross-Site Request Forgery).
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 10)]
class CsrfTokenListener
{
    public function __construct(
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Ignorer les routes qui ne sont pas admin (endpoints publics)
        if (!str_starts_with($request->getPathInfo(), '/api/admin')) {
            return;
        }

        // Les requêtes GET/HEAD/OPTIONS sont en lecture seule,
        // elles ne modifient rien donc pas besoin de vérification CSRF
        // Il faut vérifier POST/PUT  (PATCH/DELETE pas utilisé dans le projet)
        if (in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return;
        }

        // Récupérer le token envoyé par le frontend dans le header HTTP
        $token = $request->headers->get('X-CSRF-Token');

        // Valider le token contre celui stocké en session côté serveur
        // Si absent ou invalide → bloquer la requête avec une erreur 403
        if (!$token || !$this->csrfTokenManager->isTokenValid(new CsrfToken('admin', $token))) {
            $event->setResponse(new JsonResponse([
                'error' => 'Token CSRF invalide ou manquant',
            ], JsonResponse::HTTP_FORBIDDEN));
        }
    }
}
