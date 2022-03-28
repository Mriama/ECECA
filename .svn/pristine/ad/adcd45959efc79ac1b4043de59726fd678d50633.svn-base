<?php

namespace App\Repository;

use App\Utils\EpleUtils;

use Doctrine\ORM\EntityRepository;

/**
 * RefAcademieRepository
 */
class RefAcademieRepository extends EntityRepository {

    public function queryBuilderFindRefAcademieSansContactByRefTypeElection($typeElect=null) {
        $idZonesAvecContact = array();
        $refContacts = $this->_em->getRepository('EPLEElectionBundle:RefContact')->findRefContactsByIdZoneTypeElection(EpleUtils::TOUTES_ZONES, $typeElect);

        $qb = $this->createQueryBuilder('aca');

        if (!empty($refContacts)) {
            $getIdZonesAvecContact = function ($contact) {
                return $contact->getIdZone();
            };

            $idZonesAvecContact = array_map($getIdZonesAvecContact, $refContacts);
        }

        if (!empty($idZonesAvecContact)) {
            $qb->where('aca.code not in ( :idZones )');
            $qb->setParameter('idZones', $idZonesAvecContact);
        }

        $qb->orderBy('aca.libelle', 'ASC');

        return $qb;
    }

    /**
     * @param : $typeElection : facultatif : RefTypeElection : nul par défaut
     * @return ArrayCollection of RefAcademie
     * "findRefAcademieSansContactByRefTypeElection" permet de récupérer les RefAcademie sans contact
     * pour un type d'élection donné $typeElection
     * Si $typeElection=null, cela renvoi tous les RefAcademie sans contact pour tout type d'élection
     */
    public function findRefAcademieSansContactByRefTypeElection($typeElect=null) {
        return $this->queryBuilderFindRefAcademieSansContactByRefTypeElection($typeElect)->getQuery()->getResult();
    }

    public function findListAcademies(){
        $qb  = $this->createQueryBuilder('acad');
        $qb->groupBy('acad.code');
        return $qb->getQuery()->getResult();
    }

    //Check date desactivation:
    public function checkifAcademieHasDisactivateDate($codeAcademie){
        $query = $this->createQueryBuilder('acad')
            ->select('acad.dateDesactivation')
            ->where('acad.code = :code')
            ->setParameter('code', $codeAcademie)
            ->setMaxResults(1);
        return $query->getQuery()->getResult();
    }

    //Check date activation:
    public function checkifAcademieHasActivateDate($codeAcademie){
        $query = $this->createQueryBuilder('acad')
            ->select('acad.dateDesactivation')
            ->where('acad.code = :code')
            ->setParameter('code', $codeAcademie)
            ->setMaxResults(1);
        return $query->getQuery()->getResult();
    }


    public function findTheAcademie($code){
        $query = $this->createQueryBuilder('acad')
            ->select('acadf.code, acadf.libelle')
            ->leftJoin('acad.AcademieFusion', 'acadf')
            ->where('acad.code = :code ')
            ->setParameter('code', $code)
            ->setMaxResults(1);

        return $query->getQuery()->getResult();

    }


    public function listeActiveAcademies($campagne = null){
        if($campagne) {
            $qb = $this->createQueryBuilder('acad')
                ->where('acad.dateDesactivation > :dateFin')
                ->andWhere('acad.dateActivation <= :dateDebut')
                ->setParameter('dateFin', new \DateTime($campagne->getAnneeDebut() . "-12-31"))
                ->setParameter('dateDebut', new \DateTime($campagne->getAnneeDebut() . "-01-01"))
                ->groupBy('acad.code')
                ->orderBy('acad.libelle');
            return $qb->getQuery()->getResult();
        } else {
            $qb = $this->createQueryBuilder('acad')
                ->groupBy('acad.code');
            return $qb->getQuery()->getResult();
        }
    }
    public function listeActiveAcademiesByDateCampagne($dateCamp){

        $qb  = $this->createQueryBuilder('acad')
            ->where('acad.dateDesactivation > :dateCamp')
            ->andwhere('acad.dateActivation <= :dateCamp')
            ->setParameter('dateCamp',  $dateCamp);


        return $qb->getQuery()->getResult();
    }

    public function findAcademieFisuByParParent($code){
        $qb  = $this->createQueryBuilder('acad')
            ->leftJoin('acad.AcademieFusion', 'acadf')
            ->where('acadf.code = :code')
            ->setParameter('code', $code);
        return $qb->getQuery()->getResult();
    }


    public function countchildAcademies($codeAcademie){
        $query = $this->createQueryBuilder('acad')
            ->select('COUNT(acad.code)')
            ->leftJoin('acad.AcademieFusion', 'acadf')
            ->where('acadf.code = :code')
            ->andWhere('acad.AcademieFusion IS NOT NULL')
            ->setParameter('code', $codeAcademie);
        return $query->getQuery()->getSingleScalarResult();

    }
    public function getchildnewAcademies($codeAcademie){
        $query = $this->createQueryBuilder('acad')
            ->leftJoin('acad.AcademieFusion', 'acadf')
            ->where('acadf.code = :code')
            ->andWhere('acad.AcademieFusion IS NOT NULL')
            ->setParameter('code', $codeAcademie);
        return $query->getQuery()->getResult();

    }

    public function getActiveAcademies($datenow){
        $qb  = $this->createQueryBuilder('acad')
            ->where('acad.dateDesactivation > :datenow')
            ->setParameter('datenow', $datenow)
            ->groupBy('acad.code');
        return $qb->getQuery()->getResult();
    }

}