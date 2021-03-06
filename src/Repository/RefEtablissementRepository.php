<?php

namespace App\Repository;

use App\Entity\EleCampagne;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use App\Entity\RefAcademie;
use App\Entity\RefDepartement;
use App\Entity\RefCommune;
use App\Entity\RefTypeElection;
use App\Entity\RefTypeEtablissement;
use App\Entity\EleEtablissement;
use App\Entity\RefProfil;
use App\Controller\StatistiqueController;
use App\Utils\EpleUtils;
use Doctrine\ORM\Query\ResultSetMapping;
use App\Entity\RefSousTypeElection;

class RefEtablissementRepository extends EntityRepository {

    public function queryBuilderEtablissementPaginationParTypeEtablissementZoneCommune($typeEtablissement, $zone = null, $profil = null, $commune = null, $etabSansCom = false, $page = null, $etablissement_per_page = null) {
        $query = $this->createQueryBuilder('e')->leftjoin(' e.commune', 'c')->addSelect('c');
        if ($zone != null) {
            $query->leftJoin('c.departement', 'd');
            $query->addSelect('d');
        }

        $query->where('e.typeEtablissement = :type');

        if ($etabSansCom) {
            $query->andWhere('e.commune is null');
        } else {
            if (!empty($commune)) {
                $query->andWhere('c.id = :com');
                $query->setParameter('com', $commune);
            } else {
                if ($zone instanceof RefDepartement) {
                    $query->andWhere('d.numero = :dep');
                    $query->setParameter('dep', $zone);
                }
                if ($zone instanceof RefAcademie) {
                    $query->andWhere('d.academie = :aca');
                    $query->setParameter('aca', $zone);
                }
            }
        }

        $query->setParameter('type', $typeEtablissement);
        if ($page != null && $etablissement_per_page != null)
            $query->setFirstResult(($page * $etablissement_per_page) - $etablissement_per_page)->setMaxResults($etablissement_per_page);

        $query->orderBy('e.actif', 'DESC');
        $query->addOrderBy('c.codePostal', 'ASC');

        return $query;
    }

    /**
     * @param integer $typeEtablissement : entity type d'??tablissement
     * @param integer $zone : facultatif : entity d??partement ou academie
     * @param integer $commune : facultatif : entity commune
     * @param integer $etabSansCom : facultatif : boolean ?? true si on cherche des ??tablissements sans commune
     * "getNbEtabParTypeEtablissementZoneCommune" Compte le nombre d'??tablissement en fonction du typeEtablissementId obligatoire
     * 	et de zone (academie ou departement), commune, etabSansCom si donn??
     *  @return int nbEtablissement
     *
     */
    function getNbEtabParTypeEtablissementZoneCommune($typeEtablissement = null, $zone = null, $commune = null, $etabSansCom = false, $actif = false, $user = null, $campagne = null, $isEreaErpdExclus = false) {
        $query = $this->createQueryBuilder("e")->select("count(e.uai)");
        $query->leftjoin("e.commune", "c");

        if ($zone != null) {
            $query = $query->leftJoin("c.departement", "d");
        }

        if ($typeEtablissement != null) {
            $query = $query->leftJoin("e.typeEtablissement", "te");
            $query->where("te.id = :type")->setParameter("type", $typeEtablissement);
        } else {
            if ($user != null && $user->getPerimetre() != null && $user->getPerimetre()->getDegres() != null) {
                $query = $query->leftJoin("e.typeEtablissement", "te");
                $query->where("te.degre in (:degres)")->setParameter("degres", $user->getPerimetre()->getDegres());
            }
        }

        // Evol 013E RG_STATG_15
        if ($isEreaErpdExclus) {
            $query->andWhere("te.code NOT LIKE '".RefTypeEtablissement::CODE_EREA_ERPD."'");
        }

        if ($etabSansCom) {
            $query->andWhere("e.commune is null");
        } else {
            if (!empty($commune)) {
                $query->andWhere("c.id = :com");
                $query->setParameter("com", $commune);
            } else {
                if ($zone instanceof RefDepartement) {
                    $query->andWhere("d.numero = :dep");
                    $query->setParameter("dep", $zone);
                }
                if ($zone instanceof RefAcademie) {
                    // DSDEN multidepartements Resultat : [ECT] fonctionnellement, tous les departements appartiennent a la meme academie
                    if (null != $user && $user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DSDEN) {
                        $query->andWhere('d.numero in ('.EpleUtils::getNumerosDepts($user->getPerimetre()->getDepartements()).')');
                    } else {
                        $query->andWhere("d.academie = :aca");
                        $query->setParameter("aca", $zone);
                    }
                }
            }
        }

        // Stats generales : CE/DE/IEN limiter aux etabs du perimetre
        if (null != $user
            && ($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_IEN
                || $user->getProfil()->getCode() == RefProfil::CODE_PROFIL_CE
                || $user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DE)) {
            $query->andWhere('e.uai in ('.EpleUtils::getUais($user->getPerimetre()->getEtablissements()).')');
        }

        if ($actif == true) {
            $query->andWhere("e.actif = true");
        }

        return $query->getQuery()->getSingleScalarResult();
    }

