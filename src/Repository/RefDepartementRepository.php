<?php

namespace App\Repository;

use App\Utils\EpleUtils;

use App\Entity\RefContact;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;

/**
 * RefDepartementRepository
 */
class RefDepartementRepository extends EntityRepository {

    public function queryBuilderRefDepartementSansContactByRefTypeElection($typeElect=null) {
        $idZonesAvecContact = array();
        $refContacts = $this->_em->getRepository(RefContact::class)->findRefContactsByIdZoneTypeElection(EpleUtils::TOUTES_ZONES, $typeElect);

        $qb = $this->createQueryBuilder('dept');

        if (!empty($refContacts)) {
            $getIdZonesAvecContact = function ($contact) {
                return $contact->getIdZone();
            };

            $idZonesAvecContact = array_map($getIdZonesAvecContact, $refContacts);

            if (!empty($idZonesAvecContact)) {
                $qb->where('dept.numero not in ( :idZones )');
                $qb->setParameter('idZones', $idZonesAvecContact);
            }
        }
        $qb->orderBy('dept.libelle', 'ASC');

        return $qb;
    }

    /**
     * @param : $typeElection : facultatif : RefTypeElection : nul par défaut
     * @return ArrayCollection of RefDepartement
     * "findRefDepartementSansContactByRefTypeElection" permet de récupérer les RefDepartement sans contact
     * pour un type d'élection donné $typeElection
     * Si $typeElection=null, cela renvoi tous les RefDepartement sans contact pour tout type d'élection
     */
    public function findRefDepartementSansContactByRefTypeElection($typeElect=null) {
        return $this->queryBuilderRefDepartementSansContactByRefTypeElection($typeElect)->getQuery()->getResult();
    }

    public function findListDepartements(){
        $qb  = $this->createQueryBuilder('dept');
        $qb->groupBy('dept.numero');
        return $qb->getQuery()->getResult();
    }

    function findBydepartementAdademiefusionner($code){
        $qb  = $this->createQueryBuilder('dept')

            ->leftJoin('dept.academie', 'acad')
            ->leftJoin('acad.AcademieFusion', 'acadf')
            ->where('acadf.code = :code')

            ->andwhere('acad.AcademieFusion IS NOT NULL')
            ->setParameter('code', $code);
        return $qb->getQuery()->getResult();
    }
}