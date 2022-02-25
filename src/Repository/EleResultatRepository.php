<?php
namespace App\Repository;

use App\Entity\EleCampagne;
use App\Entity\EleConsolidation;
use App\Entity\EleEtablissement;
use App\Entity\EleResultat;
use App\Entity\RefAcademie;
use App\Entity\RefDepartement;
use App\Entity\RefTypeElection;
use App\Entity\RefTypeEtablissement;
use App\Utils\EpleUtils;
use App\Entity\RefUser;
use App\Entity\RefProfil;


use Doctrine\ORM\EntityRepository;
use App\Entity\RefCommune;

/**
 * EleResultatRepository
 */
class EleResultatRepository extends EntityRepository {

	public function queryFindDatasResultatsGlobauxByCampagneZoneTypeEtab(EleCampagne $campagne, $zone=EPLEUtils::TOUTES_ZONES, $typeEtab="tous", $perimetre = null) {
		// Evol 015E afficher le nombre de sieges reellement attribués
		$stringQuery = "SELECT o.id as idOrg, o.libelle as libOrg, o.ordre as ordreOrg,
						sum(r.nbVoix) as nbVoix, sum(CASE WHEN r.nbSieges < r.nbCandidats  THEN r.nbSieges ELSE r.nbCandidats END) as nbSieges, sum(r.nbSiegesSort) as nbSiegesSort
						FROM EPLEElectionBundle:EleResultat r
						JOIN r.organisation o
						JOIN r.consolidation cons ";
		
		if (($zone instanceof RefAcademie) or ($zone instanceof RefDepartement)) {
			$stringQuery .= " WITH (cons.idZone = :zoneId) ";
			
			if ($zone instanceof RefAcademie) {
				$fct_getIdsDept = function ($dept) {
					return $dept->getIdZone();
				};
					
				$zonesDept = array_map($fct_getIdsDept, $zone->getDepartements()->toArray());
				$str_zoneIds = implode(" ', '", $zonesDept);
			
				$stringQuery .= " OR (cons.idZone in ( '" . $str_zoneIds . "' )) ";
			}	
		}
		
		$stringQuery .= " JOIN cons.campagne camp WITH camp.id = :campagneId ";
		
		if ($typeEtab instanceof RefTypeEtablissement) {
		    // Correction filtre de recherche des resultats
		    if(RefTypeEtablissement::ID_TYP_2ND_DEGRE == $typeEtab->getId()) {
		        $stringQuery .=" JOIN cons.typeEtablissement te WITH te.degre = 2 ";
		    } else {
		        $stringQuery .=" JOIN cons.typeEtablissement te WITH te = :typeEtab_sent ";
		    }
		} else if ($typeEtab === RefTypeEtablissement::CODE_URL_2ND_DEGRE) {
			$stringQuery .=" JOIN cons.typeEtablissement te WITH te.degre = 2 ";
		} else {
        	if ($perimetre != null && $perimetre->getDegres() != null) {
        		$stringQuery .=" JOIN cons.typeEtablissement te WITH te.degre in (:degres) ";
        	}
        }
		
		$stringQuery .= " GROUP BY o.id
						  ORDER BY o.ordre ASC, o.libelle ASC";
		
		$query = $this->_em->createQuery($stringQuery)
								->setParameter('campagneId', $campagne->getId());
		if (($zone instanceof RefAcademie) or ($zone instanceof RefDepartement)) { $query->setParameter('zoneId', $zone->getIdZone()); }
		// Correction filtre de recherche des résultats
		if ($typeEtab instanceof RefTypeEtablissement && RefTypeEtablissement::ID_TYP_2ND_DEGRE != $typeEtab->getId()) {
			$query->setParameter('typeEtab_sent', $typeEtab); 
		} else {
        	if ($perimetre != null && $perimetre->getDegres() != null) {
        		$query->setParameter('degres', $perimetre->getDegres());
        	}
        }

		return $query;
	}
	
