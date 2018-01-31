<?php

namespace App\Service;


use App\Entity\GameLocation;
use App\Entity\GameToSchedule;
use App\Entity\ScheduledGame;
use App\Entity\TeamInformation;
use App\Repository\DefaultScheduleRepository;
use App\Repository\GameLocationRepository;
use App\Repository\GameToScheduleRepository;
use App\Repository\ScheduledGameRepository;
use App\Repository\TeamInformationRepository;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ScheduleService
 * @package App\Service
 */
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

    /** @var SymfonyStyle */
    protected $io;

    /**
     * ScheduleService constructor.
     *
     * @param DefaultScheduleRepository $defaultScheduleRepository
     * @param GameLocationRepository    $gameLocationRepository
     * @param TeamInformationRepository $teamInformationRepository
     * @param ScheduledGameRepository   $scheduledGameRepository
     * @param GameToScheduleRepository  $gameToScheduleRepository
     * @param DateUtilityService        $dateUtilityService
     * @param LoggerInterface           $logger
     */
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
     * @param SymfonyStyle $io
     *
     * @return bool
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function generateSchedule(
        string $scheduleStartDate,
        int $gameLengthInMins,
        array $flowControl,
        SymfonyStyle $io
    ) {

        $this->scheduleStartDate = $scheduleStartDate;
        $this->gameLengthInMins = $gameLengthInMins;
        $this->flowControl = $flowControl;
        $this->io = $io;

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

        // Do the heavy lifting now - generate that dang schedule from all the pieces we have
        $this->executeSchedulingLogic();

        return true;
    }

    private function truncateScheduleTables()
    {
        $this->io->warning("Deleting existing scheduling details");
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
                $this->io->error("ERROR: Unable to find default schedule for division " . $divisionInfo['teamDivision'] . " containing " . $divisionInfo['NrTeamsInDiv'] . " teams");
                die();
            }
            $this->maxWeeksToSchedule = max($weeksInSchedule, $this->maxWeeksToSchedule);

            $this->io->text("--- prepare Division " . $divisionInfo['teamDivision'] . " schedule details");
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
                /** @var TeamInformation $homeTeamInfo */
                $homeTeamInfo = $this->teamInformationRepo->find($homeTeamId);
                $gameToSchedule
                    ->setWeekNr($divisionSchedule->getWeekNr())
                    ->setVisitTeamId($visitTeamId)
                    ->setHomeTeamId($homeTeamId)
                    ->setDivision($homeTeamInfo->getTeamDivision());
                $this->gameToScheduleRepo->save($gameToSchedule);
                unset($gameToSchedule);
            }
        }

        $this->io->text("--- Need to build a schedule covering " . $this->maxWeeksToSchedule . " weeks");
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
            $weekDatesInfo = $this->convertWeekNrToCalendarDates($weekNr);
            $this->logger->info("Start of current week [" . $weekNr ."]: " . $weekDatesInfo['start']->format("l, Y-m-d"));

            $daysToShift = new \DateInterval("P1D");

            for ($dow = 1; $dow <= 7; $dow++) {
                // value runs 1 (Mon) thru 7 (Sun)
                $dayOfWeekValue = (int) $weekDatesInfo['start']->format("N");
                $this->logger->debug("Day of Week: " . $dayOfWeekValue);
                $dbData = $this->gameLocationRepo
                    ->fetchGamesSlatedForDayOfWeek(
                        $this->dateUtilityService->getDayOfWeekText($dayOfWeekValue)
                    );
                if (!empty($dbData)) {
                    $this->processTimeslotsForWeek($dbData, $weekDatesInfo['start']);
                    $this->io->text("    Schedule date: " . $weekDatesInfo['start']->format("l, Y-m-d"));
                }

                // At the end we wish to move to the next day
                $daysToShift->d = 1;
                $weekDatesInfo['start']->add($daysToShift);
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
    private function executeSchedulingLogic()
    {
        $this->io->success("Schedule preparation complete. Now doing actual schedule building");

        // Get the list of team divisions, so we can generate a random order to process each week of the schedule
        $divisionList = $this->teamInformationRepo->getListOfTeamDivisions();
        $this->logger->debug("DIVISION LIST:\n" . print_r($divisionList, true));

        $divisionList = $this->createRandomDivisionOrder($divisionList);
        $this->io->text("--- Looping through the games to be scheduled and finding time slots");
        for ($weekNr = 1; $weekNr <= $this->maxWeeksToSchedule; $weekNr++) {
            $this->io->text("\n        starting scheduling for week " . $weekNr);
            foreach ($divisionList as $division) {
                $this->io->text("            processing Division " . $division);
                $this->scheduleDivisionGamesInWeek($division, $weekNr);
            }
        }
    }

    /**
     * @param $divisionList
     *
     * @return string[]
     * @throws \Exception if error occurs
     */
    private function createRandomDivisionOrder($divisionList)
    {
        $rc = shuffle($divisionList);
        if (!$rc) {
            throw new \Exception(
                "Error in " . __METHOD__ . ": unable to create random order of divisions",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $message = "--- division order randomized as:\n";
        $subMessage = "";
        foreach ($divisionList as $division) {
            $subMessage .= empty($subMessage) ? "        " : ", ";
            $subMessage .= $division;
        }
        $this->io->text($message . $subMessage);

        return $divisionList;
    }

    /**
     * Schedule games for a specified division for the requested week
     *
     * @param string $division
     * @param int    $weekNr
     */
    private function scheduleDivisionGamesInWeek(string $division, int $weekNr)
    {
        $gamesToSchedule = $this->gameToScheduleRepo->findGamesForDivInWeek($division, $weekNr);
        if (empty($gamesToSchedule)) {
            $this->io->text("             - [NOTE] No games for division for week");
            return;
        }

        $this->logger->debug("Week: " . $weekNr . "\nDivision: " . $division . "\n" . print_r($gamesToSchedule, true));

        $weekDatesInfo = $this->convertWeekNrToCalendarDates($weekNr);
        foreach ($gamesToSchedule as $game) {
            $slotsAvail = $this->scheduledGameRepo->findAvailSlots($weekDatesInfo['start'], $weekDatesInfo['end']);
            $slotsAvail = $this->filterAvailSlots($slotsAvail);
            $this->assignSlot($slotsAvail);
            $this->gameToScheduleRepo->remove($game->getGameToScheduleId());
        }
    }

    /**
     * @param ScheduledGame[] $slotsAvail
     *
     * @return ScheduledGame[]
     */
    private function filterAvailSlots(array $slotsAvail)
    {
        $this->logger->debug("Available Slots:\n" . print_r($slotsAvail, true));

        return $slotsAvail;
    }

    /**
     * @param ScheduledGame[] $slotsAvail
     */
    private function assignSlot(array $slotsAvail)
    {
        return;
    }

    /**
     * @param $weekNr
     *
     * @return \DateTime[]
     */
    private function convertWeekNrToCalendarDates(int $weekNr): array
    {
        $weekDatesInfo['start'] = new \DateTime($this->scheduleStartDate);
        $daysToShift = new \DateInterval("P1D");
        $daysToShift->d = ($weekNr - 1) * 7;
        $weekDatesInfo['start']->add($daysToShift);

        $daysToShift->d = 6;
        $weekDatesInfo['end'] = new \DateTime($weekDatesInfo['start']->format("Y-m-d"));
        $weekDatesInfo['end']->add($daysToShift);


        return $weekDatesInfo;
    }
}
