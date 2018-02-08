<?php

namespace App\Service;

use App\Entity\ScheduledGame;
use App\Repository\TeamInformationRepository;

/**
 * Class ScheduledGameService
 * @package App\Service
 */
class ScheduledGameService
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
     * @param ScheduledGame $game
     *
     * @return ScheduledGame
     */
    public function mapScheduledGame(ScheduledGame $game)
    {
        $game->setHomeTeamName($this->teamInformationRepo->getTeamName($game->getHomeTeamId()));
        $game->setVisitTeamName($this->teamInformationRepo->getTeamName($game->getVisitTeamId()));

        return $game;
    }

    /**
     * Get readable dump of a ScheduledGame entity
     *
     * @param ScheduledGame $game
     *
     * @return string
     */
    public function dumpScheduledGame(ScheduledGame $game)
    {
        $dump = "Game Id: " . $game->getScheduledGameId() . "\n";
        $dump .= "DateTime: " . $game->getGameDate()->format("D, d m Y") . $game->getGameTime()->format(" H:i:s\n");
        $dump .= "Location: " . $game->getGameLocation() . "\n";
        $dump .= "Teams: " . $game->getVisitTeamName() . " @ " . $game->getHomeTeamName() . "\n";
        $dump .= !empty($game->getGameNotes()) ? "Notes: " . $game->getGameNotes() . "\n" : "";
        $dump .= !empty($game->getSchedulingNotes()) ? "Comments: " . $game->getSchedulingNotes() . "\n" : "";

        return $dump;
    }
}