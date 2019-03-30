<?php

namespace App\Controller;

use App\Service\ReportService;
use App\Service\ScheduledGameService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class DefaultController
 * @package App\Controller
 */
class DefaultController extends Controller
{
    /**
     * Display default page
     *
     * @Route(
     *     "/",
     *     name="about"
     * )
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        return $this->render(
            'base.html.twig',
            array(
                'title' => "Softball Scheduler",
                'module' => "about",
            )
        );
    }

    /**
     * Display full league schedule in calendar format
     *
     * @Route(
     *     "/schedule",
     *     name="calendar_schedule"
     * )
     *
     * @param ScheduledGameService $scheduledGameService
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function scheduleAction(ScheduledGameService $scheduledGameService)
    {
        $scheduledGamesData = $scheduledGameService->dumpSchedule();

        return $this->render(
            'base.html.twig',
            array(
                'title' => "Softball Scheduler",
                'module' => "calendar_schedule",
                'schedule' => $scheduledGamesData,
            )
        );
    }

    /**
     * Display home and away report for teams over schedule
     *
     * @Route(
     *     "/report_home_n_away",
     *     name="report_home_and_away"
     * )
     *
     * @param ReportService $reportService
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function reportHomeAndAwayAction(ReportService $reportService)
    {
        $reportData = $reportService->HomeAndAway();

        return $this->render(
            'base.html.twig',
            array(
                'title' => "Softball Scheduler",
                'module' => 'report_home_and_away',
                'report' => $reportData,
            )
        );
    }

    /**
     * Display nights and times for teams over schedule
     *
     * @Route(
     *     "/report_nite_n_time",
     *     name="report_nights_and_times"
     * )
     *
     * @param ReportService $reportService
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function reportNightsAndTimesAction(ReportService $reportService)
    {
        $reportData = $reportService->nightsAndTimes();

        return $this->render(
            'base.html.twig',
            array(
                'title' => "Softball Scheduler",
                'module' => 'report_nights_and_times',
                'report' => $reportData,
            )
        );
    }

    /**
     * Display schedule with ability to select specific teams or division
     *
     * @Route(
     *     "/selectable_schedule",
     *     name="selectable_schedule"
     * )
     *
     * @param ScheduledGameService $scheduledGameService
     * @param ReportService $reportService
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function selectableScheduleAction(
        ScheduledGameService $scheduledGameService,
        ReportService $reportService
    ) {
        $teamData = $reportService->fetchListOfTeams();
        $divisionData = $reportService->fetchListOfDivisions();

        $scheduledGamesData = $scheduledGameService->dumpSchedule();

        return $this->render(
            'base.html.twig',
            array(
                'title' => "Softball Scheduler",
                'module' => "selectable_schedule",
                'schedule' => $scheduledGamesData,
                'teams' => $teamData,
                'divisions' => $divisionData,
            )
        );
    }

    /**
     * @Route(
     *     "/filter",
     *     name="filter_schedule"
     * )
     *
     * @Method(
     *     {"POST"}
     * )
     *
     * @param Request $request
     * @param ScheduledGameService $scheduledGameService
     * @param ReportService $reportService
     *
     * @return Response
     */
    public function filterScheduleAction(
        Request $request,
        ScheduledGameService $scheduledGameService,
        ReportService $reportService
    ) {
        $teamData = $reportService->fetchListOfTeams();
        $divisionData = $reportService->fetchListOfDivisions();

        $filtersChosen = $request->request->all();
        $teamsFilter = array();
        foreach ($filtersChosen as $selector) {
            $teamsFilter = array_merge($selector, $teamsFilter);
        }
        $scheduledGameData = $scheduledGameService->dumpFilteredSchedule($teamsFilter);

        return $this->render(
            'base.html.twig',
            array(
                'title' => "Softball Scheduler",
                'module' => "selectable_schedule",
                'schedule' => $scheduledGameData,
                'teams' => $teamData,
                'divisions' => $divisionData,
            )
        );
    }
}
