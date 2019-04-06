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

use DateInterval;
use DateTime;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ScheduleService
 * @package App\Service
 */
class ScheduleService
{
    // Number of consecutive games allowed on same day of week
    const MAX_CONSECUTIVE_SAME_DAY = 4;

    // Number of consecutive games allowed at same time
    const MAX_CONSECUTIVE_SAME_TIME = 3;

    // Chance that a slot will not be removed from consideration
    // This is used for team preference slot removals to determine when a slot will be kept instead of removed
    // Meant to represent percentage chance of 100 that slot will not be deleted
    const SLOT_PRESERVATION_LIMIT = 10;

    // Do not balance team dow scheduling until this specified week
    const SKIP_SCHEDULE_BALANCE_UNTIL_WEEK = 4;

    // Number of games that comprise half the schedule - some logic may adjust itself depending if scheduling
    // games in the first or 2nd half of the season.
    const SCHEDULE_MIDPOINT_GAMES = 8;

    private $dowScheduleBalanceFactors = array(
        'Mon' => array(
            'low' => 15,
            'high' => 30,
        ),
        'Tue' => array(
            'low' => 30,
            'high' => 60,
        ),
        'Wed' => array(
            'low' => 30,
            'high' => 60,
        ),
    );

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

    /** @var GameToScheduleService */
    protected $gameToScheduleService;

    /** @var DateUtilityService */
    protected $dateUtilityService;

    /** @var LoggerInterface */
    protected $logger;

    /** @var null|DateTime */
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

    /** @var string */
    protected $collectedSchedulingNotes;

    /** @var resource */
    private $executionLog;

    /**
     * ScheduleService constructor.
     *
     * @param DefaultScheduleRepository $defaultScheduleRepository
     * @param GameLocationRepository    $gameLocationRepository
     * @param TeamInformationRepository $teamInformationRepository
     * @param ScheduledGameRepository   $scheduledGameRepository
     * @param GameToScheduleRepository  $gameToScheduleRepository
     * @param GameToScheduleService     $gameToScheduleService
     * @param DateUtilityService        $dateUtilityService
     * @param LoggerInterface           $logger
     */
    public function __construct(
        DefaultScheduleRepository $defaultScheduleRepository,
        GameLocationRepository $gameLocationRepository,
        TeamInformationRepository $teamInformationRepository,
        ScheduledGameRepository $scheduledGameRepository,
        GameToScheduleRepository $gameToScheduleRepository,
        GameToScheduleService $gameToScheduleService,
        DateUtilityService $dateUtilityService,
        LoggerInterface $logger
    )
    {
        $this->defaultScheduleRepo = $defaultScheduleRepository;
        $this->gameLocationRepo = $gameLocationRepository;
        $this->teamInformationRepo = $teamInformationRepository;
        $this->scheduledGameRepo = $scheduledGameRepository;
        $this->gameToScheduleRepo = $gameToScheduleRepository;
        $this->gameToScheduleService = $gameToScheduleService;
        $this->dateUtilityService = $dateUtilityService;
        $this->logger = $logger;
    }

    /**
     * Generate schedule - determine games to schedule, create open slots and assign games to slots
     *
     * @param string $scheduleStartDate
     * @param int    $gameLengthInMins
     * @param array  $flowControl
     * @param SymfonyStyle $io
     *
     * @return bool
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

        $this->executionLog = fopen( "/tmp/scheduleExecution.log", "w");
        if ($this->executionLog === false) {
            $io->error("Unable to create execution log file");
            die();
        }

        $this->writeToExecutionLog("\n==============================\nScheduler\n==============================\n");

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
             $this->deleteSkippedTimeSlots();
        }

        // Do the heavy lifting now - generate that dang schedule from all the pieces we have
        $this->executeSchedulingLogic();

        // See if there was any games left to schedule
        $this->io->note("Games left to schedule: " . $this->gameToScheduleRepo->howManyGamesLeftToSchedule());

        return true;
    }

    /**
     * Write message to execution log
     *
     * @param string $message
     */
    private function writeToExecutionLog(string $message)
    {
        fwrite($this->executionLog, $message);
    }

