<?php

namespace App\Controller;

use DateTime;
use App\Entity\RefTypeElection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Httpfoundation\Response;

use App\Entity\RefProfil;

use App\Utils\EpleUtils;
use App\Entity\RefTypeEtablissement;

class AjaxController extends AbstractController {

    /**
     * "findAcademieDepartementCommuneByZoneAction" Recherche la liste des départements et la liste des communes en fonction de l'académie associé
     *  si le departement est renseigné on recupere l'academie et les communes associés
     *  sinon si l'academie est renseigné alors on recupere les departements et les communes associés
     *  sinon on recupere l'ensemble des departements mais aucune communes car trop nombreuse
     *  on renvoie l'identifiant de l'academie, une liste de département
     */

    public function findAcademieDepartementCommuneByZoneAction() {


        //$user = $this->get('security.context')->getToken()->getUser();
        // Récupération des départements du périmètre de l'utilisateur
        //'liste_academie' => $this->getAcademies(),
        $retour = array('responseCode' => 200, 'liste_academies' => $this->getAcademies(), 'liste_departement' => $this->getDepartements(), 'liste_commune' => $this->getCommunes(), 'liste_etablissement' => $this->getEtablissements());
        $return = json_encode($retour); // json encode the array
        return new Response($return, 200, array('Content-Type' => 'application/json'));

        /*
		$user = $this->get('security.context')->getToken()->getUser();

		// TODO pour tests
		$info = '';

		$request = $this->get('request');
		$academie_id = $request->request->get('formAcademie');
		$departement_id = $request->request->get('formDepartement');
		$commune_id = $request->request->get('formCommune');
		$typeEtab_id = $request->request->get('formTypeEtab');
		$zoneUser = EpleUtils::getZone($em, $request->request->get('idZoneUser'));

		$info =  $request->request->get('idZoneUser');

		$academie = $em->getRepository('EPLEElectionBundle:RefAcademie')->find($academie_id);
		$departement = $em->getRepository('EPLEElectionBundle:RefDepartement')->find($departement_id);
		$commune = $em->getRepository('EPLEElectionBundle:RefCommune')->find($commune_id);
		$typeEtab = $em->getRepository('EPLEElectionBundle:RefTypeEtablissement')->find($typeEtab_id);

		$departements = array();
		$communes = array();
		$liste_etablissement = array();

		$isChoixEtabLocked = false;

		$profilsLimitEtab = array(RefProfil::CODE_PROFIL_IEN, RefProfil::CODE_PROFIL_CE, RefProfil::CODE_PROFIL_DE);

		if(in_array($user->getProfil()->getCode(), $profilsLimitEtab)){

		    //Pour certains profils on doit bloquer la case à cocher 'choix_etab'
		    $isChoixEtabLocked = true;

		    // Retourner sa liste d'établissements par écrasement
		    $etablissements = array();
		    $indice = 0;
		    foreach ($user->getPerimetre()->getEtablissements() as $etablissement){
		        $etablissements[$indice]['uai'] = $etablissement->getUai();
		        $etablissements[$indice]['libelle'] = $etablissement->getLibelle();
		        $etablissements[$indice]['commune'] =  $etablissement->getCommune() ? $etablissement->getCommune()->getLibelle() : 'N/A';
		        $indice++;
		    }
		    $retour = array('responseCode' => 200, 'academie_id' => '', 'liste_departement' => $departements, 'liste_commune' => $communes, 'liste_etablissement' => $etablissements, 'isChoixEtabLocked' => $isChoixEtabLocked);
		}else{
		if (!empty($departement)) {

		    // Le département est renseigné
		    $logger->info('departement id : '.$departement_id);

		    if ($zoneUser instanceof \App\Entity\RefDepartement){
			    $liste_departements = $em->getRepository('EPLEElectionBundle:RefDepartement')->findBy(array('numero' => $zoneUser->getIdZone()), array('libelle' => 'ASC'));
			    $liste_communes = $em->getRepository('EPLEElectionBundle:RefCommune')->findCommuneParZone($zoneUser);
		    }elseif ($zoneUser instanceof \App\Entity\RefAcademie){
		        $liste_departements = $em->getRepository('EPLEElectionBundle:RefDepartement')->findBy(array('academie' => $departement->getAcademie()->getCode()), array('libelle' => 'ASC'));
		        $liste_communes = $em->getRepository('EPLEElectionBundle:RefCommune')->findCommuneParZone($departement);
		    }elseif ($zoneUser instanceof \App\Entity\RefEtablissement){
		        //Etablissement principal
		        $liste_departements = $em->getRepository('EPLEElectionBundle:RefDepartement')->findBy(array('numero' => $zoneUser->getCommune()->getDepartement()->getNumero()), array('libelle' => 'ASC'));
		        $liste_communes = $em->getRepository('EPLEElectionBundle:RefCommune')->findBy(array('id' => $zoneUser->getCommune()->getId()));
                    } else{
                        // Zone nationale
                        $liste_departements = $em->getRepository('EPLEElectionBundle:RefDepartement')->findBy(array('academie' => $departement->getAcademie()->getCode()), array('libelle' => 'ASC'));
                        $liste_communes = $em->getRepository('EPLEElectionBundle:RefCommune')->findCommuneParZone($departement);
                    }

			foreach ($liste_departements as $key => $unDepartement) {
				$departements[$key]['libelle'] = $unDepartement->getLibelle();
				$departements[$key]['numero'] = $unDepartement->getNumero();
			}

			foreach ($liste_communes as $key => $uneCommune){
				$communes[$key]['libelle'] = $uneCommune->getLibelle();
				$communes[$key]['id'] = $uneCommune->getId();
				$communes[$key]['cp'] = $uneCommune->getCodePostal();
			}

			if (!empty($commune)){
			    // La commune est renseignée par un on change js
				$logger->info('commune id : '.$commune_id);
				$liste_etablissement = $em->getRepository('EPLEElectionBundle:RefEtablissement')->findEtablissementParZone($commune, $typeEtab);
			} else {
			    // La commune n'est pas renseignée (select à Toutes)
			    $logger->info('commune non renseignee');
			    if ($zoneUser instanceof \App\Entity\RefDepartement){
			        $liste_etablissement = $em->getRepository('EPLEElectionBundle:RefEtablissement')->findEtablissementParZone($departement, $typeEtab);
			    }elseif ($zoneUser instanceof \App\Entity\RefAcademie){
			        $liste_etablissement = $em->getRepository('EPLEElectionBundle:RefEtablissement')->findEtablissementParZone($academie, $typeEtab);
			    } else {
			    	// Liste des établissements de l'utilisateur
			    	foreach($user->getPerimetre()->getEtablissements() as $etablissement){
			    		array_push($liste_etablissement, $etablissement);
			    	}
			    }
			}

			$etablissements = array();

			$indice = 0;
			foreach ($liste_etablissement as $key2 => $unEtablissement){
				$etablissements[$indice]['uai'] = $unEtablissement->getUai();
				$etablissements[$indice]['libelle'] = $unEtablissement->getLibelle();
				$etablissements[$indice]['commune'] = $unEtablissement->getCommune()->getLibelle();
				$logger->info('etab uai : '.$unEtablissement->getUai().' - Libelle : '.$unEtablissement->getLibelle().' - Type : '.$unEtablissement->getTypeEtablissement()->getCode());
				$indice++;
			}
			$logger->info('Nb etab : '.$indice);
			$retour = array('responseCode' => 200, 'academie_id' => $departement->getAcademie()->getCode(), 'liste_departement' => $departements, 'liste_commune' => $communes, 'liste_etablissement' => $etablissements, 'isChoixEtabLocked' => $isChoixEtabLocked);

		} else {
			if (!empty($academie)) {

				if ($zoneUser instanceof \App\Entity\RefDepartement){
				    $paramsSearchDept = array('numero' => $zoneUser->getIdZone());
				} else {
				    $paramsSearchDept = array('academie' => $academie->getCode());
				}
				$isZoneEntity = ($zoneUser instanceof \App\Entity\RefDepartement or $zoneUser instanceof \App\Entity\RefAcademie ) ? true : false;

				$liste_departement = $em->getRepository('EPLEElectionBundle:RefDepartement')->findBy($paramsSearchDept, array('libelle' => 'ASC'));
				foreach ($liste_departement as $key => $unDepartement) {
					$departements[$key]['libelle'] = $unDepartement->getLibelle();
					$departements[$key]['numero'] = $unDepartement->getNumero();
				}

				$liste_communes = $em->getRepository('EPLEElectionBundle:RefCommune')->findCommuneParZone($isZoneEntity ? $zoneUser : $academie);
				foreach ($liste_communes as $key => $uneCommune) {
					$communes[$key]['libelle'] = $uneCommune->getLibelle();
					$communes[$key]['id'] = $uneCommune->getId();
					$communes[$key]['cp'] = $uneCommune->getCodePostal();
				}


				$etablissements = array();
				if (!empty($commune)) {
					$logger->info('commune id : '.$commune_id);
					$liste_etablissement = $em->getRepository('EPLEElectionBundle:RefEtablissement')->findEtablissementParZone($commune, $typeEtab);
				} else {
					$logger->info('commune non renseigne 1');
					$liste_etablissement = $em->getRepository('EPLEElectionBundle:RefEtablissement')->findEtablissementParZone($isZoneEntity ? $zoneUser : $academie, $typeEtab);
				}

				$indice = 0;
				foreach ($liste_etablissement as $key2 => $unEtablissement){
					$etablissements[$indice]['uai'] = $unEtablissement->getUai();
					$etablissements[$indice]['libelle'] = $unEtablissement->getLibelle();
					$etablissements[$indice]['commune'] = $unEtablissement->getCommune()->getLibelle();
					$logger->info('etab uai : '.$unEtablissement->getUai().' - Libelle : '.$unEtablissement->getLibelle());
					$indice++;
				}
				$logger->info('Nb etab : '.$indice);
				$retour = array('responseCode' => 200, 'academie_id' => $academie->getCode(), 'liste_departement' => $departements, 'liste_commune' => $communes, 'liste_etablissement' => $etablissements, 'isChoixEtabLocked' => $isChoixEtabLocked);

			} else {

				if ($zoneUser instanceof \App\Entity\RefAcademie) {
					$paramsSearchDept = array('academie' => $zoneUser->getIdZone());
				} else if ($zoneUser instanceof \App\Entity\RefDepartement){
					$paramsSearchDept = array('numero' => $zoneUser->getIdZone());
				} else {
					$paramsSearchDept = array();
				}

				$liste_departement = $em->getRepository('EPLEElectionBundle:RefDepartement')->findBy($paramsSearchDept, array('libelle' => 'ASC'));
				foreach ($liste_departement as $key => $unDepartement) {
					$departements[$key]['libelle'] = $unDepartement->getLibelle();
					$departements[$key]['numero'] = $unDepartement->getNumero();
				}

				$etablissements = array();

				$retour = array('responseCode' => 200, 'academie_id' => '', 'liste_departement' => $departements, 'liste_commune' => $communes, 'liste_etablissement' => $etablissements, 'isChoixEtabLocked' => $isChoixEtabLocked);
			}
		}
		}

		$retour['info'] = $info;


                 * */
    }


