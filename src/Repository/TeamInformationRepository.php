<?php

namespace App\Repository;

use App\Entity\TeamInformation;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Class TeamInformationRepository
 * @package App\Repository
 */
class TeamInformationRepository extends ServiceEntityRepository
{
    /**
     * TeamInformationRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, TeamInformation::class);
    }

    /**
     * @param TeamInformation $entity
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(TeamInformation $entity)
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    /**
     * Truncate the table
     */
    public function truncate()
    {
        $sql = "SET FOREIGN_KEY_CHECKS=0;
                TRUNCATE Team_Information;
                SET FOREIGN_KEY_CHECKS=1;
                ";
        $dbConn = $this->getEntityManager()->getConnection();
        $dbCmd = $dbConn->prepare($sql);
        $dbCmd->execute();
    }

    /**
     * Get the count of teams in divisions
     *
     * @return array
     */
    public function getNrOfTeamsInDiv()
    {
        $sql = "SELECT ti.teamDivision, COUNT(ti.teamDivision) as NrTeamsInDiv
                FROM App:TeamInformation ti
                GROUP BY ti.teamDivision
               ";
        $dbData = $this->getEntityManager()
            ->createQuery($sql)
            ->getArrayResult();

        return $dbData;
    }

    /**
     * Map the id of team in division to its unique team id in the league
     *
     * @param int    $teamDivisionId
     * @param string $division
     *
     * @return int
     */
    public function mapTeamDivIdToTeamId(int $teamDivisionId, string $division)
    {
        $sql = "SELECT ti.teamInformationId
                FROM App:TeamInformation ti
                WHERE ti.teamDivision = :division
                  AND ti.teamNumInDiv = :teamDivisionId
               ";
        $dbData = $this->getEntityManager()
            ->createQuery($sql)
            ->setParameters(array(
                'division' => $division,
                'teamDivisionId' => $teamDivisionId,
            ))
            ->getResult(Query::HYDRATE_SINGLE_SCALAR);

        return $dbData;
    }

    /**
     * Get set of divisions in league
     *
     * @return string[]
     */
    public function getListOfTeamDivisions()
    {
        $divisionList = array();

        $sql = "SELECT DISTINCT(ti.teamDivision)
                FROM App:TeamInformation ti
                ORDER BY ti.teamDivision
               ";
        $dbData = $this->getEntityManager()
            ->createQuery($sql)
            ->getArrayResult();

        foreach ($dbData as $row) {
            $divisionList[] = $row[1];
        }

        return $divisionList;
    }

    /**
     * Get list of teams in specified division
     *
     * @param string $division
     *
     * @return TeamInformation[] | null
     */
    public function getListOfTeamsInDivision(string $division)
    {
        $sql = "SELECT ti
                FROM App:TeamInformation ti
                WHERE ti.teamDivision = :division
                ORDER BY ti.teamInformationId
               ";
        $dbData = $this->getEntityManager()
            ->createQuery($sql)
            ->setParameters(array(
                'division' => $division,
            ))
            ->getResult(Query::HYDRATE_OBJECT);

        return $dbData;
    }

    /**
     * Get name of team from provided id
     *
     * @param int $teamId
     *
     * @return string
     */
    public function getTeamName(int $teamId)
    {
        $sql = "SELECT ti.teamName
                FROM App:TeamInformation ti
                WHERE ti.teamInformationId = :teamId
               ";
        $teamName = $this->getEntityManager()
            ->createQuery($sql)
            ->setParameters(array(
                'teamId' => $teamId,
            ))
            ->getResult(Query::HYDRATE_SINGLE_SCALAR);

        return $teamName;
    }
}
