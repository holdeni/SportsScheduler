<?php

namespace App\Controller;

use App\Service\ReportService;
use App\Service\ScheduledGameService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class DefaultController
 * @package App\Controller
 */
class DefaultController extends AbstractController
{
    /**
     * Display default page
     *
     * @Route(
     *     path="/",
     *     name="about"
     * )
     *
     * @return Response
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
     *     path="/schedule",
     *     name="calendar_schedule"
     * )
     *
     * @param ScheduledGameService $scheduledGameService
     *
     * @return Response
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
     *     path="/report_home_n_away",
     *     name="report_home_and_away"
     * )
     *
     * @param ReportService $reportService
     *
     * @return Response
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
     *     path="/report_nite_n_time",
     *     name="report_nights_and_times"
     * )
     *
     * @param ReportService $reportService
     *
     * @return Response
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
     * Display consecutive time slot totals for teams
     *
     * @Route(
     *     path="/report_consecutive_times",
     *     name="report_consecutive_times"
     * )
     *
     * @param ReportService $reportService
     *
     * @return Response
     */
    public function reportConsecutiveTimesAction(ReportService $reportService)
    {
        $reportData = $reportService->consecutiveTimes();

        return $this->render(
            'base.html.twig',
            array(
                'title' => "Softball Scheduler",
                'module' => 'report_consecutive_times',
                'report' => $reportData,
            )
        );
    }

    /**
     * Display schedule with ability to select specific teams or division
     *
     * @Route(
     *     path="/selectable_schedule",
     *     name="selectable_schedule"
     * )
     *
     * @param ScheduledGameService $scheduledGameService
     * @param ReportService $reportService
     *
     * @return Response
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
     *     path="/filter",
     *     name="filter_schedule",
     *     methods={"POST"}
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

    /**
     * @Route(
     *     path="/favicon.ico",
     *     name="get_favicon",
     *     methods={"GET"}
     * )
     *
     * @return Response
     */
    public function getFaviconAction()
    {
        // @todo - We should return a Favorite icon image

        return Response::create("");
    }
}
