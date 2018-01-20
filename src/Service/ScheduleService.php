<?php

namespace App\Service;


use App\Entity\ScheduledGame;
use App\Repository\DefaultScheduleRepository;
use App\Repository\GameLocationRepository;
use App\Repository\ScheduledGameRepository;
use App\Repository\TeamInformationRepository;

use Psr\Log\LoggerInterface;

class ScheduleService
{
    /** @var DefaultScheduleRepository */
    protected $defaultScheduleRepo;

    /** @var GameLocationRepository */
    protected $gameLocationRepo;

    /** @var TeamInformationRepository */
    protected $teamInformationRepo;

    /** @var ScheduledGameRepository */
    protected $scheduledGameRepo;

    /** @var DateUtilityService */
    protected $dateUtilityService;

    /** @var LoggerInterface */
    protected $logger;

    /** @var null|\DateTime */
    protected $scheduleStartDate = null;

    /** @var null|int */
    protected $gameLengthInMins = null;

    /** @var int */
    protected $maxWeeksToSchedule;

    public function __construct(
        DefaultScheduleRepository $defaultScheduleRepository,
        GameLocationRepository $gameLocationRepository,
        TeamInformationRepository $teamInformationRepository,
        ScheduledGameRepository $scheduledGameRepository,
        DateUtilityService $dateUtilityService,
        LoggerInterface $logger
    )
    {
        $this->defaultScheduleRepo = $defaultScheduleRepository;
        $this->gameLocationRepo = $gameLocationRepository;
        $this->teamInformationRepo = $teamInformationRepository;
        $this->scheduledGameRepo = $scheduledGameRepository;
        $this->dateUtilityService = $dateUtilityService;
        $this->logger = $logger;
    }

    public function generateSchedule(
        string $scheduleStartDate,
        int $gameLengthInMins
    ) {

        $this->scheduleStartDate = $scheduleStartDate;
        $this->gameLengthInMins = $gameLengthInMins;

        /**
         * Figure out how weeks in schedule by determining size of each division and number of games for division in default schedule
         */
        $this->reviewScheduleRequirements();

        /**
         * @todo Using default schedule, build the set of games that need to be scheduled into Games_To_Schedule table
         */
        $this->createTimeSlots();

        /**
         * @todo Start processing games to be scheduled and fit them into the schedule
         */

        return true;
    }

    private function createTimeSlots()
    {
        /**
         * @todo Create time slots using game information and save to Generated_Schedule table in DB for nr of weeks in schedule
         */
        // For now, we assume our first week is a full week so we don't have any partial week of timeslots to create
        // For now, we assume we start on Mon
        for ($weekNr = 1; $weekNr <= $this->maxWeeksToSchedule; $weekNr++) {
            // Get the start date for the current week
            //    - we do it this way as we don't know how many days we actually built with scheduled games in the
            //      previous week
            $dateToSchedule = new \DateTime($this->scheduleStartDate);
            $daysToShift = new \DateInterval("P1D");
            $daysToShift->d = ($weekNr - 1) * 7;
            $dateToSchedule->add($daysToShift);
            $this->logger->info("Start of current week [" . $weekNr ."]: " . $dateToSchedule->format("l, Y-m-d"));

            for ($dow = 1; $dow <= 7; $dow++) {
                // value runs 1 (Mon) thru 7 (Sun)
                $dayOfWeekValue = (int) $dateToSchedule->format("N");
                $this->logger->debug("Day of Week: " . $dayOfWeekValue);
                $dbData = $this->gameLocationRepo
                    ->fetchGamesSlatedForDayOfWeek(
                        $this->dateUtilityService->getDayOfWeekText($dayOfWeekValue)
                    );
                if (!empty($dbData)) {
                    $this->logger->debug("DBDATA:\n" . print_r($dbData, true));
                    foreach ($dbData as $row) {
                        $availableTime = $row->getEndAvailable()->diff($row->getStartAvailable());
                        $availableMins =
                            (int) $availableTime->format('%h') * 60 +
                            (int) $availableTime->format('%i');
                        $availableSlots = floor($availableMins / $this->gameLengthInMins);
                        $this->logger->debug("Start:\n" . print_r($row->getStartAvailable(), true));
                        $this->logger->debug("End:\n" . print_r($row->getEndAvailable(), true));

                        $this->logger->debug("Available time:\n" . print_r($availableTime, true));
                        $this->logger->debug("Available minutes: " . $availableMins);
                        $this->logger->debug("Number of slots available:\n" . print_r($availableSlots, true));

                        $timeToSchedule = $row->getStartAvailable();
//                        $gameInterval = new \DateInterval("T" . $this->gameLengthInMins . "M");
                        $gameInterval = new \DateInterval("PT" . $this->gameLengthInMins . "M");
                        for ($slots = 1; $slots <= $availableSlots; $slots++) {
                            $scheduledGame = new ScheduledGame();
                            $scheduledGame
                                ->setGameDate($dateToSchedule)
                                ->setGameTime($timeToSchedule)
                                ->setGameLocation($row->getGameLocationName());
                            $this->scheduledGameRepo->save($scheduledGame);
                            $timeToSchedule->add($gameInterval);
                            unset($scheduledGame);
                        }
                    }
                    echo "    Schedule date: " . $dateToSchedule->format("l, Y-m-d") . "\n";
                }

                // At the end we wish to move to the next day
                $daysToShift->d = 1;
                $dateToSchedule->add($daysToShift);
            };

            // Destroy the date counters for the current week, as we will build new ones for the next week
            unset($daysToShift);
            unset($dateToSchedule);
        }
    }

    private function reviewScheduleRequirements()
    {
        $this->maxWeeksToSchedule = 0;

        $divTeamInfo = $this->teamInformationRepo->getNrOfTeamsInDiv();
        foreach ($divTeamInfo as $key => $divisionInfo) {
            $weeksInSchedule = $this->defaultScheduleRepo->getScheduleLengthInWeeks($divisionInfo['NrTeamsInDiv']);
            if ($weeksInSchedule == 0) {
                die("ERROR: Unable to find default schedule for division " . $divisionInfo['teamDivision'] . " containing " . $divisionInfo['NrTeamsInDiv'] . " teams\n");
            }
            $this->maxWeeksToSchedule = max($weeksInSchedule, $this->maxWeeksToSchedule);
        }

        echo "-- we need to build a schedule covering " . $this->maxWeeksToSchedule . " weeks\n";
    }
}