    private function getAcademies(){

        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();

        $academies = array();
        $acas = array();

        // TODO PARTIE DYNAMIQUE (EN FONCTION DES PROFILS)
        if($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DGESCO) {
            $campagneCode = $this->get('request')->request->get('formCampagne');
            $campagne = $em->getRepository('EPLEElectionBundle:EleCampagne')->find($campagneCode);
            if($campagne != null) {
                $campagneDebut = new \DateTime($campagne->getAnneeDebut(). "-01-01");
                $campagneFin = new \DateTime($campagne->getAnneeDebut(). "-12-31");
                $allAcademies = $em->getRepository('EPLEElectionBundle:RefAcademie')->findAll();
                foreach ($allAcademies as $aca) {
                    if($aca->getDateActivation() <= $campagneDebut && $aca->getDateDesactivation() >= $campagneFin) {
                        $formatData = array("libelle" => $aca->getLibelle(), "code" => $aca->getCode() );
                        array_push($academies, $formatData);
                    }
                }
                usort($academies, function($a, $b) {
                    return strcmp(strtolower($a['libelle']), strtolower($b['libelle']));
                });
            }
        }
        /*$acas = $em->getRepository('EPLEElectionBundle:RefAcademie')->findAll(array('libelle' => 'ASC'));
        // Fin TODO

        if(!empty($acas)){
            foreach ($acas as $key => $aca) {
                $academies[$key]['libelle'] = $aca->getLibelle();
                $academies[$key]['code'] = $aca->getCode();
            }
        }*/

        return $academies;

    }

