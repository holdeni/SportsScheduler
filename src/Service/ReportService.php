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
use Psr\Log\LoggerInterface;


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

    /** @var LoggerInterface */
    private $logger;

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
     * @param LoggerInterface $logger
     */
    public function __construct(
        ScheduledGameRepository $scheduledGameRepository,
        TeamInformationRepository $teamInformationRepository,
        LoggerInterface $logger
    ) {
        $this->scheduledGameRepo = $scheduledGameRepository;
        $this->teamInformationRepo = $teamInformationRepository;
        $this->logger = $logger;
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
     * Collect statistics on each teams games over consecutive use of the same timeslot, regardless of night
     *
     * @return array
     */
    public function consecutiveTimes()
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
            'data' => array(),
        );

        $divList = $this->teamInformationRepo->getListOfTeamDivisions();

        foreach ($divList as $division) {
            $teamList = $this->teamInformationRepo->getListOfTeamsInDivision($division);

            foreach ($teamList as $team) {
//                $this->logger->debug($team->getTeamName());
                $teamGames = $this->scheduledGameRepo->listAllScheduledGames($team->getTeamInformationId());
                $reportData['data'][$division][$team->getTeamName()] =
                    $this->reviewTeamScheduleForConsecutiveTimes($teamGames);
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
            // KMLL timeslots
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
            // OTSL timeslots
//            switch ($gameTime) {
//                case '16:45':
//                    $timeslot = 'slot1';
//                    break;
//
//                default:
//                    // @todo This should throw an exception
//                    $timeslot = '';
//                    break;
//            }

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
     * @param array $teamGames
     *
     * @return array
     */
    private function reviewTeamScheduleForConsecutiveTimes(array $teamGames)
    {
        $gameIndex = 0;
        $streakGameIds = array(
            $teamGames[$gameIndex]->getScheduledGameId()
        );
        $curGame = 1;
        $slots = array(
            'slot1' => 0,
            'slot2' => 0,
            'slot3' => 0,
        );

        do {
            do {
                if ($teamGames[$gameIndex]->getGameTime()->format("H:i") ==
                    $teamGames[$curGame]->getGameTime()->format("H:i")
                ) {
//                    $this->logger->debug("Consecutive check ongoing: " . $teamGames[$curGame]->getGameTime()->format("H:i"));
                    $streakGameIds[] = $teamGames[$curGame]->getScheduledGameId();
                    $curGame++;
                    $consecutiveStreakActive = true;
                } else {
                    $consecutiveStreakActive = false;
//                    $this->logger->debug("Consecutive streak resetting");
                }
            } while ($consecutiveStreakActive && isset($teamGames[$curGame]));

            if ($consecutiveStreakActive) {
                $lengthOfStreak = count($streakGameIds);
                if ($lengthOfStreak >= 4) {
                    $this->logger->debug("Streak longer than minimum threshold: " . $lengthOfStreak);
                    $this->logger->debug(print_r($streakGameIds, true));
                    switch ($teamGames[$gameIndex]->getGameTime()->format("H:i")) {
                        case '18:30':
                            $slots['slot1']++;
                            break;

                        case '20:00':
                            $slots['slot2']++;
                            break;

                        case '21:30':
                            $slots['slot3']++;
                            break;
                    }
                }
            }

            if (isset($teamGames[$curGame])) {
                $gameIndex = $curGame;
                $streakGameIds = array(
                    $teamGames[$gameIndex]->getScheduledGameId()
                );
                $curGame++;
            }
        } while (isset($teamGames[$curGame]));

        return $slots;
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

    /**
     * @return array
     */
    public function fetchListOfTeams()
    {
        $teamData = array();
        $dbData = $this->teamInformationRepo->fetchListOfTeams();
        foreach ($dbData as $team)
        {
            $teamData[] = array(
                'teamName' => $team->getTeamName(),
                'division' => $team->getTeamDivision(),
                'teamId' => $team->getTeamInformationId(),
            );
        }

        return $teamData;
    }

    /**
     * @return string[]
     */
    public function fetchListOfDivisions()
    {
        $divisionData = $this->teamInformationRepo->getListOfTeamDivisions();

        return $divisionData;
    }
}
