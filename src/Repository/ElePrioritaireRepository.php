<?php

namespace App\Repository;
use App\Entity\ElePrioritaire;
use App\Entity\EleParticipation;
use Doctrine\ORM\EntityRepository;
use App\Entity\RefTypeEtablissement;

/**
 * ElePrioritaireRepository
 */
class ElePrioritaireRepository extends EntityRepository {
	
	public function findListElePrioritaireParParticipation($participation){
			$qb = $this->createQueryBuilder('elePrio');
			$qb->join('elePrio.participation', 'elePart');
			$qb->leftjoin('elePrio.typePrioritaire', 'typePrio');
			$qb->select('typePrio.code as categorie, elePart.id as libelle, elePrio.id as Id, sum(elePrio.nbInscrits) as nbIns, sum(elePrio.nbVotants) as nbVotants, sum(elePrio.nbExprimes) as nbExpr');
			$qb->where('elePart.id = :participation');
			$qb->setParameter('participation',$participation);
			$qb->groupBy('typePrio.code');
			$qb->orderBy('typePrio.code', 'DESC');
			
			return $qb->getQuery()->getResult();
	}
	
	
	/**
	 *
	 * @param EleCampagne $campagne
	 * @param RefAcademie ou RefDepartement $niveau
	 * @param RefTypeEtablissement $typeEtablissement
	 * @return List<Departement ou Académie> avec les informations sur les participations
	 *         "findParticipationDetailleeParTypeZoneEtTypePrioritaire" permet de récuperer la liste des départements ou des académies
	 *         ainsi que les identifiants des participations
	 *         pour une $campagne, un $niveau et un $typeEtablissement donnés
	 */
	/* public function findParticipationDetailleeParTypeZoneEtTypePrioritaire($campagneId, $campagnePrecId, $typeZone, $idTypeEtablissement = null, $perimetre = null)
	{
	     
    	$libelle = 'refDepartement.libelle';
    	if($typeZone == 'academie'){
    		$libelle = 'refAcademie.libelle';
    	}    	

        $stringQuery = '
        		SELECT eleCampagne.id as idCampagne, '.$libelle.', refTypePrio.code, sum(elePrio.nbInscrits) as sumInscrits, sum(elePrio.nbVotants) as sumVotants,
        		sum(elePrio.nbVotants - elePrio.nbNulsBlancs) as sumExprimes, (sum(elePrio.nbVotants)/sum(elePrio.nbInscrits))*100 as p
				FROM EPLEElectionBundle:EleConsolidation eleConso
				JOIN EPLEElectionBundle:ElePrioritaire elePrio WITH elePrio.participation = eleConso.participation
				JOIN EPLEElectionBundle:RefTypePrioritaire refTypePrio WITH  refTypePrio.id = elePrio.typePrioritaire
				JOIN EPLEElectionBundle:RefDepartement refDepartement WITH refDepartement.numero = eleConso.idZone
        		JOIN EPLEElectionBundle:EleCampagne eleCampagne WITH eleCampagne.id = eleConso.campagne
        		JOIN EPLEElectionBundle:RefTypeEtablissement refTypeEtab WITH refTypeEtab.id = eleConso.typeEtablissement
				JOIN EPLEElectionBundle:RefAcademie refAcademie WITH refAcademie.code = refDepartement.academie
        		WHERE eleCampagne.id IN ( '.$campagneId.', '.$campagnePrecId.')';
       
        /* Vérifier si c'est type "2ème degré choisi, id = 2+3+4", Prise en compte de l'ID du type 6 (2eme degré) 
        if (null != $idTypeEtablissement) {
        	if ($idTypeEtablissement == RefTypeEtablissement::ID_TYP_2ND_DEGRE) {   
        		// Pour 2eme degré, le type EREA-ERPD n'est pas pris en compte     		
        		$stringQuery .= 'AND refTypeEtab.id in ('.RefTypeEtablissement::ID_TYP_COLLEGE.', '.RefTypeEtablissement::ID_TYP_LYCEE.', '.RefTypeEtablissement::ID_TYP_LYC_PRO.')';
        	} else {
        		$stringQuery .= 'AND refTypeEtab.id = '.$idTypeEtablissement;
        		//$stringQuery .= 'AND refTypeEtab.id != '.RefTypeEtablissement::ID_TYP_EREA_ERPD; // YME 0145664
        	}
        } else {
        	if ($perimetre != null && $perimetre->getDegres() != null) {
        		$stringDegres = implode("','", $perimetre->getDegres());
        		$stringQuery .= ' AND refTypeEtab.degre in (\'' . $stringDegres . '\')';
        		//$stringQuery .= 'AND refTypeEtab.id != '.RefTypeEtablissement::ID_TYP_EREA_ERPD; // YME 0145664
        	}
        }        
   
	    
	    // restriction par perimetre
	    if ($perimetre != null && $perimetre->getAcademies() != null) {
	        $stringAcademies = "";
	        foreach ($perimetre->getAcademies() as $uneAca) {
	            $stringAcademies .= "'" . $uneAca->getCode() . "',";
	        }
	        $stringAcademies = substr($stringAcademies, 0,  strlen($stringAcademies) - 1);
	        $stringQuery .= ' AND refAcademie.code in (' . $stringAcademies . ')';
	    }
	    
	    if ($perimetre != null && $perimetre->getDepartements() != null) {
	        $stringDepartements = "";
	        foreach ($perimetre->getDepartements() as $unDept) {
	            $stringDepartements .= "'" . $unDept->getNumero() . "',";
	        }
	        $stringDepartements = substr($stringDepartements, 0,  strlen($stringDepartements) - 1);
	        $stringQuery .= ' AND refDepartement.numero in (' . $stringDepartements . ')';
	    }
	    
        $stringQuery .= '
				GROUP BY eleCampagne.id, '.$libelle.', refTypePrio.code
        		ORDER BY '.$libelle.', refTypePrio.code, eleCampagne.id desc';
		
	    $query = $this->_em->createQuery($stringQuery);
	
	    return $query->getResult();
	} */
	
}