    /**
     * @param integer $typeEtablissement : entity type d'??tablissement
     * @param integer $zone : facultatif : entity d??partement ou academie ou commune
     * @param integer $typeElection : type d'??lection
     * "getNbEtabParTypeEtablissementZoneTypeElection" Compte le nombre d'??tablissement en fonction du typeEtablissementId obligatoire
     * 	et de zone (academie ou departement ou commune)
     * 	pour un type d'??lection donn?? (pas d'??coles pour PE)
     *  @return int nbEtablissement
     *
     */
    public function getNbEtabParTypeEtablissementZoneTypeElection($typeEtablissement = null, $zone = null, $typeElection = null, $user = null, $isEreaErpdExclus = false , $codeNatEtab = null, $actif = false) {

        $query = $this->createQueryBuilder('e')->select('count(e.uai)');
        $query->leftjoin('e.commune', 'c')->where('e.actif = true');
        $query->leftJoin('e.typeEtablissement', 'te');

        if ($zone != null) {
            if ($zone instanceof RefCommune) {
                $query->andWhere('e.commune = :comm');
                $query->setParameter('comm', $zone);
            }
            if ($zone instanceof RefDepartement) {
                $query->leftJoin('c.departement', 'd');
                $query->andWhere('d.numero = :dep');
                $query->setParameter('dep', $zone);
            }
            if ($zone instanceof RefAcademie) {
                $query->leftJoin('c.departement', 'd');
                if (null != $zone->getCode()) { // ajout DSDEN multidepartements

                    // DSDEN multidepartements Resultat : [ECT] fonctionnellement, tous les departements appartiennent a la meme academie
                    if (null != $user && $user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DSDEN) {
                        $query->andWhere('d.numero in (' . EpleUtils::getNumerosDepts($user->getPerimetre()->getDepartements()) . ')');
                    } else {
                        //Evol 018E : Reforme territoriale
                        $children = $this->getEntityManager()->getRepository(RefAcademie::class)->getchildnewAcademies($zone->getCode());
                        if(!empty($children)) {
                            $values  = array();
                            $values[':acad' . $zone->getCode()]  = $zone->getCode();
                            foreach ($children as $child) {
                                $values[':acad' . $child->getCode()]  = $child->getCode();
                            }
                            $query->andWhere('d.academie IN ('. implode(", ",array_keys($values)) . ')');
                            foreach ($values as $key => $value) {
                                $query->setParameter($key, $value);
                            }
                        } else {
                            $query->andWhere('d.academie = :aca');
                            $query->setParameter('aca', $zone);
                        }
                    }

                } else {
                    // DSDEN multidepartements TableauDeBord
                    $query->andWhere('d.numero in ('.EpleUtils::getNumerosDepts($zone->getDepartements()).')');
                }
            }
        }
        // Ect && !$isEreaErpdExclus
        if ($typeEtablissement instanceof RefTypeEtablissement) {
            //Si 2nd degr??
            if($typeEtablissement->getId() == RefTypeEtablissement::ID_TYP_2ND_DEGRE){
                $query->andWhere('te.degre = 2');
            } else {
                $query->andWhere('e.typeEtablissement = :typeEtablissement');
                $query->setParameter('typeEtablissement', $typeEtablissement);
            }
        }

        if (null != $typeElection && $typeElection->getId() != RefTypeElection::ID_TYP_ELECT_PARENT) {
            // Pas de premier degr?? pour les ??lections de repr??sentants
            $query->andWhere('te.degre = 2');
        }

        // EVOL 013E RG_CONSULT_7_2
        if($isEreaErpdExclus){
            $query->andWhere('te.code NOT LIKE :typeEtab');
            $query->setParameter('typeEtab', RefTypeEtablissement::CODE_EREA_ERPD);
        }

        // Evol 013E Filtre nature etab
        if(null != $codeNatEtab){
            $query->leftjoin('e.uai_nature', 'nature');
            $query->andWhere('nature.type_nature = :codeNatEtab');
            $query->setParameter('codeNatEtab', $codeNatEtab);
        }

        // BBL Si actif est positionn?? on prend que les etabs actifs sinon on prend tous
        if($actif == true){
            $query->andWhere('e.actif = true');
        }
        //   echo $query->getQuery()->getSQL();die();
        return $query->getQuery()->getSingleScalarResult();
    }

