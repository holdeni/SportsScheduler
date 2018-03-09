<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *     name="Team_Information",
 * )
 * @ORM\Entity(
 *     repositoryClass="App\Repository\TeamInformationRepository"
 * )
 */
class TeamInformation
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(
     *     strategy="IDENTITY"
     * )
     * @ORM\Column(
     *     name="Team_Information_Id",
     *     type="integer",
     *     options={
     *         "unsigned"=true
     *     }
     * )
     */
    private $teamInformationId;

    /**
     * @ORM\Column(
     *     name="Team_Division",
     *     type="string",
     *     length=3,
     *     nullable=false
     * )
     */
    private $teamDivision;

    /**
     * @ORM\Column(
     *     name="Team_Name",
     *     type="string",
     *     length=32,
     *     nullable=false
     * )
     */
    private $teamName;

    /**
     * @ORM\Column(
     *     name="Team_Num_In_Div",
     *     type="integer",
     *     nullable=false,
     *     options={
     *         "unsigned"=true
     *     }
     * )
     */
    private $teamNumInDiv;

    /**
     * @ORM\Column(
     *     name="Preferences",
     *     type="json",
     *     nullable=true,
     *     options={
     *         "default" = null
     *     }
     * )
     */
    private $preferences;

    /**
     * TeamInformation constructor.
     *
     * @param int    $teamNumInDiv
     * @param string $teamName
     * @param string $teamDivision
     */
    public function __construct(
        int $teamNumInDiv,
        string $teamName,
        string $teamDivision
    ) {
        $this->teamNumInDiv = $teamNumInDiv;
        $this->teamName = $teamName;
        $this->teamDivision = $teamDivision;
    }

    /**
     * @return int
     */
    public function getTeamInformationId() : int
    {
        return $this->teamInformationId;
    }

    /**
     * @param int $teamInformationId
     *
     * @return TeamInformation
     */
    public function setTeamInformationId(int $teamInformationId) : TeamInformation
    {
        $this->teamInformationId = $teamInformationId;

        return $this;
    }

    /**
     * @return string
     */
    public function getTeamDivision() : string
    {
        return $this->teamDivision;
    }

    /**
     * @return string
     */
    public function getTeamName() : string
    {
        return $this->teamName;
    }

    /**
     * @return int
     */
    public function getTeamNumInDiv() : int
    {
        return $this->teamNumInDiv;
    }

    /**
     * @return array
     */
    public function getPreferences() : array
    {
        return $this->preferences;
    }

    /**
     * @param array $preferences
     *
     * @return TeamInformation
     */
    public function setPreferences(array $preferences) : TeamInformation
    {
        $this->preferences = $preferences;

        return $this;
    }
}