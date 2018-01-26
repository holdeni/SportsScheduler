<?php

namespace App\Repository;

use App\Entity\TeamInformation;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Symfony\Bridge\Doctrine\RegistryInterface;

class TeamInformationRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, TeamInformation::class);
    }

    public function save(TeamInformation $entity)
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
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

        return($dbData);
    }

    /**
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
}
