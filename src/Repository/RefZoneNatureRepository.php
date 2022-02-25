<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use App\Entity\RefZoneNature;


class RefZoneNatureRepository extends EntityRepository{
	
	/**
	 * 
	 * @param unknown $uaiZoneNature
	 * @return string
	 * 
	 * Fonction permettant d'extraire une zone nature via son UAI
	 * Elle sert a alimenter le champs "Zone nature" dans l'import RAMSESE
	 */
	public function findOneLibelleNatureByUai($uaiZoneNature){

		$resultat = "";
		if(!empty($uaiZoneNature)){
			$query = $this->createQueryBuilder('nature')
			->select('nature.libelle_court')
			->where("nature.uai_nature = :uai_nature")
			->setParameter('uai_nature', $uaiZoneNature);
			$resultat = $query->getQuery()->getSingleScalarResult();			
		}
		return $resultat;
	}
	
	public function findOneByTypeNature($typeNature){
	    
        $query = $this->createQueryBuilder('nature')
        ->where("nature.type_nature =:typeNature")
        ->setParameter('typeNature', $typeNature);
        return $query->getQuery()->getResult();

	}
}
?>