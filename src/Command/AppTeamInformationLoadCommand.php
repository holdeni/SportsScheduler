<?php

namespace App\Command;

use App\Entity\TeamInformation;
use App\Repository\TeamInformationRepository;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class AppTeamInformationLoadCommand extends ContainerAwareCommand
{
    protected static $defaultName = 'app:team-information:load';

    /** @var SymfonyStyle */
    protected $io;

    /** @var string|null  */
    protected $csvFilename = null;

    /** @var resource|null */
    protected $csvFileHandle = null;

    /** @var TeamInformationRepository */
    protected $teamInformationRepo;

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $em  = $this->getContainer()->get('doctrine')->getManager();
        $this->teamInformationRepo = $em->getRepository('App:TeamInformation');
    }

    protected function configure()
    {
        $this
            ->setDescription('Load a CSV file containing information on the teams')
            ->addArgument(
                'csvFile',
                InputArgument::REQUIRED,
                'CSV file containing team information'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
        $arguments['csvFile']   = $input->getArgument('csvFile');

        $this->validateArguments($arguments);

        $this->openCsvFile();
        $this->processCSVLoad();
        $this->closeCsvFile();

        $this->io->success('... team information has been successfully loaded');
    }

    protected function validateArguments(array $arguments)
    {
        foreach ($arguments as $argumentKey => $argumentValue) {
            switch ($argumentKey) {
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
            }
        }

        if ($this->csvFilename === null) {
            $this->io->error("Command is missing required values to operating. Exiting");
            die();
        }
    }

    protected function openCsvFile()
    {
        $this->csvFileHandle = fopen($this->csvFilename, "r");
        if ($this->csvFileHandle === false) {
            die("Error on opening csv file");
        }

    }

    protected function closeCsvFile()
    {
        if ($this->csvFileHandle != null) {
            fclose($this->csvFileHandle);
        }
    }

    protected function processCSVLoad()
    {
        $row = 0;
        $this->io->text("... reading in team information");

        while ($line = fgetcsv($this->csvFileHandle)) {
            /**
             * @todo Should allow for a header row
             * @todo Should header row to reorder fields
             * @todo Use constants for the index values into $line
             */

            $row++;

            $gameLocation = new TeamInformation();
            $gameLocation->setTeamNumInDiv($line[0])
                ->setTeamName($line[1])
                ->setTeamDivision($line[2]);
            $this->teamInformationRepo->save($gameLocation);
            unset($gameLocation);

            if ($row % 5 === 0) {
                $this->io->text("    - loaded " . $row . " records");
            }
        }

        $this->io->text("... completed reading in " . $row . " team records");
    }

}