	/*public function queryFindDatasResultatsEnCoursByCampagneZoneTypeEtab(EleCampagne $campagne, $zone, $typeEtab, $etatSaisie, $refUser = null, $isEreaErpdExclus = false, $idSousTypeElection = null)
	{
		$parameters = array();
		// Evol 015E afficher le nombre de sieges reellement attribués
		$stringQuery = "SELECT o.id as idOrg, o.libelle as libOrg, o.ordre as ordreOrg,
						sum(r.nbVoix) as nbVoix, 
						sum(CASE WHEN r.nbSieges < r.nbCandidats
							THEN r.nbSieges
							ELSE r.nbCandidats 
						END) AS nbSieges,
						sum(r.nbSiegesSort) as nbSiegesSort, sum(r.nbCandidats) as nbCandidats
						FROM EPLEElectionBundle:EleResultat r
						JOIN r.organisation o
						JOIN r.electionEtab eleEtab
						JOIN eleEtab.etablissement etab ";
		
		// Test d'abord si une commune est communiquée	
		if (!empty($zone)) {
			if ($zone instanceof RefCommune) {
				$stringQuery .= ' JOIN etab.commune comm WITH comm = :commune ';
				$parameters['commune'] = $zone;
			} else {
				$stringQuery .= ' JOIN etab.commune comm ';
			}
				
			if ($zone instanceof RefAcademie) {
				// DSDEN multidepartements Resultat : [ECT] fonctionnellement, tous les departements appartiennent a la meme academie
				if (null != $refUser && $refUser->getProfil()->getCode() == RefProfil::CODE_PROFIL_DSDEN) {
					$stringQuery .= ' JOIN comm.departement dept WITH dept.numero in ('.EpleUtils::getNumerosDepts($refUser->getPerimetre()->getDepartements()).') ';
				} else {
					$stringQuery .= ' JOIN comm.departement dept WITH dept.academie = :academie ';
					$parameters['academie'] = $zone;
				}
			}
			if ($zone instanceof RefDepartement) {
				$stringQuery .= ' JOIN comm.departement dept WITH dept = :departement ';
				$parameters['departement'] = $zone;
			}
		}
				
		
		$stringQuery .= " JOIN eleEtab.campagne camp WITH camp.id = :campagneId ";
		$parameters['campagneId'] = $campagne->getId();

		if ($typeEtab instanceof RefTypeEtablissement) {
		    // Correction filtre de recherche des resultats
		    if(RefTypeEtablissement::ID_TYP_2ND_DEGRE == $typeEtab->getId()) {
		        $stringQuery .=" JOIN etab.typeEtablissement te WITH te.degre = 2 ";
		    } else {
		        $stringQuery .=" JOIN etab.typeEtablissement te WITH te = :typeEtab_sent ";
		        $parameters['typeEtab_sent'] = $typeEtab;
		    }
		} else if ($typeEtab === RefTypeEtablissement::CODE_URL_2ND_DEGRE) {
		    $stringQuery .=" JOIN etab.typeEtablissement te WITH te.degre = 2 ";
		} else{
			$stringQuery .=" JOIN etab.typeEtablissement te ";
		}
		
		$stringQuery .= " WHERE 1=1 ";
		
		// Evol 013E RG_STATG_15
		if($isEreaErpdExclus){
			$stringQuery .= " AND te.id <> '".RefTypeEtablissement::ID_TYP_EREA_ERPD."'"; // YME 0145664
		}
		
		$stringQuery .= " AND eleEtab.validation IN (:etatSaisie) ";
		
		// Stats generales : CE/DE/IEN limiter aux etabs du perimetre
		if (null != $refUser
				&& ($refUser->getProfil()->getCode() == RefProfil::CODE_PROFIL_IEN
						|| $refUser->getProfil()->getCode() == RefProfil::CODE_PROFIL_CE
						|| $refUser->getProfil()->getCode() == RefProfil::CODE_PROFIL_DE)) {
			$stringQuery .= " AND etab.uai IN (". EpleUtils::getUais($refUser->getPerimetre()->getEtablissements()). ") ";
		}
		
		$parameters['etatSaisie'] = $etatSaisie;
		
		if(null != $idSousTypeElection){
			$stringQuery .= " AND eleEtab.sousTypeElection = ".$idSousTypeElection;
		}
		
		$stringQuery .= " GROUP BY o.id
						  ORDER BY o.ordre ASC, o.libelle ASC";
		
		$query = $this->_em->createQuery($stringQuery)->setParameters($parameters);
		return $query;
	}*/
	
	
	
