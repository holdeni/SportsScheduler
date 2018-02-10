<?php

namespace App\Service;

use App\Entity\ScheduledGame;
use App\Repository\ScheduledGameRepository;
use App\Repository\TeamInformationRepository;

/**
 * Class ScheduledGameService
 * @package App\Service
 */
class ScheduledGameService
{
    /** @var ScheduledGameRepository */
    private $scheduledGameRepo;

    /** @var TeamInformationRepository */
    private $teamInformationRepo;

    /**
     * ScheduledGameService constructor.
     *
     * @param ScheduledGameRepository $scheduledGameRepository
     * @param TeamInformationRepository $teamInformationRepository
     */
    public function __construct(
        ScheduledGameRepository $scheduledGameRepository,
        TeamInformationRepository $teamInformationRepository
    ) {
        $this->scheduledGameRepo = $scheduledGameRepository;
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
        if (!empty($game->getHomeTeamId())) {
            $game->setHomeTeamName($this->teamInformationRepo->getTeamName($game->getHomeTeamId()));
            $game->setVisitTeamName($this->teamInformationRepo->getTeamName($game->getVisitTeamId()));
        }

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
        $dump .= "DateTime: " . $game->getGameDate()->format("D, d m Y") . $game->getGameTime()->format(" H:i\n");
        $dump .= "Location: " . $game->getGameLocation() . "\n";
        $dump .= "Teams: " . $game->getVisitTeamName() . " @ " . $game->getHomeTeamName() . "\n";
        $dump .= !empty($game->getGameNotes()) ? "Notes: " . $game->getGameNotes() . "\n" : "";
        $dump .= !empty($game->getSchedulingNotes()) ? "Comments: " . $game->getSchedulingNotes() . "\n" : "";

        return $dump;
    }

    /**
     * @param ScheduledGame $game
     *
     * @return array
     */
    public function formatAsArray(ScheduledGame $game)
    {
        $gameData = array(
            'Game Id' => $game->getScheduledGameId(),
            'Date' => $game->getGameDate()->format("Y-m-d"),
            'Time' => $game->getGameTime()->format("H:i"),
            'Location' => $game->getGameLocation(),
            'Teams' => (!empty($game->getHomeTeamId()))
                ? $game->getVisitTeamName() . " @ " . $game->getHomeTeamName()
                : '',
        );

        return $gameData;
    }

    /**
     * @return array
     */
    public function dumpSchedule()
    {
        $scheduledGamesDump = array();

        $scheduledGames = $this->scheduledGameRepo->listAllScheduledGame();
        foreach ($scheduledGames as $game) {
            $this->mapScheduledGame($game);
            $scheduledGamesDump[] = $this->formatAsArray($game);
        }

        return $scheduledGamesDump;
    }
}