    /**
     * @param integer $zone : obligatoire : entity d??partement, academie ou commune
     * @param integer $typeEtablissement : facultatif : entity type d'??tablissement
     * @param integer $page : identifiant de la page ?? afficher
     * @param integer $nbEtabParPage : nombre de r??sultat ?? rechercher pour l'affichage
     * "findEtablissementParZone" r??cup??re la liste d'??tablissement
     *  filtr?? par $zone (academie, d??partement ou commune) et $typeEtablissement si donn??
     *  @return array RefEtablissement
     *
     */
    function findEtablissementParZone($zone, $degre= null, $typeEtablissement = null, $page = null, $nbEtabParPage = null, $actif = false, $uai = '', $profil = null, $natEtab = null, $isEreaErpdExclus = false) {

        $query = $this->createQueryBuilder('e');
        $query->leftjoin('e.commune', 'c')->addSelect('c')->leftjoin('c.departement', 'd')->addSelect('d');
        if ($zone instanceof RefDepartement) {
            $query->where('d.numero = :zone');
        }
        if ($zone instanceof RefAcademie) {
            $query->where('d.academie = :zone');
        }
        if ($zone instanceof RefCommune) {
            $query->where('e.commune = :zone');
        }
        if (!empty($typeEtablissement)) {
            if($typeEtablissement->getId() == RefTypeEtablissement::ID_TYP_2ND_DEGRE){
                $query->leftjoin('e.typeEtablissement', 'typeEtab');
                $query->Andwhere('typeEtab.degre = 2');
            } else {
                $query->Andwhere('e.typeEtablissement = :type');
                $query->setParameter('type', $typeEtablissement);
            }
        }

        if (!empty($natEtab)) {
            $query->leftJoin('e.uai_nature', 'nature');
            $query->Andwhere('nature.type_nature = :natEtab');
            $query->setParameter('natEtab', $natEtab);
        }

        //les ??lections RP ne sont pas concern??es par les ??tabs 1er degr??
        if (!empty($degre)) {
            $query->leftJoin('e.typeEtablissement', 'te');
            $query->andWhere('te.degre = :degre');
            $query->setParameter('degre', $degre);
        }

        if ($isEreaErpdExclus) {
            $query->leftJoin('e.typeEtablissement', 'tEtab');
            $query->andWhere("tEtab.code NOT LIKE '".RefTypeEtablissement::CODE_EREA_ERPD."'");
        }

        if ($actif == true)
            $query->andWhere('e.actif = true');

        if ($uai != '') {
            $query->Andwhere('e.uai = :uai');
            $query->setParameter('uai', $uai);
        }

        if ($zone instanceof RefDepartement or $zone instanceof RefAcademie or $zone instanceof RefCommune) {
            $query->setParameter('zone', $zone);
        }

        if ($page != null && $nbEtabParPage != null) {
            $query->setFirstResult(($page * $nbEtabParPage) - $nbEtabParPage)->setMaxResults($nbEtabParPage);
        }

        $query->orderBy('c.libelle', 'ASC');
        $query->addOrderBy('e.uai', 'ASC');

        return $query->getQuery()->getResult();
    }

