<?php

namespace App\Repository;


use App\Entity\RefAcademie;
use App\Entity\RefDepartement;
use App\Utils\EpleUtils;
use App\Entity\RefProfil;

use Doctrine\ORM\EntityRepository;

/**
 * RefContactRepository
 */
class RefContactRepository extends EntityRepository {

    /**
     * @param : $zoneId : string : TOUTES_ZONES (pour dire tous les enregistrements) ou identifiants d'une académie ou d'un département
     * @param : $typeElection : facultatif : RefTypeElection : nul par défaut
     * @return ArrayCollection of RefContact
     * "findRefContactsByIdZoneTypeElection" permet de récupérer les RefContact d'académie ou de département
     * en fonction de $zoneId et de $typeElection
     */
    public function findRefContactsByIdZoneTypeElection($zoneId, $typeElection=null, $user=null) {
        $contacts = array();
        $zone = EpleUtils::getZone($this->_em, $zoneId);
        if($zone instanceof RefAcademie) {
            $codesAca = array();
            $allAca = array();
            array_push($codesAca, $zone->getCode());
            array_push($allAca, $zone);
            $childenAcad = $this->_em->getRepository('EPLEElectionBundle:RefAcademie')->getchildnewAcademies($zone->getCode());
            if ($childenAcad != null && !empty($childenAcad)) {
                foreach ($childenAcad as $aca) {
                    array_push($allAca, $aca);
                }
            }
        }

        if(!empty($zone)) {
            $qb = $this->createQueryBuilder('refCon');
            if($typeElection!=null) {
                $qb->join('refCon.typeElection', 'te', 'With', 'te.id = :typeElectionId');
                $qb->setParameter('typeElectionId', $typeElection->getId());
            }
            // correction de l'anomalie 0173232
            if ($user != null) {
                $qb->where('refCon.idZone = ( :zoneId )');
                $qb->setParameter('zoneId', $zoneId);
            } else {
                if ($zone instanceof RefDepartement) {
                    $qb->where('refCon.idZone = ( :zoneId )');
                    $qb->setParameter('zoneId', $zone->getNumero());
                } else if ($zone instanceof RefAcademie) {
                    $zones = $codesAca;
                    foreach ($allAca as $aca) {
                        foreach ($aca->getDepartements() as $dept) {
                            $zones[] = $dept->getNumero();
                        }
                    }
                    $qb->where('refCon.idZone in ( :zones )');
                    $qb->setParameter('zones', $zones);
                }
            }
            $contacts = $qb->getQuery()->getResult();
        }
        return $contacts;
    }

    /**
     * Courriel de relance : determination de l'unique contact de la zone souhaité pour une Election
     * @param $zoneId
     * @param null $typeElection
     * @param null $user
     * @return array|int|string
     */
    public function findUniqueContactByZoneAndTypeElection($zoneId, $typeElection) {
        $contacts = array();
        $zone = EpleUtils::getZone($this->_em, $zoneId);

        if(!empty($zone)) {
            $qb = $this->createQueryBuilder('refCon');
            if($typeElection!=null) {
                $qb->join('refCon.typeElection', 'te', 'With', 'te.id = :typeElectionId');
                $qb->setParameter('typeElectionId', $typeElection->getId());
            }
            if ($zone instanceof RefDepartement) {
                $qb->where('refCon.idZone = ( :zoneId )');
                $qb->setParameter('zoneId', $zone->getNumero());
            } else if ($zone instanceof RefAcademie) {
                $qb->where('refCon.idZone = ( :zoneId )');
                $qb->setParameter('zoneId', $zone->getCode());
            }
            $contacts = $qb->getQuery()->getResult();
        }
        return $contacts;
    }

    /**
     * @param : $typeZone : string : TOUTES_ZONES ou 'RefAcademie' (nom entitité d'une académie ) ou 'RefDepartement' (nom entité d'un département)
     * @param : $typeElection : facultatif : RefTypeElection : null par défaut
     * @return ArrayCollection of RefContact
     * "findRefContactsByTypeZoneTypeElection" permet de récupérer des RefContact
     * en fonction de $typeZone et de RefTypeElection si renseigné
     */
    public function findRefContactsByTypeZoneTypeElection($typeZone, $typeElection=null) {
        $contacts = array();
        $zones = array();

        if( $typeZone == EpleUtils::TOUTES_ZONES or $typeZone == RefAcademie::getNameEntity() or $typeZone == RefDepartement::getNameEntity() ) {

            switch ($typeZone) {
                case RefAcademie::getNameEntity():
                    $academies = $this->_em->getRepository('EPLEElectionBundle:RefAcademie')->findAll();
                    $getCodeAcademies = function($aca){ return $aca->getCode(); };
                    $zones = array_map($getCodeAcademies, $academies);
                    break;
                case RefDepartement::getNameEntity():
                    $departements = $this->_em->getRepository('EPLEElectionBundle:RefDepartement')->findAll();
                    $getCodeDepts = function($dept){ return $dept->getNumero(); };
                    $zones = array_map($getCodeDepts, $departements);
                    break;
            }

            $qb = $this->createQueryBuilder('refCon');

            if($typeElection !=null) {
                $qb->join('refCon.typeElection', 'te', 'With', 'te.id = :typeElectionId');
                $qb->setParameter('typeElectionId', $typeElection->getId());
            }

            if(!empty($zones)) {
                $qb->where('refCon.idZone in (:zones)');
                $qb->setParameter('zones', $zones);
            }
            $contacts = $qb->getQuery()->getResult();
        }

        return $contacts;
    }

    /**
     * @param : $typeZone : string : TOUTES_ZONES ou nom entitité d'une académie ou nom entité d'un département
     * @param : $typeElection : facultatif : RefTypeElection : nul par défaut
     * @return ArrayCollection of ContactModel
     * "findContactModelsByTypeZoneTypeElection" permet de récupérer des ContactModel
     * en fonction de $typeZone et de $typeElection si renseigné
     */
    public function findContactModelsByTypeZoneTypeElection($typeZone, $typeElection=null) {
        $modelContacts = array();

        $lstRefContacts = $this->findRefContactsByTypeZoneTypeElection($typeZone, $typeElection);
        foreach ($lstRefContacts as $contact) {
            $modelContacts[] = new \App\Model\ContactModel(EpleUtils::getZone($this->_em, $contact->getIdZone()), $contact);
        }

        $tri = function($a, $b) {
            if ($a->getLibelle() == $b->getLibelle()) { return 0; }
            return ($a->getLibelle() < $b->getLibelle()) ? -1 : 1;
        };

        usort($modelContacts, $tri);


        return $modelContacts;
    }

    /**
     *
     * @param unknown $idZone
     * @return unknown
     */
    public function findContactsByIdZone($idZone){
        $contacts = array();

        $qb = $this->createQueryBuilder('refCon');
        $qb->where('refCon.idZone = (:idZone)');
        $qb->setParameter('idZone', $idZone);

        $contacts = $qb->getQuery()->getResult();

        return $contacts;

    }

}