    /**
     *
     * @return type
     */
    private function getDepartements(){

        $user = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $departements = array();
        $depts = array();
        $academieCode = $this->get('request')->request->get('formAcademie');
        $campagne = $em->getRepository('EPLEElectionBundle:EleCampagne')->getLastCampagne(RefTypeElection::ID_TYP_ELECT_PARENT);

        // TODO PARTIE DYNAMIQUE (EN FONCTION DES PROFILS)

        switch ($user->getProfil()->getCode()){
            case RefProfil::CODE_PROFIL_DGESCO:
                if(null != $user->getPerimetre()->getDepartements()){
                    if('' != $academieCode){
                        // On ne renvoie que les départements liés à l'académie
                        $depts_tmp = $user->getPerimetre()->getDepartements();
                        foreach($depts_tmp as $dept){
                            $academie = $dept->getAcademie();
                            $dateCampagneDebut = new DateTime($campagne->getAnneeDebut() . '-01-01');
                            if($academie->getCode() == $academieCode){
                                $depts[] = $dept;
                            }
                            else if($academie->getDateDesactivation() <= $dateCampagneDebut
                                && $academie->getAcademieFusion() !== null
                                && $academie->getAcademieFusion()->getCode() == $academieCode) {
                                $depts[] = $dept;
                            }
                        }
                    }else{
                        // Pas de code académie : on renvoie tous les départements de l'utilisateur
                        $depts = $user->getPerimetre()->getDepartements();
                    }
                }else if('' != $academieCode){
                    $checkChild = $em->getRepository('EPLEElectionBundle:RefAcademie')->findAcademieFisuByParParent($academieCode);

                    if(!empty($checkChild)){
                        $depts = $em->getRepository('EPLEElectionBundle:RefDepartement')->findBydepartementAdademiefusionner($academieCode);

                        $e ='';
                    }else{
                        $depts = $em->getRepository('EPLEElectionBundle:RefDepartement')->findBy(array('academie' => $academieCode), array('libelle' => 'ASC'));
                    }
                }

                break;
            case RefProfil::CODE_PROFIL_RECT:
                if(null != $user->getPerimetre()->getDepartements()){
                    if('' != $academieCode){
                        // On ne renvoie que les départements liés à l'académie
                        $depts_tmp = $user->getPerimetre()->getDepartements();
                        $academie = $em->getRepository('EPLEElectionBundle:RefAcademie')->find($academieCode);
                        $checkChild = $em->getRepository('EPLEElectionBundle:RefAcademie')->findAcademieFisuByParParent($academieCode);
                        $checkParent = $academie->getAcademieFusion();

                        if(!empty($checkChild) || !is_null($checkParent)){
                            $depts = $depts_tmp;
                        }else{
                            foreach($depts_tmp as $dept){
                                $academie = $dept->getAcademie();
                                $dateCampagneDebut = new DateTime($campagne->getAnneeDebut() . '-01-01');
                                if($academie->getCode() == $academieCode){
                                    $depts[] = $dept;
                                }
                                else if($academie->getDateDesactivation() <= $dateCampagneDebut
                                    && $academie->getAcademieFusion() !== null
                                    && $academie->getAcademieFusion()->getCode() == $academieCode) {
                                    $depts[] = $dept;
                                }
                            }
                        }

                    }else{
                        // Pas de code académie : on renvoie tous les départements de l'utilisateur
                        $depts = $user->getPerimetre()->getDepartements();
                    }
                }else if('' != $academieCode){
                    //$user->getProfil()->getCode() == RefProfil::CODE_PROFIL_RECT

                    $depts = $em->getRepository('EPLEElectionBundle:RefDepartement')->findBy(array('academie' => $academieCode), array('libelle' => 'ASC'));
                }
                break;
            case RefProfil::CODE_PROFIL_DSDEN:
                if(null != $user->getPerimetre()->getDepartements()){
                    if('' != $academieCode){
                        // On ne renvoie que les départements liés à l'académie
                        $depts_tmp = $user->getPerimetre()->getDepartements();
                        foreach($depts_tmp as $dept){
                            $academie = $dept->getAcademie();
                            $dateCampagneDebut = new DateTime($campagne->getAnneeDebut() . '-01-01');
                            if($academie->getCode() == $academieCode){
                                $depts[] = $dept;
                            }
                            else if($academie->getDateDesactivation() <= $dateCampagneDebut
                                && $academie->getAcademieFusion() !== null
                                && $academie->getAcademieFusion()->getCode() == $academieCode) {
                                $depts[] = $dept;
                            }
                        }
                    }else{
                        // Pas de code académie : on renvoie tous les départements de l'utilisateur
                        $depts = $user->getPerimetre()->getDepartements();
                    }
                }else if('' != $academieCode){
                    $depts = $em->getRepository('EPLEElectionBundle:RefDepartement')->findBy(array('academie' => $academieCode), array('libelle' => 'ASC'));
                }
                break;
            case RefProfil::CODE_PROFIL_IEN:
                if(null != $user->getPerimetre()->getDepartements()){
                    if('' != $academieCode){
                        // On ne renvoie que les départements liés à l'académie
                        $depts_tmp = $user->getPerimetre()->getDepartements();
                        foreach($depts_tmp as $dept){
                            $academie = $dept->getAcademie();
                            $dateCampagneDebut = new DateTime($campagne->getAnneeDebut() . '-01-01');
                            if($academie->getCode() == $academieCode){
                                $depts[] = $dept;
                            }
                            else if($academie->getDateDesactivation() <= $dateCampagneDebut
                                && $academie->getAcademieFusion() !== null
                                && $academie->getAcademieFusion()->getCode() == $academieCode) {
                                $depts[] = $dept;
                            }
                        }
                    }else{
                        // Pas de code académie : on renvoie tous les départements de l'utilisateur
                        $depts = $user->getPerimetre()->getDepartements();
                    }
                }else if('' != $academieCode){
                    $depts = $em->getRepository('EPLEElectionBundle:RefDepartement')->findBy(array('academie' => $academieCode), array('libelle' => 'ASC'));
                }
                 break;
            case RefProfil::CODE_PROFIL_CE:
                if(null != $user->getPerimetre()->getDepartements()){
                    if('' != $academieCode){
                        // On ne renvoie que les départements liés à l'académie
                        $depts_tmp = $user->getPerimetre()->getDepartements();
                        foreach($depts_tmp as $dept){
                            $academie = $dept->getAcademie();
                            $dateCampagneDebut = new DateTime($campagne->getAnneeDebut() . '-01-01');
                            if($academie->getCode() == $academieCode){
                                $depts[] = $dept;
                            }
                            else if($academie->getDateDesactivation() <= $dateCampagneDebut
                                && $academie->getAcademieFusion() !== null
                                && $academie->getAcademieFusion()->getCode() == $academieCode) {
                                $depts[] = $dept;
                            }
                        }
                    }else{
                        // Pas de code académie : on renvoie tous les départements de l'utilisateur
                        $depts = $user->getPerimetre()->getDepartements();
                    }
                }else if('' != $academieCode){
                    $depts = $em->getRepository('EPLEElectionBundle:RefDepartement')->findBy(array('academie' => $academieCode), array('libelle' => 'ASC'));
                }
                 break;
            case RefProfil::CODE_PROFIL_DE:
                if(null != $user->getPerimetre()->getDepartements()){
                    if('' != $academieCode){
                        // On ne renvoie que les départements liés à l'académie
                        $depts_tmp = $user->getPerimetre()->getDepartements();
                        foreach($depts_tmp as $dept){
                            $academie = $dept->getAcademie();
                            $dateCampagneDebut = new DateTime($campagne->getAnneeDebut() . '-01-01');
                            if($academie->getCode() == $academieCode){
                                $depts[] = $dept;
                            }
                            else if($academie->getDateDesactivation() <= $dateCampagneDebut
                                && $academie->getAcademieFusion() !== null
                                && $academie->getAcademieFusion()->getCode() == $academieCode) {
                                $depts[] = $dept;
                            }
                        }
                    }else{
                        // Pas de code académie : on renvoie tous les départements de l'utilisateur
                        $depts = $user->getPerimetre()->getDepartements();
                    }
                }else if('' != $academieCode){
                    $depts = $em->getRepository('EPLEElectionBundle:RefDepartement')->findBy(array('academie' => $academieCode), array('libelle' => 'ASC'));
                }
                 break;
            case RefProfil::CODE_PROFIL_PARENTS:
                if(null != $user->getPerimetre()->getDepartements()){
                    if('' != $academieCode){
                        // On ne renvoie que les départements liés à l'académie
                        $depts_tmp = $user->getPerimetre()->getDepartements();
                        foreach($depts_tmp as $dept){
                            $academie = $dept->getAcademie();
                            $dateCampagneDebut = new DateTime($campagne->getAnneeDebut() . '-01-01');
                            if($academie->getCode() == $academieCode){
                                $depts[] = $dept;
                            }
                            else if($academie->getDateDesactivation() <= $dateCampagneDebut
                                && $academie->getAcademieFusion() !== null
                                && $academie->getAcademieFusion()->getCode() == $academieCode) {
                                $depts[] = $dept;
                            }
                        }
                    }else{
                        // Pas de code académie : on renvoie tous les départements de l'utilisateur
                        $depts = $user->getPerimetre()->getDepartements();
                    }
                }else if('' != $academieCode){
                    $depts = $em->getRepository('EPLEElectionBundle:RefDepartement')->findBy(array('academie' => $academieCode), array('libelle' => 'ASC'));
                }
                 break;
            default:
                break;
        }


        if(!empty($depts)){
            foreach ($depts as $key => $dept) {
                $departements[$key]['libelle'] = $dept->getLibelle();
                $departements[$key]['numero'] = $dept->getNumero();
            }
        }

        //Tri des département dans l'ordre alphabétique
        usort($departements, function ($a, $b) {
            return ($a["libelle"] < $b["libelle"]) ? -1 : 1;
        });
        return $departements;

    }