    function findListEtablissementForExport($lstElectEtab) {
        $qb = $this->createQueryBuilder('refEtab');
        $qb->select('refEtab');
        $qb->join('refEtab.commune', 'commune');
        $qb->addSelect('commune');
        $uais = array();
        foreach ($lstElectEtab as $electEtab) {
            if (!empty($electEtab)) {
                array_push($uais, $electEtab->getEtablissement()->getUai());
            }
        }
        $qb->where('refEtab.uai IN (:uais)');
        $qb->setParameter('uais',$uais);

        return $qb->getQuery()->getResult();
    }

    function findListEtablissementsByUais($uais) {
        $qb = $this->createQueryBuilder('refEtab');
        $qb->select('refEtab');
        $qb->join('refEtab.commune', 'commune');
        $qb->addSelect('commune');
        if ($uais != null && sizeof($uais) > 0) {
            $qb->where('refEtab.uai IN (:uais)');
            $qb->setParameter('uais',$uais);
        }
        return $qb->getQuery()->getResult();
    }



    /**
     * @param EleCampagne $campagne : obligatoire : campagne en cours de saisie / validation
     * @param string $zone : obligatoire : entit?? d??partement, acad??mie ou national
     *
     * "findEtablissementsARelancer" r??cup??re la liste des ??tablissements qui n'ont pas encore saisi leurs r??sultats
     *  filtr?? par $campagne, $zone (academie, d??partement)
     *
     *  @return array RefEtablissement
     */
    public function findEtablissementsARelancer($campagne, $zone, $typeEtab = null, $natureEtab = null, $isEreaErpdExclus = false, $offset) {
        $query_etab = $this->_em->createQueryBuilder()
            ->add('select', 'e')
            ->add('from', 'App\Entity\RefEtablissement e')
            ->leftJoin('e.typeEtablissement', 'te');

        $query_etab = $query_etab->leftjoin('e.commune', 'c')
            ->leftjoin('c.departement', 'd')
            ->where('e.actif = true');


        if ($zone instanceof RefDepartement) {
            $query_etab->andWhere('d.numero = :zone')->setParameter('zone', $zone);
        }
        if ($zone instanceof RefAcademie) {
            $codesAca[] = $zone->getCode();
            $childenAcad = $this->_em->getRepository(RefAcademie::class)->getchildnewAcademies($zone->getCode());
            if ($childenAcad != null && !empty($childenAcad)) {
                foreach ($childenAcad as $aca) {
                    array_push($codesAca, $aca->getCode());
                }
            }
            $query_etab->andWhere('d.academie in ( :zone )')->setParameter('zone', $codesAca);
        }

        // Type ??tablissement (pas de premier degr?? pour ??lections PEE / ASS)
        if ($campagne->getTypeElection()->getId() != RefTypeElection::ID_TYP_ELECT_PARENT) {
            $query_etab->andWhere('te.degre = 2');
        }

        // le type d'??tablissement
        if ($typeEtab != null) {
            if ($typeEtab->getId() == RefTypeEtablissement::ID_TYP_2ND_DEGRE) {
                $query_etab->andWhere('te.degre = 2');
            } else {
                $query_etab->andWhere('te.id = :idTypeEtab');
                $query_etab->setParameter('idTypeEtab', $typeEtab->getId());
            }
        }

        //  la nature d'??tablissement
        if ($natureEtab != null) {
            $query_etab->leftJoin('e.uai_nature', 'nature');
            $query_etab->Andwhere('nature.type_nature = :natureEtab');
            $query_etab->setParameter('natureEtab', $natureEtab);
        }

        // exclus les etablissements EREA/ERPD
        if ($isEreaErpdExclus) {
            $query_etab->andWhere("te.code NOT LIKE '".RefTypeEtablissement::CODE_EREA_ERPD."'");
        }

        // on ne r??cup??re pas ceux dont la saisie est faite et transmise
        $query_ele_etab = $this->_em->createQueryBuilder()
            ->add('select', 'etab.uai')
            ->add('from', 'App\Entity\EleEtablissement eleEtab')
            ->join('eleEtab.etablissement', 'etab')
            ->where('eleEtab.campagne = :campagne')
            ->andWhere('eleEtab.validation != :saisie');

        $query_etab->andwhere($query_etab->expr()->notIn('e.uai', $query_ele_etab->getDQL()));
        $query_etab->setParameter('campagne', $campagne);
        $query_etab->setParameter('saisie', EleEtablissement::ETAT_SAISIE);

        // envoi de mail par paquet pour eviter la saturation de la memoire serveur par 1000 mails
        return $query_etab->getQuery()->setMaxResults(1000)->setFirstResult($offset)->getResult();
    }

