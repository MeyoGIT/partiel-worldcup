<?php

namespace App\Service;

use App\Entity\Game;
use App\Repository\GameRepository;
use App\Repository\TeamRepository;

/**
 * Service de calcul du classement des groupes.
 *
 * Reproduit le système de classement FIFA de la Coupe du Monde :
 * - 3 points pour une victoire, 1 pour un nul, 0 pour une défaite
 * - Départage : points → différence de buts → buts marqués → ordre alphabétique
 *
 * Appelé par GameController sur GET /api/standings/{group}
 * pour afficher le tableau de classement d'un groupe (A à L).
 */
class StandingsService
{
    public function __construct(
        private GameRepository $gameRepository,
        private TeamRepository $teamRepository
    ) {}

    /**
     * Calcule le classement complet d'un groupe.
     *
     * Algorithme :
     * 1. Récupère les 4 équipes du groupe
     * 2. Récupère tous les matchs terminés du groupe
     * 3. Pour chaque match, met à jour les stats des 2 équipes
     * 4. Trie les équipes selon les critères FIFA
     * 5. Attribue les positions (1er, 2e, 3e, 4e)
     *
     * @param string $groupName Lettre du groupe (A à L)
     * @return array Tableau de classement trié, chaque entrée contient :
     *               position, team{id,name,code,flag}, played, won, drawn,
     *               lost, goalsFor, goalsAgainst, goalDifference, points
     */
    public function calculateStandings(string $groupName): array
    {
        // Étape 1 : récupérer les équipes et les matchs terminés du groupe
        $teams = $this->teamRepository->findByGroup($groupName);
        $games = $this->gameRepository->findFinishedByGroup($groupName);

        // Étape 2 : initialiser les statistiques de chaque équipe à zéro
        $standings = [];
        foreach ($teams as $team) {
            $standings[$team->getId()] = [
                'team' => [
                    'id' => $team->getId(),
                    'name' => $team->getName(),
                    'code' => $team->getCode(),
                    'flag' => $team->getFlag()
                ],
                'played' => 0,       // Matchs joués
                'won' => 0,          // Victoires
                'drawn' => 0,        // Nuls
                'lost' => 0,         // Défaites
                'goalsFor' => 0,     // Buts marqués
                'goalsAgainst' => 0, // Buts encaissés
                'goalDifference' => 0,
                'points' => 0
            ];
        }

        // Étape 3 : parcourir chaque match terminé et mettre à jour les stats
        // des deux équipes (domicile et extérieur)
        foreach ($games as $game) {
            $homeId = $game->getHomeTeam()->getId();
            $awayId = $game->getAwayTeam()->getId();
            $homeScore = $game->getHomeScore();
            $awayScore = $game->getAwayScore();

            // Sécurité : ignorer les matchs dont une équipe n'est pas dans le groupe
            if (!isset($standings[$homeId]) || !isset($standings[$awayId])) {
                continue;
            }

            // Incrémenter les matchs joués pour les deux équipes
            $standings[$homeId]['played']++;
            $standings[$awayId]['played']++;

            // Comptabiliser les buts marqués et encaissés
            // Les buts "for" de l'un sont les buts "against" de l'autre
            $standings[$homeId]['goalsFor'] += $homeScore;
            $standings[$homeId]['goalsAgainst'] += $awayScore;

            $standings[$awayId]['goalsFor'] += $awayScore;
            $standings[$awayId]['goalsAgainst'] += $homeScore;

            // Attribuer les points selon le résultat
            if ($homeScore > $awayScore) {
                // Victoire domicile : 3 points pour le domicile, 0 pour l'extérieur
                $standings[$homeId]['won']++;
                $standings[$homeId]['points'] += 3;
                $standings[$awayId]['lost']++;
            } elseif ($homeScore < $awayScore) {
                // Victoire extérieur : 3 points pour l'extérieur, 0 pour le domicile
                $standings[$awayId]['won']++;
                $standings[$awayId]['points'] += 3;
                $standings[$homeId]['lost']++;
            } else {
                // Match nul : 1 point pour chacun
                $standings[$homeId]['drawn']++;
                $standings[$awayId]['drawn']++;
                $standings[$homeId]['points'] += 1;
                $standings[$awayId]['points'] += 1;
            }
        }

        // Étape 4 : calculer la différence de buts (buts marqués - buts encaissés)
        foreach ($standings as &$standing) {
            $standing['goalDifference'] = $standing['goalsFor'] - $standing['goalsAgainst'];
        }

        // Étape 5 : trier les équipes selon les critères FIFA
        // Priorité : points > différence de buts > buts marqués > nom alphabétique
        usort($standings, function ($a, $b) {
            // 1. Le plus de points en premier
            if ($a['points'] !== $b['points']) {
                return $b['points'] - $a['points'];
            }
            // 2. La meilleure différence de buts
            if ($a['goalDifference'] !== $b['goalDifference']) {
                return $b['goalDifference'] - $a['goalDifference'];
            }
            // 3. Le plus de buts marqués
            if ($a['goalsFor'] !== $b['goalsFor']) {
                return $b['goalsFor'] - $a['goalsFor'];
            }
            // 4. Ordre alphabétique (dernier recours)
            return strcmp($a['team']['name'], $b['team']['name']);
        });

        // Étape 6 : attribuer la position (1er, 2e, 3e, 4e)
        $position = 1;
        foreach ($standings as &$standing) {
            $standing['position'] = $position++;
        }

        // Retourner un tableau indexé numériquement (0, 1, 2, 3)
        return array_values($standings);
    }
}
