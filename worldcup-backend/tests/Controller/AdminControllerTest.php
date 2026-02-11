<?php

namespace App\Tests\Controller;

use App\Entity\Game;
use App\Repository\GameRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests d'intégration pour les endpoints admin.
 *
 * Utilise WebTestCase (client HTTP simulé) pour tester les endpoints
 * sur la vraie base de données de test, avec authentification et CSRF.
 */
class AdminControllerTest extends WebTestCase
{
    /**
     * Vérifie que les endpoints admin sont inaccessibles sans authentification.
     * Un utilisateur non connecté doit recevoir 401, 302 ou 403.
     */
    public function testAdminEndpointsRequireAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/admin/matches');

        $statusCode = $client->getResponse()->getStatusCode();
        $this->assertTrue(
            in_array($statusCode, [401, 302, 403]),
            "Expected 401, 302 or 403 but got $statusCode"
        );
    }

    /**
     * Connecte l'admin et retourne le token CSRF pour les requêtes suivantes.
     * Les credentials viennent du .env (ADMIN_EMAIL / ADMIN_PASSWORD).
     */
    private function loginAdmin($client): string
    {
        $client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => $_ENV['ADMIN_EMAIL'],
            'password' => $_ENV['ADMIN_PASSWORD'],
        ]));

        $data = json_decode($client->getResponse()->getContent(), true);

        return $data['csrfToken'];
    }

    /**
     * Helper pour envoyer une requête admin avec le token CSRF dans le header.
     * Simule ce que fait l'interceptor axios du frontend.
     */
    private function adminRequest($client, string $csrfToken, string $method, string $uri, array $body = null): void
    {
        $headers = ['HTTP_X_CSRF_TOKEN' => $csrfToken];
        if ($body !== null) {
            $headers['CONTENT_TYPE'] = 'application/json';
        }
        $client->request($method, $uri, [], [], $headers, $body !== null ? json_encode($body) : null);
    }

    /**
     * Cherche un match au statut "scheduled" en BDD pour les tests.
     * Retourne null si aucun match programmé n'est disponible (test skippé).
     */
    private function findScheduledGameId(): ?int
    {
        $container = static::getContainer();
        $gameRepository = $container->get(GameRepository::class);
        $games = $gameRepository->findBy(['status' => Game::STATUS_SCHEDULED], ['id' => 'ASC'], 1);

        return count($games) > 0 ? $games[0]->getId() : null;
    }

    /**
     * Test d'intégration : démarrage d'un match (scheduled → live).
     *
     * Teste le flux complet : requête HTTP → CsrfTokenListener → AccessControl
     * → AdminController → MatchService → BDD → réponse JSON.
     */
    public function testStartMatch(): void
    {
        // Créer un client HTTP simulé (ne passe pas par le réseau, appelle directement le kernel Symfony)
        $client = static::createClient();

        // Se connecter en admin et récupérer le token CSRF
        $csrfToken = $this->loginAdmin($client);

        // Trouver un match au statut "scheduled" en BDD
        $gameId = $this->findScheduledGameId();
        if ($gameId === null) {
            $this->markTestSkipped('Aucun match programmé trouvé dans la BDD de test');
        }

        // Envoyer la requête POST avec le token CSRF dans le header
        $this->adminRequest($client, $csrfToken, 'POST', "/api/admin/matches/$gameId/start");

        // Vérifier que la réponse est 200 OK
        $this->assertResponseIsSuccessful();

        // Vérifier le contenu JSON retourné
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals(Game::STATUS_LIVE, $data['data']['status']);  // Status passé à "live"
        $this->assertEquals(0, $data['data']['homeScore']);               // Score initialisé à 0
        $this->assertEquals(0, $data['data']['awayScore']);               // Score initialisé à 0
    }

    /**
     * Teste que la mise à jour du score échoue si les données sont manquantes.
     * Envoie un body vide → doit retourner 400 avec un message d'erreur.
     */
    public function testUpdateScoreInvalidData(): void
    {
        $client = static::createClient();

        $csrfToken = $this->loginAdmin($client);

        // D'abord démarrer un match pour avoir un match LIVE
        $gameId = $this->findScheduledGameId();
        if ($gameId === null) {
            $this->markTestSkipped('Aucun match programmé trouvé dans la BDD de test');
        }

        $this->adminRequest($client, $csrfToken, 'POST', "/api/admin/matches/$gameId/start");

        // Tenter de mettre à jour le score sans données → 400
        $this->adminRequest($client, $csrfToken, 'PATCH', "/api/admin/matches/$gameId/score", []);

        $this->assertResponseStatusCodeSame(400);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
    }

    /**
     * Teste le cycle complet : démarrer un match puis le terminer avec un score final.
     * Vérifie que le status passe à "finished" et que les scores sont corrects.
     */
    public function testFinishMatch(): void
    {
        $client = static::createClient();

        $csrfToken = $this->loginAdmin($client);

        // D'abord démarrer un match pour avoir un match LIVE
        $gameId = $this->findScheduledGameId();
        if ($gameId === null) {
            $this->markTestSkipped('Aucun match programmé trouvé dans la BDD de test');
        }

        $this->adminRequest($client, $csrfToken, 'POST', "/api/admin/matches/$gameId/start");
        $this->assertResponseIsSuccessful();

        // Terminer le match avec un score final de 2-1
        $this->adminRequest($client, $csrfToken, 'POST', "/api/admin/matches/$gameId/finish", [
            'homeScore' => 2,
            'awayScore' => 1,
        ]);

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals(Game::STATUS_FINISHED, $data['data']['status']);
        $this->assertEquals(2, $data['data']['homeScore']);
        $this->assertEquals(1, $data['data']['awayScore']);
    }
}