    /**
     * @param EleCampagne $campagne : obligatoire : campagne en cours de saisie / validation
     * @param string $zone : obligatoire : entit?? d??partement, acad??mie ou national
     *
     * "findEtablissementsWithoutMail" r??cup??re la liste des ??tablissements qui n'ont pas encore saisi leurs r??sultats et n'ayant pas des adresses mails
     *  filtr?? par $campagne, $zone (academie, d??partement)
     *
     *  @return array RefEtablissement
     */
    public function findEtablissementsWithoutMail($campagne, $zone) {
        $query_etab = $this->_em->createQueryBuilder()
            ->add('select', 'e')
            ->add('from', 'App\Entity\RefEtablissement e');

        $query_etab = $query_etab->leftjoin('e.commune', 'c')
            ->leftjoin('c.departement', 'd')
            ->where('e.actif = true')
            ->andWhere("e.contact IS NULL OR e.contact = '' ");


        if ($zone instanceof RefDepartement) {
            $query_etab->andWhere('d.numero = :zone')->setParameter('zone', $zone);
        }
        if ($zone instanceof RefAcademie) {
            $codesAca[] = $zone->getCode();
            $childenAcad = $this->_em->getRepository(RefAcademie::class)->getchildnewAcademies($zone->getCode());
            if ($childenAcad != null && !empty($childenAcad)) {
                foreach ($childenAcad as $aca) {
                    array_push($codesAca, $aca->getCode());
                }
            }
            $query_etab->andWhere('d.academie in ( :zone )')->setParameter('zone', $codesAca);
        }

        // Type ??tablissement (pas de premier degr?? pour ??lections PEE / ASS)
        if ($campagne->getTypeElection()->getId() != RefTypeElection::ID_TYP_ELECT_PARENT) {
            $query_etab->leftJoin('e.typeEtablissement', 'te');
            $query_etab->andWhere('te.degre = 2');
        }

        // on ne r??cup??re pas ceux dont la saisie est faite et transmise
        $query_ele_etab = $this->_em->createQueryBuilder()
            ->add('select', 'etab.uai')
            ->add('from', 'App\Entity\EleEtablissement eleEtab')
            ->join('eleEtab.etablissement', 'etab')
            ->where('eleEtab.campagne = :campagne')
            ->andWhere('eleEtab.validation != :saisie');

        $query_etab->andwhere($query_etab->expr()->notIn('e.uai', $query_ele_etab->getDQL()));
        $query_etab->setParameter('campagne', $campagne);
        $query_etab->setParameter('saisie', EleEtablissement::ETAT_SAISIE);

        $query_etab->orderBy('d.numero', 'ASC');
        $query_etab->addOrderBy('e.uai', 'ASC');
        return $query_etab->getQuery()->getResult();
    }

