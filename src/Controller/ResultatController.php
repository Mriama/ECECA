<?php

namespace App\Controller;

use App\Entity\EleAlerte;
use App\Entity\EleConsolidation;
use App\Entity\EleParticipation;
use App\Entity\EleResultat;
use App\Entity\EleResultatDetail;
use App\Entity\RefOrganisation;
use App\Utils\ConsolidationService;
use DateTime;
use App\Entity\RefUser;
use App\Utils\EpleUtils;
use App\Entity\RefProfil;
use App\Entity\RefCommune;
use App\Entity\EleCampagne;
use App\Entity\RefAcademie;
use App\Entity\RefDepartement;
use App\Entity\RefModaliteVote;
use App\Entity\RefTypeElection;
use App\Utils\EcecaExportUtils;
use App\Entity\EleEtablissement;
use App\Entity\RefEtablissement;
use App\Form\ResultatZoneEtabType;
use App\Entity\RefSousTypeElection;
use App\Entity\RefTypeEtablissement;
use App\Form\NbSiegesTirageAuSortType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Routing\Annotation\Route;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ResultatController extends AbstractController {

    private $request;
    private $doctrine;

    public function __construct(RequestStack $request, ManagerRegistry $doctrine) {
        $this->request = $request->getCurrentRequest();
        $this->doctrine = $doctrine;
    }

    /**
     * Formulaire de recherche des résultats (page principale)
     *
     * @param String $codeUrlTypeElect : code type élection
     */
    public function indexAction($codeUrlTypeElect) {
        $em = $this->doctrine->getManager();

        // reset session pour les users ETAB venant de leurs résultats
        $this->request->getSession()->remove('select_academie');
        $this->request->getSession()->remove('select_departement');
        $this->request->getSession()->remove('select_commune');
        $this->request->getSession()->remove('select_etablissement');
        $this->request->getSession()->remove('select_choix_etab');
        $this->request->getSession()->remove('select_typeEtab');
        $this->request->getSession()->remove('select_sousTypeElect');
        $this->request->getSession()->remove('select_etatSaisie');
        $this->request->getSession()->remove('dept_num');

        $user = $this->getUser();
        $profilsLimitEtab = array(RefProfil::CODE_PROFIL_IEN, RefProfil::CODE_PROFIL_CE, RefProfil::CODE_PROFIL_DE);

        $typeElectionId = RefTypeElection::getIdRefTypeElectionByCodeUrl($codeUrlTypeElect);
        $typeElection = $em->getRepository(RefTypeELection::class)->find($typeElectionId);
        if (null == $typeElection) {
            throw $this->createNotFoundException('Type élection '.$codeUrlTypeElect.' inconnu');
        }

        $campagne = $em->getRepository(EleCampagne::class)->getLastCampagne($typeElectionId);
        if (empty($campagne)) {
            throw $this->createNotFoundException('Les résultats de l\'établissement n\'ont pas été trouvés car la campagne est inconnue.');
        }
        if (!$user->canConsult($typeElection)) {
            throw new AccessDeniedException();
        }

        // RECT -> placer l'académie en session
        if ($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_RECT) {
            $academies = $user->getPerimetre()->getAcademies();
            if(count($academies) == 1){
                $this->request->getSession()->set('select_academie', $academies[0]->getCode());
            }elseif(count($academies) > 1){
                $academies[0]->getAcademieFusion()->getCode();
                $academies[0]->setlibelle($academies[0]->getAcademieFusion()->getLibelle());
                $this->request->getSession()->set('select_academie', $academies[0]->getCode());
            }
        }

        // DSDEN -> placer l'académie et le département de l'utilisateur en session
        if ($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DSDEN) {
            $academies = $user->getPerimetre()->getAcademies();
            $this->request->getSession()->set('select_academie', $academies[0]->getCode());
            $departements = $user->getPerimetre()->getDepartements();
            $this->request->getSession()->set('select_departement', $departements[0]->getNumero());
        }

        // CE / DE / IEN -> placer l'établissement de l'utilisateur en session
        if (in_array($user->getProfil()->getCode(), $profilsLimitEtab)) {
            $etablissements = $user->getPerimetre()->getEtablissements();
            $this->request->getSession()->set('select_etablissement', $etablissements[0]->getUai());
            $communes = $user->getPerimetre()->getCommunes();
            $this->request->getSession()->set('select_commune', $communes[0]->getId());
            $academies = $user->getPerimetre()->getAcademies();
            $this->request->getSession()->set('select_academie', $academies[0]->getCode());
            $departements = $user->getPerimetre()->getDepartements();
            $this->request->getSession()->set('select_departement', $departements[0]->getNumero());
            $this->request->getSession()->set('select_choix_etab', true);
            $params['retourLstRech'] = 0;
        }

        $params = $this->getParametersRecherche($user, $campagne);

        if (in_array($user->getProfil()->getCode(), $profilsLimitEtab)) {
            $params['retourLstRech'] = 0;
        }

        // 0154831 changement de message des résultats transmis pour DE/CE
        $params['isDeCe'] = ($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_CE || $user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DE);
        $params['isIEN'] = ($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_IEN);
        $params['isDE'] = ($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DE);

        // Evol message info pour le nombre total des établissements exclu EREA/ERPD
        if (($typeElectionId == RefTypeElection::ID_TYP_ELECT_ASS_ATE || $typeElectionId == RefTypeElection::ID_TYP_ELECT_PEE)
            && ($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DGESCO
                || $user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DSDEN
                || $user->getProfil()->getCode() == RefProfil::CODE_PROFIL_RECT)) {
            $params['info'] = $this->getParameter('info');
            $params['typeElectCode'] = $typeElection->getCode();
        }

        // 014E RG_EXPORT_COMPLET-DETAILLE_001
        if ($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DSDEN || $user->getProfil()->getCode() == RefProfil::CODE_PROFIL_RECT || $user->getProfil()->getCode() == RefProfil::CODE_PROFIL_IEN) {
            $params['accesExportDetaille'] = true;
        }

        $params['erreurs'] = $this->getParameter('erreurs');
        $params['warning'] = $this->getParameter('warning');
        // 0239479: Erreur aléatoire lors de l affichage de la page Résultats
        $form = $this->createForm(NbSiegesTirageAuSortType::class, null);
        $params['formTirage'] = $form->createView();
        return $this->render('resultat/indexConsultationResultats.html.twig', $params);
    }

    /**
     * Cette fonction permet de lancer l'action de retour dans le formulaire de recherche par établissement
     *
     * @param String $codeUrlTypeElect : code type élection
     */
    public function retourListeEtablissementAction($codeUrlTypeElect){

        $em = $this->doctrine->getManager();

        $this->request->getSession()->remove('select_sous_type_election');
        $this->request->getSession()->remove('select_etablissement');
        $this->request->getSession()->remove('dept_num');

        $typeElectionId = RefTypeElection::getIdRefTypeElectionByCodeUrl($codeUrlTypeElect);
        $typeElection = $em->getRepository(RefTypeELection::class)->find($typeElectionId);
        if (null == $typeElection) {
            throw $this->createNotFoundException('Type élection '.$codeUrlTypeElect.' inconnu');
        }

        $campagne = $em->getRepository(EleCampagne::class)->getLastCampagne($typeElectionId);
        if (empty($campagne)) {
            throw $this->createNotFoundException('Les résultats de l\'établissement n\'ont pas été trouvés car la campagne est inconnue.');
        }
        $user = $this->getUser();
        if (!$user->canConsult($typeElection)) {
            throw new AccessDeniedException();
        }

        $params = $this->getParametersRecherche($user, $campagne);
        $params['erreurs'] = $this->getParameter('erreurs');
        $params['warning'] = $this->getParameter('warning');

        return $this->render('resultat/indexConsultationResultats.html.twig', $params);
    }

    /**
     * Paramètres pour la recherche des résultats
     * @codeCoverageIgnore
     */
    private function getParametersRecherche(RefUser $user, $campagne){

        $em = $this->doctrine->getManager();
        $profilsLimitEtab = array(RefProfil::CODE_PROFIL_IEN, RefProfil::CODE_PROFIL_CE, RefProfil::CODE_PROFIL_DE);
        $profilsHasSousTypeElect = array(RefProfil::CODE_PROFIL_CE, RefProfil::CODE_PROFIL_RECT, RefProfil::CODE_PROFIL_DSDEN);
        $params = array();

        $joursCalendaires = $this->getParameter('jours_calendaires');
        $joursCalendairesIen = $this->getParameter('jours_calendaires_ien');

        $codeUrlTypeEtab = 'tous';
        $idSousTypeElect = null;

        $params['campagne'] = $campagne;
        $params['typeElect'] = $campagne->getTypeElection();

        $this->request->getSession()->set('campagne', $campagne);
        $this->request->getSession()->set('typeElect', $campagne->getTypeElection());


        $datasSearch = array();
        $datasSearch['departement'] = null;
        $datasSearch['academie'] = null;
        $datasSearch['commune'] = null;
        $datasSearch['typeEtablissement'] = null;
        $datasSearch['choix_etab'] = null;
        $datasSearch['etablissement'] = null;
        $datasSearch['sousTypeElection'] = null;

        if($this->request->getSession()->get('select_departement'))
            $datasSearch['departement'] = $em->getRepository(RefDepartement::class)->find($this->request->getSession()->get('select_departement'));
        if($this->request->getSession()->get('select_academie'))
            $datasSearch['academie']  = $em->getRepository(RefAcademie::class)->find($this->request->getSession()->get('select_academie'));
        if($this->request->getSession()->get('select_commune'))
            $datasSearch['commune'] = $em->getRepository(RefCommune::class)->find($this->request->getSession()->get('select_commune'));

        if($this->request->getSession()->get('select_typeEtab'))
            $datasSearch['typeEtablissement'] = $em->getRepository(RefTypeEtablissement::class)->find($this->request->getSession()->get('select_typeEtab'));
        if($this->request->getSession()->get('select_choix_etab'))
            $datasSearch['choix_etab'] = $this->request->getSession()->get('select_choix_etab');
        if($this->request->getSession()->get('select_etablissement'))
            $datasSearch['etablissement'] = $em->getRepository(RefEtablissement::class)->find($this->request->getSession()->get('select_etablissement'));
        if($this->request->getSession()->get('select_sousTypeElect'))
            $datasSearch['sousTypeElection'] = $em->getRepository(RefSousTypeElection::class)->find($this->request->getSession()->get('select_sousTypeElect'));

        // mantis 122046 le filtre avancement des saisies apparait tout le temps
        //         if ($campagne->isFinished()) {
        //             $datasSearch['etatSaisie'] = EleEtablissement::ETAT_VALIDATION;
        //         } else {
        if ($this->request->getSession()->get('select_etatSaisie')) {
            $datasSearch['etatSaisie'] = $this->request->getSession()->get('select_etatSaisie');
            if (!is_array($datasSearch['etatSaisie'])) {
                $datasSearch['etatSaisie'] = array(
                    $datasSearch['etatSaisie']
                );
            }
        } else {
            $datasSearch['etatSaisie'] = array(
                EleEtablissement::ETAT_VALIDATION
            );
        }
        //         }
        // RG_CONSULT_5_3
        $hasSousTypeElect = in_array($user->getProfil()->getCode(), $profilsHasSousTypeElect);
        if($hasSousTypeElect){
            // Recherche du type d'élection (ne pas afficher le champ si l' type d'élection n'en a pas)
            $hasSousTypeElect = ($params['typeElect']->getId() == RefTypeElection::ID_TYP_ELECT_ASS_ATE);
        }
        if($hasSousTypeElect){
            // Recherche d'établissements EREA-ERPD dans le périmètre de l'utilisateur
            $hasSousTypeElect = $this->hasEreaErpd($user);
        }

        $form = $this->createForm(ResultatZoneEtabType::class, null, ['user'=>$user, 'hasSousTypeElect'=>$hasSousTypeElect]);

        if ($this->request->getMethod() == 'POST') {

            //If the Departementr is empty check she has a child:

            $form->handleRequest($this->request);
            if ($form->isSubmitted() && $form->isValid()) {
                $datasForm = $form->getData();

                $datasSearch['typeEtablissement'] = $datasForm['typeEtablissement'];
                $datasSearch['academie'] = $datasForm['academie'];
                $datasSearch['departement'] = $datasForm['departement'];
                $datasSearch['commune'] = $datasForm['commune'];
                $datasSearch['etablissement'] = $datasForm['etablissement'];
                $datasSearch['etatSaisie'] = $datasForm['etatSaisie'];
                $datasSearch['choix_etab'] = $datasForm['choix_etab'];
                // 0239686: Incohérence entre les profondeurs de consultation :  le filtre sous type delection ne saffiche jamais dans les resultats
                // electZone.typeEtablissement.id == 5 and typeElect.codeUrlById == constant('\\App\\Entity\\RefTypeElection::CODE_URL_ASS_ATE')
                if( isset($datasForm['typeEtablissement']) && $datasForm['typeEtablissement']->getId()==5 && $hasSousTypeElect){
                    $datasSearch['sousTypeElection'] = $datasForm['sousTypeElection'];
                }

            } else {
                // ECT : rustine pour DGESCO, à corriger
                $arrayRequest = $this->request->request->all();

                //Contient ["academie"] (code); ["departement"] (numero); ["typeEtablissement"] (id); ["etatSaisie"]; ["choix_etab"]; ["commune"] (id); ["etablissement"] (uai); ["_token"]
                $arrayResultatZoneEtabType = $arrayRequest['resultatZoneEtabType'];
                $datasSearch['academie'] = $em->getRepository(RefAcademie::class)->find($arrayResultatZoneEtabType['academie']);
                $datasSearch['departement'] = $em->getRepository(RefDepartement::class)->find($arrayResultatZoneEtabType['departement']);
                $datasSearch['commune'] = $em->getRepository(RefCommune::class)->find($arrayResultatZoneEtabType['commune']);
                $datasSearch['typeEtablissement'] = $em->getRepository(RefTypeEtablissement::class)->find($arrayResultatZoneEtabType['typeEtablissement']);
                $datasSearch['choix_etab'] = (array_key_exists('choix_etab', $arrayResultatZoneEtabType) && $arrayResultatZoneEtabType['choix_etab'] == "1") ? true : false;
                $datasSearch['etablissement'] = $em->getRepository(RefEtablissement::class)->find($arrayResultatZoneEtabType['etablissement']);
                $datasSearch['etatSaisie'] = $arrayResultatZoneEtabType['etatSaisie'];
            }

            // si demande de liste d'établissement sans précision, on restreint à la zone du User
            if ($datasSearch['choix_etab'] && empty($datasSearch['commune'])) {
                $zoneUser = ($user->getIdZone() != null) ? EpleUtils::getZone($em, $user->getIdZone()) : null;
                if ($zoneUser instanceof RefDepartement) {
                    $datasSearch['academie'] = $zoneUser->getAcademie();
                    $datasSearch['departement'] = $zoneUser;
                } else {
                    if ($zoneUser instanceof RefAcademie) {
                        $datasSearch['academie'] = $zoneUser;
                    }
                }
            }
        }

        if (!empty($datasSearch['departement'])) {
            $zone = $datasSearch['departement'];

        } elseif (!empty($datasSearch['academie'])) {
            $zone = $datasSearch['academie'];
        } else {
            $zone = 'nationale';
        }

        $etatSaisie = $datasSearch['etatSaisie'];
        if (!is_array($etatSaisie)) {
            $etatSaisie = array(
                $etatSaisie
            );
        }
        $params['etatSaisieTxt'] = EleEtablissement::getLibellesEtatsSaisie($etatSaisie);

        // YME - 0146066
        if(!empty($datasSearch['typeEtablissement'])
            && RefTypeEtablissement::ID_TYP_EREA_ERPD == $datasSearch['typeEtablissement']->getId()
            && RefTypeElection::ID_TYP_ELECT_ASS_ATE == $params['typeElect']->getId()
            && null != $datasSearch['sousTypeElection']){
            $idSousTypeElect = $datasSearch['sousTypeElection']->getId();
        }

        if ($datasSearch['choix_etab']) {
            // Recherche par établissement
            if (!empty($datasSearch['etablissement'])) {
                $zone = $datasSearch['etablissement'];
            } else if (!empty($datasSearch['commune'])) {
                $zone = $datasSearch['commune'];
            }

            // Filtrage selon le périmètre de l'utilisateur
            if (in_array($user->getProfil()->getCode(), $profilsLimitEtab)) {

                if (null != $user->getPerimetre()->getEtablissements()) {
                    $zone = array();
                    foreach ($user->getPerimetre()->getEtablissements() as $etab) {
                        if ((!empty($datasSearch['commune']) && $etab->getCommune()->getId() == $datasSearch['commune']->getId()) || empty($datasSearch['commune'])) {
                            $zone[] = $etab;
                        }
                    }
                }
            }

            // 014E exclus des EREA-ERPD dans les résultats ASS et ATE et PEE && (($typeEtab != null && $typeEtab->getCode() != RefTypeEtablissement::CODE_EREA_ERPD) || $typeEtab == null)
            $isEreaErpdExclus = false;
            if (($campagne->getTypeElection()->getId() == RefTypeElection::ID_TYP_ELECT_ASS_ATE ||$campagne->getTypeElection()->getId() == RefTypeElection::ID_TYP_ELECT_PEE)
                && ($datasSearch['typeEtablissement'] != null && $datasSearch['typeEtablissement']->getCode() != RefTypeEtablissement::CODE_EREA_ERPD || $datasSearch['typeEtablissement'] == null)) {
                $isEreaErpdExclus = true;
            }

            //$logger->info('DATA SEARCH DEPT : '.$datasSearch['departement']->getNumero());

            if (empty($datasSearch['etablissement'])) {
                if (!empty($datasSearch['departement'])) {
                    $zone = $datasSearch['departement'];
                    $isPeriodeP2Ter = $campagne->isP2Ter($joursCalendairesIen, $joursCalendaires, $datasSearch['departement']->getAcademie());
                }
                if (!empty($datasSearch['commune'])) {
                    $zone = $datasSearch['commune'];
                    $isPeriodeP2Ter = $campagne->isP2Ter($joursCalendairesIen, $joursCalendaires, $datasSearch['commune']->getDepartement()->getAcademie());
                }
                if(empty($datasSearch['departement']) && empty($datasSearch['commune'])) {
                    $isPeriodeP2Ter = $campagne->isP2Ter($joursCalendairesIen, $joursCalendaires);
                }
                $params['P2Ter'] = $isPeriodeP2Ter;

                // Selection de tous les établissements
                $params['lst_electEtab'] = $em->getRepository(EleEtablissement::class)->findByCampagneTypeEtabZone($campagne, $datasSearch['typeEtablissement'], $zone, $etatSaisie, $user, $idSousTypeElect, $isEreaErpdExclus);

                // Rajout du code url pour le sous-type d'élection
                // YME un peu tricky mais l'objet sousTypeElection n'est pas récupéré par SQL
                foreach($params['lst_electEtab'] as &$eltEletab){
                    if(null != $eltEletab['sousTypeElectionId']){
                        $sousTypeElection = new RefSousTypeElection();
                        $sousTypeElection->setId($eltEletab['sousTypeElectionId']);
                        $eltEletab['codeUrlSousTypeElect'] = $sousTypeElection->getCodeUrlById();
                    }
                }

                if (!empty($datasSearch['commune'])) {
                    $params['commune_selectionne'] = $datasSearch['commune']->getId();
                }
            } else {

                if (!$user->canConsultEtab($datasSearch['etablissement'], $params['typeElect'])) {
                    throw new AccessDeniedException();
                }
                $params = array_merge($params, $this->getParametersForConsultationResultatsEtablissement($campagne, $datasSearch['etablissement']->getUai(), $datasSearch['sousTypeElection']));

                $isPeriodeP2Ter = $campagne->isP2Ter($joursCalendairesIen, $joursCalendaires, $datasSearch['etablissement']->getCommune()->getDepartement()->getAcademie());
                $params['P2Ter'] = $isPeriodeP2Ter;

                // Vérifie si l'utilisateur a le droit de modifier les résultats // mantis 0122204
                if ($user->canEditEtab($datasSearch['etablissement'], $campagne, $joursCalendaires)) {
                    $params['accesSaisie'] = true;
                }

                // Vérifie si l'utilisateur a le droit de transmettre les résultats pour validation
                if ($user->canTransmitResultsEtab($datasSearch['etablissement'], $campagne->getTypeElection()) && $campagne->isOpenSaisie($user->getPerimetre()->getAcademies(), $joursCalendaires)) // mantis 0122204 accès au bouton transmettre en periode de saisie seulement
                {
                    $params['accesTransmission'] = true;
                }

                // Vérification des droits d'accès au PV
                // YME - 145679
                if ($user->canGetPVRempli($datasSearch['etablissement'])) {
                    $params['accesPVRempli'] = true;
                }

                // PV tirage au sort accessible aussi pendant la période de validation si pas de saisie du tirage au sort effectué
                if ($user->canSaisieTirageAuSort($datasSearch['etablissement'], $params['electEtablissement'], $campagne, $joursCalendaires, $joursCalendairesIen) || $user->canGetPVTirageAuSortInAndAfterValidation($datasSearch['etablissement'], $params['electEtablissement'], $campagne, $joursCalendaires)) {
                    $params['accesPVTirageAuSort'] = true;
                }

                // 0167899 recherche par etablissement on retourne au tableau de bord
                $params['retourLstRech'] = 0;
            }
        } else {

            // Recherche par département / académie
            if (!empty($datasSearch['typeEtablissement'])) {
                $codeUrlTypeEtab = $datasSearch['typeEtablissement']->getCodeUrlById();
                $params['codeUrlTypeEtab'] = $codeUrlTypeEtab;
                $this->request->getSession()->set('codeUrlTypeEtab', $params['codeUrlTypeEtab']);
            }

            // Filtrage selon le périmètre de l'utilisateur
            // Etablissements
            if (in_array($user->getProfil()->getCode(), $profilsLimitEtab)) {
                if (null != $user->getPerimetre()->getEtablissements()) {
                    $zone = array();
                    foreach ($user->getPerimetre()->getEtablissements() as $etab) {
                        if (
                            (
                                ((!empty($datasSearch['academie']) && $etab->getCommune()->getDepartement()->getAcademie()->getCode() == $datasSearch['academie']->getCode()) || empty($datasSearch['academie'])) || ((!empty($datasSearch['departement']) && $etab->getCommune()->getDepartement()->getNumero() == $datasSearch['departement']->getNumero()) || empty($datasSearch['departement'])) || ((!empty($datasSearch['commune']) && $etab->getCommune()->getId() == $datasSearch['commune']->getId()) || empty($datasSearch['commune']))
                            ) && (!empty($datasSearch['typeEtablissement']) && $etab->getTypeEtablissement()->getId() == $datasSearch['typeEtablissement']->getId()) || empty($datasSearch['typeEtablissement'])
                        ) {
                            $zone[] = $etab;
                        }
                    }
                }
                // EVOL 013E EX_012 passer le sous-type d'élection
                $params['lst_electEtab'] = $em->getRepository(EleEtablissement::class)->findByCampagneTypeEtabZone($campagne, $datasSearch['typeEtablissement'], $zone, $etatSaisie, $user, $idSousTypeElect);
            } else {
                $params = array_merge($params, $this->getParametersForConsultationResultatsZone($campagne, $zone, $user, $codeUrlTypeEtab, $etatSaisie, $idSousTypeElect));
            }
        }

        // Mise à jour des variables de session
        $this->request->getSession()->set('select_academie', ($datasSearch['academie'] instanceof RefAcademie) ? $datasSearch['academie']->getIdZone() : null);
        $this->request->getSession()->set('select_departement', ($datasSearch['departement'] instanceof RefDepartement) ? $datasSearch['departement']->getIdZone() : null);
        $this->request->getSession()->set('select_commune', ($datasSearch['commune'] instanceof RefCommune) ? $datasSearch['commune']->getId() : null);
        $this->request->getSession()->set('select_typeEtab', ($datasSearch['typeEtablissement'] instanceof RefTypeEtablissement) ? $datasSearch['typeEtablissement']->getId() : null);
        $this->request->getSession()->set('select_etatSaisie', $datasSearch['etatSaisie']);
        $this->request->getSession()->set('select_choix_etab', $datasSearch['choix_etab']);
        $this->request->getSession()->set('select_etablissement', ($datasSearch['etablissement'] instanceof RefEtablissement) ? $datasSearch['etablissement']->getUai() : null);
        $this->request->getSession()->set('select_sousTypeElect', ($datasSearch['sousTypeElection'] instanceof RefSousTypeElection) ? $datasSearch['sousTypeElection']->getId() : null);

        $params['form'] = $form->createView();

        return $params;

    }


    /**
     * Consultation des résultats à valider
     * Pour les DSDEN et rectorats
     *
     * @param String $codeUrlTypeElect : code type élection
     */
    public function consultationResultatsTransmisZoneAction($codeUrlTypeElect, $numDept) {

        $em = $this->doctrine->getManager();

        // on réinitialise la session et on y met tout ce qu'il faut
        $this->request->getSession()->remove('select_academie');
        $this->request->getSession()->remove('select_departement');
        $this->request->getSession()->remove('select_typeEtab');
        $this->request->getSession()->remove('select_commune');
        $this->request->getSession()->remove('select_etablissement');
        $this->request->getSession()->remove('select_sousTypeElect');

        $this->request->getSession()->set('select_etatSaisie', array(
            EleEtablissement::ETAT_TRANSMISSION
        ));
        $this->request->getSession()->set('select_choix_etab', true);

        $this->request->getSession()->set('select_typeEtab', $this->request->getSession()->get('select_typeEtablissement'));

        $typeElectionId = RefTypeElection::getIdRefTypeElectionByCodeUrl($codeUrlTypeElect);
        $typeElection = $em->getRepository(RefTypeELection::class)->find($typeElectionId);
        if (null == $typeElection) {
            throw $this->createNotFoundException('Type élection inconnu');
        }

        if ($codeUrlTypeElect == RefTypeElection::CODE_URL_A_ATTE || $codeUrlTypeElect == RefTypeElection::CODE_URL_SS) {
            $this->request->getSession()->set('select_sousTypeElect', RefSousTypeElection::getIdRefSousTypeElectionByCodeUrl($codeUrlTypeElect));
        }

        $user = $this->getUser();
        if (!$user->canConsult($typeElection)) {
            throw new AccessDeniedException();
        }

        $campagne = $em->getRepository(EleCampagne::class)->getLastCampagne($typeElectionId);
        if (empty($campagne)) {
            throw $this->createNotFoundException('Les résultats de l\'établissement n\'ont pas été trouvés car la campagne est inconnue.');
        }

        $zoneUser = ($user->getIdZone() != null) ? EpleUtils::getZone($em, $user->getIdZone()) : null;
        if ($zoneUser instanceof RefDepartement) {
            $this->request->getSession()->set('select_academie', $zoneUser->getAcademie()
                ->getCode());
            $this->request->getSession()->set('select_departement', $zoneUser->getNumero());
        } else
            if ($zoneUser instanceof RefAcademie) {
                $this->request->getSession()->set('select_academie', $zoneUser->getCode());
            }

        // mettre en session le flag de retour vers le tableau de bord deplié
        if ($numDept != null && $numDept != 0) {
            $this->request->getSession()->set('tdbDeplieRetour', true);
            $this->request->getSession()->set('select_departement', $numDept);
        }

        // Et on envoie la page de résultats
        $params = $this->getParametersRecherche($user, $campagne);
        $params['erreurs'] = $this->getParameter('erreurs');
        $params['warning'] = $this->getParameter('warning');

        return $this->render('resultat/indexConsultationResultats.html.twig', $params);
    }

    /**
     * Fonction permettant d'afficher les résultats d'un établissement pour un type d'élection
     *
     * @param String $codeUrlTypeElect: code type élection
     * @param String $uai: identifiant établissement
     * @param String $fileUpload : indique si l'upload s'est effectué correctement
     */
    public function consultationResultatsEtablissementAction($codeUrlTypeElect, $uai, $fileUpload = false, $retourLstRech = 0, $fromEdit = 0) {

        $em = $this->doctrine->getManager();

        $joursCalendaires = $this->getParameter('jours_calendaires');
        $joursCalendairesIen = $this->getParameter('jours_calendaires_ien');
        $this->request->getSession()->set('tdbRetour', false);
        $typeElectionId = RefTypeElection::getIdRefTypeElectionByCodeUrl($codeUrlTypeElect);
        $sousTypeElection = null;
        if(null == $typeElectionId || $codeUrlTypeElect == RefSousTypeElection::CODE_URL_A_ATTE || $codeUrlTypeElect == RefSousTypeElection::CODE_URL_SS){
            $sousTypeElectionId = RefSousTypeElection::getIdRefSousTypeElectionByCodeUrl($codeUrlTypeElect);
            $sousTypeElection = $em->getRepository(RefSousTypeElection::class)->find($sousTypeElectionId);
            $typeElection = $sousTypeElection->getTypeElection();
            $typeElectionId = $typeElection->getId();
        }else{
            $typeElection = $em->getRepository(RefTypeELection::class)->find($typeElectionId);
        }

        if (null == $typeElection && null == $sousTypeElection) {
            throw $this->createNotFoundException('Type élection '.$codeUrlTypeElect.' inconnu');
        }

        $campagne = $em->getRepository(EleCampagne::class)->getLastCampagne($typeElectionId);
        if (empty($campagne)) {
            throw $this->createNotFoundException('Les résultats de l\'établissement n\'ont pas été trouvés car la campagne est inconnue.');
        }

        $etablissement = $em->getRepository(RefEtablissement::class)->find($uai);
        $user = $this->getUser();

        if (!$user->canConsultEtab($etablissement, $typeElection)) {
            throw new AccessDeniedException();
        }

        $params = $this->getParametersForConsultationResultatsEtablissement($campagne, $uai, $sousTypeElection);
        $isPeriodeP2Ter = $campagne->isP2Ter($joursCalendairesIen, $joursCalendaires, $etablissement->getCommune()->getDepartement()->getAcademie());

        // 0154831 changement de message des résultats transmis pour DE/CE
        $params['isDeCe'] = ($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_CE || $user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DE);
        $params['isIEN'] = ($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_IEN);
        $params['isDE'] = ($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DE);
        $params['isDSDENorRect'] = ($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DSDEN || $user->getProfil()->getCode() == RefProfil::CODE_PROFIL_RECT);

        // Vérifie si l'utilisateur a le droit de modifier les résultats
        if ($user->canEditEtab($etablissement, $campagne, $joursCalendaires)) {
            $params['accesSaisie'] = true;
        }

        // Vérifie si l'utilisateur a le droit de transmettre les résultats pour validation
        if ($user->canTransmitResultsEtab($etablissement, $typeElection) && $campagne->isOpenSaisie($user->getPerimetre()->getAcademies(), $joursCalendaires)) // mantis 0122204 accès au bouton transmettre en periode de saisie seulement
        {
            $params['accesTransmission'] = true;
        }

        // Vérifie si l'utilisateur a le droit de valider les résultats d'un établissement
        // 014E RG_SAISIE_TIRAGE-AU-SORT_IEN_010
        if ($user->canValidateEtab ( $etablissement, $campagne, $joursCalendaires )) {

            ///////////////////////////////////////////////////
            ///    EVOLUTION 016E dévalidation tirage au sort
            ///////////////////////////////////////////////////

            //Periode P2Bis avec tirage au sort non saisi => consultation uniquement
            $params ['accesValidation'] = (
                ($params ['electEtablissement']->getIndDeficit() == 1 || $params ['electEtablissement']->getIndCarence() == 1)
                && $params['electEtablissement']->getIndTirageSort() == 0
                && $params['electEtablissement']->getParticipation()->getNbSiegesSort() == null
                && $typeElectionId == RefTypeElection::ID_TYP_ELECT_PARENT
                && $etablissement->getTypeEtablissement()->getId() == RefTypeEtablissement::ID_TYP_1ER_DEGRE
                && $params['electEtablissement'] != EleEtablissement::ETAT_VALIDATION
                && $campagne->isP2Bis($joursCalendairesIen, $joursCalendaires, $etablissement->getCommune()->getDepartement()->getAcademie())
            ) ? false : true;

            //On vérifie si on est dans la péeriode P2Ter (Meme s'il y'a pas eu de validation de tirage au sort par l'IEN
            //on affiche quand meme la ligne nombre de sieges pourvus par tirage au sort qui sera vide)
            // !!! uniquement sur cette péeriode
            $params ['accessDevalidationSort'] = (
                (
                    ($params ['electEtablissement']->getIndDeficit() == 1 || $params ['electEtablissement']->getIndCarence() == 1)
                    && $params['electEtablissement']->getIndTirageSort() == 1
                    && is_numeric($params['electEtablissement']->getParticipation()->getNbSiegesSort())
                    && $typeElectionId == RefTypeElection::ID_TYP_ELECT_PARENT
                    && $etablissement->getTypeEtablissement()->getId() == RefTypeEtablissement::ID_TYP_1ER_DEGRE
                    && $params['electEtablissement'] != EleEtablissement::ETAT_VALIDATION
                ) or (
                    $isPeriodeP2Ter
                    && ($params ['electEtablissement']->getIndDeficit() == 1 || $params ['electEtablissement']->getIndCarence() == 1)
                    && $params['electEtablissement']->getIndTirageSort() == 0
                    && $params['electEtablissement']->getParticipation()->getNbSiegesSort() == null
                    && $typeElectionId == RefTypeElection::ID_TYP_ELECT_PARENT
                    && $etablissement->getTypeEtablissement()->getId() == RefTypeEtablissement::ID_TYP_1ER_DEGRE
                    && $params['electEtablissement'] != EleEtablissement::ETAT_VALIDATION
                )
                // SESAM 0316056 (afficher la ligne apres un retour pour anomalie)
                or (
                    ($params ['electEtablissement']->getIndDeficit() == 1 || $params ['electEtablissement']->getIndCarence() == 1)
                    && $params['electEtablissement']->getIndTirageSort() == 1
                    && $params['electEtablissement']->getParticipation()->getNbSiegesSort() == null
                    && $params['isDSDENorRect']
                    && $typeElectionId == RefTypeElection::ID_TYP_ELECT_PARENT
                    && $etablissement->getTypeEtablissement()->getId() == RefTypeEtablissement::ID_TYP_1ER_DEGRE
                    && $params['electEtablissement'] != EleEtablissement::ETAT_VALIDATION
                )
            ) ? true : false ;
        }

        // Vérifie si l'utilisateur a le droit de dévalider les résultats d'un établissement
        if ($user->canDevalidateEtab($etablissement, $campagne, $joursCalendaires)) {
            $params['accesDevalidation'] = true;
        }
        // Vérification des droits d'accès au PV        
        if ($user->canGetPVRempli($etablissement)) {
            $params['accesPVRempli'] = true;
        }
        if ($user->canUploadPVSigne($etablissement)) {
            $params['uploadPVSigne'] = true;
        }
        if ($user->canGetPVSigne($etablissement) && $params['electEtablissement']->getFichier() != null) {
            $params['accesPVSigne'] = true;
        }

        // PV tirage au sort accessible aussi pendant la période de validation si pas de saisie du tirage au sort effectué
        // 014E RG_SAISIE_TIRAGE-AU-SORT_IEN_005 RG_SAISIE_TIRAGE-AU-SORT_IEN_006
        if ($user->canSaisieTirageAuSort($etablissement, $params['electEtablissement'], $campagne, $joursCalendaires, $joursCalendairesIen) || $user->canGetPVTirageAuSortInAndAfterValidation($etablissement, $params['electEtablissement'], $campagne, $joursCalendaires)) {
            $params['accesPVTirageAuSort'] = true;
        }

        // Indique si l'upload du PV s'est bien passé
        if ($fileUpload)
            $params['fileUpload'] = true;

        // 014E retour vers la liste des établissements
        $params['retourLstRech'] = $retourLstRech;

        $this->request->getSession()->set('select_etablissement', $uai); // nécessaire pour les exports
        $this->request->getSession()->set('select_choix_etab', true);
        $this->request->getSession()->set('dept_num', $etablissement->getCommune()->getDepartement()->getNumero());
        $params['erreurs'] = $this->getParameter('erreurs');
        $params['warning'] = $this->getParameter('warning');
        $params['fromEdit'] = $fromEdit;
        $form = $this->createForm(NbSiegesTirageAuSortType::class, null);
        $params['formTirage'] = $form->createView();
        return $this->render('resultat/indexConsultationResultats.html.twig', $params);
    }

    /**
     * Change l'état d'avancement de la validation des résultats d'un établissement
     *
     *
     * @param string $etablissementUai
     * @param string $typeElectionId
     * @param string $etat
     * @throws AccessDeniedException
     */
    public function changementEtatEleEtabAction(ConsolidationService $consolidationService, $etablissementUai, $codeUrlTypeElect, $etat, $retourLstRech) {

        $em = $this->doctrine->getManager();

        $joursCalendaires = $this->getParameter('jours_calendaires');

        $typeElectionId = RefTypeElection::getIdRefTypeElectionByCodeUrl($codeUrlTypeElect);
        $sousTypeElection = null;
        if(null == $typeElectionId || $codeUrlTypeElect == RefSousTypeElection::CODE_URL_A_ATTE || $codeUrlTypeElect == RefSousTypeElection::CODE_URL_SS){
            $sousTypeElectionId = RefSousTypeElection::getIdRefSousTypeElectionByCodeUrl($codeUrlTypeElect);
            $sousTypeElection = $em->getRepository(RefSousTypeElection::class)->find($sousTypeElectionId);
            $typeElection = $sousTypeElection->getTypeElection();
            $typeElectionId = $typeElection->getId();
        } else {
            $typeElection = $em->getRepository(RefTypeElection::class)->find($typeElectionId);
        }

        if (empty($typeElection) && null != $sousTypeElection) { throw $this->createNotFoundException('Le type d\'élection '.$codeUrlTypeElect.' n\'a pas été trouvé.'); }
        $this->request->getSession()->set('typeElectionId', $typeElection->getId());

        $campagne = $em->getRepository(EleCampagne::class)->getLastCampagneNonArchive($typeElection);
        if (empty($campagne)) {
            throw $this->createNotFoundException('Aucune campagne ouverte pour ce type d\'élection n\'a été trouvée.');
        }

        $etablissement = $em->getRepository(RefEtablissement::class)->findOneByUai($etablissementUai);
        if (empty($etablissement)) {
            throw $this->createNotFoundException('L\'établissement  n\'a pas été trouvé.');
        }

        $eleEtab = $em->getRepository(EleEtablissement::class)->getEleEtablissementGlobale($campagne, $etablissement, $sousTypeElection);

        // Vérification des droits d'accès à la transmission des résultats
        $user = $this->getUser();

        if (!$user->canTransmitResultsEtab($etablissement, $typeElection) && $etat == EleEtablissement::ETAT_TRANSMISSION) {
            throw new AccessDeniedException();
        }
        // Vérification des droits d'accès à la validation des résultats transmis
        if (!$user->canValidateEtab($etablissement, $campagne, $joursCalendaires) && $etat == EleEtablissement::ETAT_VALIDATION) {
            throw new AccessDeniedException();
        }

        // Consolidation
        // Evol 013E RG_VALID_04_2
        if ($etat == EleEtablissement::ETAT_VALIDATION
            && !($eleEtab->getEtablissement()->getTypeEtablissement()->getCode() == RefTypeEtablissement::CODE_EREA_ERPD
                && ($typeElectionId == RefTypeElection::ID_TYP_ELECT_ASS_ATE || $typeElectionId == RefTypeElection::ID_TYP_ELECT_PEE))
        ){
            $consolidationService->consolidationEleEtab($eleEtab);
        }

        // Retour pour Anomalie (passage de l'état Transmis à l'état Saisi)
        if ($eleEtab->isTransmis() && $etat == EleEtablissement::ETAT_SAISIE && null != $eleEtab->getFichier()) {

            // Renommage du fichier
            $nomFichier = $eleEtab->getFichier()->getUrl();
            $contenuNomFichier = explode('.', $nomFichier);
            $nomFichier = $contenuNomFichier[0] . '_Invalidé.' . $contenuNomFichier[1];

            // Renommage du fichier côté disque
            echo $eleEtab->getFichier()->getWebPath() . '<br/>';
            echo $eleEtab->getFichier()->getUploadRootDir() . '/' . $nomFichier . '<br/>';
            rename($eleEtab->getFichier()->getWebPath(), $eleEtab->getFichier()->getUploadRootDir() . '/' . $nomFichier);

            // Renommage du PV à Invalidé côté base
            $eleEtab->getFichier()->setUrl($nomFichier);
        }

        // Changement d'état
        $eleEtab->setValidation($etat);
        $em->persist($eleEtab);
        $em->flush();

        $array = array(
            'uai' => $etablissementUai
        );

        if(null != $sousTypeElection){
            $array['codeUrlTypeElect'] = $sousTypeElection->getCodeUrlById();
        } else if( !empty($typeElection) ){
            $array['codeUrlTypeElect'] = $typeElection->getCodeUrlById();
        }

        // retour à la liste des établissements dans la recherche par étab
        if($retourLstRech){
            $array['retourLstRech'] = $retourLstRech;
        }

        return $this->redirect($this->generateUrl('ECECA_resultats_etablissement', $array));
    }

    public function devalidationTirageAuSortEleEtabAction($etablissementUai, $codeUrlTypeElect, $retourLstRech = 0) {

        $em = $this->doctrine->getManager();

        $typeElectionId = RefTypeElection::getIdRefTypeElectionByCodeUrl($codeUrlTypeElect);
        $sousTypeElection = null;
        if (null == $typeElectionId) {
            $sousTypeElectionId = RefSousTypeElection::getIdRefSousTypeElectionByCodeUrl($codeUrlTypeElect);
            $sousTypeElection = $em->getRepository(RefSousTypeElection::class)->find($sousTypeElectionId);
            $typeElection = $sousTypeElection->getTypeElection();
            $typeElectionId = $typeElection->getId();
        } else {
            $typeElection = $em->getRepository(RefTypeELection::class)->find($typeElectionId);
        }

        if (null == $typeElection && null == $sousTypeElection) {
            throw $this->createNotFoundException('Type élection '.$codeUrlTypeElect.' inconnu');
        }

        $campagne = $em->getRepository(EleCampagne::class)->getLastCampagne($typeElectionId);
        if (empty($campagne)) {
            throw $this->createNotFoundException('Les résultats de l\'établissement n\'ont pas été trouvés car la campagne est inconnue.');
        }

        $etablissement = $em->getRepository(RefEtablissement::class)->find($etablissementUai);

        $array = array('uai'=>$etablissementUai, 'codeUrlTypeElect'=> $typeElection->getCodeUrlById());

        $form = $this->createForm(NbSiegesTirageAuSortType::class, null);
        if ($this->request->getMethod() == 'POST') {
            $form->handleRequest($this->request);
            if ($form->isSubmitted() && $form->isValid()) {

                $datasForm = $form->getData();

                $nbSiegesTirageAuSort = $datasForm['nbSiegesTirageAuSort'];
                $params = $this->getParametersForConsultationResultatsEtablissement($campagne, $etablissementUai, $sousTypeElection);

                $params['etablissement'] = $etablissement;

                // recuperation de eleEtablissement, eleParticipation
                $datasEleEtablissement = $params['electEtablissement'];
                $datasParticipation = $datasEleEtablissement->getParticipation();

                //$datasParticipation->setNbSiegesSort($nbSiegesTirageAuSort);
                $em->getRepository(EleEtablissement::class)->updateIndTirageSort(
                    $datasEleEtablissement->getId(),
                    EleEtablissement::ETAT_TIRAGE_AU_SORT_RECTORAT_DSDEN
                );

                $em->getRepository(EleParticipation::class)->updateNbSiegesSort(
                    $datasParticipation->getId(),
                    $nbSiegesTirageAuSort
                );

                $em->clear();
                // mantis 146200 : suppression des eleAlertes au moment de l'enregistrement du nbSiegesTirageAuSort mais plus au téléchargement du PV de tirage au sort
                $listeAlerte = $em->getRepository(EleAlerte::class)->findBy(array('electionEtab'=>$datasEleEtablissement->getId()));
                if (count($listeAlerte) > 0) {
                    foreach ($listeAlerte as $alerte) {
                        $em->remove($alerte);
                    }
                    $em->flush();
                }
            }
        }
        return $this->redirect($this->generateUrl('ECECA_resultats_etablissement', $array));
    }

    /**
     * Change l'état d'avancement de la dévalidation des résultats d'un établissement
     *
     *
     * @param string $etablissementUai
     * @param string $typeElectionId
     * @param string $etat
     * @throws AccessDeniedException
     */
    public function changementEtatEleEtabDevalidationAction(ConsolidationService $consolidationService, $etablissementUai, $codeUrlTypeElect, $etat, $retourLstRech) {
        $em = $this->doctrine->getManager();

        $joursCalendaires = $this->getParameter('jours_calendaires');

        $typeElectionId = RefTypeElection::getIdRefTypeElectionByCodeUrl($codeUrlTypeElect);
        $sousTypeElection = null;
        if (null == $typeElectionId || $codeUrlTypeElect == RefSousTypeElection::CODE_URL_A_ATTE || $codeUrlTypeElect == RefSousTypeElection::CODE_URL_SS){
            $sousTypeElectionId = RefSousTypeElection::getIdRefSousTypeElectionByCodeUrl($codeUrlTypeElect);
            $sousTypeElection = $em->getRepository(RefSousTypeElection::class)->find($sousTypeElectionId);
            $typeElection = $sousTypeElection->getTypeElection();
            $typeElectionId = $typeElection->getId();
        } else {
            $typeElection = $em->getRepository(RefTypeElection::class)->find($typeElectionId);
        }

        if (empty($typeElection) && null != $sousTypeElection) { throw $this->createNotFoundException('Le type d\'élection '.$codeUrlTypeElect.' n\'a pas été trouvé.'); }
        $this->request->getSession()->set('typeElectionId', $typeElection->getId());

        $campagne = $em->getRepository(EleCampagne::class)->getLastCampagneNonArchive($typeElection);
        if (empty($campagne)) {
            throw $this->createNotFoundException('Aucune campagne ouverte pour ce type d\'élection n\'a pas été trouvé.');
        }

        $etablissement = $em->getRepository(RefEtablissement::class)->findOneByUai($etablissementUai);
        if (empty($etablissement)) {
            throw $this->createNotFoundException('L\'établissement  n\'a pas été trouvé.');
        }

        $eleEtab = $em->getRepository(EleEtablissement::class)->getEleEtablissementGlobale($campagne, $etablissement, $sousTypeElection);

        // Vérification des droits d'accès à la transmission des résultats
        $user = $this->getUser();

        // Vérification des droits d'accès à la dévalidation des résultats validés
        if (!$user->canDevalidateEtab($etablissement, $campagne, $joursCalendaires) && $etat == EleEtablissement::ETAT_TRANSMISSION) {
            throw new AccessDeniedException();
        }

        // Deconsolidation
        if ($etat == EleEtablissement::ETAT_TRANSMISSION
            && !($eleEtab->getEtablissement()->getTypeEtablissement()->getCode() == RefTypeEtablissement::CODE_EREA_ERPD
                && ($typeElectionId == RefTypeElection::ID_TYP_ELECT_ASS_ATE || $typeElectionId == RefTypeElection::ID_TYP_ELECT_PEE))
        ){
            $consolidationService->deconsolidationEleEtab($eleEtab, $campagne);
        }

        // Changement d'état
        $eleEtab->setValidation($etat);
        $em->persist($eleEtab);
        $em->flush();

        $array = array(
            'uai' => $etablissementUai
        );

        if (null != $sousTypeElection){
            $array['codeUrlTypeElect'] = $sousTypeElection->getCodeUrlById();
        } else if ( !empty($typeElection) ){
            $array['codeUrlTypeElect'] = $typeElection->getCodeUrlById();
        }

        // retour à la liste des établissements dans la recherche par etab
        if ($retourLstRech){
            $array['retourLstRech'] = $retourLstRech;
        }

        return $this->redirect($this->generateUrl('ECECA_resultats_etablissement', $array));
    }

    /**
     * Validation en masse des résultats des établissements
     *
     * @param string $etablissementUai
     * @param string $typeElectionId
     * @param string $etat
     * @throws AccessDeniedException
     */
    public function massValidationAction(ConsolidationService $consolidationService) {
        $em = $this->doctrine->getManager();

        // Vérification des droits d'accès à la validation en masse des résultats
        $user = $this->getUser();
        if (!$user->canMassValidate()) {
            throw new AccessDeniedException();
        }

        $listEleEtabsConso = array(); // liste des eleEtabs dont la consolidation est à faire / MàJ
        $listEleEtabsValid = array(); // liste des eleEtabs à valider

        // Récupération des informations ele_etablissements
        foreach($this->request->request as $idEleEtab=>$on){
            // YME - DEFECT HPQC #211
            if (is_int($idEleEtab)){
                $eleEtab = $em->getRepository(EleEtablissement::class)->getEleEtablissementGlobaleById($idEleEtab);
                if (  ! ( ($eleEtab->getEtablissement()->getTypeEtablissement()->getCode() == RefTypeEtablissement::CODE_EREA_ERPD)
                    && (($eleEtab->getCampagne()->getTypeElection()->getId() == RefTypeElection::ID_TYP_ELECT_ASS_ATE)
                        || ($eleEtab->getCampagne()->getTypeElection()->getId() == RefTypeElection::ID_TYP_ELECT_PEE)) ) )
                {
                    $listEleEtabsConso[] = $eleEtab;
                }
                $listEleEtabsValid[] = $eleEtab->getId();
            }
        }

        // Consolidation en masse
        $consolidationService->massConsolidationEleEtab($listEleEtabsConso);

        // Validation en masse
        $em->getRepository(EleEtablissement::class)->massValideEtabs($listEleEtabsValid);

        // Redirection vers la liste des résultats par département
        return $this->redirect($this->generateUrl('ECECA_tableau_bord'));
    }


    /**
     * Fonction permettant l'édition au format Excel des résultats d'une zone ou d'un établissement
     *
     * @param string $codeUrlTypeElect
     * @throws AccessDeniedException
     */
    public function exportResultatsXLSAction($codeUrlTypeElect, $etablissementUai = 'tous') {
        $em = $this->doctrine->getManager();
        $user = $this->getUser();

        $joursCalendaires = $this->getParameter('jours_calendaires');

        $typeElectionId = RefTypeElection::getIdRefTypeElectionByCodeUrl($codeUrlTypeElect);
        $sousTypeElection = null;
        if (null == $typeElectionId || $codeUrlTypeElect == RefSousTypeElection::CODE_URL_A_ATTE || $codeUrlTypeElect == RefSousTypeElection::CODE_URL_SS) {
            $sousTypeElectionId = RefSousTypeElection::getIdRefSousTypeElectionByCodeUrl($codeUrlTypeElect);
            $sousTypeElection = $em->getRepository(RefSousTypeElection::class)->find($sousTypeElectionId);
            $typeElection = $sousTypeElection->getTypeElection();
            $typeElectionId = $typeElection->getId();
        } else {
            $typeElection = $em->getRepository(RefTypeElection::class)->find($typeElectionId);
        }

        if (empty($typeElection) && null != $sousTypeElection) {
            throw $this->createNotFoundException('Type élection inconnu');
        }

        if (!$user->canConsult($typeElection)) {
            throw new AccessDeniedException();
        }

        $campagne = $em->getRepository(EleCampagne::class)->getLastCampagne($typeElectionId);
        if (empty($campagne)) {
            throw $this->createNotFoundException('Les résultats de l\'établissement n\'ont pas été trouvés car la campagne est inconnue.');
        }

        if ($etablissementUai != 'tous') {
            $etablissement = $em->getRepository(RefEtablissement::class)->findOneByUai($etablissementUai);
            if (empty($etablissement)) {
                throw $this->createNotFoundException('L\'établissement  n\'a pas été trouvé.');
            }

            $eleEtab = $em->getRepository(EleEtablissement::class)->getEleEtablissementGlobale($campagne, $etablissement, $sousTypeElection);
            if (empty($eleEtab)) {
                throw $this->createNotFoundException('Les résultats de l\'établissement n\'ont pas encore été saisis.');
            }
        }

        //$params = unserialize($this->request->getSession()->get('params'));
        $params = $this->getParametersRecherche($user, $campagne);
        $params['typeElect'] = $typeElection;
        if ($sousTypeElection != null) {
            $params['sousTypeElect'] = $sousTypeElection;
        }

        $codeUrlTypeEtab = isset($params['codeUrlTypeEtab']) ? $params['codeUrlTypeEtab'] : 'tous';

        // On test si la recherche est effectué par zone ou par établissement
        $isZone = true;
        $hasModaliteVote = false;
        if (!empty($params['electZone'])) {
            $elect = $params['electZone'];

        } else {
            $isZone = false;
            $params['electEtablissement']=$eleEtab;
            $elect = $params['electEtablissement'];
        }

        $campagne = $elect->getCampagne();
        $participation = $elect->getParticipation();
        $resultats = $elect->getResultats();

        $resDetails = null;
        if ($elect instanceof EleEtablissement) {
            $resDetails = $em->getRepository(EleResultatDetail::class)->findByEleEtablissement($elect);
            $elect->setResultatsDetailles($resDetails);
        }


        // Génération du fichier Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $styleArray = array(
            'font' => array(
                'bold' => true
            )
        );
        $styleArrayTitre = array(
            'font' => array(
                'color' => array(
                    'rgb' => 'CC33CC'
                )
            )
        );
        $styleArrayItalic = array(
            'font' => array(
                'italic' => true
            )
        );
        if (!empty($params['nationale'])) {
            $libelle_recherche = 'Nationaux';
            $filtre_aca = 'Tous';
            $filtre_dept = 'Tous';
        } elseif (!empty($params['aca'])) {
            $libelle_recherche = "";

            //Determine l'académie de l'établissement (avant ou après fusion de certaines académie)
            $filtre_aca = $params['aca'];
            $dateDebutCampagne = new Datetime($campagne->getAnneeDebut() . "-01-01");
            if($filtre_aca->getDateDesactivation() <= $dateDebutCampagne) {
                $filtre_aca = $filtre_aca->getAcademieFusion();
            }
            if($filtre_aca != null) {
                $filtre_aca = $filtre_aca->getLibelle();
                $libelle_recherche = $filtre_aca;
            }
            $filtre_dept = 'Tous';
        } elseif (!empty($params['dept'])) {
            $libelle_recherche = $params['dept']->getLibelle();

            //Determine l'académie de l'établissement (avant ou après fusion de certaines académie)
            $filtre_aca = $params['dept']->getAcademie();
            $dateDebutCampagne = new Datetime($campagne->getAnneeDebut() . "-01-01");
            if($filtre_aca->getDateDesactivation() <= $dateDebutCampagne) {
                $filtre_aca = $filtre_aca->getAcademieFusion();
            }
            if($filtre_aca != null) {
                $filtre_aca = $filtre_aca->getLibelle();
            }

            $filtre_dept = $params['dept']->getLibelle();
        } else {
            $libelle_recherche = 'de l\'établissement ' . $elect->getEtablissement()->getLibelle() . ' (' . $elect->getEtablissement()->getUai() . ')';
            // si l'établissement est fermé on le mentionne dans l'excel
            if($elect->getEtablissement()->getActif()==null)
                $libelle_recherche = $libelle_recherche. ' (fermé)';
        }

        // Création du titre
        $typeElectionLibelle = ($sousTypeElection != null ? $sousTypeElection->getLibelle() : $typeElection->getLibelle());
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('A1', $typeElectionLibelle . ' - Résultats ' . $libelle_recherche);

        // Affiche des champs du filtre appliqué à la recherche
        if (!empty($params['nationale']) || !empty($params['aca']) || !empty($params['dept'])) {
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A4', 'Académie');
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A5', 'Département');
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A6', 'Type d\'établissement');
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('B4', $filtre_aca);
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('B5', $filtre_dept);
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('B6', ucwords($codeUrlTypeEtab));
        } else {
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A4', 'Type d\'établissement');
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A5', 'Catégorie');
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A6', 'Commune (Département)');
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('B4', $elect->getEtablissement()
                ->getTypeEtablissement()
                ->getLibelle());
            if ($elect->getEtablissement()->getTypePrioritaire()) {
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('B5', $elect->getEtablissement()
                    ->getTypePrioritaire()->getCode());
            } else {
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('B5', 'N/A');
            }
            if ($elect->getEtablissement()->getCommune()) {
                $libelle_com = $elect->getEtablissement()
                    ->getCommune()
                    ->getLibelle();
                $libelle_dep = $elect->getEtablissement()
                    ->getCommune()
                    ->getDepartement()
                    ->getLibelle();
            } else {
                $libelle_com = 'N/C';
                $libelle_dep = 'N/C';
            }
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('B6', $libelle_com . ' (' . $libelle_dep . ')');
        }

        // Rappel
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('A2', '- Rappel');
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('A3', 'Campagne');
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('B3', $campagne->getAnneeDebut() . ' - ' . $campagne->getAnneeFin());

        if (!empty($params['nationale']) || !empty($params['aca']) || !empty($params['dept'])) {
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A7', 'Nombre d\'établissements');
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('B7', $elect->getNbEtabTotal());
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A8', 'Nombre d\'établissements exprimés');
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('B8', $elect->getNbEtabExprimes());
        }
        // Participation
        if (!empty($participation)) {
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A11', '- Participation');
            $sheet->getStyle('B11')->applyFromArray($styleArray);
            $ligne = 12;

            //Ajout de la modalite de vote
            if($typeElection->getId() == RefTypeElection::ID_TYP_ELECT_PARENT) {
                if($isZone) {
                    $hasModaliteVote = true;
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('B'. $ligne++, 'Modalité de vote');
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('A'. $ligne, "Vote " . RefModaliteVote::LIBELLE_MODALITE_VOTE_URNE_CORRESPONDANCE);
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('B'. $ligne++, $participation->getConsolidationVoteUrneCorrespondance());
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('A'. $ligne, "Vote " . RefModaliteVote::LIBELLE_MODALITE_VOTE_CORRESPONDANCE);
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('B'. $ligne++, $participation->getConsolidationVoteCorrespondance());
                    $ligne++;
                } elseif (!$isZone && $elect->getIndCarence() == 0) {
                    $hasModaliteVote = true;
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('A'. $ligne, 'Modalité de vote');
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('B'. $ligne++, $participation->getModaliteVote() != null ? $participation->getModaliteVote()->getLibelle() : "");
                    $ligne++;
                }
            }

            $spreadsheet->setActiveSheetIndex(0)->setCellValue('B'. $ligne++, 'Résultats bruts');
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A'. $ligne, 'Nombre d\'inscrits');
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('B'. $ligne++, $participation->getNbInscrits());
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A'. $ligne, 'Nombre de votants');
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('B'. $ligne++, $participation->getNbVotants());
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A'. $ligne, 'Nombre de suffrages exprimés');
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('B'. $ligne++, $participation->getNbExprimes());
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A'. $ligne, 'Nombre de votes blancs ou nuls');
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('B'. $ligne++, $participation->getNbNulsBlancs());
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A'. $ligne, 'Taux de participation');
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('B'. $ligne++, number_format($participation->getTaux(), 2) . '%');

            $ligne++;
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A'. $ligne++, '- Résultats');
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A'. $ligne, 'Nombre de sièges à pourvoir');
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('B'. $ligne++, $participation->getNbSiegesPourvoir());
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A'. $ligne, 'Quotient');
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('B'. $ligne++, $participation->getQuotient());
        } else {
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A11', 'Aucun résultat disponible');
        }

        $ligne = $hasModaliteVote ? $ligne+2 : 24;
        // Résultats
        if (sizeof($resultats) != 0) {
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A' . $ligne, '- Répartition détaillée des sièges');
            $ligne++;
            // Répartition détaillée
            $libelle_plus_age = 'Plus âgé';

            $spreadsheet->setActiveSheetIndex(0)
                ->setCellValue('A' . $ligne, 'Liste')
                ->setCellValue('B' . $ligne, 'Nombre de candidats')
                ->setCellValue('C' . $ligne, 'Nombre de suffrages')
                ->setCellValue('D' . $ligne, 'Sièges attribués au quotient et au plus fort reste')
                ->setCellValue('E' . $ligne, $libelle_plus_age)
                ->setCellValue('F' . $ligne, 'Total');
            $sheet->getStyle('B' . $ligne)->applyFromArray($styleArray);
            $sheet->getStyle('C' . $ligne)->applyFromArray($styleArray);
            $sheet->getStyle('D' . $ligne)->applyFromArray($styleArray);
            $sheet->getStyle('E' . $ligne)->applyFromArray($styleArray);
            $sheet->getStyle('F' . $ligne)->applyFromArray($styleArray);
            $ligne ++;
            $sommeNbCandidats = 0;
            $sommeNbVoix = 0;
            $sommeNbSieges = 0;
            $sommeNbSiegesSort = 0;
            $sommeTotal = 0;
            foreach ($resultats as $res) {
                $spreadsheet->setActiveSheetIndex(0)
                    ->setCellValue('A' . $ligne, $res->getOrganisation()
                        ->getLibelle())
                    ->setCellValue('B' . $ligne, $res->getNbCandidats())
                    ->setCellValue('C' . $ligne, $res->getNbVoix())
                    ->setCellValue('D' . $ligne, $res->getNbSieges())
                    ->setCellValue('E' . $ligne, $res->getNbSiegesSort())
                    ->setCellValue('F' . $ligne, $res->getNbSieges() + $res->getNbSiegesSort());
                $ligne ++;

                // les details
                if ($res->getOrganisation()->getDetaillee()) {
                    if ($resDetails != null) {
                        foreach ($resDetails as $resDetail) {
                            if ($resDetail->getOrganisation()->getId() == $res->getOrganisation()->getId()) {
                                $spreadsheet->setActiveSheetIndex(0)
                                    ->setCellValue('A' . $ligne, "Dont " .$resDetail->getLibelle())
                                    ->setCellValue('B' . $ligne, $resDetail->getNbCandidats())
                                    ->setCellValue('C' . $ligne, $resDetail->getNbVoix())
                                    ->setCellValue('D' . $ligne, $resDetail->getNbSieges())
                                    ->setCellValue('E' . $ligne, $resDetail->getNbSiegesSort())
                                    ->setCellValue('F' . $ligne, $resDetail->getNbSieges() + $resDetail->getNbSiegesSort());
                                $sheet->getStyle('A' . $ligne)->applyFromArray($styleArrayItalic);
                                $ligne ++;
                            }
                        }
                    }
                }

                $sommeNbCandidats = $sommeNbCandidats + $res->getNbCandidats();
                $sommeNbVoix = $sommeNbVoix + $res->getNbVoix();
                $sommeNbSieges = $sommeNbSieges + $res->getNbSieges();
                $sommeNbSiegesSort = $sommeNbSiegesSort + $res->getNbSiegesSort();
                $sommeTotal = $sommeTotal + $res->getNbSieges() + $res->getNbSiegesSort();
            }
            $spreadsheet->setActiveSheetIndex(0)
                ->setCellValue('A' . $ligne, 'TOTAL TOUTES LISTES')
                ->setCellValue('B' . $ligne, $sommeNbCandidats)
                ->setCellValue('C' . $ligne, $sommeNbVoix)
                ->setCellValue('D' . $ligne, $sommeNbSieges)
                ->setCellValue('E' . $ligne, $sommeNbSiegesSort)
                ->setCellValue('F' . $ligne, $sommeTotal);
        }

        if (!empty($participation)) {
            if(($etablissementUai != 'tous' && $etablissement->getTypeEtablissement()->getDegre() == 1) or ($etablissementUai == 'tous' && $params['electZone']->getTypeEtablissement()->getDegre() == '1')){
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 3), 'Nombre de sièges pourvus par tirage au sort');
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 3), $participation->getNbSiegesSort());
                $sheet->getStyle('A' . ($ligne + 3))->applyFromArray($styleArray);
                $sheet->getStyle('B' . ($ligne + 3))->getAlignment()->setHorizontal('center');
            }
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 2), 'Nombre de sièges pourvus');
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 2), $participation->getNbSiegesPourvus());
            $sheet->getStyle('A' . ($ligne + 2))->applyFromArray($styleArray);
            $sheet->getStyle('B' . ($ligne + 2))->getAlignment()->setHorizontal('center');
        }

        // Activer la 1ère feuille
        $spreadsheet->setActiveSheetIndex(0);

        $sheet->getColumnDimension('A')->setWidth(45);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(30);
        $sheet->getColumnDimension('D')->setWidth(30);
        $sheet->getColumnDimension('E')->setWidth(30);

        $sheet->getStyle('A1')->applyFromArray($styleArrayTitre);
        $sheet->getStyle('A1:A' . ($ligne))->applyFromArray($styleArray);
        $sheet->getStyle('B1:B' . ($ligne))->getAlignment()->setHorizontal('center');
        $sheet->getStyle('C1:C' . ($ligne))->getAlignment()->setHorizontal('center');
        $sheet->getStyle('D1:D' . ($ligne))->getAlignment()->setHorizontal('center');
        $sheet->getStyle('E1:E' . ($ligne))->getAlignment()->setHorizontal('center');


        // Création du writer
        $writer = new Xlsx($spreadsheet, 'Excel5');

        // Création du nom du fichier
        $fileName = EcecaExportUtils::generateFileName('Resultats', $params);

        // Créer la réponse
        $response =  new StreamedResponse(
            function () use ($writer) {
                $writer->save('php://output');
            }
        );
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment;filename=' . $fileName . '.xls');
        return $response;
    }

    /**
     * Fonction permettant l'édition au format Excel des résultats d'une zone
     * Export complet des établissements classés par code postal
     *
     * @param string $codeUrlTypeElect
     * @throws AccessDeniedException
     */
    public function exportResultatsXLSCompletAction($codeUrlTypeElect) {
        ini_set("memory_limit", '-1');
        ini_set('max_execution_time', '300');
        set_time_limit(300);

        $em = $this->doctrine->getManager();

        $typeElectionId = RefTypeElection::getIdRefTypeElectionByCodeUrl($codeUrlTypeElect);
        $typeElection = $em->getRepository(RefTypeELection::class)->find($typeElectionId);

        if (null == $typeElection) {
            throw $this->createNotFoundException('Type élection inconnu');
        }

        $user = $this->getUser();

        if (!$user->canConsult($typeElection)) {
            throw new AccessDeniedException();
        }

        $campagne = $em->getRepository(EleCampagne::class)->getLastCampagne($typeElectionId);
        if (empty($campagne)) {
            throw $this->createNotFoundException('Les résultats de l\'établissement n\'ont pas été trouvés car la campagne est inconnue.');
        }


        $params = $this->getParametersRecherche($user, $campagne);

        $typeEtabForm = $em->getRepository(RefTypeEtablissement::class)->find($this->request->getSession()->get('select_typeEtab'));
        $deptForm = $em->getRepository(RefDepartement::class)->find($this->request->getSession()->get('select_departement'));
        $etatSaisie = $this->request->getSession()->get('select_etatSaisie');
        $idSousTypeElect = $this->request->getSession()->get('select_sousTypeElect');

        // 014E exclus des EREA-ERPD dans les résultats ASS et ATE et PEE && (($typeEtab != null && $typeEtab->getCode() != RefTypeEtablissement::CODE_EREA_ERPD) || $typeEtab == null)
        $isEreaErpdExclus = false;
        if(($typeElectionId == RefTypeElection::ID_TYP_ELECT_ASS_ATE ||$typeElectionId == RefTypeElection::ID_TYP_ELECT_PEE)
            && ($typeEtabForm != null && $typeEtabForm->getCode() != RefTypeEtablissement::CODE_EREA_ERPD || $typeEtabForm == null)) {
            $isEreaErpdExclus = true;
        }

        $lstElectEtab = $em->getRepository(EleEtablissement::class)->findByCampagneTypeEtabZoneExport($campagne, $typeEtabForm, $deptForm, $etatSaisie, $isEreaErpdExclus, $idSousTypeElect);

        $lstRefEtab = array();
        $resEtablissements = array();
        $resDetailsEtablissements = array();

        if (!empty($lstElectEtab)) {
            $lstRefEtab = $em->getRepository(RefEtablissement::class)->findListEtablissementForExport($lstElectEtab);
            $resEtablissements = $em->getRepository(EleResultat::class)->findByEleEtablissementsOrderByOrdre($campagne, $lstElectEtab);
            $resDetailsEtablissements = $em->getRepository(EleResultatDetail::class)->findByEleEtablissementsForExport($campagne, $lstElectEtab);
        }

        $data = array();
        $typeElect = $params['typeElect'];
        $codeUrlTypeEtab = $params['codeUrlTypeEtab'];

        foreach ($lstElectEtab as $electEtab) {
            $data[$electEtab->getEtablissement()->getUai()] = $this->getParametersResultatsEtablissement($resEtablissements, $resDetailsEtablissements, $electEtab);
        }

        // On test si la recherche est effectué par zone
        if (!empty($params['electZone'])) {
            $elect = $params['electZone'];
        }

        $campagne = $elect->getCampagne();
        $participation = $elect->getParticipation();
        $resultats = $elect->getResultats();

        // Recuperer toutes les organisations de cette campagne en prenant en compte de son type d'election
        $listeOrganisations = $em->getRepository(RefOrganisation::class)->findOrganisationNonObseletByRefTypeElection($typeElectionId);
        $organisationsExportComplet = array();
        foreach ($listeOrganisations as $organisation) {
            array_push($organisationsExportComplet, $organisation);
        }


        // Génération du fichier Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();


        if (!empty($params['dept'])) {
            $libelle_recherche = $params['dept']->getLibelle();
            $filtre_aca = $params['dept']->getAcademie()->getLibelle();
            $filtre_dept = $params['dept']->getLibelle();
        }

        //Determine l'académie de l'établissement (avant ou après fusion de certaines académie)
        $currentAcademie = $params['dept']->getAcademie();
        $dateDebutCampagne = new Datetime($campagne->getAnneeDebut() . "-01-01");
        if($currentAcademie->getDateDesactivation() <= $dateDebutCampagne) {
            $currentAcademie = $currentAcademie->getAcademieFusion();
        }
        if($currentAcademie != null) {
            $currentAcademie = $currentAcademie->getLibelle();
        }

        // Création du titre
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('A1', $typeElect->getLibelle() . ' - Résultats ' . $libelle_recherche);

        // Recuperation des oragnisations par campagne (prise en compte de son type d'election)
        $ordreAlphabetique = ($typeElection->getId() == RefTypeElection::ID_TYP_ELECT_PARENT) ? 'M' : 'L';

        // Affiche des champs du filtre appliqué à la recherche
        // Informations généréles de la campagne
        if (!empty($params['dept'])) {
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A4', 'Académie');
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A5', 'Département');
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A6', 'Type d\'établissement');
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('B4', $currentAcademie);
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('B5', $filtre_dept);
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('B6', ucwords($codeUrlTypeEtab));
        }

        $spreadsheet->setActiveSheetIndex(0)->setCellValue('A3', 'Campagne');
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('B3', $campagne->getAnneeDebut() . ' - ' . $campagne->getAnneeFin());

        // RÉPARTITION DES VOIX
        $spreadsheet->setActiveSheetIndex(0)->setCellValue($ordreAlphabetique.'9', 'RÉPARTITION DES VOIX');
        $spreadsheet->setActiveSheetIndex(0)->getStyle($ordreAlphabetique.'9')->getFont()->setSize(15);// Taille de la police pour les deux titres

        // Contenu du tableau
        if (!empty($participation)) {
            // Ligne des libelles
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A10', 'RNE');
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('B10', 'Type d\'établissement');
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('C10', 'Nom');
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('D10', 'Commune');

            //Ajout de la modalite de vote pour les elections parents
            if($typeElection->getId() == RefTypeElection::ID_TYP_ELECT_PARENT) {
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('E10', 'Modalité de vote');
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('F10', 'Nombre d\'inscrits');
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('G10', 'Nombre de votants');
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('H10', 'Nombre de suffrages exprimés');
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('I10', 'Nombre de votes blancs ou nuls');
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('J10', 'Taux de Participation');
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('K10', 'Nombre de sièges à pourvoir');
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('L10', 'Nombre de sièges pourvus');
            } else {
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('E10', 'Nombre d\'inscrits');
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('F10', 'Nombre de votants');
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('G10', 'Nombre de suffrages exprimés');
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('H10', 'Nombre de votes blancs ou nuls');
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('I10', 'Taux de Participation');
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('J10', 'Nombre de sièges à pourvoir');
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('K10', 'Nombre de sièges pourvus');
            }

            // Affichage de toutes les oragnisations en fonction du type d'election de cette campagne
            $arrayOrgasCollonesVoix = array();
            foreach ($organisationsExportComplet as $organisation) {
                $spreadsheet->setActiveSheetIndex(0)->setCellValue($ordreAlphabetique.'10', $organisation->getLibelle());
                $arrayOrgasCollonesVoix[$organisation->getId()] = $ordreAlphabetique;
                $ordreAlphabetique++;
            }

            $spreadsheet->setActiveSheetIndex(0)->setCellValue($ordreAlphabetique.'10', 'TOTAL');
            $debutFusion = ($typeElection->getId() == RefTypeElection::ID_TYP_ELECT_PARENT) ? 'M9' : 'L9';
            $spreadsheet->setActiveSheetIndex(0)->mergeCells($debutFusion.':'.$ordreAlphabetique.'9');// Fusionnement de cellules
            $ordreAlphabetique++;
            $debut = $ordreAlphabetique;

            // ATTRIBUTION DES SIÈGES
            $spreadsheet->setActiveSheetIndex(0)->setCellValue($ordreAlphabetique.'9', 'ATTRIBUTION DES SIÈGES');
            $spreadsheet->setActiveSheetIndex(0)->getStyle($ordreAlphabetique.'9')->getFont()->setSize(15);// Taille de la police pour les deux titres

            // Affichage de toutes les oragnisations en fonction du type d'election de cette campagne
            $arrayOrgasCollonesSieges = array();
            foreach ($organisationsExportComplet as $organisation) {
                $spreadsheet->setActiveSheetIndex(0)->setCellValue($ordreAlphabetique.'10', $organisation->getLibelle());
                $arrayOrgasCollonesSieges[$organisation->getId()] = $ordreAlphabetique;
                $ordreAlphabetique++;
            }

            $spreadsheet->setActiveSheetIndex(0)->setCellValue($ordreAlphabetique.'10', 'TOTAL');
            $spreadsheet->setActiveSheetIndex(0)->mergeCells($debut.'9:'.$ordreAlphabetique.'9');


            // Remplir le tableau Etab par Etab
            // $lstRefEtab represente tous les etablissements ayant participe a cette campagne d'election
            $ligne = 11;

            foreach ($lstRefEtab as $refEtab) {

                // Obtenir les resultats d'un etablissement
                $eleEtab = $data[$refEtab->getUai()]; //$eleEtab => un objet "EleEtablissement"
                $participation = $eleEtab->getParticipation() ;
                $resultats = $eleEtab->getResultats();  // Une collection d'objets "EleResultat" pour un établissement
                $resultatsDet = $em->getRepository(EleResultatDetail::class)->findByEleEtablissement($eleEtab);


                $spreadsheet->setActiveSheetIndex(0)->setCellValue('A'.$ligne, $refEtab->getUai());
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('B'.$ligne, $refEtab->getTypeEtablissement()->getLibelle());
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('C'.$ligne, $refEtab->getLibelle());
                if ($refEtab->getCommune()) {
                    $libelle_com = $refEtab->getCommune()->getLibelle();
                    $libelle_dep = $refEtab->getCommune()->getDepartement()->getLibelle();
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('D'.$ligne, $libelle_com);
                } else {
                    $libelle_com = 'N/C';
                    $libelle_dep = 'N/C';
                }

                if($typeElection->getId() == RefTypeElection::ID_TYP_ELECT_PARENT) {
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('E'.$ligne, $participation->getModaliteVote() != null ? $participation->getModaliteVote()->getLibelle() : "");
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('F'.$ligne, $participation->getNbInscrits());
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('G'.$ligne, $participation->getNbVotants());
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('H'.$ligne, $participation->getNbExprimes());
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('I'.$ligne, $participation->getNbNulsBlancs());
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('J'.$ligne, number_format($participation->getTaux(), 2) . '%');
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('K'.$ligne, $participation->getNbSiegesPourvoir());
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('L'.$ligne, $participation->getNbSiegesPourvus());
                } else {
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('E'.$ligne, $participation->getNbInscrits());
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('F'.$ligne, $participation->getNbVotants());
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('G'.$ligne, $participation->getNbExprimes());
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('H'.$ligne, $participation->getNbNulsBlancs());
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('I'.$ligne, number_format($participation->getTaux(), 2) . '%');
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('J'.$ligne, $participation->getNbSiegesPourvoir());
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('K'.$ligne, $participation->getNbSiegesPourvus());
                }

                // Servant à remplir les champs: REPARTITION DES VOIX
                $sommeNbRepartitionVoix = 0;
                $positionVoixDebut = ($typeElection->getId() == RefTypeElection::ID_TYP_ELECT_PARENT) ? 'M' : 'L';  // Positionner les deux cellules des nombres totaux

                /* Remplissage pour les organisations depuis la liste $resultats  */
                // [LZO] defect 259 HPQC
                if (sizeof($resultats) != 0) {
                    foreach ($resultats as $resultat) {

                        if ($resultat->getNbVoix() != null) {
                            $positionVoix = $arrayOrgasCollonesVoix[$resultat->getOrganisation()->getId()];
                            $spreadsheet->setActiveSheetIndex(0)->setCellValue($positionVoix.$ligne, $resultat->getNbVoix());
                            $sommeNbRepartitionVoix += $resultat->getNbVoix();
                            $positionVoixDebut++;

                        } else {
                            $positionVoix = $arrayOrgasCollonesVoix[$resultat->getOrganisation()->getId()];
                            $spreadsheet->setActiveSheetIndex(0)->setCellValue($positionVoix.$ligne, '0');
                            $positionVoixDebut++;
                        }
                    }

                    // Somme des organistions
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue($positionVoixDebut.$ligne, $sommeNbRepartitionVoix);
                    $positionVoixDebut++;

                    // Servant à remplir les champs: ATTRIBUTION DES SIÈGES
                    $sommeNbRepartitionSieges = 0;

                    foreach ($resultats as $resultat) {
                        if ($resultat->getNbSieges() != null) {
                            $positionSieges = $arrayOrgasCollonesSieges[$resultat->getOrganisation()->getId()];

                            $nbSiegeChange = false;
                            $nbSiegeReel = 0;
                            foreach ($resultatsDet as $resultDet) {
                                if ($resultDet->getOrganisation()->getId() == $resultat->getOrganisation()->getId()) {
                                    $nbSiegeReel = $nbSiegeReel + min($resultDet->getNbSieges(), $resultDet->getNbCandidats());
                                    $nbSiegeChange = true;
                                }
                            }

                            $nbSiegeReel = $nbSiegeChange ? $nbSiegeReel : min($resultat->getNbSieges(), $resultat->getNbCandidats());
                            $spreadsheet->setActiveSheetIndex(0)->setCellValue(
                                $positionSieges.$ligne,
                                $nbSiegeReel
                            );
                            $sommeNbRepartitionSieges += $nbSiegeReel;
                            $positionVoixDebut++;
                        } else {
                            $positionSieges = $arrayOrgasCollonesSieges[$resultat->getOrganisation()->getId()];
                            $spreadsheet->setActiveSheetIndex(0)->setCellValue($positionSieges.$ligne, '0');
                            $positionVoixDebut++;
                        }
                    }
                    // Somme des organisations
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue(
                        $positionVoixDebut.$ligne,
                        $sommeNbRepartitionSieges
                    );
                }
                /* Fin de Remplissage pour les organisations depuis la liste $resultats  */

                $ligne++; // Pour le prochain etablissement
            }

        } else {
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A10', 'Aucun résulatat disponible');
        }

        // Activer la 1ère feuille
        $spreadsheet->setActiveSheetIndex(0);

        $sheet->getColumnDimension('A')->setWidth(45);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(25);
        $sheet->getColumnDimension('D')->setWidth(25);
        $sheet->getColumnDimension('E')->setWidth(25);

        // Création du writer
        $writer = new Xlsx($spreadsheet, 'Excel5');

        // Création du nom du fichier
        $fileName = EcecaExportUtils::generateFileName('Resultats', $params);

        // Créer la réponse
        $response =  new StreamedResponse(
            function () use ($writer) {
                $writer->save('php://output');
            }
        );
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment;filename=' . $fileName . '.xls');

        return $response;

    } // Fin de la fonction

    /**
     * Fonction permettant l'édition au format CSV des résultats d'une zone
     * Export CSV des resultats par etablissement par liste
     *
     * @param string $codeUrlTypeElect
     * @throws AccessDeniedException
     */
    public function exportResultatsCSVCompletDetailleAction($codeUrlTypeElect) {

        $em = $this->doctrine->getManager();

        $typeElectionId = RefTypeElection::getIdRefTypeElectionByCodeUrl($codeUrlTypeElect);
        $typeElection = $em->getRepository(RefTypeELection::class)->find($typeElectionId);

        if (null == $typeElection) {
            throw $this->createNotFoundException('Type élection inconnu');
        }

        $user = $this->getUser();

        if (!$user->canConsult($typeElection)) {
            throw new AccessDeniedException();
        }

        $campagne = $em->getRepository(EleCampagne::class)->getLastCampagne($typeElectionId);
        if (empty($campagne)) {
            throw $this->createNotFoundException('Les résultats de l\'établissement n\'ont pas été trouvés car la campagne est inconnue.');
        }

        $params = $this->getParametersRecherche($user, $campagne);

        $typeEtabForm = $em->getRepository(RefTypeEtablissement::class)->find($this->request->getSession()->get('select_typeEtab'));
        $deptForm = $em->getRepository(RefDepartement::class)->find($this->request->getSession()->get('select_departement'));
        $idSousTypeElection = $this->request->getSession()->get('select_sousTypeElect');
        $etatSaisie = $this->request->getSession()->get('select_etatSaisie');

        // 014E exclus des EREA-ERPD dans les résultats ASS et ATE et PEE && (($typeEtab != null && $typeEtab->getCode() != RefTypeEtablissement::CODE_EREA_ERPD) || $typeEtab == null)
        $isEreaErpdExclus = false;
        if(($typeElectionId == RefTypeElection::ID_TYP_ELECT_ASS_ATE ||$typeElectionId == RefTypeElection::ID_TYP_ELECT_PEE)
            && ($typeEtabForm != null && $typeEtabForm->getCode() != RefTypeEtablissement::CODE_EREA_ERPD || $typeEtabForm == null)) {
            $isEreaErpdExclus = true;
        }

        $lstElectEtab = $em->getRepository(EleEtablissement::class)->findByCampagneTypeEtabZoneExport($campagne, $typeEtabForm, $deptForm, $etatSaisie, $isEreaErpdExclus, $idSousTypeElection);

        $lstRefEtab = array();
        $resEtablissements = array();
        $resDetailsEtablissements = array();

        if (!empty($lstElectEtab)) {
            $lstRefEtab = $em->getRepository(RefEtablissement::class)->findListEtablissementForExport($lstElectEtab);
            $resEtablissements = $em->getRepository(EleResultat::class)->findByEleEtablissementsOrderByOrdre($campagne, $lstElectEtab);
            $resDetailsEtablissements = $em->getRepository(EleResultatDetail::class)->findByEleEtablissementsForExport($campagne, $lstElectEtab);
        }

        $datas = array();
        foreach ($lstElectEtab as $electEtab) {
            if (!$isEreaErpdExclus && $typeElectionId == RefTypeElection::ID_TYP_ELECT_ASS_ATE) {
                $datas[$electEtab->getEtablissement()->getUai().'_'.$electEtab->getSousTypeElection()->getId()] = $this->getParametersResultatsEtablissement($resEtablissements, $resDetailsEtablissements, $electEtab);
            } else {
                $datas[$electEtab->getEtablissement()->getUai()] = $this->getParametersResultatsEtablissement($resEtablissements, $resDetailsEtablissements, $electEtab);
            }
        }
        $datas['campagne'] = $campagne;
        $datas['typeElection'] = $typeElection;

        // filename
        $params['detaille'] = true;
        $fileName = EcecaExportUtils::generateFileName('Resultats', $params);

        // délimiteur
        $delimiteur = '|';

        $response = new StreamedResponse ();
        $response->setCallback(function() use( $datas, $delimiteur) {
            $handle = fopen ( 'php://output', 'w+' );
            // Nom des colonnes du CSV
            fputcsv($handle, array(
                $delimiteur . 'election',
                'annee',
                'academie',
                'departement',
                'commune',
                'code_RNE',
                'code',
                'libelle',
                'liste',
                'liste_locale',
                'nb_voix',
                'suffrages_exprimes',
                '% voix',
                'nb_sieges_a_pourvoir',
                'nb_sieges_calcules',
                'nb_candidats',
                'total_sieges_pourvus',
                '% sieges_pourvus' . $delimiteur
            ), $delimiteur, ' ');

            foreach ($datas as $eleEtab) {

                if ($eleEtab instanceof EleEtablissement) {
                    // Obtenir les resultats d'un etablissement
                    $resultats = $eleEtab->getResultats (); // Une collection d'objets "EleResultat" pour un établissement
                    $resDetails = $eleEtab->getResultatsDetailles ();
                    $etablissement = $eleEtab->getEtablissement();
                    $participation = $eleEtab->getParticipation();

                    // le suffrages_exprimes = nbre de votants - les nbre des nulles et blancs pour la zone
                    $nbSuffrageExp = $participation->getNbExprimes();
                    // le nobmre des sieges pourvus
                    $nbSiegesPourvus = $participation->getNbSiegesPourvus();
                    // le nobmre des sieges à pourvoir
                    $nbSiegesPourvoir = $participation->getNbSiegesPourvoir();

                    if ($resultats != null) {
                        foreach ( $resultats as $res ) {
                            // RG_ EXPORT _COMPLET-DETAILLE_008 RG_ EXPORT _COMPLET-DETAILLE_007
                            $organisation = $res->getOrganisation ();
                            $isOrgHasdetail = false;
                            // si l'organisation a des sous-organisations détaillées

                            //Determine l'académie de l'établissement (avant ou après fusion de certaines académie)
                            $currentAcademie = $etablissement->getCommune ()->getDepartement()->getAcademie ();
                            $dateDebutCampagne = new Datetime($datas['campagne']->getAnneeDebut() . "-01-01");
                            if($currentAcademie->getDateDesactivation() <= $dateDebutCampagne) {
                                $currentAcademie = $currentAcademie->getAcademieFusion();
                            }
                            if($currentAcademie != null) {
                                $currentAcademie = $currentAcademie->getLibelle();
                            }

                            if ($organisation->getDetaillee ()) {
                                if ($resDetails != null) {
                                    foreach ( $resDetails as $resDetail ) {
                                        if ($resDetail->getOrganisation ()->getId () == $organisation->getId ()) {
                                            $isOrgHasdetail = true;
                                            // % voix (nombre de suffrages recueillis par la liste / nombre total de suffrages exprimés) * 100
                                            $PoucentageNbVoix = ($nbSuffrageExp != 0) ? number_format(($resDetail->getNbVoix () / $nbSuffrageExp) * 100, 2) : number_format(0, 2);
                                            // % sieges (min entre le nombre de sieges calcules pour la liste et le nombre de candidats titulaires pour la liste / nombre total de sieges pourvus) * 100
                                            $PoucentageSieges = ($nbSiegesPourvus != 0) ? number_format((min($resDetail->getNbCandidats(),($resDetail->getNbSieges () + $resDetail->getNbSiegesSort ())) / $nbSiegesPourvus) * 100, 2) : number_format(0, 2);
                                            // Champs du fichier csv
                                            fputcsv($handle, array(
                                                $delimiteur . $datas['typeElection']->getCode(),
                                                $datas['campagne']->getAnneeDebut() . '-' . $datas['campagne']->getAnneeFin(),
                                                $currentAcademie,
                                                $etablissement->getCommune()->getDepartement()->getLibelle(),
                                                $etablissement->getCommune()->getLibelle(),
                                                $etablissement->getUai(),
                                                $etablissement->getTypeEtablissement()->getCode(),
                                                $etablissement->getLibelle(),
                                                $res->getOrganisation()->getLibelle(),
                                                $resDetail->getLibelle(),
                                                $resDetail->getNbVoix(),
                                                $nbSuffrageExp,
                                                $PoucentageNbVoix,
                                                $nbSiegesPourvoir,
                                                min($resDetail->getNbCandidats(), ($resDetail->getNbSieges() + $resDetail->getNbSiegesSort())),
                                                //$resDetail->getNbSieges () + $resDetail->getNbSiegesSort (),
                                                $resDetail->getNbCandidats(),
                                                $nbSiegesPourvus,
                                                $PoucentageSieges
                                            ), $delimiteur, ' ');
                                        }
                                    }
                                }
                            } // RG_ EXPORT _COMPLET-DETAILLE_013 sinon on met la valeur de liste locale à null
                            if (!$isOrgHasdetail) {
                                // % voix (nombre de suffrages recueillis par la liste / nombre total de suffrages exprimés) * 100
                                $PoucentageNbVoix = ($nbSuffrageExp != 0) ? number_format(($res->getNbVoix () / $nbSuffrageExp) * 100, 2) : number_format(0, 2);
                                // % sieges (min entre le nombre de sieges calcules pour la liste et le nombre de candidats titulaires pour la liste / nombre total de sieges pourvus) * 100
                                $PoucentageSieges = ($nbSiegesPourvus != 0) ? number_format((min($res->getNbCandidats(),($res->getNbSieges () + $res->getNbSiegesSort ())) / $nbSiegesPourvus) * 100, 2) : number_format(0, 2);
                                // Champs du fichier csv
                                fputcsv($handle, array(
                                    $delimiteur . $datas['typeElection']->getCode(),
                                    $datas['campagne']->getAnneeDebut() . '-' . $datas['campagne']->getAnneeFin(),
                                    $currentAcademie,
                                    $etablissement->getCommune()->getDepartement()->getLibelle(),
                                    $etablissement->getCommune()->getLibelle(),
                                    $etablissement->getUai(),
                                    $etablissement->getTypeEtablissement()->getCode(),
                                    $etablissement->getLibelle(),
                                    $res->getOrganisation()->getLibelle(),
                                    "NULL",
                                    $res->getNbVoix(),
                                    $nbSuffrageExp,
                                    $PoucentageNbVoix,
                                    $nbSiegesPourvoir,
                                    min($res->getNbCandidats(), ($res->getNbSieges() + $res->getNbSiegesSort())),
                                    //$res->getNbSieges () + $res->getNbSiegesSort (),
                                    $res->getNbCandidats(),
                                    $nbSiegesPourvus,
                                    $PoucentageSieges
                                ), $delimiteur, ' ');

                            }
                        }
                    }
                }
            }
            fclose ( $handle );
        });

        $response->setStatusCode(200);
        $response->headers->set ( 'Content-Type', 'text/csv; charset=utf-8' );
        $response->headers->set ( 'Content-Disposition', 'attachment; filename=' . $fileName . '.csv' );

        return $response;
    } // Fin de la fonction


    // FONCTIONS POUR OBTENIR DES PARAMETRES

    /**
     * Cas de consultation des résultats d'un seul établissement
     * @codeCoverageIgnore
     */
    private function getParametersForConsultationResultatsEtablissement(EleCampagne $campagne, $uai, RefSousTypeElection $sousTypeElection = null) {
        $em = $this->doctrine->getManager();
        $params = array();
        $etab = $em->getRepository(RefEtablissement::class)->find($uai);

        if (empty($etab)) {
            throw $this->createNotFoundException('Les résultats n\'ont pas été trouvés car l\'établissement est inconnu (' . $uai . ').');
        }

        if (null != $sousTypeElection){
            $params['sousTypeElect'] = $sousTypeElection;
        }

        $params['typeElect'] = $campagne->getTypeElection();
        $rech = array(
            'etablissement' => $etab->getUai(),
            'campagne' => $campagne->getId()
        );

        if (null != $sousTypeElection){
            $rech['sousTypeElection'] = $sousTypeElection->getId();
        }

        $eleEtab = $em->getRepository(EleEtablissement::class)->findOneBy($rech);

        if (empty($eleEtab)) {
            $eleEtab = new EleEtablissement();
            $eleEtab->setCampagne($campagne);
            $eleEtab->setEtablissement($etab);
            $eleEtab->setValidation(null);
        } else {
            $res = $em->getRepository(EleResultat::class)->findByEleEtablissementOrderByOrdre($eleEtab);
            $resDetail = $em->getRepository(EleResultatDetail::class)->findByEleEtablissement($eleEtab);

            // Evol 015E afficher le nombre de sieges reellement attribué
            // traitement du nombre de sieges reels si pas de detail
            if ($res != null) {
                foreach ($res as $resultat) {
                    if ($resultat->getNbSieges() != null && $resultat->getNbCandidats() != null)
                        $resultat->setNbSieges(min($resultat->getNbSieges(), $resultat->getNbCandidats()));
                }
            }
            // traitement du nombre de sieges detail reel
            if ($resDetail != null) {
                foreach ($resDetail as $resultatDetail) {
                    if ($resultatDetail->getNbSieges() != null && $resultatDetail->getNbCandidats() != null)
                        $resultatDetail->setNbSieges(min($resultatDetail->getNbSieges(), $resultatDetail->getNbCandidats()));
                }
            }
            // si listes detaillees le nombre de sieges reel est la somme des sieges reel detaille
            if ($res != null && $resDetail != null) {
                foreach ($res as $result) {
                    $nbSiegeReel = 0;
                    foreach ($resDetail as $resultDetail) {
                        if ($resultDetail->getOrganisation()->getId() == $result->getOrganisation()->getId())
                            $nbSiegeReel = $nbSiegeReel + $resultDetail->getNbSieges();
                    }
                    if ($nbSiegeReel > 0)
                        $result->setNbSieges($nbSiegeReel);
                }
            }
            // on set les derniers resultats
            $eleEtab->setResultats($res);
            $eleEtab->setResultatsDetailles($resDetail);
        }

        $params['electEtablissement'] = $eleEtab;
        $params['campagne'] = $campagne;

        $joursCalendairesIen = $this->getParameter('jours_calendaires_ien');
        $joursCalendaires = $this->getParameter('jours_calendaires');
        $isPeriodeP2Ter = $campagne->isP2Ter($joursCalendairesIen, $joursCalendaires, $etab->getCommune()->getDepartement()->getAcademie());
        $params['P2Ter'] = $isPeriodeP2Ter;

        // Recherche de l'état d'avancement de la saisie de l'établissement
        if ($eleEtab->isSaisi()) {
            $params['statutSaisi'] = true;
        } else
            if ($eleEtab->isTransmis()) {
                $params['statutTransmis'] = true;
                // defect #206
                if ($eleEtab->isEnAttenteDeNouvellesElections()) {
                    $params['statutEnAttenteDeNouvellesElections'] = true;
                }
            } else
                if ($eleEtab->isValide()) {
                    $params['statutValide'] = true;
                }

        $params['saisi'] = EleEtablissement::ETAT_SAISIE;
        $params['transmis'] = EleEtablissement::ETAT_TRANSMISSION;
        $params['valide'] = EleEtablissement::ETAT_VALIDATION;

        return $params;
    }


    private function getParametersResultatsEtablissement($resEtablissements, $resDetailsEtablissements, $eleEtab) {

        foreach ($resEtablissements as $result) {
            if ($result->getElectionEtab()->getId() == $eleEtab->getId()) {
                $eleEtab->addResultat($result);
            }
        }

        foreach ($resDetailsEtablissements as $resDet){
            if ($resDet->getElectionEtab()->getId() == $eleEtab->getId()) {
                $eleEtab->addResultatDetail($resDet);
            }
        }

        return $eleEtab;
    }

    // FONCTIONS POUR OBTENIR DES Resultats d'un etablissement


    /**
     * Cas de consultation des résultats pour un département, une académie ou toute la France (nationale)
     * Possibilité de choisir quels états d'avancement des saisies on veut voir
     * @codeCoverageIgnore
     */
    private function getParametersForConsultationResultatsZone($campagne, $zone, $user, $codeUrlTypeEtab = 'tous', $etatSaisie, $idSousTypeElect) {
        $em = $this->doctrine->getManager();

        $params = array();

        // Identification de la zone
        if ($zone === 'nationale') {
            $params['nationale'] = true;
        } else if ($zone instanceof RefDepartement) {
            $params['dept'] = $zone;
        } else if ($zone instanceof RefAcademie) {
            $params['aca'] = $zone;
        } else {
            throw $this->createNotFoundException('Les résultats n\'ont pas été trouvés car la zone (académie ou département) est inconnue (' . $zone . ').');
        }
        $codeUrl = RefTypeEtablissement::getIdRefTypeEtabByCodeUrl($codeUrlTypeEtab);
        if($codeUrl != null){
            $typeEtab = $em->getRepository(RefTypeEtablissement::class)->find($codeUrl);
        }else{
            $typeEtab = null;
        }

        if (empty($campagne)) {
            throw $this->createNotFoundException('Les résultats de l\'établissement n\'ont pas été trouvés car la campagne est inconnue.');
        }

        // 014E exclus des EREA-ERPD dans les résultats ASS et ATE et PEE && (($typeEtab != null && $typeEtab->getCode() != RefTypeEtablissement::CODE_EREA_ERPD) || $typeEtab == null)
        $isEreaErpdExclus = false;
        if(($campagne->getTypeElection()->getId() == RefTypeElection::ID_TYP_ELECT_ASS_ATE || $campagne->getTypeElection()->getId() == RefTypeElection::ID_TYP_ELECT_PEE)
            && (($typeEtab != null && $typeEtab->getCode() != RefTypeEtablissement::CODE_EREA_ERPD) || $typeEtab == null)) {
            $isEreaErpdExclus = true;
        }

        $params['electZone'] = $em->getRepository(EleEtablissement::class)->getConsolidationByCampagneTypeEtabZoneEtatSaisie($em, $campagne, $typeEtab, $zone, $etatSaisie, $user, $isEreaErpdExclus, $idSousTypeElect);
        $params['nbEtabExprDetailles'] = array();

        if ($zone === 'nationale') {
            $zone = null;
        }

        $nbEtabParZone = $em->getRepository(RefEtablissement::class)->getNbEtabParTypeEtablissementZoneTypeElection($typeEtab, $zone, $campagne->getTypeElection(), $user, $isEreaErpdExclus);

        $params['electZone']->setNbEtabTotal($nbEtabParZone);

        if ($params['electZone']->getNbEtabExprimes() == null) {
            $params['electZone']->setNbEtabExprimes(0);
        }

        $params['nbEtabParZone'] = $nbEtabParZone;


        return $params;

    }

    /**
     * Permet de déterminer si l'utilisateur a des établissements de type EREA-ERPD dans son périmètre.
     * @param $user
     * @return boolean
     */
    private function hasEreaErpd($user){
        $em = $this->doctrine->getManager();

        if($user->getPerimetre()->isLimitedToEtabs()){

            foreach($user->getPerimetre()->getEtablissements() as $etab){
                if($etab->getTypeEtablissement()->getCode() == RefTypeEtablissement::CODE_EREA_ERPD){
                    return true;
                }
            }
        }else{
            foreach($user->getPerimetre()->getDepartements() as $departement){
                $etbs = $em->getRepository(RefEtablissement::class)->findEtablissementByZoneUser($departement, $user);
                foreach ($etbs as $etb){
                    if($etb->getTypeEtablissement()->getCode() == RefTypeEtablissement::CODE_EREA_ERPD){
                        return true;
                    }
                }
            }
            return false;
        }
    }
}
?>