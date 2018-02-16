<?php

namespace App\Controller;

use App\Service\ReportService;
use App\Service\ScheduledGameService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
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
     * Display Home and Away report
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
}
