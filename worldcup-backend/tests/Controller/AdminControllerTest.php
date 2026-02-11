<?php

namespace App\Tests\Controller;

use App\Entity\Game;
use App\Repository\GameRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminControllerTest extends WebTestCase
{
    public function testAdminEndpointsRequireAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/admin/matches');

        // Sans authentification, accès refusé (401 ou redirection)
        $statusCode = $client->getResponse()->getStatusCode();
        $this->assertTrue(
            in_array($statusCode, [401, 302, 403]),
            "Expected 401, 302 or 403 but got $statusCode"
        );
    }

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

    private function adminRequest($client, string $csrfToken, string $method, string $uri, array $body = null): void
    {
        $headers = ['HTTP_X_CSRF_TOKEN' => $csrfToken];
        if ($body !== null) {
            $headers['CONTENT_TYPE'] = 'application/json';
        }
        $client->request($method, $uri, [], [], $headers, $body !== null ? json_encode($body) : null);
    }

    private function findScheduledGameId(): ?int
    {
        $container = static::getContainer();
        $gameRepository = $container->get(GameRepository::class);
        $games = $gameRepository->findBy(['status' => Game::STATUS_SCHEDULED], ['id' => 'ASC'], 1);

        return count($games) > 0 ? $games[0]->getId() : null;
    }

    public function testStartMatch(): void
    {
        $client = static::createClient();

        $csrfToken = $this->loginAdmin($client);

        $gameId = $this->findScheduledGameId();
        if ($gameId === null) {
            $this->markTestSkipped('Aucun match programmé trouvé dans la BDD de test');
        }

        $this->adminRequest($client, $csrfToken, 'POST', "/api/admin/matches/$gameId/start");

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals(Game::STATUS_LIVE, $data['data']['status']);
        $this->assertEquals(0, $data['data']['homeScore']);
        $this->assertEquals(0, $data['data']['awayScore']);
    }

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

        // Tenter de mettre à jour le score sans données
        $this->adminRequest($client, $csrfToken, 'PATCH', "/api/admin/matches/$gameId/score", []);

        $this->assertResponseStatusCodeSame(400);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
    }

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

        // Terminer le match avec un score final
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