    /**
     *
     * @return type
     */
    private function getCommunes(){

        $user = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $communes = array();
        $comms = array();
        $depts = array();
        $academieCode = $this->get('request')->request->get('formAcademie');
        $departementNumero = $this->get('request')->request->get('formDepartement');

        // TODO PARTIE DYNAMIQUE (EN FONCTION DES PROFILS)
        // Restriction au niveau du périmètre géographique
        if(null != $user->getPerimetre()->getCommunes()){
            $comms_tmp =  $user->getPerimetre()->getCommunes();

            if('' != $departementNumero){
                //Recherche des communes par département
                foreach($comms_tmp as $comm){
                    if($comm->getDepartement()->getNumero() == $departementNumero){
                        $comms[] = $comm;
                    }
                }

            }else if ('' != $academieCode){
                // Recherche des départements par académie
                if(null != $user->getPerimetre()->getDepartements()){
                    $depts_tmp = $user->getPerimetre()->getDepartements();
                    foreach($depts_tmp as $dept){
                        if($dept->getAcademie()->getCode() == $academieCode){
                            //Recherche des communes par département
                            foreach($comms_tmp as $comm){
                                if($comm->getDepartement()->getNumero() == $dept->getNumero()){
                                    $comms[] = $comm;
                                }
                            }
                        }
                    }
                }
            }else{
                // YME - HPQC DEFECT #223
                foreach($comms_tmp as $comm){
                    $comms[] = $comm;
                }

            }

        }else if('' != $departementNumero){
            $comms = $em->getRepository('EPLEElectionBundle:RefCommune')->findBy(array('departement' => $departementNumero), array('libelle' => 'ASC'));
        }

        // Fin TODO
        if(null != $comms){
            foreach ($comms as $key => $comm) {
                $communes[$key]['libelle'] = $comm->getLibelle();
                $communes[$key]['id'] = $comm->getId();
                $communes[$key]['cp'] = $comm->getCodePostal();
            }
        }
        return $communes;
    }

