<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use App\Entity\EleCampagne;
use App\Entity\EleConsolidation;
use App\Entity\EleParticipation;
use App\Entity\EleResultat;
use App\Entity\EleEtablissement;
use App\Entity\EleResultatDetail;
use App\Entity\RefAcademie;
use App\Entity\RefDepartement;
use App\Entity\RefTypeEtablissement;
use App\Utils\EpleUtils;
use App\Entity\RefCommune;

/**
 * EleConsolidationRepository
 */
class EleConsolidationRepository extends EntityRepository {

    public function queryFindDatasConsolidationOfResultatsGlobauxByCampagneZoneTypeEtab(EleCampagne $campagne, $zone = EpleUtils::TOUTES_ZONES, $typeEtab = "tous", $perimetre = null) {

        $stringQuery = "	SELECT	camp.id, sum(p.nbInscrits) as nbIns, sum(p.nbVotants) as nbVotants, sum((p.nbVotants-p.nbNulsBlancs)) as nbExpr,
							   	sum(p.nbSiegesPourvoir) as nbSiegPourvoir, sum(p.nbSiegesPourvus) as nbSiegPourvus,
							   	sum(cons.nbEtabExprimes) as nbEtabExpr, sum(cons.nbEtabTotal) as nbEtabTotal
						FROM EleConsolidation cons
						JOIN cons.participation p
						JOIN cons.campagne camp WITH camp.id = :campagneId ";

        if ($typeEtab instanceof RefTypeEtablissement) {
            // Correction filtre de recherche des résultats
            if(RefTypeEtablissement::ID_TYP_2ND_DEGRE == $typeEtab->getId()) {
                $stringQuery .=" JOIN cons.typeEtablissement te WITH te.degre = 2 ";
            } else {
                $stringQuery .= " JOIN cons.typeEtablissement te WITH te = :typeEtab_sent ";
            }
        } else if ($typeEtab === RefTypeEtablissement::CODE_URL_2ND_DEGRE) {
            $stringQuery .= " JOIN cons.typeEtablissement te WITH te.degre = 2 ";
        } else {
        	if ($perimetre != null && $perimetre->getDegres() != null) {
        		$stringQuery .=" JOIN cons.typeEtablissement te WITH te.degre in (:degres) ";
        	}
        }

        if (($zone instanceof RefAcademie) or ( $zone instanceof RefDepartement)) {
        	if ($zone->getIdZone() != null) { // ajout DSDEN multidepartements
        		$stringQuery .= " WHERE cons.idZone = '" . $zone->getIdZone() . "' ";
        		
        		if ($zone instanceof RefAcademie) {
        			$fct_getIdsDept = function ($dept) {
        				return $dept->getIdZone();
        			};
        		
        			$zonesDept = array_map($fct_getIdsDept, $zone->getDepartements()->toArray());
        			$str_zoneIds = implode(" ', '", $zonesDept);
        		
        			$stringQuery .= " OR cons.idZone in ( '" . $str_zoneIds . "' ) ";
        		}
        	} else { // ajout DSDEN multidepartements
        		if ($zone instanceof RefAcademie) {
        			$stringQuery .= " WHERE cons.idZone in ( " . EpleUtils::getNumerosDepts($zone->getDepartements()) . " ) ";
        		}
        	}
            
        }

        $stringQuery .= " GROUP BY camp.id ";

        $query = $this->_em->createQuery($stringQuery)->setParameter('campagneId', $campagne->getId());
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
    
    public function getNbEtabTotalWithCampagneByCampagneZoneTypeEtab(EleCampagne $campagne, $zone = null, $typeEtab = "tous", $perimetre = null) {
    	
    	$stringQuery = "	SELECT	cons.idZone as idZone, cons.nbEtabTotal as nbEtabTotal
						FROM EleConsolidation cons
						JOIN cons.campagne camp WITH camp.id = :campagneId ";
    	// var_dump($zone);die();
    	if ($typeEtab instanceof RefTypeEtablissement) {
    		// Correction filtre de recherche des résultats
    		if(RefTypeEtablissement::ID_TYP_2ND_DEGRE == $typeEtab->getId()) {
    			$stringQuery .=" JOIN cons.typeEtablissement te WITH te.degre = 2 ";
    		} else {
    			$stringQuery .= " JOIN cons.typeEtablissement te WITH te = :typeEtab_sent ";
    		}
    	} else if ($typeEtab === RefTypeEtablissement::CODE_URL_2ND_DEGRE) {
    		$stringQuery .= " JOIN cons.typeEtablissement te WITH te.degre = 2 ";
    	} else {
    		if ($perimetre != null && $perimetre->getDegres() != null) {
    			$stringQuery .=" JOIN cons.typeEtablissement te WITH te.degre in (:degres) ";
    		}
    	}
    	
    	$stringQuery .= " GROUP BY cons.idZone";
    	
    	$query = $this->_em->createQuery($stringQuery)->setParameter('campagneId', $campagne->getId());
    	// Correction filtre de recherche des résultats
    	if ($typeEtab instanceof RefTypeEtablissement && RefTypeEtablissement::ID_TYP_2ND_DEGRE != $typeEtab->getId()) {
    		$query->setParameter('typeEtab_sent', $typeEtab);
    	} else {
    		if ($perimetre != null && $perimetre->getDegres() != null) {
    			$query->setParameter('degres', $perimetre->getDegres());
    		}
    	}
    	$result = $query->getResult();
    	
    	$nbEtabTotalArray = array();
    	foreach ($result as $array) {
    		$nbEtabTotalArray [$array ["idZone"]] = $array ["nbEtabTotal"];
    	}
    	$nbEtabTotal = 0;
    	// cas d'un DGESCO
    	if($zone == null) {
			return array_sum($nbEtabTotalArray);    	
    	} else if ($zone != null && $zone instanceof RefAcademie) {
        	if ($zone->getIdZone() != null) { // ajout DSDEN multidepartements
        		if ($zone instanceof RefAcademie) {
        			$fct_getIdsDept = function ($dept) {
        				return $dept->getIdZone();
        			};
        		
        			$zonesDept = array_map($fct_getIdsDept, $zone->getDepartements()->toArray());
        			foreach ($zonesDept as $key=>$dept) {
        				if (array_key_exists($dept, $nbEtabTotalArray)) $nbEtabTotal += $nbEtabTotalArray [$dept];
        			}
        		}
        	} else { // ajout DSDEN multidepartements
        		$depts = explode(',', EpleUtils::getNumerosDepts($zone->getDepartements()));
        		foreach ($depts as $key=>$dept) {
        			if (array_key_exists($dept, $nbEtabTotalArray)) $nbEtabTotal += $nbEtabTotalArray [$dept];
        		}
        	}
            
        } else if ($zone != null && $zone instanceof RefDepartement) {
        	$nbEtabTotal = $nbEtabTotalArray[$zone->getNumero()];
        }
    	return $nbEtabTotal;
    }

    /**
     * @param : $campagne : RefCampagne
     * @param : $zone : RefDepartement, RefAcademie ou TOUTES_ZONES
     * @param : $typeEtab : RefTypeEtablissement ou "tous" ou "2nd"
     * @return des informations nécessaires pour des résultats globaux (= départemental ou académique ou national)
     * "findDatasGlobauxFromEleConsolidationByCampagneZoneTypeEtab" permet de récupérer diverses informations
     * pour $campagne, $zone et $typeEtab données
     */
    public function findDatasGlobauxFromEleConsolidationByCampagneZoneTypeEtab(EleCampagne $campagne, $zone = EpleUtils::TOUTES_ZONES, $typeEtab = "tous", $perimetre = null) {
        return $this->queryFindDatasConsolidationOfResultatsGlobauxByCampagneZoneTypeEtab($campagne, $zone, $typeEtab, $perimetre)->getResult();
    }

    /**
     * @param : $campagne : RefCampagne
     * @param : $zone : RefDepartement, RefAcademie ou TOUTES_ZONES
     * @param : $typeEtab : RefTypeEtablissement ou "tous" ou "2nd"
     * @return nombre d'établissements exprimés sur une zone (= départemental ou académique ou national) par type d'établissements
     * "getNbEtabExprWithTypeEtabFromEleConsolidationByCampagneZoneTypeEtab" permet de récupérer le nombre d'établissements exprimés
     * par type d'établissement pour $campagne, $zone et $typeEtab données
     */
    public function getNbEtabExprWithTypeEtabFromEleConsolidationByCampagneZoneTypeEtab(EleCampagne $campagne, $zone = EpleUtils::TOUTES_ZONES, $typeEtab = "tous", $perimetre = null) {

        $stringQuery = "	SELECT sum(cons.nbEtabExprimes) as nbEtabExpr, te.id as idTypeEtab, te.code as codeTypeEtab, te.libelle as libTypeEtab
						FROM EleConsolidation cons
						JOIN cons.campagne camp WITH camp.id = :campagneId 
						JOIN cons.typeEtablissement te ";

        if ($typeEtab instanceof RefTypeEtablissement) {
        	// Correction filtre de recherche des résultats
        	if(RefTypeEtablissement::ID_TYP_2ND_DEGRE == $typeEtab->getId()) {
        		$stringQuery .=" WITH te.degre = 2 ";
        	} else {
            	$stringQuery .=" WITH te = :typeEtab_sent ";
        	}
        } else if ($typeEtab === RefTypeEtablissement::CODE_URL_2ND_DEGRE) {
            $stringQuery .=" WITH te.degre = 2 ";
        } else {
        	if ($perimetre != null && $perimetre->getDegres() != null) {
        		$stringQuery .=" WITH te.degre in (:degres) ";
        	}
        }		
        
        // On teste d'abord si $zone est bien une academie ou un departement
        if (($zone instanceof RefAcademie) or ( $zone instanceof RefDepartement)) {
        	$stringQuery .=" WHERE cons.idZone = :zoneId ";
        	if ($zone instanceof RefAcademie) {
        		$fct_getIdsDept = function ($dept) {
        			return $dept->getIdZone();
        		};
        	
        		$zonesDept = array_map($fct_getIdsDept, $zone->getDepartements()->toArray());
        		$str_zoneIds = implode(" ', '", $zonesDept);
        	
        		$stringQuery .=" OR cons.idZone in ( '" . $str_zoneIds . "' ) ";
        	}
        }
        
        $stringQuery .= " GROUP BY te.id 
						  ORDER BY te.id ASC, te.libelle ASC ";

        $query = $this->_em->createQuery($stringQuery)
                ->setParameter('campagneId', $campagne->getId());
        if ($typeEtab instanceof RefTypeEtablissement) {
            $query->setParameter('typeEtab_sent', $typeEtab);
        } else {
        	if ($perimetre != null && $perimetre->getDegres() != null) {
        		$query->setParameter('degres', $perimetre->getDegres());
        	}
        }
  		
  		if (($zone instanceof RefAcademie) or ( $zone instanceof RefDepartement)) {
            $query->setParameter('zoneId', $zone->getIdZone());
        }

        return $query->getResult();
    }

    /**
     * @param : $campagne : RefCampagne
     * @param : $zone : circonscription ou TOUTES_ZONES
     * @param : $typeEtab : RefTypeEtablissement ou "tous" ou "2nd"
     * @return nombre d'établissements exprimés sur une zone (= circonscription) par type d'établissements
     * "getNbEtabExprWithTypeEtabFromEleConsolidationByCampagneZoneTypeEtabCirconscription" permet de récupérer le nombre d'établissements exprimés
     * par type d'établissement pour $campagne, $zone et $typeEtab données
     */
    /* public function getNbEtabExprWithTypeEtabFromEleConsolidationByCampagneZoneTypeEtabCirconscription(EleCampagne $campagne, $zone=EpleUtils::TOUTES_ZONES, $user, $typeEtab="tous"){

      //echo "***************** Circonscription ----- ".$zone."<br /> <br />";
      $stringQuery = "SELECT sum(cons.nbEtabExprimes) as nbEtabExpr, te.id as idTypeEtab, te.code as codeTypeEtab, te.libelle as libTypeEtab
      FROM EleConsolidation cons
      JOIN cons.campagne camp WITH camp.id = :campagneId
      JOIN cons.typeEtablissement te";

      if ($typeEtab instanceof RefTypeEtablissement) {
      $stringQuery .=" WITH te = :typeEtab_sent ";
      } else if ($typeEtab === RefTypeEtablissement::CODE_URL_2ND_DEGRE) {
      $stringQuery .=" WITH te.degre = 2 ";
      }

      $stringQuery .=" WHERE cons.idZone = :zoneId ";
      $stringQuery .= " GROUP BY te.id
      ORDER BY te.id ASC, te.libelle ASC ";

      $query = $this->_em->createQuery($stringQuery)
      ->setParameter('campagneId', $campagne->getId());
      if ($typeEtab instanceof RefTypeEtablissement) { $query->setParameter('typeEtab_sent', $typeEtab); }
      $query->setParameter('zoneId',$zone);
      //echo "***************** Circonscription size of----- ".sizeof($query->getResult())."<br /> <br />";
      return $query->getResult();

      } */

    /**
     * Fonction permettant de récuperer l'ensemble des consolidations et des résultats
     * pour une campagne, un type d'établissement et une zone donnée
     * @param $em
     * @param EleCampagne $campagne
     * @param RefTypeEtablissement $typeEtab
     * @param string $zone
     * @return \App\Entity\EleConsolidation
     */
    public function getEleConsolidationGlobale($campagne, $typeEtab = null, $zone = null, $refUser = null) {
        $eleZoneToutTypesEtabs = new EleConsolidation();
        $eleZoneToutTypesEtabs->setCampagne($campagne);

        if (($zone instanceof RefAcademie) or ( $zone instanceof RefDepartement)) {
            $eleZoneToutTypesEtabs->setIdZone($zone->getIdZone());
        }
     

        if ($typeEtab instanceof RefTypeEtablissement) {
            if(RefTypeEtablissement::ID_TYP_2ND_DEGRE == $typeEtab->getId()) {
                $te = new RefTypeEtablissement();
                $te->setDegre(RefTypeEtablissement::SECOND_DEGRE);
                $eleZoneToutTypesEtabs->setTypeEtablissement($te);
            } else {
                $eleZoneToutTypesEtabs->setTypeEtablissement($typeEtab);
            }
        } else {
            $te = new RefTypeEtablissement();
            $te->setHasEclair(true);
            $eleZoneToutTypesEtabs->setTypeEtablissement($te);
        }

        $perimetre = null;
        if($refUser != null) {
            $perimetre = $refUser->getPerimetre();
        }

        $consolidationGlobale = $this->findDatasGlobauxFromEleConsolidationByCampagneZoneTypeEtab($campagne, $zone, $typeEtab, $perimetre);

        if (!empty($consolidationGlobale)) { // Construction d'une participation
            $eleZoneToutTypesEtabs->setNbEtabExprimes($consolidationGlobale[0]['nbEtabExpr']);
            $eleZoneToutTypesEtabs->setNbEtabTotal($consolidationGlobale[0]['nbEtabTotal']);
            
            $participation = new EleParticipation();
            $participation->setNbInscrits($consolidationGlobale[0]['nbIns']);
            $participation->setNbVotants($consolidationGlobale[0]['nbVotants']);
            $participation->setNbNulsBlancs($consolidationGlobale[0]['nbVotants'] - $consolidationGlobale[0]['nbExpr']);
            $participation->setNbSiegesPourvoir($consolidationGlobale[0]['nbSiegPourvoir']);
            $participation->setNbSiegesPourvus($consolidationGlobale[0]['nbSiegPourvus']);
            $eleZoneToutTypesEtabs->setParticipation($participation);
        }

        // Résultats globaux
        $resultatsGlobaux = $this->_em->getRepository(EleResultat::class)
                ->findDatasGlobauxFromEleResultatByCampagneZoneTypeEtab($campagne, $zone, $typeEtab, $perimetre);

        // Evol 015E affichage du nombre de sieges reellement attribue
        // Résultats globaux detaillés
        $resultatsDetailGlobaux = $this->_em->getRepository(EleResultatDetail::class)
                ->findDatasGlobauxFromEleResultatDetailByCampagneZoneTypeEtab($campagne, $zone, $typeEtab, $refUser);

        if (!empty($resultatsGlobaux)) {
            $em = $this->_em;
            $fct_datasToEleResult = function ($datas) use ($em) {
                $r = new EleResultat();
                $r->setOrganisation($em->getRepository(RefOrganisation::class)->find($datas['idOrg']));
                $r->setNbVoix($datas['nbVoix']);
                $r->setNbSieges($datas['nbSieges']);
                $r->setNbSiegesSort($datas['nbSiegesSort']);
                return $r;
            };

            $resultats = array_map($fct_datasToEleResult, $resultatsGlobaux);
            
            // chercher les resultats detaillés de la zone
            if (! empty($resultatsDetailGlobaux)) {
            	$fct_datasToEleResultDetail = function ($datas) use($em)
            	{
            		$rd = new EleResultatDetail();
            		$rd->setOrganisation($em->getRepository(RefOrganisation::class)
            				->find($datas['idOrg']));
            		$rd->setNbVoix($datas['nbVoix']);
            		$rd->setNbSieges($datas['nbSieges']);
            		$rd->setNbSiegesSort($datas['nbSiegesSort']);
            		$rd->setNbCandidats($datas['nbCandidats']);
            		return $rd;
            	};
            	$resultatsDetails = array_map($fct_datasToEleResultDetail, $resultatsDetailGlobaux);
            	// comparer les sieges rellement attribués dans les listes detaillées et le nombre se sieges theorique
            	foreach ($resultats as $resultat) {
            		$nbCandidats = 0;
            		$nbSiegeTh = 0;
            		foreach ($resultatsDetails as $resultDetail) {
            			if ($resultDetail->getOrganisation()->getId() == $resultat->getOrganisation()->getId()) {
            				// le nombre de sieges reelement attribués à la liste
            				$nbCandidats = $nbCandidats + $resultDetail->getNbCandidats();
            				// le nombre de sieges theorique attribués à la liste
            				$nbSiegeTh = $nbSiegeTh + $resultDetail->getNbSieges();
            			}
            		}
            		if ($nbCandidats > 0 && $nbSiegeTh > 0) {
            			// la somme des nombres de siege reelement attribués à la zone de recherche
            			$nbSieges = $resultat->getNbSieges() - $nbSiegeTh + $nbCandidats;
            			$resultat->setNbSieges($nbSieges);
            		}
            		 
            	}
            }
            $eleZoneToutTypesEtabs->setResultats($resultat);
        }

        return $eleZoneToutTypesEtabs;
    }

    public function queryFindDatasConsolidationOfResultatsGlobauxByCampagneNiveauTypeEtab($campagne, $zone, $typeEtab = "tous") {

        $stringQuery = "SELECT sum(cons.nbEtabExprimes) as nbEtabExpr
						FROM EleConsolidation cons
						JOIN cons.campagne camp WITH camp.id = :campagneId ";

        if ($typeEtab instanceof RefTypeEtablissement) {
            // Correction filtre de recherche des résultats
            if(RefTypeEtablissement::ID_TYP_2ND_DEGRE == $typeEtab->getId()) {
                $stringQuery .=" JOIN cons.typeEtablissement te WITH te.degre = 2 ";
            } else {
                $stringQuery .= " JOIN cons.typeEtablissement te WITH te = :typeEtab_sent ";
            }
        }


        $stringQuery .= " WHERE cons.idZone = '" . $zone . "'";

        // recuperer les departements si $zone represente une academie
        $academie = $this->_em->getRepository(RefAcademie::class)->find($zone);
        if ($academie != null && $academie instanceof RefAcademie) {
            $fct_getIdsDept = function ($dept) {
                return $dept->getIdZone();
            };

            $zonesDept = array_map($fct_getIdsDept, $academie->getDepartements()->toArray());
            $str_zoneIds = implode(" ', '", $zonesDept);

            $stringQuery .= " OR cons.idZone in ( '" . $str_zoneIds . "' ) ";
        }


        $query = $this->_em->createQuery($stringQuery)
                ->setParameter('campagneId', $campagne->getId());

        // Correction filtre de recherche des résultats
        if ($typeEtab instanceof RefTypeEtablissement && RefTypeEtablissement::ID_TYP_2ND_DEGRE != $typeEtab->getId()) {
            $query->setParameter('typeEtab_sent', $typeEtab);
        }

        return $query->getOneOrNullResult();
    }
    
    /**
     *
     * @param EleCampagne $campagne
     * @param EleCampagne $campagnePrec
     * @param RefAcademie ou RefDepartement $typeZone
     * @param RefTypeEtablissement $typeEtablissement
     * @return List<Departement ou Academie> consolidées
     *         "findParticipationConsolidationByNiveauCampagne" permet de récuperer la liste des eleEtablissements et des consolidations
     *         pour les campagnes $campagne et $campagnePrec en fonction d'un $niveau et d'un $typeEtablissement donnés
     */
    public function findConsolidationByNiveauCampagne($campagneId, $campagnePrecId, $typeZone, $typeEtablissement = null, $perimetre = null)
    {       
        
//         SELECT ec.id_campagne, rd.libelle, sum(ep.nb_inscrits) as sumInscrits,
//         sum(ep.nb_votants) as sumVotants,
//         sum(ep.nb_votants - ep.nb_nuls_blancs) as sumExprimes,
//         (sum(ep.nb_votants)/sum(ep.nb_inscrits))*100 as p1,
//         sum(ep.nb_sieges_pourvoir) as sumSiegesPourvoir,
//         sum(ep.nb_sieges_pourvus) as sumSiegesPourvus,
//         (sum(ep.nb_sieges_pourvus)/sum(ep.nb_sieges_pourvoir))*100 as p2,
//         sum(ec.nb_etab_exprimes) as sumEtabExprimes
//         FROM ele_participation ep, ele_consolidation ec, ref_departement rd, ref_academie ra
//         WHERE ec.id_participation = ep.id
//         AND ec.id_zone = rd.numero
//         GROUP BY ec.id_campagne, rd.libelle


        $id = 'refDept.numero as id';
        $libelle = 'refDept.libelle';
        $etabTotal = 'eleConso.nbEtabTotal as sumEtabTotal';
        if($typeZone == 'academie'){
            $id = 'refAca.code as id';
            $libelle = 'refAca.libelle';
            $etabTotal = 'sum(DISTINCT eleConso.nbEtabTotal) as sumEtabTotal';
        }
    
        $stringQuery = '
        		SELECT '.$id.', '.$libelle.', eleCamp.id as idCampagne,
        		    sum(elePart.nbInscrits) as sumInscrits, sum(elePart.nbVotants) as sumVotants, sum(elePart.nbVotants - elePart.nbNulsBlancs) as sumExprimes, (sum(elePart.nbVotants)/sum(elePart.nbInscrits))*100 as p1,
        		    sum(elePart.nbSiegesPourvoir) as sumSiegesPourvoir, sum(elePart.nbSiegesPourvus) as sumSiegesPourvus, (sum(elePart.nbSiegesPourvus)/sum(elePart.nbSiegesPourvoir))*100 as p2,
        		    sum(eleConso.nbEtabExprimes) as sumEtabExprimes, '.$etabTotal.'
				FROM EleConsolidation eleConso
        		JOIN EleCampagne eleCamp WITH eleCamp.id = eleConso.campagne
                JOIN EleParticipation elePart WITH elePart.id = eleConso.participation
        		JOIN RefTypeEtablissement refTypeEtab WITH refTypeEtab.id = eleConso.typeEtablissement    
                JOIN RefDepartement refDept WITH eleConso.idZone = refDept.numero
        		JOIN RefAcademie refAca WITH refAca.code = refDept.academie
        		WHERE eleCamp.id IN ( '.$campagneId.', '.$campagnePrecId.')';
        
        // Exclusion systématique des données des établissements EREA-ERPD
        
        if ($typeEtablissement != null) {               	
        	if ($typeEtablissement->getId() == RefTypeEtablissement::ID_TYP_2ND_DEGRE) {
        		$stringQuery .= ' AND refTypeEtab.id in ('.RefTypeEtablissement::ID_TYP_COLLEGE.', '.RefTypeEtablissement::ID_TYP_LYCEE.', '.RefTypeEtablissement::ID_TYP_LYC_PRO.')';
        	} else {
        		$stringQuery .= ' AND refTypeEtab.id = '.$typeEtablissement->getId();
        		//$stringQuery .= 'AND refTypeEtab.id != '.RefTypeEtablissement::ID_TYP_EREA_ERPD; // YME 0145664
        	}
        } else {
        	if ($perimetre != null && $perimetre->getDegres() != null) {
        		$stringDegres = implode("','", $perimetre->getDegres());
        		$stringQuery .= ' AND refTypeEtab.degre in (\'' . $stringDegres . '\')';
        	}
        }        
    
        // restriction par perimetre
        if ($perimetre != null && $perimetre->getAcademies() != null) {
            $stringAcademies = "";
            foreach ($perimetre->getAcademies() as $uneAca) {
                $stringAcademies .= "'" . $uneAca->getCode() . "',";
            }
            $stringAcademies = substr($stringAcademies, 0,  strlen($stringAcademies) - 1);
            $stringQuery .= ' AND refAca.code in (' . $stringAcademies . ')';
        }
    
        if ($perimetre != null && $perimetre->getDepartements() != null) {
            $stringDepartements = "";
            foreach ($perimetre->getDepartements() as $unDept) {
                $stringDepartements .= "'" . $unDept->getNumero() . "',";
            }
            $stringDepartements = substr($stringDepartements, 0,  strlen($stringDepartements) - 1);
            $stringQuery .= ' AND refDept.numero in (' . $stringDepartements . ')';
        }
    
        $stringQuery .= '
				GROUP BY '.$libelle.', eleCamp.id
        		ORDER BY '.$libelle.', eleCamp.id desc';
    
        $query = $this->_em->createQuery($stringQuery);

        return $query->getResult();
    
    }


    public function findListEtabTotalByNiveauCampagne($campagneId, $typeZone, $typeEtablissement = null, $perimetre = null)
    {

        $libelle = 'refDept.libelle';
        $etabTotal = 'eleConso.nbEtabTotal as sumEtabTotal';
        if($typeZone == 'academie'){
            $libelle = 'refAca.libelle';
            $etabTotal = 'sum(DISTINCT eleConso.nbEtabTotal) as sumEtabTotal';
        }

        $stringQuery = '
        		SELECT '.$etabTotal.'
				FROM EleConsolidation eleConso
        		JOIN EleCampagne eleCamp WITH eleCamp.id = eleConso.campagne
                JOIN EleParticipation elePart WITH elePart.id = eleConso.participation
        		JOIN RefTypeEtablissement refTypeEtab WITH refTypeEtab.id = eleConso.typeEtablissement    
                JOIN RefDepartement refDept WITH eleConso.idZone = refDept.numero
        		JOIN RefAcademie refAca WITH refAca.code = refDept.academie
        		WHERE eleCamp.id = '.$campagneId;

        // Exclusion systématique des données des établissements EREA-ERPD

        if ($typeEtablissement != null) {
            if ($typeEtablissement->getId() == RefTypeEtablissement::ID_TYP_2ND_DEGRE) {
                $stringQuery .= ' AND refTypeEtab.id in ('.RefTypeEtablissement::ID_TYP_COLLEGE.', '.RefTypeEtablissement::ID_TYP_LYCEE.', '.RefTypeEtablissement::ID_TYP_LYC_PRO.')';
            } else {
                $stringQuery .= ' AND refTypeEtab.id = '.$typeEtablissement->getId();
                //$stringQuery .= 'AND refTypeEtab.id != '.RefTypeEtablissement::ID_TYP_EREA_ERPD; // YME 0145664
            }
        } else {
            if ($perimetre != null && $perimetre->getDegres() != null) {
                $stringDegres = implode("','", $perimetre->getDegres());
                $stringQuery .= ' AND refTypeEtab.degre in (\'' . $stringDegres . '\')';
            }
        }

        // restriction par perimetre
        if ($perimetre != null && $perimetre->getAcademies() != null) {
            $stringAcademies = "";
            foreach ($perimetre->getAcademies() as $uneAca) {
                $stringAcademies .= "'" . $uneAca->getCode() . "',";
            }
            $stringAcademies = substr($stringAcademies, 0,  strlen($stringAcademies) - 1);
            $stringQuery .= ' AND refAca.code in (' . $stringAcademies . ')';
        }

        if ($perimetre != null && $perimetre->getDepartements() != null) {
            $stringDepartements = "";
            foreach ($perimetre->getDepartements() as $unDept) {
                $stringDepartements .= "'" . $unDept->getNumero() . "',";
            }
            $stringDepartements = substr($stringDepartements, 0,  strlen($stringDepartements) - 1);
            $stringQuery .= ' AND refDept.numero in (' . $stringDepartements . ')';
        }

        $stringQuery .= '
				GROUP BY '.$libelle.', eleCamp.id
        		ORDER BY '.$libelle.', eleCamp.id desc';

        $query = $this->_em->createQuery($stringQuery);
        // echo $query->getSQL();die();
        return $query->getResult();

    }
}
