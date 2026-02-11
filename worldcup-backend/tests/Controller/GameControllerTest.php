<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GameControllerTest extends WebTestCase
{
    public function testGetMatches(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/matches');

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayHasKey('total', $data['meta']);
        $this->assertArrayHasKey('page', $data['meta']);
        $this->assertArrayHasKey('limit', $data['meta']);
        $this->assertArrayHasKey('pages', $data['meta']);
        $this->assertIsArray($data['data']);
    }

    public function testGetMatchNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/matches/99999');

        $this->assertResponseStatusCodeSame(404);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
    }

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

        // Chaque entrée du classement doit avoir la bonne structure
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
