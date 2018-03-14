<?php
/**
 * Created by PhpStorm.
 * User: henry
 * Date: 2018-02-16
 * Time: 11:28 AM
 */

namespace App\Service;

use App\Entity\ScheduledGame;
use App\Repository\ScheduledGameRepository;
use App\Repository\TeamInformationRepository;


/**
 * Class ReportService
 * @package App\Service
 */
class ReportService
{
    /** @var ScheduledGameRepository */
    private $scheduledGameRepo;

    /** @var TeamInformationRepository */
    private $teamInformationRepo;

    /** @var array */
    private $daysOfWeek = array(
        'Sun',
        'Mon',
        'Tue',
        'Wed',
        'Thu',
        'Fri',
        'Sat',
        'Sun',
    );

    /**
     * ReportService constructor.
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
     * Collect statistics on each teams home/away ratio
     *
     * @return array
     */
    public function homeAndAway()
    {
        $reportData = array();

        $divList = $this->teamInformationRepo->getListOfTeamDivisions();

        foreach ($divList as $division) {
            $teamList = $this->teamInformationRepo->getListOfTeamsInDivision($division);

            foreach ($teamList as $team) {
                $reportData[$division][$team->getTeamName()]['AWAY'] =
                    $this->scheduledGameRepo->getCountHomeAndAwayForTeam(
                        $team->getTeamInformationId(),
                        'AWAY'
                    );
                $reportData[$division][$team->getTeamName()]['HOME'] =
                    $this->scheduledGameRepo->getCountHomeAndAwayForTeam(
                        $team->getTeamInformationId(),
                        'HOME'
                    );
            }
        }

        return $reportData;
    }

    /**
     * Collect statistics on each teams games over days of week and the time slots
     *
     * @return array
     */
    public function nightsAndTimes()
    {
        $reportData = array(
            'timeslots' => array(
                'slot1' => array(
                    'time' => '6:30p',
                ),
                'slot2' => array(
                    'time' => '8:00p',
                ),
                'slot3' => array(
                    'time' => '9:30p',
                ),
            ),
            'daysOfWeek' => array(
//                'Sun',
                'Mon',
                'Tue',
                'Wed',
//                'Thu',
//                'Fri',
//                'Sat',
//                'Sun',
                'Totals',
            ),
            'data' => array(),
        );

        $divList = $this->teamInformationRepo->getListOfTeamDivisions();

        foreach ($divList as $division) {
            $teamList = $this->teamInformationRepo->getListOfTeamsInDivision($division);

            foreach ($teamList as $team) {
                $teamGames = $this->scheduledGameRepo->listAllScheduledGames($team->getTeamInformationId());
                $reportData['data'][$division][$team->getTeamName()] =
                    $this->reviewTeamScheduleForNightsAndTimes($teamGames);
            }
        }

        return $reportData;
    }

    /**
     * Review a team schedule tracking which day of week and times games are scheduled upon
     *
     * @param ScheduledGame[] $teamGames
     *
     * @return array
     */
    private function reviewTeamScheduleForNightsAndTimes(array $teamGames)
    {
        $data = $this->initializeNightandTimesRow();

        foreach ($teamGames as $game) {
            $dayOfWeek = $game->getGameDate()->format('D');
            $gameTime = $game->getGameTime()->format("H:i");
            switch ($gameTime) {
                case '18:30':
                    $timeslot = 'slot1';
                    break;

                case '20:00':
                    $timeslot = 'slot2';
                    break;

                case '21:30':
                    $timeslot = 'slot3';
                    break;

                default:
                    // @todo This should throw an exception
                    $timeslot = '';
                    break;
            }

            $data[$dayOfWeek][$timeslot]++;
            $data['Totals'][$timeslot]++;
        }

        foreach ($this->daysOfWeek as $dayOfWeek ) {
            foreach ($data[$dayOfWeek] as $timeslot => $value) {
                $data['Totals'][$dayOfWeek] += $value;
                $data['Totals']['Totals'] += $value;
            }
        }

        return $data;
    }

    /**
     * Initialize structure used for tracking night and times usage for a team
     *
     * @return array
     */
    private function initializeNightandTimesRow()
    {
        $row = array();

        $indices = $this->daysOfWeek;
        $indices[] = 'Totals';

        $timeslots = array(
            'slot1',
            'slot2',
            'slot3',
        );

        foreach ($indices as $index) {
            $row[$index] = array();
            foreach ($timeslots as $slot) {
                $row[$index][$slot] = 0;
            }
        }

        foreach ($this->daysOfWeek as $dayOfWeek) {
            $row['Totals'][$dayOfWeek] = 0;
        }
        $row['Totals']['Totals'] = 0;

        return $row;
    }
}
