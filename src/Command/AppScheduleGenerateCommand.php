<?php

namespace App\Command;

use App\Service\ScheduleService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class AppScheduleGenerateCommand extends Command
{
    protected static $defaultName = 'app:schedule:generate';

    /** @var SymfonyStyle */
    protected $io;

    /** @var null|\DateTime */
    protected $scheduleStartDate = null;

    /** @var int */
    protected $gameLengthInMins = 0;

    /** @var ScheduleService */
    protected $scheduleService;

    public function __construct(ScheduleService $scheduleService)
    {
        $this->scheduleService = $scheduleService;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Generate a default schedule, given some seeding information')
            ->addOption(
                'startDate',
                null,
                InputOption::VALUE_REQUIRED,
                'First day of schedule (YYYY-MM-DD)'
            )
            ->addOption(
                'gameLength',
                null,
                InputOption::VALUE_REQUIRED,
                'Time, in minutes, each game is alloted'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
        $arguments['startDate']   = $input->getOption('startDate');
        $arguments['gameLength'] = $input->getOption('gameLength');

        $this->validateArguments($arguments);
        $this->scheduleService->generateSchedule(
            $this->scheduleStartDate,
            $this->gameLengthInMins
        );

        $this->io->success("... a schedule has been successfully generated");
    }

    private function validateArguments($arguments)
    {
        foreach ($arguments as $argumentKey => $argumentValue) {
            switch ($argumentKey) {
                case 'startDate':
                    if (strtotime($argumentValue) === false
                    ) {
                        $this->io->error("Value " . $argumentValue . " is not a valid start date");
                        die();
                    }

                    $this->io->text("... using as start date: " . $argumentValue);
                    $this->scheduleStartDate = $argumentValue;

                    break;

                case 'gameLength':
                    $minsInDay = 24 * 60;
                    $gameLength = (int) $argumentValue;
                    if (0 >= $gameLength ||
                        $gameLength > $minsInDay
                    ) {
                        $this->io->error(
                            "Value " . $argumentValue . " must be an integer value between 1 and  " . $minsInDay . ": " . $gameLength
                        );
                        die();
                    }
                    $this->io->text("... using as game length: " . $argumentValue);
                    $this->gameLengthInMins = $argumentValue;

                    break;
            }
        }

        if ($this->scheduleStartDate === null) {
            $this->io->error("Command is missing required values to operating. Exiting");
            die();
        }

    }
}