	public function queryFindDatasResultatsEnCoursByCampagneZoneTypeEtab(EleCampagne $campagne, $zone, $typeEtab, $etatSaisie, $refUser = null, $isEreaErpdExclus = false, $idSousTypeElection = null)
	{

		// Evol 015E afficher le nombre de sieges reellement attribués
		$stringQuery = "SELECT o.id as idOrg,
							o.libelle as libOrg,
							o.ordre as ordreOrg,
							sum(CASE WHEN det.nb_voix IS NOT NULL THEN	det.nb_voix ELSE r.nb_voix END) as nbVoix, 
							sum(CASE WHEN det.nb_sieges_sort IS NOT NULL THEN det.nb_sieges_sort ELSE r.nb_sieges_sort END) as nbSiegesSort,
							sum(CASE WHEN det.nb_candidats IS NOT NULL THEN det.nb_candidats ELSE r.nb_candidats END) as nbCandidats,
							sum(CASE WHEN det.nb_sieges IS NOT NULL THEN
							    LEAST( det.nb_sieges, det.nb_candidats)
							ELSE
							    CASE WHEN r.nb_sieges < r.nb_candidats
							        THEN r.nb_sieges
							        ELSE r.nb_candidats 
							    END
							END) AS nbSieges
						FROM ele_resultat r
						INNER JOIN ref_organisation o ON r.id_organisation = o.id
						INNER JOIN ele_etablissement eleEtab ON r.id_etablissement = eleEtab.id
						INNER JOIN ref_etablissement etab ON eleEtab.uai = etab.uai ";
		
		// Test d'abord si une commune est communiquée
		
		if (!empty($zone)) {
			$stringQuery .= ' INNER JOIN ref_commune comm ON etab.id_commune = comm.id ';
			if ($zone instanceof RefCommune) {
				$stringQuery .= ' AND comm.id = '.$zone->getId();
			}
				
			if ($zone instanceof RefAcademie) {
				$stringQuery .= ' INNER JOIN ref_departement dept ON comm.departement = dept.numero ';
				// DSDEN multidepartements Resultat : [ECT] fonctionnellement, tous les departements appartiennent a la meme academie
				if (null != $refUser && $refUser->getProfil()->getCode() == RefProfil::CODE_PROFIL_DSDEN) {
					$stringQuery .= ' AND dept.numero in ('.EpleUtils::getNumerosDepts($refUser->getPerimetre()->getDepartements()).') ';
				} else {
                    $children = $this->getEntityManager()->getRepository('EPLEElectionBundle:RefAcademie')->getchildnewAcademies($zone->getCode());
                    if(!empty($children)) {
                        $stringQuery .= ' AND dept.academie IN ("'.$zone->getCode().'" ';
                        foreach ($children as $child) {
                            $stringQuery.= ', "' . $child->getCode() . '" ';
                        }
                        $stringQuery.= ')';
                    } else {
                        $stringQuery .= ' AND dept.academie = "'.$zone->getCode().'" ';
                    }
				}
			}
			if ($zone instanceof RefDepartement) {
				$stringQuery .= ' INNER JOIN ref_departement dept ON comm.departement = dept.numero ';
				$stringQuery .= ' AND dept.numero = "'.$zone->getNumero().'" ';
			}
		}
				
		
		$stringQuery .= " INNER JOIN ele_campagne camp ON eleEtab.id_campagne = camp.id AND camp.id = ".$campagne->getId();

		$stringQuery .=" INNER JOIN ref_type_etablissement te ON etab.id_type_etablissement = te.id ";

		if ($typeEtab instanceof RefTypeEtablissement) {
		    // Correction filtre de recherche des resultats
		    if(RefTypeEtablissement::ID_TYP_2ND_DEGRE == $typeEtab->getId()) {
		        $stringQuery .=" AND te.degre = 2 ";
		    } else {
		        $stringQuery .=" AND te.id = ". $typeEtab->getId();
		    }
		} else if ($typeEtab === RefTypeEtablissement::CODE_URL_2ND_DEGRE) {
		    $stringQuery .=" AND te.degre = 2 ";
		}
		$stringQuery .= " LEFT JOIN ele_resultat_detail det ON det.id_organisation = o.id AND det.id_etablissement = eleEtab.id";
		$stringQuery .= " WHERE 1=1 ";
		
		// Evol 013E RG_STATG_15
		if($isEreaErpdExclus){
			$stringQuery .= " AND te.id <> '".RefTypeEtablissement::ID_TYP_EREA_ERPD."'"; // YME 0145664
		}
		
		if (is_array($etatSaisie))
			$stringQuery .= " AND eleEtab.validation IN ('".implode("','", $etatSaisie)."') ";
		else
			$stringQuery .= " AND eleEtab.validation IN ('".$etatSaisie."') ";
		
		// Stats generales : CE/DE/IEN limiter aux etabs du perimetre
		if (null != $refUser
				&& ($refUser->getProfil()->getCode() == RefProfil::CODE_PROFIL_IEN
						|| $refUser->getProfil()->getCode() == RefProfil::CODE_PROFIL_CE
						|| $refUser->getProfil()->getCode() == RefProfil::CODE_PROFIL_DE)) {
			$stringQuery .= " AND etab.uai IN (". EpleUtils::getUais($refUser->getPerimetre()->getEtablissements()). ") ";
		}
	
		
		if(null != $idSousTypeElection){
			$stringQuery .= " AND eleEtab.id_sous_type_election = ".$idSousTypeElection;
		}
		
		$stringQuery .= " GROUP BY o.id
						  ORDER BY o.ordre ASC, o.libelle ASC";
		
		$query = $this->getEntityManager()->getConnection()->prepare($stringQuery);
		$query->execute();
		//$this->_em->getConnection()->prepare($stringQuery)->execute();
		//var_dump($query->fetch());
		return $query->fetchAll();
	}