    /**
     * Delete existing schedule details
     */
    private function truncateScheduleTables()
    {
        $this->io->warning("Deleting existing scheduling details");
        $this->gameToScheduleRepo->truncate();
        $this->scheduledGameRepo->truncate();
    }

    /**
     * Create list of games to be scheduled
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
     * Create open slots for complete schedule
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

            $daysToShift = new DateInterval("P1D");

            for ($dow = 1; $dow <= 7; $dow++) {
                // value runs 1 (Mon) thru 7 (Sun)
                $dayOfWeekValue = (int) $weekDatesInfo['start']->format("N");
                $this->logger->debug("Day of Week: " . $dayOfWeekValue);
                $dbData = $this->gameLocationRepo
                    ->fetchLocationsSlatedForDayOfWeek(
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
     * Process deletion of timeslots created on dates we are to skip
     *
     * @todo This function should also respect the times on the skipped days so you can remove just some slots.
     */
    private function deleteSkippedTimeSlots()
    {
        $dbData = $this->gameLocationRepo->fetchSkippedDates();
        foreach ($dbData as $gameDateToDelete) {
            $this->scheduledGameRepo->deleteSlotsForDate($gameDateToDelete['dayOfWeek']);
        }
    }

    /**
     * Create open slots in the schedule
     *
     * @param GameLocation[] $dbData
     * @param DateTime $dateToSchedule
     */
    private function processTimeslotsForWeek(array $dbData, DateTime $dateToSchedule): void
    {
        $gameInterval = new DateInterval("PT" . $this->gameLengthInMins . "M");

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
            $timeToSchedule = new DateTime($row->getStartAvailable()->format("H:i:s"));
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
     * Start processing games to be scheduled and fit them into the schedule
     *
     * Logic used
     *       1. Randomly or in a set sequence, pick a division
     *       2. For each game in that division
     *          - create list of acceptable dates to schedule the game on
     *              . avoid n games in a row on same night*
     *              . @todo avoid n games in a row at the same time*
     *              . @todo maintain 40% usage of both diamonds
     *          - randomly pick on of the acceptable dates
     *          - copy game details into Scheduled_Game table
     *          - delete game details from Game_To_Schedule
     *
     *       * denotes value may be set by team or set to ignore for team
     *       * denotes both teams are initially included but if no slots are available
     *         then may check only one of the teams
     *
     * @todo When team schedule preferences defined, do scheduling in reverse order of complexity
     */
    private function executeSchedulingLogic()
    {
        $this->io->success("Schedule preparation complete. Now doing actual schedule building");

        // Get the list of team divisions, so we can generate a random order to process each week of the schedule
        $divisionList = $this->teamInformationRepo->getListOfTeamDivisions();

        $divisionList = $this->createRandomDivisionOrder($divisionList);
        $this->io->text("--- Looping through the games to be scheduled and finding time slots");
        for ($weekNr = 1; $weekNr <= $this->maxWeeksToSchedule; $weekNr++) {
            $weekDatesInfo = $this->convertWeekNrToCalendarDates($weekNr);
            $this->io->text("\n        starting scheduling for week " . $weekNr . " [" . $weekDatesInfo['start']->format("l, Y-m-d") .  "]");
            foreach ($divisionList as $division) {
                $this->io->text("            processing Division " . $division);
                $this->scheduleDivisionGamesInWeek($division, $weekNr, $weekDatesInfo);
            }

            // Shuffle divisions up in the order for next week (top goes to the bottom)
            $divisionList = $this->reorderDivisionOrder($divisionList);
        }
    }

    /**
     * Create a random ordering of the divisions
     *
     * @param $divisionList
     *
     * @return string[]
     * @throws Exception if error occurs
     */
    private function createRandomDivisionOrder($divisionList)
    {
        $rc = shuffle($divisionList);
        if (!$rc) {
            throw new Exception(
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
     * Move order of divisions up one, with top being bumped to the bottom
     *
     * @param string[] $divisionList
     *
     * @return string[]
     */
    private function reorderDivisionOrder(array $divisionList) : array
    {
        $newDivisionList = array();
        for ($i = 1; $i < count($divisionList); $i++) {
            $newDivisionList[] = $divisionList[$i];
        }
        $newDivisionList[] = $divisionList[0];

        return $newDivisionList;
    }

    /**
     * Schedule games for a specified division for the requested week
     *
     * @param string $division
     * @param int    $weekNr
     * @param DateTime[] $weekDatesInfo
     */
    private function scheduleDivisionGamesInWeek(string $division, int $weekNr, array $weekDatesInfo)
    {
        $gamesToSchedule = $this->gameToScheduleRepo->findGamesForDivInWeek($division, $weekNr);
        if (empty($gamesToSchedule)) {
            $this->io->text("             - [NOTE] No games for division for week");
            return;
        }

        foreach ($gamesToSchedule as $game) {
            $this->writeToExecutionLog($this->gameToScheduleService->dumpGameToSchedule($game));
            $this->collectedSchedulingNotes = '';
            $slotsAvail = $this->scheduledGameRepo->findAvailSlots($weekDatesInfo['start'], $weekDatesInfo['end']);
            $slotsAvail = $this->filterAvailSlots($game, $slotsAvail, $weekDatesInfo);
            $gameIsScheduled = $this->assignSlot($game, $slotsAvail);
            $this->writeToExecutionLog($this->collectedSchedulingNotes);
            if ($gameIsScheduled) {
                $this->gameToScheduleRepo->remove($game->getGameToScheduleId());
                $this->writeToExecutionLog("Marking game as scheduled\n");
            } else {
                $this->writeToExecutionLog("** UNABLE TO FIND SLOT FOR GAME **\n");
            }
            $this->writeToExecutionLog("\n------------------------------\n\n");
        }
    }

    /**
     * Review list of available slots and see if any should be removed due to recent date or time overusage
     *
     * @param GameToSchedule $game
     * @param ScheduledGame[] $slotsAvail
     * @param DateTime[] $weekDatesInfo
     *
     * @return ScheduledGame[]
     */
    private function filterAvailSlots(GameToSchedule $game, array $slotsAvail, array $weekDatesInfo)
    {
        $this->collectGameSchedulingNotes(
            "Number of available slots to begin with: " . count($slotsAvail)
        );

        // Perform team specific checks, if there is at least more than 1 slot under consideration
        if (count($slotsAvail) > 1) {
            $slotsAvail = $this->reviewTeamPreferences($game, $slotsAvail);
            $this->collectGameSchedulingNotes(
                "Number of available slots after processing team preferences  " . count($slotsAvail)
            );
        }

        if (count($slotsAvail) > 1 && $weekDatesInfo['weekNr'] >= self::SKIP_SCHEDULE_BALANCE_UNTIL_WEEK) {
            $slotsAvail = $this->reviewScheduleBalance($game, $slotsAvail);
            $this->collectGameSchedulingNotes(
                "Number of available slots after reviewing schedule balance: " . count($slotsAvail)
            );
        }

        // Perform standard checks that all teams benefit from - as long as there is more than 1 slot still under
        // consideration
        if (count($slotsAvail) > 1) {
            $slotsAvail = $this->reviewRecentDays($game, $slotsAvail, $weekDatesInfo);
            $this->collectGameSchedulingNotes(
                "Number of available slots after consecutive day review: " . count($slotsAvail)
            );
        }

        // For OTSL, this check should be commented out since all games are at the same time
        if (count($slotsAvail) > 1) {
            $slotsAvail = $this->reviewRecentTimes($game, $slotsAvail, $weekDatesInfo);
            $this->collectGameSchedulingNotes(
                "Number of available slots after consecutive time slot review: " . count($slotsAvail)
            );
        }

        return $slotsAvail;
    }

    /**
     * From list of available slots, randomly pick one and assign the specified game to it
     *
     * @param GameToSchedule $game
     * @param ScheduledGame[] $slotsAvail
     *
     * @return bool  TRUE if game assigned to timeslot, false otherwise
     */
    private function assignSlot(GameToSchedule $game, array $slotsAvail)
    {
        if (count($slotsAvail) < 1) {
            $this->io->text("             [WARNING] No slot available for scheduling game: " . $game->getGameToScheduleId());
            return false;
        }

        $slotToUse = mt_rand(0, count($slotsAvail) - 1);
        $slotsAvail[$slotToUse]
            ->setDivision($game->getDivision())
            ->setVisitTeamId($game->getVisitTeamId())
            ->setHomeTeamId($game->getHomeTeamId())
            ->setTemplateScheduleWeekNumber($game->getWeekNr())
//            ->setSchedulingNotes($this->collectedSchedulingNotes)
        ;
        $this->scheduledGameRepo->save($slotsAvail[$slotToUse]);

        return true;
    }

    /**
     * Determine actual date of first day of a given week
     *
     * Assumes week numbers start at 1
     *
     * @param $weekNr
     *
     * @return DateTime[]
     */
    private function convertWeekNrToCalendarDates(int $weekNr): array
    {
        $weekDatesInfo['weekNr'] = $weekNr;

        $weekDatesInfo['start'] = new DateTime($this->scheduleStartDate);
        $daysToShift = new DateInterval("P1D");
        $daysToShift->d = ($weekNr - 1) * 7;
        $weekDatesInfo['start']->add($daysToShift);

        $daysToShift->d = 6;
        $weekDatesInfo['end'] = new DateTime($weekDatesInfo['start']->format("Y-m-d"));
        $weekDatesInfo['end']->add($daysToShift);

        return $weekDatesInfo;
    }

    /**
     * @param GameToSchedule $game
     * @param ScheduledGame[] $slotsAvail
     *
     * @return array
     */
    private function reviewScheduleBalance(GameToSchedule $game, array $slotsAvail)
    {
        $this->collectGameSchedulingNotes("- Balancing home team's schedule");
        $slotsAvail = $this->reviewBalanceForTeam($game->getHomeTeamId(), $slotsAvail);
        if (count($slotsAvail) > 1) {
            $this->collectGameSchedulingNotes("- Balancing visiting team's schedule");
            $slotsAvail = $this->reviewBalanceForTeam($game->getVisitTeamId(), $slotsAvail);
        }

        return $slotsAvail;
    }

    /**
     * @param int   $teamId
     * @param ScheduledGame[] $slotsAvail
     *
     * @return array
     */
    private function reviewBalanceForTeam(int $teamId, array $slotsAvail)
    {
        $dayOfWeekBreakdown = $this->scheduledGameRepo->getDayOfWeekBreakdownForTeam($teamId);
        $totalGames = array_sum($dayOfWeekBreakdown);
        foreach ($dayOfWeekBreakdown as $dow => $gamesPlayedOnDay) {
            if (in_array($dow, array('Sun', 'Thu', 'Fri', 'Sat'))) {
                continue;
            }
            $slotsAvail = $this->reviewUseOfGivenDayOfWeek($totalGames, $gamesPlayedOnDay, $dow, $slotsAvail);
        }

        return $slotsAvail;
    }



    /**
     * Look over past games and see if some available slots should be removed due to recent over use by day of week
     *
     * This method will review the past n weeks scheduled games and if those games have exceeded a specific limit
     * for a single day of the week, then any open slots for that day of week will be removed from scheduling
     * consideration
     *
     * @todo The value of n could be standard constant or a team-specific value
     * 'past n weeks' - n is the value of a MAX_CONSECUTIVE_SAME_DAY constant
     *
     * @param GameToSchedule $game
     * @param ScheduledGame[] $slotsAvail
     * @param DateTime[] $weekDatesInfo
     *
     * @return ScheduledGame[]
     */
    private function reviewRecentDays(GameToSchedule $game, array $slotsAvail, array $weekDatesInfo)
    {
        // We need to move n weeks back, where n is the number of consecutive weeks
        $startDate = date(
            "Y-m-d",
            strtotime($weekDatesInfo['start']->format("Y-m-d") . " " . self::MAX_CONSECUTIVE_SAME_DAY . "weeks ago")
        );

        // Check out Home team
        $pastGames = $this->scheduledGameRepo->findPastGamesForTeamId(
            $game->getHomeTeamId(),
            $startDate,
            $weekDatesInfo['end']->format("Y-m-d")
        );
        $dayOfWeek = $this->sameDayOfWeek($pastGames);
        if (!empty($dayOfWeek)) {
            $this->collectGameSchedulingNotes("Removing consecutive time slot issue for Home team: " . $dayOfWeek);
            $slotsAvail = $this->removeSlotsByDay($dayOfWeek, $slotsAvail);
        }

        // Bail if only 1 slot left
        if (count($slotsAvail) <= 1) {
            return $slotsAvail;
        }

        // Check out Away team
        $pastGames = $this->scheduledGameRepo->findPastGamesForTeamId(
            $game->getVisitTeamId(),
            $startDate,
            $weekDatesInfo['end']->format("Y-m-d")
        );
        $dayOfWeek = $this->sameDayOfWeek($pastGames);
        if (!empty($dayOfWeek)) {
            $this->collectGameSchedulingNotes("Removing consecutive time slot issue for Away team: " . $dayOfWeek);
            $slotsAvail = $this->removeSlotsByDay($dayOfWeek, $slotsAvail);
        }

        return $slotsAvail;
    }

    /**
     * Look over past games and see if some available slots should be removed due to recent over use of given time slot
     *
     * This method will review the past n weeks scheduled games and if those games have exceeded a specific limit
     * for a single time slot, then any open slots for that time will be removed from scheduling
     * consideration
     *
     * @todo The value of n could be standard constant or a team-specific value
     * 'past n weeks' - n is the value of a MAX_CONSECUTIVE_SAME_TIME constant
     *
     * @param GameToSchedule $game
     * @param ScheduledGame[] $slotsAvail
     * @param DateTime[] $weekDatesInfo
     *
     * @return ScheduledGame[]
     */
    private function reviewRecentTimes(GameToSchedule $game, array $slotsAvail, array $weekDatesInfo)
    {
        // We need to move n weeks back, where n is the number of consecutive weeks
        $startDate = date(
            "Y-m-d",
            strtotime($weekDatesInfo['start']->format("Y-m-d") . " " . self::MAX_CONSECUTIVE_SAME_TIME . "weeks ago")
        );

        // Check Home team
        $pastGames = $this->scheduledGameRepo->findPastGamesForTeamId(
            $game->getHomeTeamId(),
            $startDate,
            $weekDatesInfo['end']->format("Y-m-d")
        );
        $timeSlot = $this->sameTimeSlot($pastGames);
        if (!empty($timeSlot)) {
            $this->collectGameSchedulingNotes("Removing consecutive time slot issue for Home team: " . $timeSlot);
            $slotsAvail = $this->removeSlotsByTime($timeSlot, $slotsAvail);
        }

        // Bail if we have just one slot left
        if (count($slotsAvail) <= 1) {
            return $slotsAvail;
        }

        // Check Visiting team
        $pastGames = $this->scheduledGameRepo->findPastGamesForTeamId(
            $game->getVisitTeamId(),
            $startDate,
            $weekDatesInfo['end']->format("Y-m-d")
        );
        $timeSlot = $this->sameTimeSlot($pastGames);
        if (!empty($timeSlot)) {
            $this->collectGameSchedulingNotes("Removing consecutive time slot issue for Away team: " . $timeSlot);
            $slotsAvail = $this->removeSlotsByTime($timeSlot, $slotsAvail);
        }

        return $slotsAvail;
    }

    /**
     * Checks a range of provided dates to see if the Day of Week value exceeds a fixed limit
     *
     * @param ScheduledGame[] $pastGames
     *
     * @return string
     */
    private function sameDayOfWeek(array $pastGames)
    {
        $dayOfWeekToFilter = null;
        $consecutiveStreak = 0;
        $index = 1;
        $lastDate = null;

        foreach ($pastGames as $dateToCheck) {
            // If first time through, we set last date to the current date; this will force the
            // next check to pass (since we are comparing against the same date) so the streak will
            // increase to 1 which is fair game
            $lastDate = empty($lastDate) ? $dateToCheck->getGameDate()->format("D") : $lastDate;
            if ($lastDate == $dateToCheck->getGameDate()->format("D")) {
                $consecutiveStreak++;
            } else {
                $consecutiveStreak = 0;
            }
            $index++;
        }

        if ($consecutiveStreak >= self::MAX_CONSECUTIVE_SAME_DAY) {
            $dayOfWeekToFilter = $pastGames[0]->getGameDate()->format("D");
        }

        return $dayOfWeekToFilter;
    }

    /**
     * Checks a range of provided dates to see if the time slot value exceeds a fixed limit
     *
     * @param ScheduledGame[] $pastGames
     *
     * @return string
     */
    private function sameTimeSlot(array $pastGames)
    {
        $timeSlotFilter = null;
        $consecutiveStreak = 0;
        $index = 1;
        $lastTimeSlot = null;

        foreach ($pastGames as $dateToCheck) {
            // If first time through, we set last time slot to the current time slot; this will force the
            // next check to pass (since we are comparing against the same time slot) so the streak will
            // increase to 1 which is fair game
            $lastTimeSlot = empty($lastTimeSlot) ? $dateToCheck->getGameTime()->format("H:i") : $lastTimeSlot;
            if ($lastTimeSlot == $dateToCheck->getGameTime()->format("H:i")) {
                $consecutiveStreak++;
            } else {
                $consecutiveStreak = 0;
            }
            $index++;
        }

        if ($consecutiveStreak >= self::MAX_CONSECUTIVE_SAME_TIME) {
            $timeSlotFilter = $pastGames[0]->getGameTime()->format("H:i");
        }

        return $timeSlotFilter;
    }

    /**
     * Remove from provided list of open slots, dates that match day of week values provided
     *
     * @param string $dayOfWeek
     * @param ScheduledGame[] $slotsAvail
     *
     * @return ScheduledGame[]
     */
    private function removeSlotsByDay(string $dayOfWeek, array $slotsAvail)
    {
        $indicesToDelete = array();
        foreach ($slotsAvail as $key => $openSlot) {
            if ($openSlot->getGameDate()->format("D") == $dayOfWeek) {
                $indicesToDelete[] = $key;
            }
        }

        return $this->updateAvailableSlots(
            $slotsAvail,
            $indicesToDelete);
    }

    /**
     * Remove from provided list of open slots, dates that match time values provided
     *
     * $timeSlotFilter contains 2 entries, one for each of the HOME and AWAY team. The value for each index may be
     * blank if the consecutive limit hasn't been reached or the day of week value that will be used to remove
     * appropriate open slots.
     *
     * @param string $timeSlot
     * @param ScheduledGame[] $slotsAvail
     *
     * @return ScheduledGame[]
     */
    private function removeSlotsByTime(string $timeSlot, array $slotsAvail)
    {
        $indicesToDelete = array();
            foreach ($slotsAvail as $key => $openSlot) {
                if ($openSlot->getGameTime()->format("H:i") == $timeSlot) {
                    $indicesToDelete[] = $key;;
                }
            }

        return $this->updateAvailableSlots(
            $slotsAvail,
            $indicesToDelete);
    }

    /**
     * @param ScheduledGame[]  $slotsAvail
     * @param string $dayOfWeek
     * @param string $timeSlot
     *
     * @return array
     */
    private function removeSlotsByDayAndTime(array $slotsAvail, string $dayOfWeek, string $timeSlot)
    {
        $indicesToDelete = array();
        foreach ($slotsAvail as $key => $openSlot) {
            if ($openSlot->getGameDate()->format("D") == $dayOfWeek &&
                $openSlot->getGameTime()->format("H:i") == $timeSlot
            ) {
                $indicesToDelete[] = $key;
            }
        }

        return $this->updateAvailableSlots(
            $slotsAvail,
            $indicesToDelete,
            self::SLOT_PRESERVATION_LIMIT
        );
    }

    /**
     * Delete a set of indices from an array
     *
     * The order we process the indices to delete is in reverse so that the index of the elements
     * we wish to get rid of won't change as we remove them
     *
     * @param ScheduledGame[] $slotsAvail
     * @param array $indicesToDelete
     * @param int $chanceOfSlotPreservation
     *
     * @return array
     */
    private function updateAvailableSlots(
        array $slotsAvail,
        array $indicesToDelete,
        int $chanceOfSlotPreservation = 0
    ) {

        if (empty($indicesToDelete)) {
            return $slotsAvail;
        }

        foreach (array_reverse($indicesToDelete) as $key) {
            $randomFactor = random_int(1, 100);
            if ($randomFactor > $chanceOfSlotPreservation) {
                $this->collectGameSchedulingNotes(
                    "Removing [" . $chanceOfSlotPreservation . "]: " . $slotsAvail[$key]->getSlotSummary()
                );
                array_splice($slotsAvail, $key, 1);
            } else {
                $this->collectGameSchedulingNotes(
                    "Preserved (" . $randomFactor . "): " . $slotsAvail[$key]->getSlotSummary()
                );
            }
        }

        return $slotsAvail;
    }

    /**
     * Review team preferences for both teams involved in current game
     *
     * @param GameToSchedule $game
     * @param array          $slotsAvail
     *
     * @return array
     */
    private function reviewTeamPreferences(GameToSchedule $game, array $slotsAvail)
    {
        $homeTeamPreferences = $this->teamInformationRepo->getTeamPreferences($game->getHomeTeamId());
        if (!empty($homeTeamPreferences[0]['preferences'])) {
            $this->collectGameSchedulingNotes("Working on Home team's preferences");
            $slotsAvail = $this->processTeamPreferencesAgainstSlotsAvail(
                $slotsAvail,
                $homeTeamPreferences[0]['preferences']
            );
        }

        if (count($slotsAvail) > 1) {
            $awayTeamPreferences = $this->teamInformationRepo->getTeamPreferences($game->getVisitTeamId());
            if (!empty($awayTeamPreferences[0]['preferences'])) {
                $this->collectGameSchedulingNotes("Working on Visitor team's preferencs");
                $slotsAvail = $this->processTeamPreferencesAgainstSlotsAvail(
                    $slotsAvail,
                    $awayTeamPreferences[0]['preferences']
                );
            }
        }

        return $slotsAvail;
    }

    /**
     * Process team preferences to see which currently available slots should be removed from consideration
     *
     * @param array          $slotsAvail
     * @param array          $preferences
     *
     * @return array
     */
    private function processTeamPreferencesAgainstSlotsAvail(
        array $slotsAvail,
        array $preferences
    ) {
        foreach ($preferences as $preference) {
            $action = substr($preference, 0, 1);
            $dayOfWeek = substr($preference, 1, 3);
            $timeSlot = substr($preference, 4, 5);

            switch ($action) {
                case '-':
                    $this->collectGameSchedulingNotes("Processing MIN preference: " . $dayOfWeek . " @ " . $timeSlot);
                    $slotsAvail = $this->removeSlotsByDayAndTime($slotsAvail, $dayOfWeek, $timeSlot);
                    break;
            }
        }

        return $slotsAvail;
    }

    /**
     * Update record of decisions for scheduling current game
     *
     * @param string $note
     */
    private function collectGameSchedulingNotes(string $note)
    {
        $this->collectedSchedulingNotes .= $note . "\n";
    }

    /**
     * @param int             $totalGames
     * @param int             $gamesPlayedOnDay
     * @param string          $dow
     * @param ScheduledGame[] $slotsAvail
     *
     * @return ScheduledGame[]|array
     */
    private function reviewUseOfGivenDayOfWeek(int $totalGames, int $gamesPlayedOnDay, string $dow, array $slotsAvail)
    {
        if ($totalGames == 0) {
            return $slotsAvail;
        }

        $percentageOfSeasonPlayed = (int) floor($gamesPlayedOnDay / $totalGames * 100);
        $doNotExceedThreshold = $this->dowScheduleBalanceFactors[$dow]['high'];
        if ($totalGames <= self::SCHEDULE_MIDPOINT_GAMES) {
            $doNotExceedThreshold += 10;
        }

        if ($percentageOfSeasonPlayed > $doNotExceedThreshold) {
            $this->collectGameSchedulingNotes(
                'After ' . $totalGames   . ' game(s), removing ' . $dow . ' slots due to excessive usage [' . $percentageOfSeasonPlayed . ']'
            );
            $slotsAvail = $this->removeSlotsByDay($dow, $slotsAvail);
        }

        return $slotsAvail;
    }
}
