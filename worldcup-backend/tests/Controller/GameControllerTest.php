<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests d'intégration pour les endpoints publics (matchs et classements).
 *
 * Ces endpoints ne nécessitent pas d'authentification.
 * On vérifie la structure JSON retournée et les codes HTTP.
 */
class GameControllerTest extends WebTestCase
{
    /**
     * Teste GET /api/matches : liste paginée des matchs.
     * Vérifie que la réponse contient un tableau "data" et les métadonnées
     * de pagination (total, page, limit, pages).
     */
    public function testGetMatches(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/matches');

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);

        // Vérifier la structure de la réponse paginée
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayHasKey('total', $data['meta']);
        $this->assertArrayHasKey('page', $data['meta']);
        $this->assertArrayHasKey('limit', $data['meta']);
        $this->assertArrayHasKey('pages', $data['meta']);
        $this->assertIsArray($data['data']);
    }

    /**
     * Teste GET /api/matches/99999 : match inexistant.
     * Doit retourner 404 avec un message d'erreur JSON.
     */
    public function testGetMatchNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/matches/99999');

        $this->assertResponseStatusCodeSame(404);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
    }

    /**
     * Teste GET /api/standings/A : classement du groupe A.
     * Vérifie la structure complète d'une entrée de classement :
     * position, team, played, won, drawn, lost, points, buts, etc.
     */
    public function testGetStandings(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/standings/A');

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertEquals('A', $data['meta']['group']);
        $this->assertIsArray($data['data']);

        // Vérifier que chaque entrée du classement a tous les champs attendus
        if (count($data['data']) > 0) {
            $standing = $data['data'][0];
            $this->assertArrayHasKey('team', $standing);
            $this->assertArrayHasKey('played', $standing);
            $this->assertArrayHasKey('won', $standing);
            $this->assertArrayHasKey('drawn', $standing);
            $this->assertArrayHasKey('lost', $standing);
            $this->assertArrayHasKey('points', $standing);
            $this->assertArrayHasKey('goalsFor', $standing);
            $this->assertArrayHasKey('goalsAgainst', $standing);
            $this->assertArrayHasKey('goalDifference', $standing);
            $this->assertArrayHasKey('position', $standing);
        }
    }
}