    /**
     * suppression des refEtablissement d??sactiv??s et plus rattach??s ?? aucune campagne
     */
    public function purgeEtablissements() {
        $rsm = new ResultSetMapping();
        $rsm->addEntityResult('App\Entity\RefEtablissement', 'refEtab');
        $rsm->addFieldResult('refEtab', 'uai', 'uai');
        $query = $this->_em->createNativeQuery('SELECT ref_etablissement.uai
												FROM ref_etablissement
												LEFT JOIN ele_etablissement ON ref_etablissement.uai = ele_etablissement.uai
												WHERE ele_etablissement.uai IS NULL
												AND ref_etablissement.actif = 0', $rsm);

        $listeRefEtablissementUaiASupprimer = $query->getResult();

        $array = array();
        foreach ($listeRefEtablissementUaiASupprimer as $key=>$refEtab) {
            $array[$key] = $refEtab->getUai();
        }

        if (sizeof($array) > 0) {
            $str_uais = implode(" ', '", $array);

            $stringQuery ="	DELETE FROM App\Entity\RefEtablissement e WHERE (e.uai in ( '".$str_uais."' ))";
            $query = $this->_em->createQuery($stringQuery);

            return $query->getResult();

        }
        return 0;
    }

    /**
     * Insertion / Mise ?? jour des ??tablissements via Ramsese avec le fichier UAIS
     * pas de mise ?? jour de la commune : traitement avec le fichier UAIRATT
     */
    public function insertListeRefEtablissementByRamsese($listeRefEtablissementRamsese) {
        $db = $this->_em->getConnection();
        $j = 0;

        foreach ($listeRefEtablissementRamsese as $refEtablissementRamsese) {
            if ($j == 0) {
                $query = "INSERT INTO ref_etablissement (	uai,
																libelle,
																contact,
																actif,
																id_type_prioritaire,
	    														id_type_etablissement,
	    														date_fermeture,
    															uai_nature)
																VALUES ";
            }

            $dateFermeture = "";

            if(null != $refEtablissementRamsese->getDateFermeture()){
                $dateFermeture = $refEtablissementRamsese->getDateFermeture()->format('Y-m-d');
            }

            $query .= "("	. $db->quote($refEtablissementRamsese->getUai()) .","
                . $db->quote($refEtablissementRamsese->getLibelle()) .","
                . $db->quote($refEtablissementRamsese->getContact()) .","
                . $db->quote($refEtablissementRamsese->getActif()) .","
                . $db->quote($refEtablissementRamsese->getTypePrioritaire()->getId()) .","
                . $db->quote($refEtablissementRamsese->getTypeEtablissement()->getId()).","
                . $db->quote($dateFermeture).","
                . $db->quote($refEtablissementRamsese->getUaiNature()->getUaiNature())
                ."),";

            $j++;
            if ($j == 1000) {
                $query = substr($query, 0,  strlen($query) - 1);
                $query .= " ON DUPLICATE KEY UPDATE libelle = VALUES(libelle),
	    												contact = VALUES(contact),
	    												actif = VALUES(actif),
	    												id_type_prioritaire = VALUES(id_type_prioritaire),
	    												id_type_etablissement = VALUES(id_type_etablissement),
	    												date_fermeture = VALUES(date_fermeture),
	    					   							uai_nature = VALUES(uai_nature)";

                $stmt = $db->prepare($query);
                $params = array();
                $stmt->executeQuery($params);

                $j = 0;
            }
        }

        if ($j > 0) {
            $query = substr($query, 0,  strlen($query) - 1);
            $query .= " ON DUPLICATE KEY UPDATE libelle = VALUES(libelle),
	    											contact = VALUES(contact),
	    											actif = VALUES(actif),
	    											id_type_prioritaire = VALUES(id_type_prioritaire),
	    											id_type_etablissement = VALUES(id_type_etablissement),
	    											date_fermeture = VALUES(date_fermeture),
	    				   							uai_nature = VALUES(uai_nature)";

            $stmt = $db->prepare($query);
            $params = array();
            $stmt->executeQuery($params);
            $j = 0;
        }
    }


    /**
     * Mise ?? jour de la commune des ??tablissements via Ramsese avec le fichier UAIRATT
     */
    public function updateListeRefEtablissementCommuneByRamsese($listeDonneesAMettreAJour){

        $db = $this->_em->getConnection();

        foreach ($listeDonneesAMettreAJour as $key => $array) {
            $id_commune = 'NULL';
            if(null != $array['id_commune']){
                $id_commune = $array['id_commune'];
            }
            $query = "UPDATE ref_etablissement SET id_commune = ".$id_commune." WHERE uai = ".$db->quote($array['uai']);
            $stmt = $db->prepare($query);
            $params = array();
            $stmt->executeQuery($params);
        }
    }


    /**
     * Mise ?? jour du type prioritaire des ??tablissements via Ramsese avec le fichier UAIRATT
     */
    public function updateListeRefEtablissementTypePrioritaireByRamsese($listeDonneesAMettreAJour){

        $db = $this->_em->getConnection();

        foreach ($listeDonneesAMettreAJour as $key => $array) {
            $query = "UPDATE ref_etablissement SET id_type_prioritaire  = ".$array['typePrioritaire']." WHERE uai = ".$db->quote($array['uai']);
            $stmt = $db->prepare($query);
            $params = array();
            $stmt->executeQuery($params);
        }
    }

