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
     *     nullable=false,
     * )
     */
    private $teamDivision;

    /**
     * @ORM\Column(
     *     name="Team_Name",
     *     type="string",
     *     length=32,
     *     nullable=false,
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
     * @return int
     */
    public function getTeamInformationId()
    {
        return $this->teamInformationId;
    }

    /**
     * @param int $teamInformationId
     *
     * @return TeamInformation
     */
    public function setTeamInformationId(int $teamInformationId)
    {
        $this->teamInformationId = $teamInformationId;

        return $this;
    }

    /**
     * @return string
     */
    public function getTeamDivision()
    {
        return $this->teamDivision;
    }

    /**
     * @param string $teamDivision
     *
     * @return TeamInformation
     */
    public function setTeamDivision(string $teamDivision)
    {
        $this->teamDivision = $teamDivision;

        return $this;
    }

    /**
     * @return string
     */
    public function getTeamName()
    {
        return $this->teamName;
    }

    /**
     * @param string $teamName
     *
     * @return TeamInformation
     */
    public function setTeamName(string $teamName)
    {
        $this->teamName = $teamName;

        return $this;
    }

    /**
     * @return int
     */
    public function getTeamNumInDiv()
    {
        return $this->teamNumInDiv;
    }

    /**
     * @param int $teamNumInDiv
     *
     * @return TeamInformation
     */
    public function setTeamNumInDiv(int $teamNumInDiv)
    {
        $this->teamNumInDiv = $teamNumInDiv;

        return $this;
    }
}