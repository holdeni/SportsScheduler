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
         * @todo Figure out how weeks in schedule by determining size of each division and number of games for division in default schedule
         */

        $this->createTimeSlots();

        /**
         * @todo Using default schedule, build the set of games that need to be scheduled into Games_To_Schedule table
         */

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

}