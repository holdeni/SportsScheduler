<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *     name="Default_Schedule",
 *     indexes={
 *         @Index(
 *             name="idx_Default_Schedule_Week_Nr",
 *             columns={
 *                 "Week_Nr"
 *             }
 *         )
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\DefaultScheduleRepository")
 */
class DefaultSchedule
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(
     *     strategy="IDENTITY"
     * )
     * @ORM\Column(
     *     name="Default_Schedule_Id",
     *     type="integer",
     *     options={
     *         "unsigned"=true
     *     }
     * )
     */
    private $defaultScheduleId;

    /**
     * @ORM\Column(
     *     name="Division_Format",
     *     type="integer",
     *     nullable=false,
     *     options={
     *         "unsigned"=true
     *     }
     * )
     */
    private $divisionFormat;

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
    public function getDefaultScheduleId()
    {
        return $this->defaultScheduleId;
    }

    /**
     * @param int  $defaultScheduleId
     *
     * @return DefaultSchedule
     */
    public function setDefaultScheduleId(int $defaultScheduleId)
    {
        $this->defaultScheduleId = $defaultScheduleId;

        return $this;
    }

    /**
     * @return int
     */
    public function getDivisionFormat()
    {
        return $this->divisionFormat;
    }

    /**
     * @param int $divisionFormat
     *
     * @return DefaultSchedule
     */
    public function setDivisionFormat(int $divisionFormat)
    {
        $this->divisionFormat = $divisionFormat;

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
     * @return DefaultSchedule
     */
    public function setHomeTeamId(int $homeTeamId)
    {
        $this->homeTeamId = $homeTeamId;

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
     * @return DefaultSchedule
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
     * @return DefaultSchedule
     */
    public function setWeekNr(int $weekNr)
    {
        $this->weekNr = $weekNr;

        return $this;
    }


}