    private function getEtablissements(){

        $user = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $etablissements = array();
        $etabs = array(); // Contient les établissements filtrés selon le périmètre géographique
        $comms = array();
        $depts = array();
        $academieCode = $this->get('request')->request->get('formAcademie');
        $departementNumero = $this->get('request')->request->get('formDepartement');
        $communeId = $this->get('request')->request->get('formCommune');
        $typeEtab = $this->get('request')->request->get('formTypeEtab');

        // Restriction au niveau du périmètre géographique
        if(null != $user->getPerimetre()->getEtablissements()){
            $etabs_tmp = $user->getPerimetre()->getEtablissements();

            if('' != $communeId){
                // Recherche des établissements liés à la commune
                foreach($etabs_tmp as $etab){
                    if($etab->getCommune()->getId() == $communeId){
                        $etabs[] = $etab;
                    }
                }
            }else if('' != $departementNumero){

                // Recherche des établissements liés au département
                if(null != $user->getPerimetre()->getCommunes()){
                    $comms = $user->getPerimetre()->getCommunes();
                }else{
                    $comms = $em->getRepository('EPLEElectionBundle:RefCommune')->findBy(array('departement' => $departementNumero), array('libelle' => 'ASC'));
                }

                foreach($comms as $comm){
                    if($comm->getDepartement()->getNumero() == $departementNumero){
                        //$comms[] = $comm;
                        foreach($etabs_tmp as $etab){
                            if($etab->getCommune()->getId() == $comm->getId()){
                                $etabs[] = $etab;
                            }
                        }
                    }
                }


            }else if('' != $academieCode){

                // Recherche des établissements de l'utilisateur liés à l'académie
                if(null != $user->getPerimetre()->getDepartements()){
                    $depts = $user->getPerimetre()->getDepartements();
                }else{
                    $depts = $em->getRepository('EPLEElectionBundle:RefDepartement')->findBy(array('academie' => $academieCode), array('libelle' => 'ASC'));
                }

                foreach($depts as $dept){
                    if($dept->getAcademie()->getCode() == $academieCode){
                        //$depts[] = $dept;
                        // Recherche des communes liées au département
                        if(null != $user->getPerimetre()->getCommunes()){
                            $comms = $user->getPerimetre()->getCommunes();
                        }else{
                            $comms = $em->getRepository('EPLEElectionBundle:RefCommune')->findBy(array('departement' => $dept->getNumero()), array('libelle' => 'ASC'));
                        }
                        // Recherche des établissements liées aux communes
                        foreach($comms as $comm){
                            if($comm->getDepartement()->getNumero() == $dept->getNumero()){
                                //$comms[] = $comm;
                                foreach($etabs_tmp as $etab){
                                    if($etab->getCommune()->getId() == $comm->getId()){
                                        $etabs[] = $etab;
                                    }
                                }
                            }
                        }
                    }
                }
            }

        }else if('' != $communeId){
            $etabs = $em->getRepository('EPLEElectionBundle:RefEtablissement')->findBy(array('commune' => $communeId, 'actif' => true), array('libelle' => 'ASC')); // YME - HPQC DEFECT #220
        }

        // Restriction au niveau du type d'établissement
        $key = 0;
        foreach($etabs as $etab){
            // BBL defect 261 HPQC
            if((('' != $typeEtab) && $etab->getTypeEtablissement()->getId() == $typeEtab) || ('' == $typeEtab) || (RefTypeEtablissement::ID_TYP_2ND_DEGRE == $typeEtab && $etab->getTypeEtablissement()->getDegre() == RefTypeEtablissement::SECOND_DEGRE)){
                $etablissements[$key]['uai'] = $etab->getUai();
                $etablissements[$key]['libelle'] = $etab->getLibelle();
                $key++;
            }
        }

        return $etablissements;

    }































