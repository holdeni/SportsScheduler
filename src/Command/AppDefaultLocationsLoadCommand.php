<?php

namespace App\Command;

use App\Entity\GameLocation;
use App\Repository\GameLocationRepository;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class AppDefaultLocationsLoadCommand extends ContainerAwareCommand
{
    protected static $defaultName = 'app:default-locations:load';

    /** @var SymfonyStyle */
    protected $io;

    /** @var string|null  */
    protected $csvFilename = null;

    /** @var resource|null */
    protected $csvFileHandle = null;

    /** @var GameLocationRepository */
    protected $gameLocationRepo;

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $em  = $this->getContainer()->get('doctrine')->getManager();
        $this->gameLocationRepo = $em->getRepository('App:GameLocation');
    }

    protected function configure()
    {
        $this
            ->setDescription('Load a CSV file containing the default game locations')
            ->addArgument(
                'csvFile',
                InputArgument::REQUIRED,
                'CSV file containing game location details'
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

        $this->io->success('... game location information has been successfully loaded');
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
        $this->io->text("... reading in game location information");

        while ($line = fgetcsv($this->csvFileHandle)) {
            /**
             * @todo Should allow for a header row
             * @todo Should header row to reorder fields
             * @todo Use constants for the index values into $line
             */

            $row++;
            if ($this->isCsvInputValid($line, $row)) {
                $startAvailable = new \DateTime($line[2]);
                $endAvailable = new \DateTime($line[3]);

                $gameLocation = new GameLocation();
                $gameLocation->setGameLocationName($line[0])
                    ->setDayOfWeek($line[1])
                    ->setStartAvailable($startAvailable)
                    ->setEndAvailable($endAvailable);
                $this->gameLocationRepo->save($gameLocation);
                unset($gameLocation);

                if ($row % 5 === 0) {
                    $this->io->text("    - loaded " . $row . " records");
                }
            } else {
                die();
            }
        }

        $this->io->text("... completed reading in " . $row . " game location records");
    }

    protected function isCsvInputValid(array $line, int $row)
    {
        $dayOfWeek = $line[1];
        $validDayOfWeekValues = array(
            "Mon",
            "Monday",
            "Tue",
            "Tuesday",
            "Wed",
            "Wednesday",
            "Thu",
            "Thursday",
            "Fri",
            "Friday",
            "Sat",
            "Saturday",
            "Sun",
            "Sunday",
        );
        if (!in_array($dayOfWeek, $validDayOfWeekValues)) {
            $this->io->error("Row " . $row . " contains invalid day of week value: " . $dayOfWeek);
            return false;
        }

        $startAvailable = $line[2];
        if (!$this->isValidDateFormat($startAvailable)) {
            $this->io->error("Row " . $row . " contains invalid date for start available field: " . $startAvailable);
            return false;
        }

        $endAvailable = $line[3];
        if (!$this->isValidDateFormat($endAvailable)) {
            $this->io->error("Row " . $row . " contains invalid date for start available field: " . $endAvailable);
            return false;
        }

        return true;
    }

    protected function isValidDateFormat(string $dateToCheck)
    {
        $isValid = true;

        if (strtotime($dateToCheck) === false) {
            $isValid = false;
        }

        return $isValid;
    }
}
