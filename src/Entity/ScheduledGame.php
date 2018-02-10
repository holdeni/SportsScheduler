<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *     name="Scheduled_Game",
 *     indexes={
 *         @Index(
 *             name="idx_Game_Info",
 *             columns={
 *                 "Game_Time",
 *                 "Game_Date"
 *             }
 *         ),
 *         @Index(
 *             name="idx_Home_Team",
 *             columns={
 *                 "Home_Team_Id"
 *             }
 *         ),
 *         @Index(
 *             name="idx_Visit_Team",
 *             columns={
 *                 "Visit_Team_Id"
 *             }
 *         ),
 *         @Index(
 *             name="idx_Week_Nr",
 *             columns={
 *                 "Template_Schedule_Week_Number"
 *             }
 *         )
 *     }
 * )
 * @ORM\Entity(
 *     repositoryClass="App\Repository\ScheduledGameRepository"
 * )
 */
class ScheduledGame
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(
     *     strategy="IDENTITY"
     * )
     * @ORM\Column(
     *     name="Scheduled_Game_Id",
     *     type="integer",
     *     options={
     *         "unsigned"=true
     *     }
     * )
     */
    private $scheduledGameId;

    /**
     * @ORM\Column(
     *     name="Division",
     *     type="string",
     *     length=3,
     *     nullable=true,
     *     options={
     *         "default"=null
     *     }
     * )
     */
    private $division;

    /**
     * @ORM\Column(
     *     name="Game_Date",
     *     type="string",
     *     length=16,
     *     nullable=true,
     *     options={
     *         "default"=null
     *     }
     * )
     */
    private $gameDate;

    /**
     * @ORM\Column(
     *     name="Game_Location",
     *     type="string",
     *     length=255,
     *     nullable=true,
     *     options={
     *         "default"=null
     *     }
     * )
     */
    private $gameLocation;

    /**
     * @ORM\Column(
     *     name="Game_Notes",
     *     type="string",
     *     length=1024,
     *     nullable=true,
     *     options={
     *         "default"=null
     *     }
     * )
     */
    private $gameNotes;

    /**
     * @ORM\Column(
     *     name="Game_Time",
     *     type="string",
     *     length=16,
     *     nullable=true,
     *     options={
     *         "default"=null
     *     }
     * )
     */
    private $gameTime;

    /**
     * @ORM\Column(
     *     name="Home_Team_Id",
     *     type="integer",
     *     nullable=true,
     *     options={
     *         "unsigned"=true,
     *         "default"=null
     *     }
     * )
     */
    private $homeTeamId;

    /**
     * @ORM\Column(
     *     name="Scheduling_Notes",
     *     type="string",
     *     length=1024,
     *     nullable=true,
     *     options={
     *         "default"=null
     *     }
     * )
     */
    private $schedulingNotes;

    /**
     * @ORM\Column(
     *     name="Visit_Team_id",
     *     type="integer",
     *     nullable=true,
     *     options={
     *         "unsigned"=true,
     *         "default"=null
     *     }
     * )
     */
    private $visitTeamId;

    /**
     * @ORM\Column(
     *     name="Template_Schedule_Week_Number",
     *     type="integer",
     *     nullable=true,
     *     options={
     *         "unsigned"=true,
     *         "default"=null,
     *     }
     * )
     */
    private $templateScheduleWeekNumber;

    /** @var string */
    private $homeTeamName = '';

    /** @var string */
    private $visitTeamName = '';

    /**
     * @return int
     */
    public function getScheduledGameId()
    {
        return $this->scheduledGameId;
    }

    /**
     * @param int $scheduledGameId
     *
     * @return ScheduledGame
     */
    public function setScheduledGamesId($scheduledGameId)
    {
        $this->scheduledGameId = $scheduledGameId;

        return $this;
    }

    /**
     * @return string
     */
    public function getDivision()
    {
        return $this->division;
    }

    /**
     * @param string $division
     *
     * @return ScheduledGame
     */
    public function setDivision(string $division)
    {
        $this->division = $division;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getGameDate()
    {
        return new \DateTime($this->gameDate);
    }

    /**
     * @param \DateTime $gameDate
     *
     * @return ScheduledGame
     */
    public function setGameDate(\DateTime $gameDate)
    {
        $this->gameDate = $gameDate->format("Y-m-d");

        return $this;
    }

    /**
     * @return string
     */
    public function getGameLocation()
    {
        return $this->gameLocation;
    }

    /**
     * @param string $gameLocation
     *
     * @return ScheduledGame
     */
    public function setGameLocation(string $gameLocation)
    {
        $this->gameLocation = $gameLocation;

        return $this;
    }

    /**
     * @return string
     */
    public function getGameNotes()
    {
        return $this->gameNotes;
    }

    /**
     * @param string $gameNotes
     *
     * @return ScheduledGame
     */
    public function setGameNotes(string $gameNotes)
    {
        $this->gameNotes = $gameNotes;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getGameTime()
    {
        return new \DateTime($this->gameTime);
    }

    /**
     * @param \DateTime $gameTime
     *
     * @return ScheduledGame
     */
    public function setGameTime(\DateTime $gameTime)
    {
        $this->gameTime = $gameTime->format("H:i:s");

        return $this;
    }

    /**
     * @return int
     */
    public function getHomeTeamId()
    {
        return $this->homeTeamId;
    }

    /**
     * @param int $homeTeamId
     *
     * @return ScheduledGame
     */
    public function setHomeTeamId(int $homeTeamId)
    {
        $this->homeTeamId = $homeTeamId;

        return $this;
    }

    /**
     * @return string
     */
    public function getSchedulingNotes()
    {
        return $this->schedulingNotes;
    }

    /**
     * @param string $schedulingNotes
     *
     * @return ScheduledGame
     */
    public function setSchedulingNotes(string $schedulingNotes)
    {
        $this->schedulingNotes = $schedulingNotes;

        return $this;
    }

    /**
     * @return int
     */
    public function getVisitTeamId()
    {
        return $this->visitTeamId;
    }

    /**
     * @param int $visitTeamId
     *
     * @return ScheduledGame
     */
    public function setVisitTeamId(int $visitTeamId)
    {
        $this->visitTeamId = $visitTeamId;

        return $this;
    }

    /**
     * @return int
     */
    public function getTemplateScheduleWeekNumber()
    {
        return $this->templateScheduleWeekNumber;
    }

    /**
     * @param int $templateScheduleWeekNumber
     *
     * @return ScheduledGame
     */
    public function setTemplateScheduleWeekNumber(int $templateScheduleWeekNumber)
    {
        $this->templateScheduleWeekNumber = $templateScheduleWeekNumber;

        return $this;
    }

    /**
     * @return string
     */
    public function getHomeTeamName(): string
    {
        return $this->homeTeamName;
    }

    /**
     * @param string $homeTeamName
     *
     * @return ScheduledGame
     */
    public function setHomeTeamName(string $homeTeamName): ScheduledGame
    {
        $this->homeTeamName = $homeTeamName;

        return $this;
    }

    /**
     * @return string
     */
    public function getVisitTeamName(): string
    {
        return $this->visitTeamName;
    }

    /**
     * @param string $visitTeamName
     *
     * @return ScheduledGame
     */
    public function setVisitTeamName(string $visitTeamName): ScheduledGame
    {
        $this->visitTeamName = $visitTeamName;

        return $this;
    }


}