    // ################################ ANCIEN CODE ##################################### //

    /**
     * "findCodeMailByCommuneAction" Recherche du code email associé à une commune
     * 	si la commune existe alors on recupere le code postal de la commune et le code email de l'académie associé
     *  et on renvoie les données à la fonction Javascript pour le traitement
     *
    public function findCodeMailByCommuneAction() {
    $request = $this->get('request');
    $id_commune = $request->request->get('formCommune');
    $id_departement = $request->request->get('formDepartement');

    if ($id_commune != '') {

    $em = $this->getDoctrine()->getManager();
    $commune = $em->getRepository('EPLEElectionBundle:RefCommune')->find($id_commune);

    if (!empty($commune)) {
    $codeEmail = $commune->getDepartement()->getAcademie()->getCodeEmail();
    $numero_dep = $commune->getDepartement()->getnumero();
    }
    else {
    $departement = $em->getRepository('EPLEElectionBundle:RefDepartement')->find($id_departement);
    $codeEmail = $departement->getAcademie()->getCodeEmail();
    $numero_dep = $id_departement;
    }

    $return = array('responseCode' => 200, 'code_email' => $codeEmail, 'departement_id' => $numero_dep, 'commune_id' => $id_commune);
    } else {
    $return = array('responseCode' => 400);
    }

    $return = json_encode($return); // json encode the array
    return new Response($return, 200, array('Content-Type' => 'application/json'));
    }*/


