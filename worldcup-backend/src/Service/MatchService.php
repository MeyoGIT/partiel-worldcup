<?php

namespace App\Service;

use App\Entity\Game;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service responsable du cycle de vie des matchs.
 *
 * Gère les transitions d'état d'un match :
 *   scheduled → live → finished
 *
 * Appelé par AdminController pour les actions admin
 * (démarrer, mettre à jour le score, terminer un match).
 */
class MatchService
{
    public function __construct(
        private GameRepository $gameRepository,
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Met à jour le score d'un match en cours.
     *
     * Modifie les scores domicile/extérieur et persiste en BDD.
     * Le match doit être au statut "live" (vérifié par AdminController avant l'appel).
     */
    public function updateScore(Game $game, int $homeScore, int $awayScore): Game
    {
        $game->setHomeScore($homeScore);
        $game->setAwayScore($awayScore);

        // Flush immédiat : les scores doivent être visibles en temps réel
        // pour les spectateurs qui consultent /api/matches/live
        $this->entityManager->flush();

        return $game;
    }

    /**
     * Démarre un match programmé.
     *
     * Passe le statut de "scheduled" à "live" et initialise les scores à 0-0.
     * À partir de ce moment, le match apparaît dans /api/matches/live
     * et le frontend le met en avant avec le polling toutes les 5 secondes.
     */
    public function startMatch(Game $game): Game
    {
        $game->setStatus(Game::STATUS_LIVE);
        $game->setHomeScore(0);
        $game->setAwayScore(0);

        $this->entityManager->flush();

        return $game;
    }

    /**
     * Termine un match en cours.
     *
     * Passe le statut de "live" à "finished" et enregistre le score final.
     * Le match disparaît de /api/matches/live et ses résultats sont pris
     * en compte par StandingsService pour le calcul du classement du groupe.
     */
    public function finishMatch(Game $game, int $homeScore, int $awayScore): Game
    {
        $game->setStatus(Game::STATUS_FINISHED);
        $game->setHomeScore($homeScore);
        $game->setAwayScore($awayScore);

        $this->entityManager->flush();

        return $game;
    }

}
