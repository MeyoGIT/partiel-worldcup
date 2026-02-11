<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

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

        // Only check mutating requests to admin endpoints
        if (!str_starts_with($request->getPathInfo(), '/api/admin')) {
            return;
        }

        if (in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return;
        }

        $token = $request->headers->get('X-CSRF-Token');

        if (!$token || !$this->csrfTokenManager->isTokenValid(new CsrfToken('admin', $token))) {
            $event->setResponse(new JsonResponse([
                'error' => 'Token CSRF invalide ou manquant',
            ], JsonResponse::HTTP_FORBIDDEN));
        }
    }
}