	/**
	 * @param : $campagne : RefCampagne
	 * @param : $zone : RefDepartement, RefAcademie ou TOUTES_ZONES
	 * @param : $typeEtab : RefTypeEtablissement ou "tous" ou "2nd"
	 * @return des informations nécessaires pour des résultats globaux (= départemental ou académique ou national)
	 * "findDatasGlobauxFromEleResultatByCampagneZone" permet de récupérer diverses informations
	 * pour $campagne, $zone et $typeEtab données
	 */
	public function findDatasGlobauxFromEleResultatByCampagneZoneTypeEtab(EleCampagne $campagne, $zone=EpleUtils::TOUTES_ZONES, $typeEtab="tous", $perimetre = null) {
		return $this->queryFindDatasResultatsGlobauxByCampagneZoneTypeEtab($campagne, $zone, $typeEtab, $perimetre)->getResult();
	}
	
	/**
	 * @param : $campagne : RefCampagne
	 * @param : $zone : RefDepartement, RefAcademie ou TOUTES_ZONES
	 * @param : $typeEtab : RefTypeEtablissement ou "tous" ou "2nd"
	 * @param : $etatSaisie : array d'états de validation demandés
	 * @return des informations nécessaires pour des résultats globaux (= départemental ou académique ou national)
	 * "findDatasEnCoursFromEleResultatByCampagneZoneTypeEtab" permet de récupérer diverses informations
	 * pour $campagne, $zone, $typeEtab et $etatSaisie données
	 */
	public function findDatasEnCoursFromEleResultatByCampagneZoneTypeEtab(EleCampagne $campagne, $zone=EpleUtils::TOUTES_ZONES, $typeEtab="tous", $etatSaisie, $refUser=null, $isEreaErpdExclus = false, $idSousTypeElection = null) {
		return $this->queryFindDatasResultatsEnCoursByCampagneZoneTypeEtab($campagne, $zone, $typeEtab, $etatSaisie, $refUser, $isEreaErpdExclus, $idSousTypeElection);//->getResult();
	}
	
