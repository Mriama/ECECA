<?php

namespace App\Repository;
use App\Entity\RefTypeElection;
use App\Entity\RefSousTypeElection;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\ResultSetMapping;

class RefSousTypeElectionRepository extends EntityRepository {
	
	public function findSousTypesElectionsByRefTypeElection($idTypeElection) {
		$qb = $this->createQueryBuilder('s')->join('s.typeElection','t');
		$qb->where("t.id = :idTypeElection")
		->setParameter('idTypeElection', $idTypeElection)
		->orderBy('s.code', 'ASC');
		return $qb->getQuery()->getResult();
	}
	
	// Retourne toute la liste des sous type elections
	public function getSousTypesElections() {
		$qb = $this->createQueryBuilder('s')
		->orderBy('s.code', 'ASC');
	   $listeRefSousTypeElection = $qb->getQuery()->getResult();

    	$array = array();
    	foreach ($listeRefSousTypeElection as $refSte) {
    		$array[$refSte->getId()] = $refSte->getCode();
    	}
    	return $array;
	}
}