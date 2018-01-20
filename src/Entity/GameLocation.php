<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *     name="Game_Location",
 * )
 * @ORM\Entity(
 *     repositoryClass="App\Repository\GameLocationRepository"
 * )
 */
class GameLocation
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(
     *     strategy="IDENTITY"
     * )
     * @ORM\Column(
     *     name="Game_Location_Id",
     *     type="integer",
     *     options={
     *         "unsigned"=true
     *     }
     * )
     */
    private $gameLocationId;

    /**
     * @ORM\Column(
     *     name="Day_Of_Week",
     *     type="string",
     *     length=12,
     *     nullable=false,
     * )
     */
    private $dayOfWeek;

    /**
     * @ORM\Column(
     *     name="End_Available",
     *     type="datetime",
     *     nullable=false,
     * )
     */
    private $endAvailable;

    /**
     * @ORM\Column(
     *     name="GameLocation_Name",
     *     type="string",
     *     length=255,
     *     nullable=false,
     * )
     */
    private $GameLocationName;

    /**
     * @ORM\Column(
     *     name="Start_Available",
     *     type="datetime",
     *     nullable=false,
     * )
     */
    private $startAvailable;

    /**
     * @return int
     */
    public function getGameLocationeId()
    {
        return $this->gameLocationId;
    }

    /**
     * @param int $gameLocationId
     *
     * @return GameLocation
     */
    public function setGameLocationeId(int $gameLocationId)
    {
        $this->gameLocationId = $gameLocationId;

        return $this;
    }

    /**
     * @return string
     */
    public function getDayOfWeek()
    {
        return $this->dayOfWeek;
    }

    /**
     * @param string $dayOfWeek
     *
     * @return GameLocation
     */
    public function setDayOfWeek(string $dayOfWeek)
    {
        $this->dayOfWeek = $dayOfWeek;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getEndAvailable()
    {
        return $this->endAvailable;
    }

    /**
     * @param \DateTime $endAvailable
     *
     * @return GameLocation
     */
    public function setEndAvailable(\DateTime $endAvailable)
    {
        $this->endAvailable = $endAvailable;

        return $this;
    }

    /**
     * @return string
     */
    public function getGameLocationName()
    {
        return $this->GameLocationName;
    }

    /**
     * @param string $GameLocationName
     *
     * @return GameLocation
     */
    public function setGameLocationName(string $GameLocationName)
    {
        $this->GameLocationName = $GameLocationName;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getStartAvailable()
    {
        return $this->startAvailable;
    }

    /**
     * @param \DateTime $startAvailable
     *
     * @return GameLocation
     */
    public function setStartAvailable(\DateTime $startAvailable)
    {
        $this->startAvailable = $startAvailable;

        return $this;
    }
}
