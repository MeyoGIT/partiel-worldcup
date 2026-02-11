<?php

namespace App\DataFixtures;

use App\Entity\Game;
use App\Entity\Phase;
use App\Entity\Stadium;
use App\Entity\Team;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator,
        #[Autowire('%admin_email%')] private string $adminEmail,
        #[Autowire('%admin_password%')] private string $adminPassword
    ) {}

    public function load(ObjectManager $manager): void
    {
        $this->createAdminUser($manager);

        $phases = $this->createPhases($manager);
        $stadiums = $this->createStadiums($manager);
        $teams = $this->createTeams($manager);

        $manager->flush();

        $this->createGroupStageMatches($manager, $teams, $stadiums, $phases['groups']);
        $this->createRound16Matches($manager, $teams, $stadiums, $phases['round16']);
        $this->createQuarterFinalMatches($manager, $teams, $stadiums, $phases['quarters']);

        $manager->flush();
    }

    private function createAdminUser(ObjectManager $manager): void
    {
        $admin = new User();
        $admin->setEmail($this->adminEmail);
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPlainPassword($this->adminPassword);

        $errors = $this->validator->validateProperty($admin, 'plainPassword');
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getMessage();
            }
            throw new \RuntimeException('Mot de passe admin invalide : ' . implode(', ', $messages));
        }

        $admin->setPassword($this->passwordHasher->hashPassword($admin, $this->adminPassword));
        $manager->persist($admin);
    }

    private function createPhases(ObjectManager $manager): array
    {
        $phasesData = [
            ['name' => 'Phase de groupes', 'code' => 'groups', 'order' => 1],
            ['name' => 'Huitièmes de finale', 'code' => 'round16', 'order' => 2],
            ['name' => 'Quarts de finale', 'code' => 'quarters', 'order' => 3],
            ['name' => 'Demi-finales', 'code' => 'semis', 'order' => 4],
            ['name' => 'Match pour la 3e place', 'code' => 'third', 'order' => 5],
            ['name' => 'Finale', 'code' => 'final', 'order' => 6],
        ];

        $phases = [];
        foreach ($phasesData as $data) {
            $phase = new Phase();
            $phase->setName($data['name']);
            $phase->setCode($data['code']);
            $phase->setDisplayOrder($data['order']);
            $manager->persist($phase);
            $phases[$data['code']] = $phase;
        }

        return $phases;
    }

    private function createStadiums(ObjectManager $manager): array
    {
        $stadiumsData = [
            ['name' => 'MetLife Stadium', 'city' => 'East Rutherford', 'country' => 'USA', 'capacity' => 82500],
            ['name' => 'AT&T Stadium', 'city' => 'Arlington', 'country' => 'USA', 'capacity' => 80000],
            ['name' => 'SoFi Stadium', 'city' => 'Los Angeles', 'country' => 'USA', 'capacity' => 70240],
            ['name' => 'Estadio Azteca', 'city' => 'Mexico City', 'country' => 'Mexique', 'capacity' => 87523],
            ['name' => 'Hard Rock Stadium', 'city' => 'Miami', 'country' => 'USA', 'capacity' => 64767],
            ['name' => 'Levi\'s Stadium', 'city' => 'Santa Clara', 'country' => 'USA', 'capacity' => 68500],
            ['name' => 'NRG Stadium', 'city' => 'Houston', 'country' => 'USA', 'capacity' => 72220],
            ['name' => 'Mercedes-Benz Stadium', 'city' => 'Atlanta', 'country' => 'USA', 'capacity' => 71000],
            ['name' => 'Arrowhead Stadium', 'city' => 'Kansas City', 'country' => 'USA', 'capacity' => 76416],
            ['name' => 'Lincoln Financial Field', 'city' => 'Philadelphia', 'country' => 'USA', 'capacity' => 69796],
            ['name' => 'Gillette Stadium', 'city' => 'Foxborough', 'country' => 'USA', 'capacity' => 65878],
            ['name' => 'Lumen Field', 'city' => 'Seattle', 'country' => 'USA', 'capacity' => 68740],
            ['name' => 'BMO Field', 'city' => 'Toronto', 'country' => 'Canada', 'capacity' => 45736],
            ['name' => 'BC Place', 'city' => 'Vancouver', 'country' => 'Canada', 'capacity' => 54500],
            ['name' => 'Estadio BBVA', 'city' => 'Monterrey', 'country' => 'Mexique', 'capacity' => 53500],
            ['name' => 'Estadio Akron', 'city' => 'Guadalajara', 'country' => 'Mexique', 'capacity' => 49850],
        ];

        $stadiums = [];
        foreach ($stadiumsData as $data) {
            $stadium = new Stadium();
            $stadium->setName($data['name']);
            $stadium->setCity($data['city']);
            $stadium->setCountry($data['country']);
            $stadium->setCapacity($data['capacity']);
            $manager->persist($stadium);
            $stadiums[] = $stadium;
        }

        return $stadiums;
    }

    private function createTeams(ObjectManager $manager): array
    {
        $teamsData = [
            // Groupe A
            ['name' => 'USA', 'code' => 'USA', 'group' => 'A'],
            ['name' => 'Mexique', 'code' => 'MEX', 'group' => 'A'],
            ['name' => 'Canada', 'code' => 'CAN', 'group' => 'A'],
            ['name' => 'Jamaïque', 'code' => 'JAM', 'group' => 'A'],
            // Groupe B
            ['name' => 'France', 'code' => 'FRA', 'group' => 'B'],
            ['name' => 'Allemagne', 'code' => 'GER', 'group' => 'B'],
            ['name' => 'Colombie', 'code' => 'COL', 'group' => 'B'],
            ['name' => 'Australie', 'code' => 'AUS', 'group' => 'B'],
            // Groupe C
            ['name' => 'Argentine', 'code' => 'ARG', 'group' => 'C'],
            ['name' => 'Pays-Bas', 'code' => 'NED', 'group' => 'C'],
            ['name' => 'Sénégal', 'code' => 'SEN', 'group' => 'C'],
            ['name' => 'Équateur', 'code' => 'ECU', 'group' => 'C'],
            // Groupe D
            ['name' => 'Brésil', 'code' => 'BRA', 'group' => 'D'],
            ['name' => 'Espagne', 'code' => 'ESP', 'group' => 'D'],
            ['name' => 'Japon', 'code' => 'JPN', 'group' => 'D'],
            ['name' => 'Maroc', 'code' => 'MAR', 'group' => 'D'],
            // Groupe E
            ['name' => 'Angleterre', 'code' => 'ENG', 'group' => 'E'],
            ['name' => 'Portugal', 'code' => 'POR', 'group' => 'E'],
            ['name' => 'Pologne', 'code' => 'POL', 'group' => 'E'],
            ['name' => 'Arabie Saoudite', 'code' => 'KSA', 'group' => 'E'],
            // Groupe F
            ['name' => 'Belgique', 'code' => 'BEL', 'group' => 'F'],
            ['name' => 'Croatie', 'code' => 'CRO', 'group' => 'F'],
            ['name' => 'Suisse', 'code' => 'SUI', 'group' => 'F'],
            ['name' => 'Cameroun', 'code' => 'CMR', 'group' => 'F'],
            // Groupe G
            ['name' => 'Uruguay', 'code' => 'URU', 'group' => 'G'],
            ['name' => 'Corée du Sud', 'code' => 'KOR', 'group' => 'G'],
            ['name' => 'Ghana', 'code' => 'GHA', 'group' => 'G'],
            ['name' => 'Serbie', 'code' => 'SRB', 'group' => 'G'],
            // Groupe H
            ['name' => 'Italie', 'code' => 'ITA', 'group' => 'H'],
            ['name' => 'Danemark', 'code' => 'DEN', 'group' => 'H'],
            ['name' => 'Autriche', 'code' => 'AUT', 'group' => 'H'],
            ['name' => 'Tunisie', 'code' => 'TUN', 'group' => 'H'],
            // Groupe I
            ['name' => 'Iran', 'code' => 'IRN', 'group' => 'I'],
            ['name' => 'Pays de Galles', 'code' => 'WAL', 'group' => 'I'],
            ['name' => 'Costa Rica', 'code' => 'CRC', 'group' => 'I'],
            ['name' => 'Nouvelle-Zélande', 'code' => 'NZL', 'group' => 'I'],
            // Groupe J
            ['name' => 'Nigeria', 'code' => 'NGA', 'group' => 'J'],
            ['name' => 'Algérie', 'code' => 'ALG', 'group' => 'J'],
            ['name' => 'Égypte', 'code' => 'EGY', 'group' => 'J'],
            ['name' => 'Côte d\'Ivoire', 'code' => 'CIV', 'group' => 'J'],
            // Groupe K
            ['name' => 'Chili', 'code' => 'CHI', 'group' => 'K'],
            ['name' => 'Paraguay', 'code' => 'PAR', 'group' => 'K'],
            ['name' => 'Pérou', 'code' => 'PER', 'group' => 'K'],
            ['name' => 'Venezuela', 'code' => 'VEN', 'group' => 'K'],
            // Groupe L
            ['name' => 'Suède', 'code' => 'SWE', 'group' => 'L'],
            ['name' => 'République Tchèque', 'code' => 'CZE', 'group' => 'L'],
            ['name' => 'Ukraine', 'code' => 'UKR', 'group' => 'L'],
            ['name' => 'Écosse', 'code' => 'SCO', 'group' => 'L'],
        ];

        $teams = [];
        foreach ($teamsData as $data) {
            $team = new Team();
            $team->setName($data['name']);
            $team->setCode($data['code']);
            $team->setGroupName($data['group']);
            $team->setFlag('https://flagcdn.com/w80/' . strtolower($this->getCountryCode($data['code'])) . '.png');
            $manager->persist($team);
            $teams[$data['group']][] = $team;
        }

        return $teams;
    }

    private function getCountryCode(string $fifaCode): string
    {
        $mapping = [
            'USA' => 'us', 'MEX' => 'mx', 'CAN' => 'ca', 'JAM' => 'jm',
            'FRA' => 'fr', 'GER' => 'de', 'COL' => 'co', 'AUS' => 'au',
            'ARG' => 'ar', 'NED' => 'nl', 'SEN' => 'sn', 'ECU' => 'ec',
            'BRA' => 'br', 'ESP' => 'es', 'JPN' => 'jp', 'MAR' => 'ma',
            'ENG' => 'gb-eng', 'POR' => 'pt', 'POL' => 'pl', 'KSA' => 'sa',
            'BEL' => 'be', 'CRO' => 'hr', 'SUI' => 'ch', 'CMR' => 'cm',
            'URU' => 'uy', 'KOR' => 'kr', 'GHA' => 'gh', 'SRB' => 'rs',
            'ITA' => 'it', 'DEN' => 'dk', 'AUT' => 'at', 'TUN' => 'tn',
            'IRN' => 'ir', 'WAL' => 'gb-wls', 'CRC' => 'cr', 'NZL' => 'nz',
            'NGA' => 'ng', 'ALG' => 'dz', 'EGY' => 'eg', 'CIV' => 'ci',
            'CHI' => 'cl', 'PAR' => 'py', 'PER' => 'pe', 'VEN' => 've',
            'SWE' => 'se', 'CZE' => 'cz', 'UKR' => 'ua', 'SCO' => 'gb-sct',
        ];

        return $mapping[$fifaCode] ?? strtolower($fifaCode);
    }

    private function findTeamByCode(array $teams, string $code): Team
    {
        foreach ($teams as $groupTeams) {
            foreach ($groupTeams as $team) {
                if ($team->getCode() === $code) {
                    return $team;
                }
            }
        }
        throw new \RuntimeException("Team $code not found");
    }

    // -------------------------------------------------------
    //  PHASE DE GROUPES — 72 matchs (FINISHED)
    //  Dates : 15 janvier — 31 janvier 2026
    // -------------------------------------------------------
    private function createGroupStageMatches(ObjectManager $manager, array $teams, array $stadiums, Phase $phase): void
    {
        // Ordre des confrontations par groupe : [0v1, 2v3, 0v2, 1v3, 0v3, 1v2]
        // Scores [domicile, extérieur] cohérents pour chaque groupe
        //
        // Qualifiés en huitièmes :
        //   12 premiers de groupe + 4 meilleurs deuxièmes (ESP, POR, GER, BEL)
        $scores = [
            // Groupe A : 1.USA(9pts) 2.MEX(4pts) 3.CAN(4pts) 4.JAM(0pts)
            'A' => [[2,1],[2,0],[1,0],[3,0],[4,0],[1,1]],
            // Groupe B : 1.FRA(9pts) 2.GER(6pts) 3.COL(1pt) 4.AUS(1pt)
            'B' => [[2,1],[1,1],[1,0],[3,0],[3,0],[2,0]],
            // Groupe C : 1.ARG(9pts) 2.NED(6pts) 3.SEN(1pt) 4.ECU(1pt)
            'C' => [[1,0],[0,0],[2,0],[2,1],[3,1],[1,0]],
            // Groupe D : 1.BRA(7pts) 2.ESP(7pts) 3.MAR(3pts) 4.JPN(0pts)
            'D' => [[1,1],[0,1],[3,0],[2,0],[2,1],[2,1]],
            // Groupe E : 1.ENG(7pts) 2.POR(7pts) 3.POL(3pts) 4.KSA(0pts)
            'E' => [[1,1],[2,1],[2,0],[3,0],[3,0],[2,1]],
            // Groupe F : 1.CRO(9pts) 2.BEL(6pts) 3.SUI(3pts) 4.CMR(0pts)
            'F' => [[0,1],[1,0],[2,1],[2,0],[3,1],[1,0]],
            // Groupe G : 1.URU(9pts) 2.KOR(4pts) 3.GHA(2pts) 4.SRB(1pt)
            'G' => [[2,0],[1,1],[1,0],[2,1],[3,1],[0,0]],
            // Groupe H : 1.ITA(9pts) 2.DEN(4pts) 3.AUT(4pts) 4.TUN(0pts)
            'H' => [[2,0],[1,0],[1,0],[2,1],[2,0],[1,1]],
            // Groupe I : 1.IRN(7pts) 2.WAL(4pts) 3.CRC(4pts) 4.NZL(0pts)
            'I' => [[1,0],[2,0],[0,0],[3,1],[2,0],[1,1]],
            // Groupe J : 1.NGA(9pts) 2.ALG(4pts) 3.EGY(2pts) 4.CIV(1pt)
            'J' => [[1,0],[0,0],[2,1],[1,0],[2,0],[1,1]],
            // Groupe K : 1.CHI(9pts) 2.PER(4pts) 3.PAR(2pts) 4.VEN(1pt)
            'K' => [[2,1],[1,0],[1,0],[0,0],[3,0],[2,2]],
            // Groupe L : 1.SWE(9pts) 2.CZE(4pts) 3.UKR(2pts) 4.SCO(1pt)
            'L' => [[2,0],[1,1],[1,0],[3,1],[3,0],[1,1]],
        ];

        $combinations = [[0,1],[2,3],[0,2],[1,3],[0,3],[1,2]];
        $startDate = new \DateTime('2026-01-15');
        $matchCount = 0;
        $stadiumIdx = 0;
        $times = [16, 18, 20, 22];

        foreach ($teams as $group => $groupTeams) {
            $groupScores = $scores[$group];

            foreach ($combinations as $i => [$h, $a]) {
                $dayOffset = intdiv($matchCount, 4);
                $timeSlot = $matchCount % 4;
                $date = (clone $startDate)->modify("+$dayOffset days");
                $date->setTime($times[$timeSlot], 0);

                $game = new Game();
                $game->setHomeTeam($groupTeams[$h]);
                $game->setAwayTeam($groupTeams[$a]);
                $game->setStadium($stadiums[$stadiumIdx % count($stadiums)]);
                $game->setPhase($phase);
                $game->setGroupName($group);
                $game->setStatus(Game::STATUS_FINISHED);
                $game->setHomeScore($groupScores[$i][0]);
                $game->setAwayScore($groupScores[$i][1]);
                $game->setMatchDate($date);

                $manager->persist($game);
                $matchCount++;
                $stadiumIdx++;
            }
        }
    }

    // -------------------------------------------------------
    //  HUITIÈMES DE FINALE — 8 matchs (FINISHED)
    //  Dates : 3 — 6 février 2026
    //
    //  Tableau :
    //    R16-1 USA 2-1 ESP    ─┐
    //    R16-2 ARG 1-0 POR    ─┤ → QF1 : USA vs ARG
    //    R16-3 FRA 3-0 IRN    ─┐
    //    R16-4 BRA 2-0 NGA    ─┤ → QF2 : FRA vs BRA
    //    R16-5 ENG 2-1 BEL    ─┐
    //    R16-6 CRO 1-0 CHI    ─┤ → QF3 : ENG vs CRO
    //    R16-7 URU 3-2 GER    ─┐
    //    R16-8 ITA 2-1 SWE    ─┤ → QF4 : URU vs ITA
    // -------------------------------------------------------
    private function createRound16Matches(ObjectManager $manager, array $teams, array $stadiums, Phase $phase): void
    {
        $matchups = [
            // [home, away, homeScore, awayScore]
            ['USA', 'ESP', 2, 1],
            ['ARG', 'POR', 1, 0],
            ['FRA', 'IRN', 3, 0],
            ['BRA', 'NGA', 2, 0],
            ['ENG', 'BEL', 2, 1],
            ['CRO', 'CHI', 1, 0],
            ['URU', 'GER', 3, 2],
            ['ITA', 'SWE', 2, 1],
        ];

        $startDate = new \DateTime('2026-02-03');
        $times = [18, 21];

        foreach ($matchups as $i => $matchup) {
            $dayOffset = intdiv($i, 2);
            $timeSlot = $i % 2;
            $date = (clone $startDate)->modify("+$dayOffset days");
            $date->setTime($times[$timeSlot], 0);

            $game = new Game();
            $game->setHomeTeam($this->findTeamByCode($teams, $matchup[0]));
            $game->setAwayTeam($this->findTeamByCode($teams, $matchup[1]));
            $game->setStadium($stadiums[$i % count($stadiums)]);
            $game->setPhase($phase);
            $game->setStatus(Game::STATUS_FINISHED);
            $game->setHomeScore($matchup[2]);
            $game->setAwayScore($matchup[3]);
            $game->setMatchDate($date);

            $manager->persist($game);
        }
    }

    // -------------------------------------------------------
    //  QUARTS DE FINALE — 4 matchs (SCHEDULED)
    //  Dates : 11 — 12 février 2026
    // -------------------------------------------------------
    private function createQuarterFinalMatches(ObjectManager $manager, array $teams, array $stadiums, Phase $phase): void
    {
        $matchups = [
            // [home, away]
            ['USA', 'ARG'],
            ['FRA', 'BRA'],
            ['ENG', 'CRO'],
            ['URU', 'ITA'],
        ];

        $startDate = new \DateTime('2026-02-11');
        $times = [18, 21];
        // Grands stades pour les quarts
        $qfStadiums = [0, 3, 1, 7]; // MetLife, Azteca, AT&T, Mercedes-Benz

        foreach ($matchups as $i => $matchup) {
            $dayOffset = intdiv($i, 2);
            $timeSlot = $i % 2;
            $date = (clone $startDate)->modify("+$dayOffset days");
            $date->setTime($times[$timeSlot], 0);

            $game = new Game();
            $game->setHomeTeam($this->findTeamByCode($teams, $matchup[0]));
            $game->setAwayTeam($this->findTeamByCode($teams, $matchup[1]));
            $game->setStadium($stadiums[$qfStadiums[$i]]);
            $game->setPhase($phase);
            $game->setStatus(Game::STATUS_SCHEDULED);
            $game->setMatchDate($date);

            $manager->persist($game);
        }
    }
}
