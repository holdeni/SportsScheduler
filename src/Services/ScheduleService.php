<?php
/**
 * Created by PhpStorm.
 * User: henry
 * Date: 2018-01-12
 * Time: 5:38 PM
 */

namespace App\Services;


use App\Repository\DefaultScheduleRepository;
use App\Repository\GameLocationRepository;
use App\Repository\TeamInformationRepository;

class ScheduleService
{
    /** @var DefaultScheduleRepository  */
    protected $defaultScheduleRepo;

    /** @var GameLocationRepository  */
    protected $gameLocationRepo;

    /** @var TeamInformationRepository  */
    protected $teamInformationRepo;

    /** @var null|\DateTime */
    protected $scheduleStartDate = null;

    /** @var null|int */
    protected $gameLengthInMins = null;

    /** @var int */
    protected $maxWeeksToSchedule;

    public function __construct(
        DefaultScheduleRepository $defaultScheduleRepository,
        GameLocationRepository $gameLocationRepository,
        TeamInformationRepository $teamInformationRepository
    )
    {
        $this->defaultScheduleRepo = $defaultScheduleRepository;
        $this->gameLocationRepo = $gameLocationRepository;
        $this->teamInformationRepo = $teamInformationRepository;
    }

    public function generateSchedule(
        \DateTime $scheduleStartDate,
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
         * -  Ensure Generated_Schedule table has a week number value so easy to relate to the games to be scheduled table
         */
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