    /**
     * retourne un tableau des uai de la table ref_etablissement utilis??s dans l'import ramsese
     *
     */
    public function getArrayRefEtablissementUai() {
        $sql = "SELECT ref_etablissement.uai FROM ref_etablissement";
        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->executeQuery();
        $array = array();
        while ($row = $stmt->fetchAllAssociative()) {
            $array[$row['uai']] = $row['uai'];
        }

        return $array;
    }



    /**
     * Retourne un tableau associatif uai <-> id_commune
     * @return array
     */
    public function getArrayRefEtablissementUaiIdCommune() {
        $sql = "SELECT ref_etablissement.uai, ref_etablissement.id_commune FROM ref_etablissement";
        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->executeQuery();

        $array = array();
        while ($row = $stmt->fetchAssociative()) {
            $array[$row['uai']] = $row['id_commune'];
        }

        return $array;
    }


    /**
     * Retourne un tableau associatif uai <-> id_type_prioritaire
     * @return array
     */
    public function getArrayRefEtablissementUaiIdTypePrioritaire() {
        $sql = "SELECT ref_etablissement.uai, ref_etablissement.id_type_prioritaire FROM ref_etablissement";
        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->executeQuery();

        $array = array();
        while ($row = $stmt->fetchAssociative()) {
            $array[$row['uai']] = $row['id_type_prioritaire'];
        }
        return $array;
    }

    public function findEtablissementsByUaiOrLibelle($uaiOrLibelle) {
        $query_etab = $this->_em->createQueryBuilder()
            ->add('select', 'e')
            ->add('from', 'App\Entity\RefEtablissement e');
        if (!empty($uaiOrLibelle)) {
            $query_etab->where('e.uai like :uaiOrLibelle');
            $query_etab->orWhere('e.libelle like :uaiOrLibelle');

            $query_etab->setParameter('uaiOrLibelle', '%' . $uaiOrLibelle . '%');
        }

        return $query_etab->getQuery()->getResult();
    }

    public function findEtablissementByZoneAndUaiOrLibelle($zone, $uaiOrLibelle) {
        $query = $this->createQueryBuilder('e');

        $query->leftjoin('e.commune', 'c')->addSelect('c')->leftjoin('c.departement', 'd')->addSelect('d');
        if ($zone instanceof RefDepartement) {
            $query->where('d.numero = :zone');
        }
        if ($zone instanceof RefAcademie) {
            $query->where('d.academie = :zone');
        }
        if ($zone instanceof RefCommune) {
            $query->where('e.commune = :zone');
        }

        if (!empty($uaiOrLibelle)) {
            //cr??ation de l'expression OR
            $orUaiLibelle = $query->expr()->orx();
            $orUaiLibelle->add($query->expr()->like('e.uai', ':uaiOrLibelle'));
            $orUaiLibelle->add($query->expr()->like('e.libelle', ':uaiOrLibelle'));

            //Ajout de l'expression ?? la requ??te
            $query->andWhere($orUaiLibelle)->setParameter('uaiOrLibelle', '%' . $uaiOrLibelle . '%');

        }

        if ($zone instanceof RefDepartement or $zone instanceof RefAcademie or $zone instanceof RefCommune) {
            $query->setParameter('zone', $zone);
        }

        return $query->getQuery()->getResult();
    }

    public function findEtablissementByZoneUser($zone, $user) {
        $query = $this->createQueryBuilder('e');

        $query->leftjoin('e.commune', 'c')->addSelect('c')->leftjoin('c.departement', 'd')->addSelect('d');
        if ($zone instanceof RefDepartement) {
            $query->where('d.numero = :zone');
        }
        if ($zone instanceof RefAcademie) {
            $query->where('d.academie = :zone');
        }
        if ($zone instanceof RefCommune) {
            $query->where('e.commune = :zone');
        }

        if ($zone instanceof RefDepartement or $zone instanceof RefAcademie or $zone instanceof RefCommune) {
            $query->setParameter('zone', $zone);
        }
        //Ajout de l'expression ?? la requ??te
        $etabs = $user->getPerimetre()->getEtablissements();
        if(!empty ($etabs)){
            $query->andWhere('e.uai in ('.EpleUtils::getUais($user->getPerimetre()->getEtablissements()).')');
        }
        $query->andWhere('e.actif = 1');
        return $query->getQuery()->getResult();
    }
}