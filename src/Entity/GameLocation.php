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
     *     name="Action",
     *     type="string",
     *     length=8,
     *     nullable=false,
     * )
     */
    private $action;

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
     *     type="datetime_immutable",
     *     nullable=false,
     * )
     */
    private $endAvailable;

    /**
     * @ORM\Column(
     *     name="Game_Location_Name",
     *     type="string",
     *     length=255,
     *     nullable=false,
     * )
     */
    private $gameLocationName;

    /**
     * @ORM\Column(
     *     name="Start_Available",
     *     type="datetime_immutable",
     *     nullable=false,
     * )
     */
    private $startAvailable;

    /**
     * GameLocation constructor.
     *
     * @param string             $gameLocationName
     * @param string             $dayOfWeek
     * @param \DateTimeImmutable $startAvailable
     * @param \DateTimeImmutable $endAvailable
     * @param string             $action
     */
    public function __construct(
        string $gameLocationName,
        string $dayOfWeek,
        \DateTimeImmutable $startAvailable,
        \DateTimeImmutable $endAvailable,
        string $action
    ) {
        $this->gameLocationName = $gameLocationName;
        $this->dayOfWeek = $dayOfWeek;
        $this->startAvailable = $startAvailable;
        $this->endAvailable = $endAvailable;
        $this->action = $action;
    }

    /**
     * @return int
     */
    public function getGameLocationeId() : int
    {
        return $this->gameLocationId;
    }

    /**
     * @param int $gameLocationId
     *
     * @return GameLocation
     */
    public function setGameLocationeId(int $gameLocationId) : GameLocation
    {
        $this->gameLocationId = $gameLocationId;

        return $this;
    }

    /**
     * @return string
     */
    public function getAction() : string
    {
        return $this->action;
    }

    /**
     * @return string
     */
    public function getDayOfWeek() : string
    {
        return $this->dayOfWeek;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getEndAvailable() : \DateTimeImmutable
    {
        return $this->endAvailable;
    }

    /**
     * @return string
     */
    public function getGameLocationName() : string
    {
        return $this->gameLocationName;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getStartAvailable() : \DateTimeImmutable
    {
        return $this->startAvailable;
    }
}
