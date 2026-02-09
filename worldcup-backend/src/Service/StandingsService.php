<?php

namespace App\Service;

use App\Entity\Game;
use App\Repository\GameRepository;
use App\Repository\TeamRepository;

class StandingsService
{
    public function __construct(
        private GameRepository $gameRepository,
        private TeamRepository $teamRepository
    ) {}

    public function calculateStandings(string $groupName): array
    {
        $teams = $this->teamRepository->findByGroup($groupName);
        $games = $this->gameRepository->findFinishedByGroup($groupName);

        $standings = [];

        foreach ($teams as $team) {
            $standings[$team->getId()] = [
                'team' => [
                    'id' => $team->getId(),
                    'name' => $team->getName(),
                    'code' => $team->getCode(),
                    'flag' => $team->getFlag()
                ],
                'played' => 0,
                'won' => 0,
                'drawn' => 0,
                'lost' => 0,
                'goalsFor' => 0,
                'goalsAgainst' => 0,
                'goalDifference' => 0,
                'points' => 0
            ];
        }

        foreach ($games as $game) {
            $homeId = $game->getHomeTeam()->getId();
            $awayId = $game->getAwayTeam()->getId();
            $homeScore = $game->getHomeScore();
            $awayScore = $game->getAwayScore();

            if (!isset($standings[$homeId]) || !isset($standings[$awayId])) {
                continue;
            }

            $standings[$homeId]['played']++;
            $standings[$awayId]['played']++;

            $standings[$homeId]['goalsFor'] += $homeScore;
            $standings[$homeId]['goalsAgainst'] += $awayScore;

            $standings[$awayId]['goalsFor'] += $awayScore;
            $standings[$awayId]['goalsAgainst'] += $homeScore;

            if ($homeScore > $awayScore) {
                $standings[$homeId]['won']++;
                $standings[$homeId]['points'] += 3;
                $standings[$awayId]['lost']++;
            } elseif ($homeScore < $awayScore) {
                $standings[$awayId]['won']++;
                $standings[$awayId]['points'] += 3;
                $standings[$homeId]['lost']++;
            } else {
                $standings[$homeId]['drawn']++;
                $standings[$awayId]['drawn']++;
                $standings[$homeId]['points'] += 1;
                $standings[$awayId]['points'] += 1;
            }
        }

        foreach ($standings as &$standing) {
            $standing['goalDifference'] = $standing['goalsFor'] - $standing['goalsAgainst'];
        }

        usort($standings, function ($a, $b) {
            if ($a['points'] !== $b['points']) {
                return $b['points'] - $a['points'];
            }
            if ($a['goalDifference'] !== $b['goalDifference']) {
                return $b['goalDifference'] - $a['goalDifference'];
            }
            if ($a['goalsFor'] !== $b['goalsFor']) {
                return $b['goalsFor'] - $a['goalsFor'];
            }
            return strcmp($a['team']['name'], $b['team']['name']);
        });

        $position = 1;
        foreach ($standings as &$standing) {
            $standing['position'] = $position++;
        }

        return array_values($standings);
    }
}
