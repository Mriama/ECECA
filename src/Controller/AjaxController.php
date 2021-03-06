<?php

namespace App\Controller;

use App\Entity\EleCampagne;
use App\Entity\RefAcademie;
use App\Entity\RefCommune;
use App\Entity\RefDepartement;
use App\Entity\RefEtablissement;
use DateTime;
use App\Entity\RefTypeElection;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\RefProfil;
use App\Entity\RefTypeEtablissement;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;

class AjaxController extends AbstractController {

    private $request;
    private $doctrine;

    public function __construct(RequestStack $request, ManagerRegistry $doctrine) {
        $this->request = $request->getCurrentRequest();
        $this->doctrine = $doctrine;
    }

    /**
     * "findAcademieDepartementCommuneByZoneAction" Recherche la liste des départements et la liste des communes en fonction de l'académie associé
     *  si le departement est renseigné on recupere l'academie et les communes associés
     *  sinon si l'academie est renseigné alors on recupere les departements et les communes associés
     *  sinon on recupere l'ensemble des departements mais aucune communes car trop nombreuse
     *  on renvoie l'identifiant de l'academie, une liste de département
     */
    public function findAcademieDepartementCommuneByZoneAction() {
        $retour = array('responseCode' => 200, 'liste_academies' => $this->getAcademies(), 'liste_departement' => $this->getDepartements(), 'liste_commune' => $this->getCommunes(), 'liste_etablissement' => $this->getEtablissements());
        return new JsonResponse($retour);
    }

    private function getAcademies(){
        $em = $this->doctrine->getManager();
        $user = $this->getUser();
        $campagne = null;
        $academies = array();

        //PARTIE DYNAMIQUE (EN FONCTION DES PROFILS)
        if($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DGESCO) {
            $campagneCode = $this->request->request->get('formCampagne');
            if(!empty($campagneCode)) {
                $campagne = $em->getRepository(EleCampagne::class)->find($campagneCode);
            }
            if($campagne != null) {
                $campagneDebut = new DateTime($campagne->getAnneeDebut(). "-01-01");
                $campagneFin = new DateTime($campagne->getAnneeDebut(). "-12-31");
                $allAcademies = $em->getRepository(RefAcademie::class)->findAll();
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
        return $academies;
    }

    private function getDepartements(){
        $user = $this->getUser();
        $em = $this->doctrine->getManager();

        $departements = array();
        $depts = array();
        $academieCode = $this->request->request->get('formAcademie');
        $campagne = $em->getRepository(EleCampagne::class)->getLastCampagne(RefTypeElection::ID_TYP_ELECT_PARENT);

        //PARTIE DYNAMIQUE (EN FONCTION DES PROFILS)

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
                } else if('' != $academieCode){
                    $checkChild = $em->getRepository(RefAcademie::class)->findAcademieFisuByParParent($academieCode);

                    if(!empty($checkChild)){
                        $depts = $em->getRepository(RefDepartement::class)->findBydepartementAdademiefusionner($academieCode);

                        $e ='';
                    }else{
                        $depts = $em->getRepository(RefDepartement::class)->findBy(array('academie' => $academieCode), array('libelle' => 'ASC'));
                    }
                }
                break;

            case RefProfil::CODE_PROFIL_RECT:
                if(null != $user->getPerimetre()->getDepartements()){
                    if('' != $academieCode){
                        // On ne renvoie que les départements liés à l'académie
                        $depts_tmp = $user->getPerimetre()->getDepartements();
                        $academie = $em->getRepository(RefAcademie::class)->find($academieCode);
                        $checkChild = $em->getRepository(RefAcademie::class)->findAcademieFisuByParParent($academieCode);
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
                    } else{
                        // Pas de code académie : on renvoie tous les départements de l'utilisateur
                        $depts = $user->getPerimetre()->getDepartements();
                    }
                }else if('' != $academieCode){
                    $depts = $em->getRepository(RefDepartement::class)->findBy(array('academie' => $academieCode), array('libelle' => 'ASC'));
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
                    } else{
                        // Pas de code académie : on renvoie tous les départements de l'utilisateur
                        $depts = $user->getPerimetre()->getDepartements();
                    }
                } else if('' != $academieCode){
                    $depts = $em->getRepository(RefDepartement::class)->findBy(array('academie' => $academieCode), array('libelle' => 'ASC'));
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
                    $depts = $em->getRepository(RefDepartement::class)->findBy(array('academie' => $academieCode), array('libelle' => 'ASC'));
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
                    } else{
                        // Pas de code académie : on renvoie tous les départements de l'utilisateur
                        $depts = $user->getPerimetre()->getDepartements();
                    }
                } else if('' != $academieCode){
                    $depts = $em->getRepository(RefDepartement::class)->findBy(array('academie' => $academieCode), array('libelle' => 'ASC'));
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
                    $depts = $em->getRepository(RefDepartement::class)->findBy(array('academie' => $academieCode), array('libelle' => 'ASC'));
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
                    $depts = $em->getRepository(RefDepartement::class)->findBy(array('academie' => $academieCode), array('libelle' => 'ASC'));
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

    private function getCommunes(){
        $user = $this->getUser();
        $em = $this->doctrine->getManager();

        $communes = array();
        $comms = array();
        $academieCode = $this->request->request->get('formAcademie');
        $departementNumero = $this->request->request->get('formDepartement');

        // PARTIE DYNAMIQUE (EN FONCTION DES PROFILS)
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

            } else if ('' != $academieCode){
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
            } else{
                // YME - HPQC DEFECT #223
                foreach($comms_tmp as $comm){
                    $comms[] = $comm;
                }

            }

        } else if('' != $departementNumero){
            $comms = $em->getRepository(RefCommune::class)->findBy(array('departement' => $departementNumero), array('libelle' => 'ASC'));
        }

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
        $user = $this->getUser();
        $em = $this->doctrine->getManager();

        $etablissements = array();
        $etabs = array(); // Contient les établissements filtrés selon le périmètre géographique
        $academieCode = $this->request->request->get('formAcademie');
        $departementNumero = $this->request->request->get('formDepartement');
        $communeId = $this->request->request->get('formCommune');
        $typeEtab = $this->request->request->get('formTypeEtab');

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
            } else if('' != $departementNumero){
                // Recherche des établissements liés au département
                if(null != $user->getPerimetre()->getCommunes()){
                    $comms = $user->getPerimetre()->getCommunes();
                }else{
                    $comms = $em->getRepository(RefCommune::class)->findBy(array('departement' => $departementNumero), array('libelle' => 'ASC'));
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
            } else if('' != $academieCode){

                // Recherche des établissements de l'utilisateur liés à l'académie
                if(null != $user->getPerimetre()->getDepartements()){
                    $depts = $user->getPerimetre()->getDepartements();
                }else{
                    $depts = $em->getRepository(RefDepartement::class)->findBy(array('academie' => $academieCode), array('libelle' => 'ASC'));
                }

                foreach($depts as $dept){
                    if($dept->getAcademie()->getCode() == $academieCode){
                        //$depts[] = $dept;
                        // Recherche des communes liées au département
                        if(null != $user->getPerimetre()->getCommunes()){
                            $comms = $user->getPerimetre()->getCommunes();
                        }else{
                            $comms = $em->getRepository(RefCommune::class)->findBy(array('departement' => $dept->getNumero()), array('libelle' => 'ASC'));
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
        } else if('' != $communeId){
            $etabs = $em->getRepository(RefEtablissement::class)->findBy(array('commune' => $communeId, 'actif' => true), array('libelle' => 'ASC')); // YME - HPQC DEFECT #220
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
}