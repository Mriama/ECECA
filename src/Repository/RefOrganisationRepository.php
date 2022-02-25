<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;

class RefOrganisationRepository extends EntityRepository {

	public function findOrganisationsByRefTypeElection($idTypeElection) {
		$qb = $this->createQueryBuilder('o')->join('o.typeElection','t');
		$qb->where("t.id = :idTypeElection")
			->setParameter('idTypeElection', $idTypeElection)
			->orderBy('o.ordre', 'ASC');
		return $qb->getQuery()->getResult();
	}
	
	/**
	 * Permet de trouver une liste des organisations non obsolètes en fonction du type d'élection d'une campagne 
	 * @param integer $idTypeElection
	 * @return $resultat liste de RefOrganisation
	 */
	public function findOrganisationNonObseletByRefTypeElection($idTypeElection) {
		$qb = $this->createQueryBuilder('o')->join('o.typeElection', 't')
										    ->where("t.id = :idTypeElection")
										    ->andWhere("o.obsolete = 0")
										    ->setParameter('idTypeElection', $idTypeElection)
										    ->orderBy('o.ordre', 'ASC');
		$resultat = $qb->getQuery()->getResult();
		return $resultat;
	}
	
	
}