<?php
namespace App\Entity;

use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *     name="Game_To_Schedule",
 *     indexes={
 *         @Index(
 *             name="idx_Default_Schedule_Week_Nr",
 *             columns={
 *                 "Week_Nr"
 *             }
 *         )
 *     }
 * )
 * @ORM\Entity(
 *     repositoryClass="App\Repository\GameToScheduleRepository"
 * )
 */
class GameToSchedule
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(
     *     strategy="IDENTITY"
     * )
     * @ORM\Column(
     *     name="Game_To_Schedule_Id",
     *     type="integer",
     *     options={
     *         "unsigned"=true
     *     }
     * )
     */
    private $gameToScheduleId;

    /**
     * @ORM\Column(
     *     name="Division",
     *     type="string",
     *     length=3,
     *     nullable=false
     * )
     */
    private $division;

    /**
     * @ORM\Column(
     *     name="Home_Team_Id",
     *     type="integer",
     *     nullable=false,
     *     options={
     *         "unsigned"=true
     *     }
     * )
     */
    private $homeTeamId;

    /**
     * @ORM\Column(
     *     name="Notes",
     *     type="string",
     *     length=4096,
     *     nullable=true,
     *     options={
     *         "default"=null
     *     }
     * )
     */
    private $notes;

    /**
     * @ORM\Column(
     *     name="Visit_Team_Id",
     *     type="integer",
     *     nullable=false,
     *     options={
     *         "unsigned"=true
     *     }
     * )
     */
    private $visitTeamId;

    /**
     * @ORM\Column(
     *     name="Week_Nr",
     *     type="integer",
     *     nullable=false,
     *     options={
     *         "unsigned"=true
     *     }
     * )
     */
    private $weekNr;

    /**
     * @return int
     */
    public function getGameToScheduleId()
    {
        return $this->gameToScheduleId;
    }

    /**
     * @param int $gameToScheduleId
     *
     * @return GameToSchedule
     */
    public function setGameToScheduleId(int $gameToScheduleId)
    {
        $this->gameToScheduleId = $gameToScheduleId;

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
     * @return GameToSchedule
     */
    public function setDivision(string $division)
    {
        $this->division = $division;

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
     * @return GameToSchedule
     */
    public function setHomeTeamId(int $homeTeamId)
    {
        $this->homeTeamId = $homeTeamId;

        return $this;
    }

    /**
     * @return string
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * @param string $notes
     *
     * @return GameToSchedule
     */
    public function setNotes(string $notes)
    {
        $this->notes = $notes;

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
     * @return GameToSchedule
     */
    public function setVisitTeamId(int $visitTeamId)
    {
        $this->visitTeamId = $visitTeamId;

        return $this;
    }

    /**
     * @return int
     */
    public function getWeekNr()
    {
        return $this->weekNr;
    }

    /**
     * @param int $weekNr
     *
     * @return GameToSchedule
     */
    public function setWeekNr(int $weekNr)
    {
        $this->weekNr = $weekNr;

        return $this;
    }
}