	/**
	 * @param : $eleCons : EleConsolidation : facultatif
	 * @return liste de EleResultat
	 * "findByEleConsolidationOrderByOrdre" permet de récupérer des EleResultat rangé par l'ordre de l'organisation
	 * pour $eleCons donné
	 */
	 public function findByEleConsolidationOrderByOrdre(EleConsolidation $eleCons=null) {
		$qb = $this->createQueryBuilder('res')->innerjoin('res.organisation', 'o');
		if (!empty($eleCons)) {
			$qb->where('res.consolidation =:eleCons')->setParameter('eleCons', $eleCons);
		}
		$qb->orderBy('o.ordre', 'ASC')->addOrderBy('o.libelle', 'ASC');
		
		return $qb->getQuery()->getResult();
	}
	
	/**
	 * @param : $eleEtab : EleEtablissement : facultatif
	 * @return liste de EleResultat
	 * "findByEleEtablissementOrderByOrdre" permet de récupérer des EleResultat rangé par l'ordre de l'organisation
	 * pour $eleEtab donné
	 */
	 public function findByEleEtablissementOrderByOrdre(EleEtablissement $eleEtab=null) {
		$qb = $this->createQueryBuilder('res')->innerjoin('res.organisation', 'o');
		if (!empty($eleEtab)) {
			$qb->where('res.electionEtab =:eleEtab')->setParameter('eleEtab', $eleEtab);
		}
		$qb->orderBy('o.ordre', 'ASC')->addOrderBy('o.libelle', 'ASC');
		return $qb->getQuery()->getResult();
	}
	
	/**
	 * @param : $eleEtab : EleEtablissement : facultatif
	 * @return liste de EleResultat
	 * "findByEleEtablissementOrderByOrdre" permet de récupérer des EleResultat rangé par l'ordre de l'organisation
	 * pour $eleEtab donné
	 */
	public function findByEleEtablissementsOrderByOrdre($campagne, $lstElectEtab) {
	    
	    $qb = $this->createQueryBuilder('res')->innerjoin('res.organisation', 'o')
	    	  ->join('res.electionEtab', 'eleEtab');
	    
	    $idEtablissement = array();
	    foreach ($lstElectEtab as $electEtab) {
	        if (!empty($electEtab)) {
	            array_push($idEtablissement, $electEtab->getId());
	        }
	    }
	    
        $qb->where('eleEtab.campagne = :campagne')->setParameter('campagne', $campagne);
        $qb->andWhere('eleEtab IN (:ids)')->setParameter('ids', $idEtablissement);
	    $qb->orderBy('o.ordre', 'ASC')->addOrderBy('o.libelle', 'ASC');
	    return $qb->getQuery()->getResult();
	}
	
	
	
	/**
	 * @param $campagne : EleCampagne
	 * @param $zone : RefAcademie ou RefDepartement
	 * @param $validation : boolean : true ou false
	 * @param RefTypeEtablissement : $typeEtab
	 * @return sizeof(EleEtablissement)
	 * "getNbEleResParCampagne" permet de compter le nombre de eleEtablissement ayant des résultats
	 * pour une $campagne et une $zone données avec validation ou sans
	 */
	public function getNbEleResParCampagne($campagne, $zone=null, $validation='S', $typeEtab=null) {
	
		$query = $this->createQueryBuilder('res')
					  ->select('eleEtab.id')
					  ->join('res.electionEtab', 'eleEtab')
					  ->join('eleEtab.etablissement', 'etab');
		
		if ($zone!=null) {
			$query = $query->join('etab.commune', 'c')
				  		   ->join('c.departement', 'd');	  
		}
		$query = $query->groupBy('res.electionEtab');
		
		$query = $query->where('eleEtab.campagne = :campagne');
		
		if ($typeEtab != null)
			$query = $query->andWhere('etab.typeEtablissement = :typeEtab');
		
		if ($zone instanceof RefDepartement)
			$query = $query->andWhere('c.departement = :zone');
		if ($zone instanceof RefAcademie)
			$query = $query->andWhere('d.academie = :zone');
		

		$query = $query->andWhere('eleEtab.validation = :validation');
		
		$query = $query->setParameter('campagne', $campagne);
		
		if ($zone != null)
			$query = $query->setParameter('zone', $zone);
		if ($typeEtab != null)
			$query = $query->setParameter('typeEtab', $typeEtab);

		
		$query = $query->setParameter('validation', $validation);

		return sizeof($query->getQuery()->getResult());
	}
	
}