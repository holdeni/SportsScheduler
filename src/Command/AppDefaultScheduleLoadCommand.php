<?php

namespace App\Command;

use App\Entity\DefaultSchedule;
use App\Repository\DefaultScheduleRepository;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class AppDefaultScheduleLoadCommand
 * @package App\Command
 */
class AppDefaultScheduleLoadCommand extends ContainerAwareCommand
{
    protected static $defaultName = 'app:default-schedule:load';

    /** @var SymfonyStyle  */
    protected $io;

    /** @var string|null */
    protected $csvFilename = null;

    /** @var resource */
    protected $csvFileHandle = null;

    /** @var int  */
    protected $divFormat = 0;

    /** @var DefaultScheduleRepository */
    protected $defaultScheduleRepo;

    /**
     * Prepare the command
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $em  = $this->getContainer()->get('doctrine')->getManager();
        $this->defaultScheduleRepo = $em->getRepository('App:DefaultSchedule');
    }

    /**
     * List command-specific options
     */
    protected function configure()
    {
        $this
            ->setDescription('Load a CSV file containing a default schedule')
            ->addArgument(
                'csvFile',
                InputArgument::REQUIRED,
                'CSV file containing schedule'
            )
            ->addOption(
                'divFormat',
                null,
                InputOption::VALUE_REQUIRED,
                'Division format schedule represents'
            )
        ;
    }

    /**
     * Do the job
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
        $arguments['csvFile']   = $input->getArgument('csvFile');
        $arguments['divFormat'] = $input->getOption('divFormat');

        $this->validateArguments($arguments);

        $this->deleteExistingData();

        $this->openCsvFile();
        $this->processCSVLoad();
        $this->closeCsvFile();

        $this->io->success('... scheduled has been successfully loaded');
    }

    /**
     * Validate command line arguments
     *
     * @param array $arguments
     */
    protected function validateArguments(array $arguments)
    {
        foreach ($arguments as $argumentKey => $argumentValue) {
            switch($argumentKey) {
                case 'csvFile':
                    if (!file_exists($argumentValue) ||
                        !is_readable($argumentValue) ||
                        !is_file($argumentValue)
                    ) {
                        $this->io->error("Value -" . $argumentValue . "- is not an existing, readable file");
                        die();
                    }

                    $this->io->text("... using input file: " . $argumentValue);
                    $this->csvFilename = $argumentValue;

                    break;

                case 'divFormat':
                    $this->io->text("... setting division format to: " . $argumentValue);
                    $this->divFormat = $argumentValue;

                    break;
            }
        }

        if ($this->csvFilename === null ||
            $this->divFormat === null
        ) {
            $this->io->error("Command is missing required values to operate. Exiting.");
            die();
        }
    }

    protected function deleteExistingData()
    {
        $this->io->text("... deleting any existing schedule for division format " . $this->divFormat);
        $this->defaultScheduleRepo->delete($this->divFormat);
    }

    /**
     * Open CSV file
     */
    protected function openCsvFile()
    {
        $this->csvFileHandle = fopen($this->csvFilename, "r");
        if ($this->csvFileHandle === false) {
            die("Error on opening csv file");
        }

    }

    /**
     * Close CSV file
     */
    protected function closeCsvFile()
    {
        if ($this->csvFileHandle != null) {
            fclose($this->csvFileHandle);
        }
    }

    /**
     * Load CSV file into memory
     */
    protected function processCSVLoad()
    {
        $row = 0;
        $this->io->text("... reading in schedule information");

        while ($line = fgetcsv($this->csvFileHandle)) {
            /**
             * @todo Should allow for a header row
             * @todo Should allow header row to reorder fields
             * @todo Data input values should be checked they are valid (or should the setter functions do that?)
             * @todo Use constants for the index values into $line for readability
             */

            $row++;

            $defaultSchedule = new DefaultSchedule();
            $defaultSchedule->setDivisionFormat($this->divFormat);
            $defaultSchedule->setWeekNr($line[0])
                ->setVisitTeamId($line[1])
                ->setHomeTeamId($line[2]);
            $this->defaultScheduleRepo->save($defaultSchedule);
            unset($defaultSchedule);

            if ($row % 5 === 0) {
                $this->io->text("    - loaded " . $row . " records");
            }
        }

        $this->io->text("... completed reading in " . $row . " schedule records");
    }
}
