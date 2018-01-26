<?php

namespace App\Service;


use App\Entity\GameLocation;
use App\Entity\GameToSchedule;
use App\Entity\ScheduledGame;
use App\Repository\DefaultScheduleRepository;
use App\Repository\GameLocationRepository;
use App\Repository\GameToScheduleRepository;
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

    /** @var GameToScheduleRepository */
    protected $gameToScheduleRepo;

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

    protected $flowControl = array(
        'generateGamesToSchedule' => false,
    );

    public function __construct(
        DefaultScheduleRepository $defaultScheduleRepository,
        GameLocationRepository $gameLocationRepository,
        TeamInformationRepository $teamInformationRepository,
        ScheduledGameRepository $scheduledGameRepository,
        GameToScheduleRepository $gameToScheduleRepository,
        DateUtilityService $dateUtilityService,
        LoggerInterface $logger
    )
    {
        $this->defaultScheduleRepo = $defaultScheduleRepository;
        $this->gameLocationRepo = $gameLocationRepository;
        $this->teamInformationRepo = $teamInformationRepository;
        $this->scheduledGameRepo = $scheduledGameRepository;
        $this->gameToScheduleRepo = $gameToScheduleRepository;
        $this->dateUtilityService = $dateUtilityService;
        $this->logger = $logger;
    }

    /**
     * @param string $scheduleStartDate
     * @param int    $gameLengthInMins
     * @param array  $flowControl
     *
     * @return bool
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function generateSchedule(
        string $scheduleStartDate,
        int $gameLengthInMins,
        array $flowControl
    ) {

        $this->scheduleStartDate = $scheduleStartDate;
        $this->gameLengthInMins = $gameLengthInMins;
        $this->flowControl = $flowControl;

        if ($this->flowControl['generateGamesToSchedule']) {
            // Get rid of the current schedule details
            $this->truncateScheduleTables();

            /**
             * Determine division sizes involved and max weeks we need to schedule while preparing table with all
             * the games we will schedule
             */
            $this->determineGamesToSchedule();

            /**
             * Using default game location information, build the set of game slots that can be used
             */
             $this->createTimeSlots();
        }

        /**
         * @todo Start processing games to be scheduled and fit them into the schedule
         *       1. Randomly or in a set sequence, pick a division
         *       2. For each game in that division
         *          - create list of acceptable dates to schedule the game on
         *              . avoid n games in a row on same night*
         *              . avoid n games in a row at the same time*
         *              . maintain 40% usage of both diamonds
         *          - randomly pick on of the acceptable dates
         *          - copy game details into Scheduled_Game table
         *          - delete game details from Game_To_Schedule
         *
         *       * denotes value may be set by team or set to ignore for team
         * @todo When team schedule preferences defined, do scheduling in reverse order of complexity
         */

        return true;
    }

    private function truncateScheduleTables()
    {
        echo "--- deleting any existing scheduling details\n";
        $this->gameToScheduleRepo->truncate();
        $this->scheduledGameRepo->truncate();
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function determineGamesToSchedule()
    {
        $this->maxWeeksToSchedule = 0;

        $divTeamInfo = $this->teamInformationRepo->getNrOfTeamsInDiv();
        foreach ($divTeamInfo as $key => $divisionInfo) {
            $weeksInSchedule = $this->defaultScheduleRepo->getScheduleLengthInWeeks($divisionInfo['NrTeamsInDiv']);
            if ($weeksInSchedule == 0) {
                die("ERROR: Unable to find default schedule for division " . $divisionInfo['teamDivision'] . " containing " . $divisionInfo['NrTeamsInDiv'] . " teams\n");
            }
            $this->maxWeeksToSchedule = max($weeksInSchedule, $this->maxWeeksToSchedule);

            echo "--- prepare Division " . $divisionInfo['teamDivision'] . " schedule details\n";
            $defaultScheduleInfo = $this->defaultScheduleRepo->getDefaultSchedule($divisionInfo['NrTeamsInDiv']);
            foreach ($defaultScheduleInfo as $divisionSchedule) {
                $gameToSchedule = new GameToSchedule();
                $visitTeamId = $this->teamInformationRepo->mapTeamDivIdToTeamId(
                    $divisionSchedule->getVisitTeamId(),
                    $divisionInfo['teamDivision']
                );
                $homeTeamId = $this->teamInformationRepo->mapTeamDivIdToTeamId(
                    $divisionSchedule->getHomeTeamId(),
                    $divisionInfo['teamDivision']
                );
                $gameToSchedule
                    ->setWeekNr($divisionSchedule->getWeekNr())
                    ->setVisitTeamId($visitTeamId)
                    ->setHomeTeamId($homeTeamId);
                $this->gameToScheduleRepo->save($gameToSchedule);
                unset($gameToSchedule);
            }
        }

        echo "--- Need to build a schedule covering " . $this->maxWeeksToSchedule . " weeks\n";
    }

    /**
     *
     */
    private function createTimeSlots()
    {
        /**
         * Create time slots using game information and save to Generated_Schedule table in DB for nr of weeks in schedule
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
                    $this->processTimeslotsForWeek($dbData, $dateToSchedule);
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

    /**
     * @param GameLocation[] $dbData
     * @param \DateTime $dateToSchedule
     */
    private function processTimeslotsForWeek(array $dbData, \DateTime $dateToSchedule): void
    {
        $gameInterval = new \DateInterval("PT" . $this->gameLengthInMins . "M");

        foreach ($dbData as $row) {
            $availableTime = $row->getEndAvailable()->diff($row->getStartAvailable());
            $availableMins =
                (int) $availableTime->format('%h') * 60 +
                (int) $availableTime->format('%i');
            $availableSlots = floor($availableMins / $this->gameLengthInMins);
            $this->logger->debug("Available minutes: " . $availableMins);
            $this->logger->debug("Number of slots available:\n" . print_r($availableSlots, true));

            // Had to create a new object otherwise if I assigned the data from $row directly, I had a weird bug where
            // 2nd and subsequent weeks wouldn't work as the start time for the records in $dbData was always 23:00
            // so no intervals existed.
            $timeToSchedule = new \DateTime($row->getStartAvailable()->format("H:i:s"));
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
    }
}