    /**
     * "findCommuneByCPAction" Recherche l'ensemble des communes ayant un code postal donné
     * 	on test d'abord si il existe au moins une commune ayant ce code postal
     *  si c'est le cas alors on enregistre dans un tableau la liste des communes associées
     *  le tableau contient le libelle, le numéro de département de la commune et le code mail de l'académie associé à cette commune
     *  le tout est renvoyé pour le traitement javascript
     *
    public function findCommuneByCPAction() {

    $request = $this->get('request');
    $code_postal = $request->request->get('formCodePostal');
    $uai = $request->request->get('formUAI');

    $em = $this->getDoctrine()->getManager();

    $etablissement = $em->getRepository('EPLEElectionBundle:RefEtablissement')->find($uai);

    $select_commune = 0;
    if (!empty($etablissement)) {
    if ($etablissement->getCommune() != null) {
    $select_commune = $etablissement->getCommune()->getId();
    }
    }

    $liste_commune = $em->getRepository('EPLEElectionBundle:RefCommune')->findByCodePostal($code_postal);
    $nb = count($liste_commune);

    if ($nb != 0) {
    foreach ($liste_commune as $key => $commune) {
    $communes[$key]['id'] = $commune->getId();
    $communes[$key]['libelle'] = $commune->getLibelle();
    $communes[$key]['numero'] = $commune->getDepartement()->getNumero();
    $communes[$key]['code_mail'] = $commune->getDepartement()->getAcademie()->getCodeEmail();
    }

    $return = array('responseCode' => 200, 'communes' => $communes, 'select_commune' => $select_commune);
    } else {
    $return = array('responseCode' => 400);
    }

    $return = json_encode($return); // json encode the array
    return new Response($return, 200, array('Content-Type' => 'application/json'));
    }*/



    /*public function findEtablissementByCategory(){
        $logger = $this->get('logger');
        $em = $this->getDoctrine()->getManager();

        $request = $this->get('request');
        $categorie = $request->request->get('formCategory');
        $typeEtab = $request->request->get('formTypeEtab');
        $zoneUser = \App\Utils\EpleUtils::getZone($em, $request->request->get('idZoneUser'));

        if($categorie == "academie"){
            $listEtabByAcademy =  $em->getRepository('EPLEElectionBundle:RefEtablissement')->findEtablissementsParCategorie('academie');
        }
        else if($categorie == "departement"){
            $listEtabByAcademy =  $em->getRepository('EPLEElectionBundle:RefEtablissement')->findEtablissementsParCategorie('departement');
        }

        $retour = array('responseCode' => 200, 'type' => $categorie, 'listeEtabs' => $listEtabByAcademy);

        return new Response($return, 200, array('Content-Type' => 'application/json'));
    }*/

    /**
     * findAllAcademieAction retourne la liste de toutes les académies
     * @return \Symfony\Component\Httpfoundation\Response
     *
    public function findAllAcademieAction() {
    $em = $this->getDoctrine()->getManager();
    $liste_academies = $em->getRepository('EPLEElectionBundle:RefAcademie')->findListAcademies();
    $academies = array();
    foreach ($liste_academies as $key => $uneAcademie){
    $academies[$key]['code'] = $uneAcademie->getCode();
    $academies[$key]['libelle'] = $uneAcademie->getLibelle();
    }
    $return = array('responseCode' => 200, 'academies' => $academies);
    $return = json_encode($return); // json encode the array
    return new Response($return, 200, array('Content-Type' => 'application/json'));
    }*/

