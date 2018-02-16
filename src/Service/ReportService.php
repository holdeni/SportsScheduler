<?php
/**
 * Created by PhpStorm.
 * User: henry
 * Date: 2018-02-16
 * Time: 11:28 AM
 */

namespace App\Service;

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
}