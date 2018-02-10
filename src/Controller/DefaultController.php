<?php

namespace App\Controller;

use App\Repository\ScheduledGameRepository;
use App\Service\ScheduledGameService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Class DefaultController
 * @package App\Controller
 */
class DefaultController extends Controller
{
    /**
     * @Route("/", name="default")
     *
     * @param ScheduledGameService $scheduledGameService
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index(ScheduledGameService $scheduledGameService)
    {
        $scheduledGamesData = $scheduledGameService->dumpSchedule();

        return $this->render(
            'base.html.twig',
            array(
                'title' => "Softball Scheduler",
                'schedule' => $scheduledGamesData,
            )
        );
    }
}
