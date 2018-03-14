<?php

namespace App\Service;

use App\Entity\GameToSchedule;
use App\Repository\TeamInformationRepository;

/**
 * Class ScheduledGameService
 * @package App\Service
 */
class GameToScheduleService
{
    /** @var TeamInformationRepository */
    private $teamInformationRepo;

    /**
     * ScheduledGameService constructor.
     *
     * @param TeamInformationRepository $teamInformationRepository
     */
    public function __construct(
        TeamInformationRepository $teamInformationRepository
    ) {
        $this->teamInformationRepo = $teamInformationRepository;
    }

    /**
     * Get real values for id keys in entity
     *
     * @param GameToSchedule $game
     *
     * @return GameToSchedule
     */
    public function mapGameToSchedule(GameToSchedule $game)
    {
        $game->setHomeTeamName($this->teamInformationRepo->getTeamName($game->getHomeTeamId()));
        $game->setVisitTeamName($this->teamInformationRepo->getTeamName($game->getVisitTeamId()));

        return $game;
    }

    /**
     * Get readable dump of a ScheduledGame entity
     *
     * @param GameToSchedule $game
     *
     * @return string
     */
    public function dumpGameToSchedule(GameToSchedule $game)
    {
        if (empty($game->getHomeTeamName()) ||
            empty($game->getVisitTeamName())
        ) {
            $this->mapGameToSchedule($game);
        }

        $dump = "Game Id: " . $game->getGameToScheduleId() . "\n";
        $dump .= "Week: " . $game->getWeekNr() . "\n";
        $dump .= "Teams: " . $game->getVisitTeamName() . " [" . $game->getVisitTeamId() . "]";
        $dump .= " @ ";
        $dump .= $game->getHomeTeamName() . " [" . $game->getHomeTeamId() . "]\n";
        $dump .= "Division: " . $game->getDivision() . "\n";
        $dump .= !empty($game->getNotes()) ? "Notes: " . $game->getNotes() . "\n" : "";

        return $dump;
    }
}