    /**
     * findDepartementsByCodeAcademieAction retourne la liste des départements en fonction d'un code académie
     * @return \Symfony\Component\Httpfoundation\Response
     *
    public function findDepartementsByCodeAcademieAction() {
    $em = $this->getDoctrine()->getManager();
    $request = $this->get('request');
    $user = $this->get('security.context')->getToken()->getUser();
    $academie_code = $request->request->get('academie_code');

    $departements = array();
    if ($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DSDEN) {
    $departement_numero = $user->getIdZone();
    $departement = $em->getRepository('EPLEElectionBundle:RefDepartement')->find($departement_numero);
    $departements[0]['numero'] = $departement->getNumero();
    $departements[0]['academie'] = $departement->getAcademie()->getCode();
    $departements[0]['libelle'] = $departement->getLibelle();
    } else {
    if ($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_RECT) {
    $academie_code = $user->getIdZone();
    }

    if (!empty($academie_code)) {
    $paramsSearchDept = array('academie' => $academie_code);
    $liste_departement = $em->getRepository('EPLEElectionBundle:RefDepartement')->findBy($paramsSearchDept, array('libelle' => 'ASC'));
    } else {
    $liste_departement = $em->getRepository('EPLEElectionBundle:RefDepartement')->findListDepartements();
    }

    foreach ($liste_departement as $key => $unDepartement) {
    $departements[$key]['numero'] = $unDepartement->getNumero();
    $departements[$key]['academie'] = $unDepartement->getAcademie()->getCode();
    $departements[$key]['libelle'] = $unDepartement->getLibelle();
    }
    }
    $return = array('responseCode' => 200, 'departements' => $departements);
    $return = json_encode($return); // json encode the array
    return new Response($return, 200, array('Content-Type' => 'application/json'));
    }*/

    /**
     * findEtablissementsByNumeroDepartementAction retourne la liste des établissements en fonction d'un numero departement
     * @return \Symfony\Component\Httpfoundation\Response
     */
    /*
public function findEtablissementsByNumeroDepartementAction() {
    $em = $this->getDoctrine()->getManager();
    $request = $this->get('request');
    $user = $this->get('security.context')->getToken()->getUser();
    $departement_numero = $request->request->get('departement_numero');

    if ($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DSDEN) {
        $departement_numero = $user->getIdZone();
    }

    $departement = $em->getRepository('EPLEElectionBundle:RefDepartement')->find($departement_numero);
    $liste_etablissement = $em->getRepository('EPLEElectionBundle:RefEtablissement')->findEtablissementParZone($departement);

    $etablissements = array();
    foreach ($liste_etablissement as $key => $unEtablissement) {
        $etablissements[$key]['uai'] = $unEtablissement->getUai();
        $etablissements[$key]['libelle'] = $unEtablissement->getLibelle();
    }
    $return = array('responseCode' => 200, 'etablissements' => $etablissements);
    $return = json_encode($return); // json encode the array
    return new Response($return, 200, array('Content-Type' => 'application/json'));
}*/

    /**
     * findEtablissementByUaiOrLibelleAction retourne la liste des etablissements en fonction d'un uai ou d'un libelle etablissement
     * @return \Symfony\Component\Httpfoundation\Response
     */
    /*
public function findEtablissementByUaiOrLibelleAction() {
    $em = $this->getDoctrine()->getManager();
    $request = $this->get('request');
    $user = $this->get('security.context')->getToken()->getUser();
    $uai_or_libelle = $request->request->get('uai_or_libelle');

    $liste_etablissement = $em->getRepository('EPLEElectionBundle:RefEtablissement')->findEtablissementsByUaiOrLibelle($uai_or_libelle);
    $etablissements = array();

    if ($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_RECT) {
        $academie_code = $user->getIdZone();
        $academie = $em->getRepository('EPLEElectionBundle:RefAcademie')->find($academie_code);
        $liste_etablissement_profil = $em->getRepository('EPLEElectionBundle:RefEtablissement')->findEtablissementParZone($academie);

        foreach ($liste_etablissement as $key => $unEtablissement) {
            if (in_array($unEtablissement, $liste_etablissement_profil)) {
                $etablissements[$key]['uai'] = $unEtablissement->getUai();
                $etablissements[$key]['departement'] = $unEtablissement->getCommune()->getDepartement()->getNumero();
                $etablissements[$key]['libelle'] = $unEtablissement->getLibelle();
            }
        }
    } else if ($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DSDEN) {
        $departement_numero = $user->getIdZone();
        $departement = $em->getRepository('EPLEElectionBundle:RefDepartement')->find($departement_numero);
        $liste_etablissement_profil = $em->getRepository('EPLEElectionBundle:RefEtablissement')->findEtablissementParZone($departement);

        foreach ($liste_etablissement as $key => $unEtablissement) {
            if (in_array($unEtablissement, $liste_etablissement_profil)) {
                $etablissements[$key]['uai'] = $unEtablissement->getUai();
                $etablissements[$key]['departement'] = $unEtablissement->getCommune()->getDepartement()->getNumero();
                $etablissements[$key]['libelle'] = $unEtablissement->getLibelle();
            }
        }
    } else {
        foreach ($liste_etablissement as $key => $unEtablissement) {
            $etablissements[$key]['uai'] = $unEtablissement->getUai();
            $etablissements[$key]['departement'] = $unEtablissement->getCommune()->getDepartement()->getNumero();
            $etablissements[$key]['libelle'] = $unEtablissement->getLibelle();
        }
    }
    $return = array('responseCode' => 200, 'etablissements' => $etablissements);
    $return = json_encode($return); // json encode the array
    return new Response($return, 200, array('Content-Type' => 'application/json'));
}
     * */
}