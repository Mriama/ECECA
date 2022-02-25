<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Entity\RefAcademie;
use App\Entity\RefDepartement;
use App\Entity\RefProfil;
use App\Entity\RefRole;
use App\Entity\RefTypeElection;
use App\Utils\EpleUtils;
use App\Entity\RefTypeEtablissement;
use App\Entity\App\Entity;
use App\Form\TdbEtabType;
use App\Form\TdbZoneEtabType;
use App\Entity\RefEtablissement;
use App\Entity\EleAlerte;
use App\Entity\RefTypeAlerte;
use App\Entity\RefSousTypeElection;
use App\Entity\RefZoneNature;
use App\Entity\EleEtablissement;
use App\Entity\EleCampagne;
use App\Entity\RefUser;
use Symfony\Component\Validator\Constraints\Length;

class TableauDeBordController extends AbstractController
{

    /**
     * Fonction permettant de récuperer l'ensemble des établissements affecté à l'utilisateur connecté
     * afin d'avoir une synthèse sur l'état d'avancement des saisies
     * Le tableau de bord est récupéré par type d'utilisateur (CE, DSDEN, RECTORAT, DGESCO)
     * L'ensemble des données à afficher sont calculées puis affichées par l'index de tableau de bord
     *
     * @author Atos - ALZ
     */
    public function indexAction()
    {

        $logger = $this->container->get("connexion_logger");
        $logger->info('TableauDeBordController - indexAction - DEBUT');

        $request = $this->get('request');
        $em = $this->getDoctrine()->getManager();

        // reset session pour les users ETAB venant de la recherche
        $request->getSession()->remove('select_aca');
        $request->getSession()->remove('select_dept');
        $request->getSession()->remove('select_typeEtablissement');
        $request->getSession()->remove('select_natureEtablissement');
        $request->getSession()->remove('select_typeElection');
        $request->getSession()->remove('select_sousTypeElection');

        $tabBordZone = array();

        $user = $this->get('security.context')->getToken()->getUser();
        $listeTypeElection = $user->getPerimetre()->getTypeElections();

        $joursCalendaires = $this->container->getParameter('jours_calendaires');

        $params = array();

        if ($user != null && $user->getPerimetre()->getIsPerimetreVide()) {
            $params["perimetre_vide_message"] = $this->container->getParameter("perimetre_vide_message");
            $params["perimetre_vide_lien_site"] = $this->container->getParameter("perimetre_vide_lien_site");
            return $this->render('EPLEElectionBundle:TableauDeBord:perimetre_vide.html.twig', $params);
        }

        $zoneUser = ($user->getIdZone() != null) ? EpleUtils::getZone($em, $user->getIdZone()) : null;

        // Blocage d'accès hors période pour les fédérations de parents
        if ($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_PARENTS) {
            /* YME 013E SUPPRIME
        	$campagne = $em->getRepository('EPLEElectionBundle:EleCampagne')->getLastCampagne(RefTypeElection::ID_TYP_ELECT_PARENT);
            if (! $campagne->isOpenConsultation()) {
                $error = 'La campagne ' . $campagne->getAnneeDebut() . ' - ' . $campagne->getAnneeFin() . ' n\'est pas ouverte à la consultation.';
                $form = $this->createForm(new \App\Form\IdentificationType(), array());
                $params['form'] = $form->createView();
                $this->get('session')
                    ->getFlashBag()
                    ->set('erreur', $error);
                return $this->render('EPLEElectionBundle:Identification:login.html.twig', $params);
            } else {
            */
            // Redirection auto vers les résultats
            $typeElections = $user->getPerimetre()->getTypeElections();
            $typeElectionAutorise = $typeElections[0];
            $array = array(
                'codeUrlTypeElect' => $typeElectionAutorise->getCodeUrlById()
            );
            return $this->redirect($this->generateUrl('EPLEElectionBundle_resultats', $array));
            //}
        }

        $params['profil'] = $user->getProfil()->getCode();
        $params['zoneUser'] = $zoneUser;
        $listeZone = null;

        $params = $this->getParametersRecherche($request, $user);
        $params["datesElections"] = array();
        $academies_decalage = array (
            RefAcademie::CODE_ACA_MAYOTTE,
            RefAcademie::CODE_ACA_REUNION
        );

        if($user->getProfil()->getCode() != RefProfil::CODE_PROFIL_DGESCO) {
            foreach ($listeTypeElection as $elect) {
                $campagneTmp = $em->getRepository('EPLEElectionBundle:EleCampagne')->getLastCampagne($elect->getId());
                $debutSaisie = clone $campagneTmp->getDateDebutSaisie();
                $finSaisie = clone $campagneTmp->getDateFinSaisie();
                $debutValidation = clone $campagneTmp->getDateDebutValidation();
                $finValidation = clone $campagneTmp->getDateFinValidation();

                if(in_array($zoneUser->getCode(), $academies_decalage) && $joursCalendaires != null) {
                    $debutSaisie->setTime ( 0, 0, 0 );
                    $debutSaisie->sub ( new \DateInterval ( 'P' . $joursCalendaires . 'D' ) );

                    $finSaisie->setTime ( 0, 0, 0 );
                    $finSaisie->sub ( new \DateInterval ( 'P' . $joursCalendaires . 'D' ) );

                    $debutValidation->setTime ( 0, 0, 0 );
                    $debutValidation->sub ( new \DateInterval ( 'P' . $joursCalendaires . 'D' ) );

                    $finValidation->setTime ( 0, 0, 0 );
                    $finValidation->sub ( new \DateInterval ( 'P' . $joursCalendaires . 'D' ) );
                }

                $params["datesElections"][] = array(
                    "orderId" => $elect->getId() == RefTypeElection::ID_TYP_ELECT_PARENT ? 1 : ($elect->getId() == RefTypeElection::ID_TYP_ELECT_ASS_ATE) ? 2 : 3,
                    "nom" => $elect->getCode(),
                    "debutSaisie" => $debutSaisie,
                    "finSaisie" => $finSaisie,
                    "debutValidation" => $debutValidation,
                    "finValidation" => $finValidation
                );
            }
            usort($params["datesElections"], function($a, $b) { return $a['orderId'] - $b['orderId']; });
        }
        switch ($user->getProfil()->getCode()) {
            case RefProfil::CODE_PROFIL_CE:

            case RefProfil::CODE_PROFIL_DE:
                $tabBordEtab = array();
                foreach ($user->getPerimetre()->getEtablissements() as $key => $etab) {
                    $tabBordEtab[$key]['Etab'] = $etab;
                    $tabBordEtab[$key]['EleEtab'] = $this->getEleInfosForEtab($etab, $user, $joursCalendaires);
                }

                $params['tabBordEtab'] = $tabBordEtab;
                $params['erreurs'] = $this->container->getParameter('erreurs');
                return $this->render('EPLEElectionBundle:TableauDeBord:etabs.html.twig', $params);
                break;

            case RefProfil::CODE_PROFIL_IEN:
                $tabBordEtab = array();
                $campagne = $em->getRepository('EPLEElectionBundle:EleCampagne')->getLastCampagne(RefTypeElection::ID_TYP_ELECT_PARENT);
                foreach ($user->getPerimetre()->getEtablissements() as $key => $etab) {
                    $tabBordEtab[$key]['Etab'] = $etab;
                    $tabBordEtab[$key]['EleEtab'] = $this->getEleInfosForEtab($etab, $user, $joursCalendaires);
                }
                $params['tabBordEtab'] = $tabBordEtab;
                $params['erreurs'] = $this->container->getParameter('erreurs');
                $params['warning'] = $this->container->getParameter('warning');
                $params['campagne'] = $campagne;
                return $this->render('EPLEElectionBundle:TableauDeBord:etabs_par_election.html.twig', $params);
                break;

            case RefProfil::CODE_PROFIL_DSDEN:
                $departements = $user->getPerimetre()->getDepartements();
                foreach ($departements as $departement){
                    $listeZone[$departement->getNumero()] = $departement;
                }

                $zoneUser = new RefAcademie();
                $zoneUser->setLibelle('Départements');
                $zoneUser->setDepartements($departements);

                $tabBordGeneral = $this->getTableauDeBord($zoneUser, $user, $joursCalendaires);
                $params['zone'] = 'Départements';
                $params['zoneUser'] = $zoneUser;
                foreach ($tabBordGeneral as $tabBord) {
                    $request->getSession()->set('nbEtabArelancer'.$tabBord['typeElection']->getId(), $tabBord['nbEtabARelancer']);
                }
                break;

            case RefProfil::CODE_PROFIL_RECT:
                $academies = $user->getPerimetre()->getAcademies();
                if(count($academies) == 1){
                    foreach ($academies[0]->getDepartements() as $departement){
                        $listeZone[$departement->getNumero()] = $departement;
                    }
                }elseif (count($academies) > 1){
                    $departements = $user->getPerimetre()->getDepartements();
                    foreach ($departements as $departement){
                        $listeZone[$departement->getNumero()] = $departement;
                    }
                }
                $tabBordGeneral = $this->getTableauDeBord($zoneUser, $user, $joursCalendaires);
                $params['zone'] = 'Académie';
                $params['zoneUser'] = $zoneUser;
                foreach ($tabBordGeneral as $tabBord) {
                    $request->getSession()->set('nbEtabArelancer'.$tabBord['typeElection']->getId(), $tabBord['nbEtabARelancer']);
                }

                break;

            case RefProfil::CODE_PROFIL_DGESCO:
                //Charger The active academy in the current Year:
                $campagne = $em->getRepository('EPLEElectionBundle:EleCampagne')->getLastCampagne(RefTypeElection::ID_TYP_ELECT_PARENT);
                $listeZone = $em->getRepository('EPLEElectionBundle:RefAcademie')->listeActiveAcademies($campagne);
                $tabBordGeneral = $this->getTableauDeBord($zoneUser, $user, $joursCalendaires);
                $params['zone'] = 'Nationale';
                $params['zoneUser'] = $zoneUser;
                foreach ($tabBordGeneral as $tabBord) {
                    $request->getSession()->set('nbEtabArelancer'.$tabBord['typeElection']->getId(), $tabBord['nbEtabARelancer']);
                }
                break;

            default:
                break;
        }

        if (null != $listeZone) {
            foreach ($listeZone as $key => $zone) {
                $tabBordZone[$key]['zone'] = $zone;
                $tabBordZone[$key]['resultats'] = $this->getTableauDeBord($zone, $user, $joursCalendaires);
            }
            foreach ($tabBordZone as $tabZone) {
                foreach ($tabZone['resultats'] as $zoneRes) {
                    if ($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_RECT || $user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DSDEN) {
                        $request->getSession()->set('nbEtabArelancer'.$zoneRes['typeElection']->getId().$tabZone['zone']->getNumero(), $zoneRes['nbEtabARelancer']);
                    } elseif ($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DGESCO) {
                        $request->getSession()->set('nbEtabArelancer'.$zoneRes['typeElection']->getId().$tabZone['zone']->getCode(), $zoneRes['nbEtabARelancer']);
                    }
                }
            }
        }

        // 014E retour au tdb déplié pour DSDEN et Restorat (DGESCO dans le cas de l'académie)
        //tri des zones par ordre alphabétiques
        usort($tabBordZone, function ($a, $b) {
            return ($a["zone"]->getLibelle() < $b["zone"]->getLibelle()) ? -1 : 1;
        });
        $params['dept_num'] = $request->getSession()->get('dept_num');
        $params['tdbDeplieRetour'] = $request->getSession()->get('tdbDeplieRetour');
        $request->getSession()->remove('dept_num');
        $request->getSession()->remove('tdbDeplieRetour');
        $request->getSession()->set('tdbRetour', true);

        $params['tabBordGeneral'] = $tabBordGeneral;
        $params['tabBordZone'] = $tabBordZone;
        $params['erreurs'] = $this->container->getParameter('erreurs');
        $params['info'] = $this->container->getParameter('info');

        return $this->render('EPLEElectionBundle:TableauDeBord:index.html.twig', $params);
    }

    /**
     * Fonction permettant de récuperer les information selon la recherche effectué dans le tableau de bord
     * selon le profil de l'utilisateur connecté
     *
     *
     * @author Atos - BBL 013E
     */
    public function rechercheAction(){
        $em = $this->getDoctrine()->getManager();
        $request = $this->get('request');
        $user = $this->get('security.context')->getToken()->getUser();

        $joursCalendaires = $this->container->getParameter('jours_calendaires');
        $listeZone = null;
        $zoneUser = ($user->getIdZone() != null) ? EpleUtils::getZone($em, $user->getIdZone()) : null;
        $params = $this->getParametersRecherche($request, $user);

        $academies_decalage = array (
            RefAcademie::CODE_ACA_MAYOTTE,
            RefAcademie::CODE_ACA_REUNION
        );

        if($user->getProfil()->getCode() != RefProfil::CODE_PROFIL_DGESCO) {
            foreach ($user->getPerimetre()->getTypeElections() as $elect) {
                $campagneTmp = $em->getRepository('EPLEElectionBundle:EleCampagne')->getLastCampagne($elect->getId());
                $debutSaisie = clone $campagneTmp->getDateDebutSaisie();
                $finSaisie = clone $campagneTmp->getDateFinSaisie();
                $debutValidation = clone $campagneTmp->getDateDebutValidation();
                $finValidation = clone $campagneTmp->getDateFinValidation();

                if(in_array($zoneUser->getCode(), $academies_decalage) && $joursCalendaires != null) {
                    $debutSaisie->setTime ( 0, 0, 0 );
                    $debutSaisie->sub ( new \DateInterval ( 'P' . $joursCalendaires . 'D' ) );

                    $finSaisie->setTime ( 0, 0, 0 );
                    $finSaisie->sub ( new \DateInterval ( 'P' . $joursCalendaires . 'D' ) );

                    $debutValidation->setTime ( 0, 0, 0 );
                    $debutValidation->sub ( new \DateInterval ( 'P' . $joursCalendaires . 'D' ) );

                    $finValidation->setTime ( 0, 0, 0 );
                    $finValidation->sub ( new \DateInterval ( 'P' . $joursCalendaires . 'D' ) );
                }

                $params["datesElections"][] = array(
                    "orderId" => $elect->getId() == RefTypeElection::ID_TYP_ELECT_PARENT ? 1 : ($elect->getId() == RefTypeElection::ID_TYP_ELECT_ASS_ATE) ? 2 : 3,
                    "nom" => $elect->getCode(),
                    "debutSaisie" => $debutSaisie,
                    "finSaisie" => $finSaisie,
                    "debutValidation" => $debutValidation,
                    "finValidation" => $finValidation
                );
            }
            usort($params["datesElections"], function($a, $b) { return $a['orderId'] - $b['orderId']; });
        }

        switch ($user->getProfil()->getCode()) {
            case RefProfil::CODE_PROFIL_CE:
            case RefProfil::CODE_PROFIL_DE:

                $tabBordEtab = array();
                foreach ($user->getPerimetre()->getEtablissements() as $key => $etab) {
                    $tabBordEtab[$key]['Etab'] = $etab;
                    $tabBordEtab[$key]['EleEtab'] = $this->getEleInfosForEtab($etab, $user, $joursCalendaires);
                }

                $params['tabBordEtab'] = $tabBordEtab;
                $params['erreurs'] = $this->container->getParameter('erreurs');
                return $this->render('EPLEElectionBundle:TableauDeBord:etabs.html.twig', $params);
                break;
            case RefProfil::CODE_PROFIL_IEN:

                $campagne = $em->getRepository('EPLEElectionBundle:EleCampagne')->getLastCampagne(RefTypeElection::ID_TYP_ELECT_PARENT);
                $tabBordEtab = array();
                $zone= $params['zone'];
                // On recupere la liste d'établissement dans le dept  selectionné
                $lstEtabs = $em->getRepository('EPLEElectionBundle:RefEtablissement')->findEtablissementByZoneUser($zone, $user);
                foreach ($lstEtabs as $key => $etab) {
                    $tabBordEtab[$key]['Etab'] = $etab;
                    $tabBordEtab[$key]['EleEtab'] = $this->getEleInfosForEtab($etab, $user, $joursCalendaires);
                }

                $params['tabBordEtab'] = $tabBordEtab;
                $params['campagne'] = $campagne;
                $params['erreurs'] = $this->container->getParameter('erreurs');
                $params['warning'] = $this->container->getParameter('warning');
                return $this->render('EPLEElectionBundle:TableauDeBord:etabs_par_election.html.twig', $params);
                break;
            case RefProfil::CODE_PROFIL_DSDEN:

                $zone = $params['zone'];
                if('nationale' != $zone){
                    $listeZone[$zone->getIdZone()] = $zone;
                    $params['zoneUser'] = $zone;
                    $tabBordGeneral = $this->getTableauDeBord($zone, $user, $joursCalendaires, $params['typeEtab'], $params['codeNatureEtab'], $params['codeTypeElect'], $params['idSousTypeElect']);
                } else {
                    $departements = $user->getPerimetre()->getDepartements();
                    foreach ($departements as $departement){
                        $listeZone[$departement->getNumero()] = $departement;
                    }
                    $zoneUser = new RefAcademie();
                    $zoneUser->setLibelle('Départements');
                    $zoneUser->setDepartements($departements);
                    $tabBordGeneral = $this->getTableauDeBord($zoneUser, $user, $joursCalendaires, $params['typeEtab'], $params['codeNatureEtab'], $params['codeTypeElect'], $params['idSousTypeElect']);
                    $params['zoneUser'] = $zoneUser;
                }
                $params['zone'] = 'Départements';
                foreach ($tabBordGeneral as $tabBord) {
                    $request->getSession()->set('nbEtabArelancer'.$tabBord['typeElection']->getId(), $tabBord['nbEtabARelancer']);
                }
                break;
            case RefProfil::CODE_PROFIL_RECT:

                $zone = $params['zone'];
                $params['zone'] = 'Académie';
                if('nationale' != $zone){
                    if ($zone instanceof RefAcademie) {
                        foreach ($zone->getDepartements() as $departement){
                            $listeZone[$departement->getNumero()] = $departement;
                        }
                    } else {
                        $params['zone'] = 'Départements';
                        $listeZone[$zone->getIdZone()] = $zone;
                    }
                    $params['zoneUser'] = $zone;
                    $tabBordGeneral = $this->getTableauDeBord($zone, $user, $joursCalendaires, $params['typeEtab'], $params['codeNatureEtab'], $params['codeTypeElect'], $params['idSousTypeElect']);
                } else {
                    $academies = $user->getPerimetre()->getAcademies();
                    foreach ($academies as $aca) {
                        foreach ($aca->getDepartements() as $departement) {
                            $listeZone[$departement->getNumero()] = $departement;
                        }
                    }
                    $tabBordGeneral = $this->getTableauDeBord($zoneUser, $user, $joursCalendaires, $params['typeEtab'], $params['codeNatureEtab'], $params['codeTypeElect'], $params['idSousTypeElect']);
                    $params['zoneUser'] = $zoneUser;
                }
                foreach ($tabBordGeneral as $tabBord) {
                    $request->getSession()->set('nbEtabArelancer'.$tabBord['typeElection']->getId(), $tabBord['nbEtabARelancer']);
                }
                break;
            case RefProfil::CODE_PROFIL_DGESCO:
                $zone = $params['zone'];
                if('nationale' != $zone){
                    $listeZone[$zone->getIdZone()] = $zone;
                    $params['zoneUser'] = $zone;
                    $tabBordGeneral = $this->getTableauDeBord($zone, $user, $joursCalendaires, $params['typeEtab'], $params['codeNatureEtab'], $params['codeTypeElect'], $params['idSousTypeElect']);
                } else {
                    $listeTypeElection = $em->getRepository('EPLEElectionBundle:RefTypeElection')->findTypeElectionByCode($params['codeTypeElect']);
                    $campagne = null;
                    if(!empty($listeTypeElection)){
                        if ($listeTypeElection[0] instanceof RefSousTypeElection) {
                            $campagne = $em->getRepository('EPLEElectionBundle:EleCampagne')->getLastCampagne($listeTypeElection[0]->getTypeElection());
                        } else {
                            $campagne = $em->getRepository('EPLEElectionBundle:EleCampagne')->getLastCampagne($listeTypeElection[0]);
                        }
                    } else {
                        $campagne = $em->getRepository('EPLEElectionBundle:EleCampagne')->getLastCampagne(RefTypeElection::ID_TYP_ELECT_PARENT);
                    }

                    $listeZone = $em->getRepository('EPLEElectionBundle:RefAcademie')->listeActiveAcademies($campagne);
                    $tabBordGeneral = $this->getTableauDeBord($zoneUser, $user, $joursCalendaires, $params['typeEtab'], $params['codeNatureEtab'], $params['codeTypeElect'], $params['idSousTypeElect']);
                    $params['zoneUser'] = $zoneUser;
                }
                $params['zone'] = 'Nationale';
                foreach ($tabBordGeneral as $tabBord) {
                    $request->getSession()->set('nbEtabArelancer'.$tabBord['typeElection']->getId(), $tabBord['nbEtabARelancer']);
                }
                break;
        }

        if (null != $listeZone) {
            $tabBordZone = array();
            foreach ($listeZone as $key => $zone) {
                $tabBordZone[$key]['zone'] = $zone;
                $tabBordZone[$key]['resultats'] = $this->getTableauDeBord($zone, $user, $joursCalendaires, $params['typeEtab'], $params['codeNatureEtab'], $params['codeTypeElect'], $params['idSousTypeElect']);
                $idSousTypeElect = $params['idSousTypeElect'];
                if(null != $idSousTypeElect){
                    if($idSousTypeElect == RefTypeElection::ID_TYP_ELECT_PEE || $idSousTypeElect == RefTypeElection::ID_TYP_ELECT_ASS_ATE){
                        $sousTypeElection = $em->getRepository('EPLEElectionBundle:RefTypeElection')->find($idSousTypeElect);
                    }else {
                        $sousTypeElection = $em->getRepository('EPLEElectionBundle:RefSousTypeElection')->find($idSousTypeElect);
                    }
                    $tabBordZone[$key]['sousTypeElection'] = $sousTypeElection;
                }
            }
            // pour la relance par département (RECTORAT) et par academie (DGESCO)
            foreach ($tabBordZone as $tabZone) {
                foreach ($tabZone['resultats'] as $zoneRes) {
                    if ($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_RECT || $user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DSDEN) {
                        $request->getSession()->set('nbEtabArelancer'.$zoneRes['typeElection']->getId().$tabZone['zone']->getNumero(), $zoneRes['nbEtabARelancer']);
                    } elseif ($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DGESCO) {
                        $request->getSession()->set('nbEtabArelancer'.$zoneRes['typeElection']->getId().$tabZone['zone']->getCode(), $zoneRes['nbEtabARelancer']);
                    }
                }
            }
            //tri des zones par ordre alphabétiques
            usort($tabBordZone, function ($a, $b) {
                return ($a["zone"]->getLibelle() < $b["zone"]->getLibelle()) ? -1 : 1;
            });
            $params['tabBordZone'] = $tabBordZone;
        }

        $params['tabBordGeneral'] = $tabBordGeneral;

        // 014E retour au tdb déplié pour DSDEN et Restorat (DGESCO dans le cas de l'académie)
        $params['dept_num'] = $request->getSession()->get('dept_num');
        $params['tdbDeplieRetour'] = $request->getSession()->get('tdbDeplieRetour');
        $request->getSession()->remove('dept_num');
        $request->getSession()->remove('tdbDeplieRetour');
        $request->getSession()->set('tdbRetour', true);

        $params['erreurs'] = $this->container->getParameter('erreurs');
        $params['warning'] = $this->container->getParameter('warning');

        $request->getSession()->set('natureEtab', $params['codeNatureEtab']);
        $request->getSession()->set('typeEtab', $params['typeEtab']);

        return $this->render('EPLEElectionBundle:TableauDeBord:index.html.twig', $params);
    }

    /**
     * Récupère les infos sur l'avancée des saisies pour un établissement donné
     *
     * @param RefEtablissement $etab
     * @param RefUser $user
     * @return array ()
     * @author Atos - ALZ / YME MAJ 013E /BBL MAJ 013E
     */
    private function getEleInfosForEtab($etab, $user, $joursCalendaires, $listeElection = null, $sousTypeElect = null, $etatsAvancement = null, $indCarence = null, $indDeficit = null)
    {
        $em = $this->getDoctrine()->getManager();

        $joursCalendairesIen = $this->container->getParameter('jours_calendaires_ien');

        $listeTypeElection = array();
        // Si le sous type d'election est saisi dans le filtre on prend en compte que le type d'election rattaché
        if(null != $sousTypeElect){
            if($sousTypeElect instanceof RefSousTypeElection){
                $listeTypeElection[]= $sousTypeElect->getTypeElection();
            } else {
                $listeTypeElection[]= $sousTypeElect;
            }
        } else {
            // Si non on prend le type d'election saisi
            if(null != $listeElection){
                $listeTypeElection = $listeElection;
            }else {
                // Si non on prend tout le périmetre
                $listeTypeElection = $user->getPerimetre()->getTypeElections();
            }
        }
        $eleEtab = array();
        $i = 0; //Indice incrémental du tableau de résultat

        // Traitement des EREA-ERPD
        if ($etab->getTypeEtablissement()->getCode() == RefTypeEtablissement::CODE_EREA_ERPD){
            foreach ($listeTypeElection as $typeElection) {
                $campagne = $em->getRepository('EPLEElectionBundle:EleCampagne')->getLastCampagne($typeElection);
                $listeSousTypeElection = array();
                if(null != $sousTypeElect){
                    $listeSousTypeElection[] = $sousTypeElect;
                } else {
                    $listeSousTypeElection = $em->getRepository('EPLEElectionBundle:RefSousTypeElection')->findSousTypesElectionsByRefTypeElection($typeElection->getId());
                }
                if(!empty($listeSousTypeElection)){
                    foreach($listeSousTypeElection as $sousTypeElection){
                        $eleEtab[$i]['hasSousType'] = '#';
                        $eleEtab[$i]['typeElection'] = $sousTypeElection;
                        $eleEtab[$i]['campagne'] = $campagne;

                        if (null == $campagne) {
                            $eleEtab[$i]['eleEtablissement'] = null;
                            $eleEtab[$i]['saisiePossible'] = false;
                            $eleEtab[$i]['validationPossible'] = false;
                            $eleEtab[$i]['campagneOpenSaisie'] = false;
                        } else {
                            $eleEtablissement = $em->getRepository('EPLEElectionBundle:EleEtablissement')->findOneByCampagneEtablissement($campagne, $etab, $sousTypeElection, $etatsAvancement, $indCarence, $indDeficit);
                            $eleEtab[$i]['eleEtablissement'] = $eleEtablissement;

                            // Recuperation de l'alerte
                            $alerte = $em->getRepository('EPLEElectionBundle:EleAlerte')->findAlerteByUaiCampagne($etab->getUai(),$campagne, $sousTypeElection);
                            $eleEtab[$i]['Deficit'] = false;
                            $eleEtab[$i]['Carence'] = false;
                            if(!empty($alerte)){
                                // Si carence
                                if($alerte[0]['typeAlerte'] == RefTypeAlerte::CODE_CARENCE){
                                    $eleEtab[$i]['Carence'] = true;
                                } // si deficit
                                elseif($alerte[0]['typeAlerte'] == RefTypeAlerte::CODE_DEFICIT) {
                                    $eleEtab[$i]['Deficit'] = true;
                                }
                            }

                            // IEN ne saisi le tirage au sort que si deficit ou carence et eleAlerte existe
                            $eleEtab[$i]['saisiePVTirageAuSortPossible'] = false;
                            if(!empty($eleEtablissement)){
                                $eleEtab[$i]['saisiePVTirageAuSortPossible'] = ($user->canSaisieTirageAuSort($etab, $eleEtablissement, $campagne, $joursCalendaires, $joursCalendairesIen) && ($eleEtab[$i]['Deficit'] || $eleEtab[$i]['Carence']) && $eleEtablissement->getIndTirageSort() == 0);
                            }

                            // DSDEN et RECTORAT ne saisissent une nouvelle election que si deficit et eleAlerte deficit existe
                            $eleEtab[$i]['canSaisieNouvelleElection'] = false;
                            if(!empty($eleEtablissement)){
                                $eleEtab[$i]['canSaisieNouvelleElection'] = ($user->canSaisieNouvelleElection($etab, $eleEtablissement, $campagne, $joursCalendaires) && $eleEtab[$i]['Deficit']);
                            }

                            $eleEtab[$i]['saisiePossible'] = ($user->canEditEtab($etab, $campagne, $joursCalendaires) && (empty($eleEtablissement) || $eleEtablissement->isSaisi()));
                            $eleEtab[$i]['validationPossible'] =  ($user->canValidateEtab($etab, $campagne, $joursCalendaires) && (!empty($eleEtablissement) && $eleEtablissement->isTransmis()));
                            $eleEtab[$i]['campagneOpenSaisie'] = $campagne->isOpenSaisie($user->getPerimetre()->getAcademies(), $joursCalendaires);
                        }
                        $i++;
                    }
                }else{
                    $eleEtab[$i]['typeElection'] = $typeElection;
                    $eleEtab[$i]['campagne'] = $campagne;

                    if (null == $campagne) {
                        $eleEtab[$i]['eleEtablissement'] = null;
                        $eleEtab[$i]['saisiePossible'] = false;
                        $eleEtab[$i]['validationPossible'] = false;
                        $eleEtab[$i]['campagneOpenSaisie'] = false;
                    } else {
                        $eleEtablissement = $em->getRepository('EPLEElectionBundle:EleEtablissement')->findOneByCampagneEtablissement($campagne, $etab, null, $etatsAvancement, $indCarence, $indDeficit);

                        // Recuperation de l'alerte
                        $alerte = $em->getRepository('EPLEElectionBundle:EleAlerte')->findAlerteByUaiCampagne($etab->getUai(),$campagne);
                        $eleEtab[$i]['Deficit'] = false;
                        $eleEtab[$i]['Carence'] = false;
                        if(!empty($alerte)){
                            // Si carence
                            if($alerte[0]['typeAlerte'] == RefTypeAlerte::CODE_CARENCE){
                                $eleEtab[$i]['Carence'] = true;
                            }// si deficit
                            elseif($alerte[0]['typeAlerte'] == RefTypeAlerte::CODE_DEFICIT) {
                                $eleEtab[$i]['Deficit'] = true;
                            }
                        }

                        // IEN ne saisi le tirage au sort que si deficit ou carence et eleAlerte existe
                        $eleEtab[$i]['saisiePVTirageAuSortPossible'] = false;
                        if(!empty($eleEtablissement)){
                            $eleEtab[$i]['saisiePVTirageAuSortPossible'] = ($user->canSaisieTirageAuSort($etab, $eleEtablissement, $campagne, $joursCalendaires,$joursCalendairesIen) && ($eleEtab[$i]['Deficit'] || $eleEtab[$i]['Carence']) && $eleEtablissement->getIndTirageSort() == 0);
                        }

                        // DSDEN et RECTORAT ne saisissent une nouvelle election que si deficit et eleAlerte deficit existe
                        $eleEtab[$i]['canSaisieNouvelleElection'] = false;
                        if(!empty($eleEtablissement)){
                            $eleEtab[$i]['canSaisieNouvelleElection'] = ($user->canSaisieNouvelleElection($etab, $eleEtablissement, $campagne, $joursCalendaires) && $eleEtab[$i]['Deficit']);
                        }

                        $eleEtab[$i]['eleEtablissement'] = $eleEtablissement;
                        $eleEtab[$i]['saisiePossible'] = ($user->canEditEtab($etab, $campagne, $joursCalendaires) && (empty($eleEtablissement) || $eleEtablissement->isSaisi()));
                        $eleEtab[$i]['validationPossible'] =  ($user->canValidateEtab($etab, $campagne, $joursCalendaires) && (!empty($eleEtablissement) && $eleEtablissement->isTransmis()));
                        $eleEtab[$i]['campagneOpenSaisie'] = $campagne->isOpenSaisie($user->getPerimetre()->getAcademies(), $joursCalendaires);
                    }
                    $i++;
                }
            }
        }else{
            // Traitement des autres types d'établissement
            foreach ($listeTypeElection as $typeElection) {
                // ECT accès aux élections de type RP uniquement aux établissements du 2nd degré
                if (!($etab->getTypeEtablissement()->getDegre() == "1" && ($typeElection->getId() == RefTypeElection::ID_TYP_ELECT_ASS_ATE || $typeElection->getId() == RefTypeElection::ID_TYP_ELECT_PEE))) {
                    $campagne = $em->getRepository('EPLEElectionBundle:EleCampagne')->getLastCampagne($typeElection);
                    $eleEtab[$i]['typeElection'] = $typeElection;
                    $eleEtab[$i]['campagne'] = $campagne;
                    if (null == $campagne) {
                        $eleEtab[$i]['eleEtablissement'] = null;
                        $eleEtab[$i]['saisiePossible'] = false;
                        $eleEtab[$i]['validationPossible'] = false;
                        $eleEtab[$i]['campagneOpenSaisie'] = false;
                    } else {
                        $eleEtablissement = $em->getRepository('EPLEElectionBundle:EleEtablissement')->findOneByCampagneEtablissement($campagne, $etab, null, $etatsAvancement, $indCarence, $indDeficit);

                        // Recuperation de l'alerte
                        $alerte = $em->getRepository('EPLEElectionBundle:EleAlerte')->findAlerteByUaiCampagne($etab->getUai(),$campagne);
                        $eleEtab[$i]['Deficit'] = false;
                        $eleEtab[$i]['Carence'] = false;
                        if(!empty($alerte)){
                            // Si carence
                            if($alerte[0]['typeAlerte'] == RefTypeAlerte::CODE_CARENCE){
                                $eleEtab[$i]['Carence'] = true;
                            } // Si deficit
                            elseif($alerte[0]['typeAlerte'] == RefTypeAlerte::CODE_DEFICIT) {
                                $eleEtab[$i]['Deficit'] = true;
                            }
                        }

                        // DE (anicennement IEN) ne saisi le tirage au sort que si deficit ou carence et eleAlerte existe
                        $eleEtab[$i]['saisiePVTirageAuSortPossible'] = false;
                        if(!empty($eleEtablissement)){
                            $eleEtab[$i]['saisiePVTirageAuSortPossible'] = ($user->canSaisieTirageAuSort($etab, $eleEtablissement, $campagne, $joursCalendaires, $joursCalendairesIen) && ($eleEtab[$i]['Deficit'] || $eleEtab[$i]['Carence']) && $eleEtablissement->getIndTirageSort() == 0);
                        }

                        // DSDEN et RECTORAT ne saisissent une nouvelle election que si deficit et eleAlerte deficit existe
                        $eleEtab[$i]['canSaisieNouvelleElection'] = false;
                        if(!empty($eleEtablissement)){
                            $eleEtab[$i]['canSaisieNouvelleElection'] = ($user->canSaisieNouvelleElection($etab, $eleEtablissement, $campagne, $joursCalendaires) && $eleEtab[$i]['Deficit']);
                        }

                        $eleEtab[$i]['eleEtablissement'] = $eleEtablissement;
                        $eleEtab[$i]['saisiePossible'] = ($user->canEditEtab($etab, $campagne, $joursCalendaires) && (empty($eleEtablissement) || $eleEtablissement->isSaisi()));

                        // 014E RG_SAISIE_TIRAGE-AU-SORT_IEN_010 RG_SAISIE_TIRAGE-AU-SORT_IEN_009
                        $eleEtab[$i]['validationPossible'] = false;
                        if ($user->canValidateEtab($etab, $campagne, $joursCalendaires) && (!empty($eleEtablissement) && $eleEtablissement->isTransmis())) {
                            $eleEtab[$i]['validationPossible'] = true;
                            // le DE a saisi le résultat du tirage au sort, meme si la periode de saisie definie pour le tirage au sort est toujours en cours || lorsque la periode de saisie definie pour le DE est terminée
                            if ($typeElection->getId() == RefTypeElection::ID_TYP_ELECT_PARENT && ($eleEtablissement->getIndDeficit() == 1 || $eleEtablissement->getIndCarence () == 1) && $campagne->isOpenSaisie ( $user->getPerimetre ()->getAcademies (), $joursCalendaires, null, $joursCalendairesIen )
                                && $eleEtablissement->getIndTirageSort () < 1 && $etab->getTypeEtablissement()->getId() == RefTypeEtablissement::ID_TYP_1ER_DEGRE) {
                                $eleEtab[$i]['validationPossible'] = false;
                            }
                        }
                        $eleEtab[$i]['campagneOpenSaisie'] = $campagne->isOpenSaisie($user->getPerimetre()->getAcademies(), $joursCalendaires);
                    }
                    $i++;
                }
            }
        }

        return $eleEtab;
    }

    /**
     * Fonction permettant de calculer l'ensemble des établissement ayant saisie une liste de type d'élection
     *
     * @param
     *            refDepartement ou refAcademie $zone
     * @param RefUser $user
     * @return tableau de bord
     * @author Atos - ALZ
     */
    private function getTableauDeBord($zone, $user, $joursCalendaires, $typeEtab = null, $codeNatEtab = null, $codeTypeElect = null, $idSousTypeElect = null)
    {

        $request = $this->get('request');
        $tabBord = array();
        $nbTotalEtabAucunEnrTab = array();
        $pourcentageCarenceTab = array();
        $pourcentageNewElectionTab = array();
        $nbTotalNewElectionTab = array();
        $nbTotalCarenceTab = array();
        $nbEtabArelancerTab = array();

        if (!$user->getPerimetre()->getIsPerimetreVide()) {
            $em = $this->getDoctrine()->getManager();

            $nbTotalEtabAucunEnr = 0;
            $nbTotalCarence = 0;
            $nbTotalNewElection = 0;
            $pourcentageCarence = 0;
            $pourcentageNewElection = 0;
            $nbTotalEtabZone = 0;

            $sousTypeElect = null;
            $listeTypeElection = array();

            if(null != $codeTypeElect && !empty($codeTypeElect)){
                if(null != $idSousTypeElect && !empty($idSousTypeElect)){
                    // YME - #0145755
                    if(strlen($idSousTypeElect) == 1){
                        // ASS et ATE ou PEE
                        $typeElection = $em->getRepository('EPLEElectionBundle:RefTypeElection')->find($idSousTypeElect);
                        if(null != $typeEtab && RefTypeEtablissement::ID_TYP_EREA_ERPD == $typeEtab->getId()){
                            $listeTypeElection = $em->getRepository('EPLEElectionBundle:RefSousTypeElection')->findSousTypesElectionsByRefTypeElection($typeElection->getId());
                        }
                    }else{
                        // A et ATE ou SS
                        $sousTypeElect = $em->getRepository('EPLEElectionBundle:RefSousTypeElection')->find($idSousTypeElect);
                        $listeTypeElection[] = $sousTypeElect;
                        $typeElection = $sousTypeElect->getTypeElection();
                    }
                } else {
                    // RP ou PE
                    $listeTypeElection = $em->getRepository('EPLEElectionBundle:RefTypeElection')->findTypeElectionByCode($codeTypeElect);
                }
            } else {
                // Tous le périmetre de l'user
                $listeTypeElection = $user->getPerimetre()->getTypeElections();
            }

            // YME - EVOL 145755
            if(null != $typeEtab && RefTypeEtablissement::ID_TYP_EREA_ERPD == $typeEtab->getId()){
                $listeTypeElectionTmp = array();
                $listeSousTypeElection = array();
                foreach ($listeTypeElection as $typeElection) {
                    $listeSousTypeElection = $em->getRepository('EPLEElectionBundle:RefSousTypeElection')->findSousTypesElectionsByRefTypeElection($typeElection->getId());
                    if(null != $listeSousTypeElection){
                        foreach($listeSousTypeElection as $key=> $sousTypeElection){
                            $listeTypeElectionTmp[] = $sousTypeElection;
                        }
                    }else{
                        $listeTypeElectionTmp[] = $typeElection;
                    }
                }
                $listeTypeElection = $listeTypeElectionTmp;
            }

            // plusieurs types d'elections
            if(!empty($listeTypeElection)){

                foreach ($listeTypeElection as $key => $typeElection) {

                    // 014E exclus des EREA-ERPD dans les résultats ASS et ATE et PEE && (($typeEtab != null && $typeEtab->getCode() != RefTypeEtablissement::CODE_EREA_ERPD) || $typeEtab == null)
                    $isEreaErpdExclus = false;
                    if (($typeElection->getId () == RefTypeElection::ID_TYP_ELECT_ASS_ATE || $typeElection->getId () == RefTypeElection::ID_TYP_ELECT_PEE)
                        && (($typeEtab != null && $typeEtab->getCode () != RefTypeEtablissement::CODE_EREA_ERPD) || $typeEtab == null)) {
                        $isEreaErpdExclus = true;
                    }

                    $nbEtabParZone = 0;
                    $nbEtabSaisie = 0;
                    $nbEtabTransmis = 0;
                    $nbEtabValide = 0;
                    $nbEtabAucunEnr = 0;
                    $nbEtabZone = 0;

                    // YME - EVOL 145755
                    if($typeElection instanceof RefSousTypeElection ){
                        $campagne = $em->getRepository('EPLEElectionBundle:EleCampagne')->getLastCampagne($typeElection->getTypeElection());
                    }else{
                        $campagne = $em->getRepository('EPLEElectionBundle:EleCampagne')->getLastCampagne($typeElection);
                    }

                    $tabBord[$key]['typeElection'] = $typeElection;
                    $tabBord[$key]['campagne'] = $campagne;

                    // YME - 145940
                    if($zone instanceof RefAcademie){
                        $tabBord[$key]['campagneOpenSaisie'] = $campagne->isOpenSaisie($user->getPerimetre()->getAcademies(), $joursCalendaires, $zone);
                    }else{
                        $tabBord[$key]['campagneOpenSaisie'] = $campagne->isOpenSaisie($user->getPerimetre()->getAcademies(), $joursCalendaires);
                    }
                    if (! empty($campagne)) {
                        if ($campagne->getArchivee() == false) {
                            if($zone instanceof RefAcademie && $em->getRepository('EPLEElectionBundle:RefAcademie')->countchildAcademies($zone->getCode()) > 0) {
                                //Récupération des "enfants" de l'académie fusionné pour faire la somme des établissement
                                $fusionChild = $em->getRepository('EPLEElectionBundle:RefAcademie')->getchildnewAcademies($zone->getCode());
                                foreach ($fusionChild as $child) {
                                    $tmp = $em->getRepository('EPLEElectionBundle:RefEtablissement')->getNbEtabParTypeEtablissementZoneTypeElection($typeEtab, $child, $typeElection, null, $isEreaErpdExclus, $codeNatEtab, true);
                                    $nbEtabParZone += $tmp;
                                }
                            } else {
                                $nbEtabParZone = $em->getRepository('EPLEElectionBundle:RefEtablissement')->getNbEtabParTypeEtablissementZoneTypeElection($typeEtab, $zone, $typeElection, null, $isEreaErpdExclus, $codeNatEtab, true);
                            }
                            $nbEtabSaisie = $em->getRepository('EPLEElectionBundle:EleEtablissement')->getNbEleEtabParCampagne($campagne, $zone, 'S', $typeEtab, null, $isEreaErpdExclus, $codeNatEtab, $idSousTypeElect, false, false, true, $typeElection);
                            $nbEtabTransmis = $em->getRepository('EPLEElectionBundle:EleEtablissement')->getNbEleEtabParCampagne($campagne, $zone, 'T', $typeEtab, null, $isEreaErpdExclus, $codeNatEtab, $idSousTypeElect, false, false, true, $typeElection);
                            $nbEtabValide = $em->getRepository('EPLEElectionBundle:EleEtablissement')->getNbEleEtabParCampagne($campagne, $zone, 'V', $typeEtab, null, $isEreaErpdExclus, $codeNatEtab, $idSousTypeElect, false, false, true, $typeElection);
                            $nbEtabZone = $em->getRepository('EPLEElectionBundle:RefEtablissement')->getNbEtabParTypeEtablissementZoneTypeElection($typeEtab, $zone, $typeElection, null, $isEreaErpdExclus, $codeNatEtab, false, false, true);
                            $nbEtabAucunEnr = $nbEtabZone - $nbEtabSaisie - $nbEtabTransmis -$nbEtabValide;
                            if($nbEtabAucunEnr < 0) $nbEtabAucunEnr = 0;
                            $nbTotalCarence += $em->getRepository('EPLEElectionBundle:EleEtablissement')->getNbEleEtabParCampagne($campagne, $zone, 'O', $typeEtab, null, $isEreaErpdExclus, $codeNatEtab, $idSousTypeElect, true, false, true, $typeElection);
                            $nbTotalNewElection += $em->getRepository('EPLEElectionBundle:EleEtablissement')->getNbEleEtabParCampagne($campagne, $zone, 'O', $typeEtab, null, $isEreaErpdExclus, $codeNatEtab, $idSousTypeElect, false, true, true, $typeElection);
                            $nbTotalEtabAucunEnr += $nbEtabAucunEnr;
                            $nbTotalEtabZone += $nbEtabZone;

                            $tabBord[$key]['nbEtabTotal'] = $nbEtabParZone;
                            $tabBord[$key]['nbEtabSaisie'] = $nbEtabSaisie;
                            $tabBord[$key]['nbEtabTransmis'] = $nbEtabTransmis;
                            $tabBord[$key]['nbEtabValide'] = $nbEtabValide;
                            $tabBord[$key]['nbEtabAucunEnr'] = $nbEtabAucunEnr;
                            $tabBord[$key]['nbEtabARelancer'] = $nbEtabAucunEnr + $nbEtabSaisie;
                        } else {
                            ///////////////////////////////////////////////////////////////////
                            /// EVOL suppression de toute communication avec ele_consolidation
                            ///////////////////////////////////////////////////////////////////
                            $eleConsol = $em->getRepository('EPLEElectionBundle:EleEtablissement')->getConsolidationByCampagneTypeEtabZoneEtatSaisie($em, $campagne, $typeEtab, $zone, EleEtablissement::ETAT_VALIDATION, $user, $isEreaErpdExclus);
                            if($zone instanceof RefAcademie && $em->getRepository('EPLEElectionBundle:RefAcademie')->countchildAcademies($zone->getCode()) > 0) {
                                //Récupération des "enfants" de l'académie fusionné pour faire la somme des établissement
                                $fusionChild = $em->getRepository('EPLEElectionBundle:RefAcademie')->getchildnewAcademies($zone->getCode());
                                foreach ($fusionChild as $child) {
                                    $tmp = $em->getRepository('EPLEElectionBundle:RefEtablissement')->getNbEtabParTypeEtablissementZoneTypeElection($typeEtab, $child, $campagne->getTypeElection(), $user, $isEreaErpdExclus);
                                    $nbEtabParZone += $tmp;
                                }
                            } else {
                                $nbEtabParZone = $em->getRepository('EPLEElectionBundle:RefEtablissement')->getNbEtabParTypeEtablissementZoneTypeElection($typeEtab, $zone, $campagne->getTypeElection(), $user, $isEreaErpdExclus);
                            }
                            $participationData = $eleConsol->getParticipation();
                            if (empty($participationData)) {
                                $tabBord[$key]['aucuneDonnee'] = true;
                            } else {
                                $tabBord[$key]['nbEtabTotal'] = $nbEtabParZone;
                                $tabBord[$key]['nbEtabExpr'] = $eleConsol->getNbEtabExprimes();
                                $nbEtabAucunEnr = $nbEtabParZone - $eleConsol->getNbEtabExprimes();
                            }
                            $tabBord[$key]['nbEtabARelancer'] = 0;
                            $nbTotalEtabAucunEnr += $nbEtabAucunEnr;
                            $nbTotalEtabZone += $nbEtabParZone;
                            $nbTotalCarence += $em->getRepository('EPLEElectionBundle:EleEtablissement')->getNbEleEtabParCampagne($campagne, $zone, 'O', $typeEtab, null, $isEreaErpdExclus, $codeNatEtab, $idSousTypeElect, true, false, true, $typeElection);
                            $nbTotalNewElection += $em->getRepository('EPLEElectionBundle:EleEtablissement')->getNbEleEtabParCampagne($campagne, $zone, 'O', $typeEtab, null, $isEreaErpdExclus, $codeNatEtab, $idSousTypeElect, false, true, true, $typeElection);
                        }
                        $tabBord[$key]['validationPossible'] = ($user->canValidate($campagne, $joursCalendaires) && $nbEtabTransmis > 0);
                    }
                }
            } else { // si un seul type d'election est choisi
                $nbEtabParZone = 0;
                $nbEtabSaisie = 0;
                $nbEtabTransmis = 0;
                $nbEtabValide = 0;
                $nbEtabAucunEnr = 0;
                $nbEtabZone = 0;

                $campagne = $em->getRepository('EPLEElectionBundle:EleCampagne')->getLastCampagne($typeElection);

                if(null != $sousTypeElect){
                    $tabBord[$typeElection->getId()]['sousTypeElection'] = $sousTypeElect;
                }
                $tabBord[$typeElection->getId()]['typeElection'] = $typeElection;
                $tabBord[$typeElection->getId()]['campagne'] = $campagne;

                // YME - 145940
                if($zone instanceof RefAcademie){
                    $tabBord[$typeElection->getId()]['campagneOpenSaisie'] = $campagne->isOpenSaisie($user->getPerimetre()->getAcademies(), $joursCalendaires, $zone);
                }else{
                    $tabBord[$typeElection->getId()]['campagneOpenSaisie'] = $campagne->isOpenSaisie($user->getPerimetre()->getAcademies(), $joursCalendaires);
                }

                // 014E exclus des EREA-ERPD dans les résultats ASS et ATE et PEE && (($typeEtab != null && $typeEtab->getCode() != RefTypeEtablissement::CODE_EREA_ERPD) || $typeEtab == null)
                $isEreaErpdExclus = false;
                if (($typeElection->getId () == RefTypeElection::ID_TYP_ELECT_ASS_ATE || $typeElection->getId () == RefTypeElection::ID_TYP_ELECT_PEE)
                    && (($typeEtab != null && $typeEtab->getCode () != RefTypeEtablissement::CODE_EREA_ERPD) || $typeEtab == null)) {
                    $isEreaErpdExclus = true;
                }

                if (! empty($campagne)) {
                    if ($campagne->getArchivee() == false) {
                        if($zone instanceof RefAcademie && $em->getRepository('EPLEElectionBundle:RefAcademie')->countchildAcademies($zone->getCode()) > 0) {
                            //Récupération des "enfants" de l'académie fusionné pour faire la somme des établissement
                            $fusionChild = $em->getRepository('EPLEElectionBundle:RefAcademie')->getchildnewAcademies($zone->getCode());
                            foreach ($fusionChild as $child) {
                                $tmp = $em->getRepository('EPLEElectionBundle:RefEtablissement')->getNbEtabParTypeEtablissementZoneTypeElection($typeEtab, $child, $typeElection, null, $isEreaErpdExclus, $codeNatEtab, true);
                                $nbEtabParZone += $tmp;
                            }
                        } else {
                            $nbEtabParZone = $em->getRepository('EPLEElectionBundle:RefEtablissement')->getNbEtabParTypeEtablissementZoneTypeElection($typeEtab, $zone, $typeElection, null, $isEreaErpdExclus, $codeNatEtab, true);
                        }
                        $nbEtabSaisie = $em->getRepository('EPLEElectionBundle:EleEtablissement')->getNbEleEtabParCampagne($campagne, $zone, 'S', $typeEtab, null, $isEreaErpdExclus, $codeNatEtab, $idSousTypeElect, false, false, true);
                        $nbEtabTransmis = $em->getRepository('EPLEElectionBundle:EleEtablissement')->getNbEleEtabParCampagne($campagne, $zone, 'T', $typeEtab, null, $isEreaErpdExclus, $codeNatEtab, $idSousTypeElect, false, false, true);
                        $nbEtabValide = $em->getRepository('EPLEElectionBundle:EleEtablissement')->getNbEleEtabParCampagne($campagne, $zone, 'V', $typeEtab, null, $isEreaErpdExclus, $codeNatEtab, $idSousTypeElect, false, false, true);
                        $nbEtabZone = $em->getRepository('EPLEElectionBundle:RefEtablissement')->getNbEtabParTypeEtablissementZoneTypeElection($typeEtab, $zone, $typeElection, null, $isEreaErpdExclus, $codeNatEtab, false, false,true);
                        $nbEtabAucunEnr = $nbEtabZone - $nbEtabSaisie - $nbEtabTransmis -$nbEtabValide;
                        if($nbEtabAucunEnr < 0)
                            $nbEtabAucunEnr = 0;

                        $nbTotalCarence += $em->getRepository('EPLEElectionBundle:EleEtablissement')->getNbEleEtabParCampagne($campagne, $zone, 'O', $typeEtab, null, $isEreaErpdExclus, $codeNatEtab, $idSousTypeElect, true, false, true);
                        $nbTotalNewElection += $em->getRepository('EPLEElectionBundle:EleEtablissement')->getNbEleEtabParCampagne($campagne, $zone, 'O', $typeEtab, null, $isEreaErpdExclus, $codeNatEtab, $idSousTypeElect, false, true, true);
                        $nbTotalEtabAucunEnr += $nbEtabAucunEnr;
                        $nbTotalEtabZone += $nbEtabZone;

                        $tabBord[$typeElection->getId()]['nbEtabTotal'] = $nbEtabParZone;
                        $tabBord[$typeElection->getId()]['nbEtabSaisie'] = $nbEtabSaisie;
                        $tabBord[$typeElection->getId()]['nbEtabTransmis'] = $nbEtabTransmis;
                        $tabBord[$typeElection->getId()]['nbEtabValide'] = $nbEtabValide;
                        $tabBord[$typeElection->getId()]['nbEtabAucunEnr'] = $nbEtabAucunEnr;
                        $tabBord[$typeElection->getId ()]['nbEtabARelancer'] = $nbEtabAucunEnr + $nbEtabSaisie;

                    } else {
                        ///////////////////////////////////////////////////////////////////
                        /// EVOL suppression de toute communication avec ele_consolidation
                        ///////////////////////////////////////////////////////////////////
                        $eleConsol = $em->getRepository('EPLEElectionBundle:EleEtablissement')->getConsolidationByCampagneTypeEtabZoneEtatSaisie($em, $campagne, $typeEtab, $zone, EleEtablissement::ETAT_VALIDATION, $user, $isEreaErpdExclus);
                        if($zone instanceof RefAcademie && $em->getRepository('EPLEElectionBundle:RefAcademie')->countchildAcademies($zone->getCode()) > 0) {
                            //Récupération des "enfants" de l'académie fusionné pour faire la somme des établissement
                            $fusionChild = $em->getRepository('EPLEElectionBundle:RefAcademie')->getchildnewAcademies($zone->getCode());
                            foreach ($fusionChild as $child) {
                                $tmp = $em->getRepository('EPLEElectionBundle:RefEtablissement')->getNbEtabParTypeEtablissementZoneTypeElection($typeEtab, $child, $campagne->getTypeElection(), $user, $isEreaErpdExclus);
                                $nbEtabParZone += $tmp;
                            }
                        } else {
                            $nbEtabParZone = $em->getRepository('EPLEElectionBundle:RefEtablissement')->getNbEtabParTypeEtablissementZoneTypeElection($typeEtab, $zone, $campagne->getTypeElection(), $user, $isEreaErpdExclus);
                        }
                        $participationData = $eleConsol->getParticipation();
                        if (empty($participationData)) {
                            $tabBord[$typeElection->getId()]['aucuneDonnee'] = true;
                        } else {
                            $tabBord[$typeElection->getId()]['nbEtabTotal'] = $nbEtabParZone;
                            $tabBord[$typeElection->getId()]['nbEtabExpr'] = $eleConsol->getNbEtabExprimes();
                            $nbEtabAucunEnr = $nbEtabParZone - $eleConsol->getNbEtabExprimes();
                        }
                        $tabBord[$typeElection->getId()]['nbEtabARelancer'] = 0;
                        $nbTotalEtabAucunEnr += $nbEtabAucunEnr;
                        $nbTotalEtabZone += $nbEtabParZone;
                        $nbTotalCarence += $em->getRepository('EPLEElectionBundle:EleEtablissement')->getNbEleEtabParCampagne($campagne, $zone, 'O', $typeEtab, null, $isEreaErpdExclus, $codeNatEtab, $idSousTypeElect, true, false, true);
                        $nbTotalNewElection += $em->getRepository('EPLEElectionBundle:EleEtablissement')->getNbEleEtabParCampagne($campagne, $zone, 'O', $typeEtab, null, $isEreaErpdExclus, $codeNatEtab, $idSousTypeElect, false, true, true);
                    }
                    $tabBord[$typeElection->getId()]['validationPossible'] = ($user->canValidate($campagne, $joursCalendaires) && $nbEtabTransmis > 0);
                }

            }
            // Pour le DSDEN et le RECTORAT on a soit un ou plusieurs departements
            if($zone instanceof RefDepartement) {
                $nbTotalEtabAucunEnrTab[$zone->getNumero()] = $nbTotalEtabAucunEnr;
                $nbTotalCarenceTab[$zone->getNumero()] = $nbTotalCarence;
                $nbTotalNewElectionTab[$zone->getNumero()] = $nbTotalNewElection;
                $pourcentageCarenceTab[$zone->getNumero()] = empty($nbTotalEtabZone)? 0 : ( ($nbTotalCarence / $nbTotalEtabZone) * 100 );
                $pourcentageNewElectionTab[$zone->getNumero()] = empty($nbTotalEtabZone)? 0 : ( ($nbTotalNewElection / $nbTotalEtabZone) * 100 );
            }
            // defect hpqc 245
            // ECT on met en session les parametres de calcul pour chaque departement
            foreach ($nbTotalEtabAucunEnrTab as $key => $value) {
                $nbTotalEtabAucunEnrSession = 'nbTotalEtabAucunEnrTab'.$key;
                $request->getSession()->set($nbTotalEtabAucunEnrSession, $value);
            }
            foreach ($pourcentageCarenceTab as $key => $value) {
                $pourcentageCarenceSession = 'pourcentageCarenceTab'.$key;
                $request->getSession()->set($pourcentageCarenceSession, $value);
            }
            foreach ($pourcentageNewElectionTab as $key => $value) {
                $pourcentageNewElectionSession = 'pourcentageNewElectionTab'.$key;
                $request->getSession()->set($pourcentageNewElectionSession, $value);
            }
            foreach ($nbTotalCarenceTab as $key => $value) {
                $nbTotalCarenceSession = 'nbTotalCarenceTab'.$key;
                $request->getSession()->set($nbTotalCarenceSession, $value);
            }
            foreach ($nbTotalNewElectionTab as $key => $value) {
                $nbTotalNewElectionSession = 'nbTotalNewElectionTab'.$key;
                $request->getSession()->set($nbTotalNewElectionSession, $value);
            }
            // fin mis en session
        }

        return $tabBord;
    }

    public function getEtablissementsByNumDepartementAction() {
        $em = $this->getDoctrine()->getManager();
        //Evolution 219401 mass validation
        //Nombre max des résultats à valider par la validation de masse
        $maxMassValidationSelect = $this->container->getParameter('max_mass_validation_select');
        $user = $this->get('security.context')->getToken()->getUser();
        $joursCalendaires = $this->container->getParameter('jours_calendaires');

        $params = array();
        $tabBordEtab = array();
        $listeElection = array();
        $listeSousType = array();
        $etatsAvancement = array();

        //les élections RP ne sont pas concernées par les étabs 1er degré
        // $degre = null;
        // ECT ce bloc n'est plus utile
        // $listeTypeElection = $user->getPerimetre()->getTypeElections();
        /*foreach ($listeTypeElection as $typeElection) {
            if ($typeElection->getId()==RefTypeElection::ID_TYP_ELECT_ASS_ATE || $typeElection->getId()==RefTypeElection::ID_TYP_ELECT_PEE )  {
                $degre='2';
                break;
            }
        }*/
        $request = $this->get('request');

        // On recupere les champs selectionnés dans le 1er filtre
        $departement_numero = $request->request->get('departement_numero');
        $idTypeEtab = $request->request->get('idTypeEtab');
        $codeTypeElect = $request->request->get('codeTypeElect');
        $idSousTypeElection = $request->request->get('idSousTypeElect');
        $natEtab = $request->request->get('natEtab');

        // On recupere les champs selectionnés dans le 2eme filtre
        $etatSaisi = $request->request->get('etatSaisi');
        $etatNonEff = $request->request->get('etatNonEff');
        $etatValide = $request->request->get('etatValide');
        $etatTransmis = $request->request->get('etatTransmis');
        $pvCarence = $request->request->get('pvCarence');
        $nvElect = $request->request->get('nvElect');

        // aucune saisi n'est effectue
        if(!empty($etatNonEff)){
            $etatNonEff = true;
        }
        // etat saisi
        if (!empty($etatSaisi)){
            $etatsAvancement[] = EleEtablissement::ETAT_SAISIE;
        }
        // etat transmission
        if (!empty($etatTransmis)){
            $etatsAvancement[] = EleEtablissement::ETAT_TRANSMISSION;
        }
        // etat validation
        if(!empty($etatValide)){
            $etatsAvancement[] = EleEtablissement::ETAT_VALIDATION;
        }
        // indicateur si candidat
        $indCarence = null;
        if (!empty($pvCarence)){
            $indCarence = 1;
        }
        // Indicateur si deficit
        $indDeficit = null;
        if (!empty($nvElect)){
            $indDeficit = 1;
        }

        // on recupere la liste des type d'election
        if($codeTypeElect == RefTypeElection::CODE_PE){
            $listeElection[] = $em->getRepository('EPLEElectionBundle:RefTypeElection')->find(RefTypeElection::ID_TYP_ELECT_PARENT);
        } elseif($codeTypeElect == RefTypeElection::CODE_RP) {
            $listeElection[] = $em->getRepository('EPLEElectionBundle:RefTypeElection')->find(RefTypeElection::ID_TYP_ELECT_ASS_ATE);
            $listeElection[] = $em->getRepository('EPLEElectionBundle:RefTypeElection')->find(RefTypeElection::ID_TYP_ELECT_PEE);
        } else {
            $listeElection = null;
        }

        // on récupére le sous-type d'election
        if(null != $idSousTypeElection && !empty($idSousTypeElection)){
            if($idSousTypeElection == RefTypeElection::ID_TYP_ELECT_ASS_ATE || $idSousTypeElection == RefTypeElection::ID_TYP_ELECT_PEE){
                $sousTypeElection = $em->getRepository('EPLEElectionBundle:RefTypeElection')->find($idSousTypeElection);
            } else{
                $sousTypeElection = $em->getRepository('EPLEElectionBundle:RefSousTypeElection')->find($idSousTypeElection);
            }
        } else {
            $sousTypeElection = null;
        }

        //$listeTypeElection = $user->getPerimetre()->getTypeElections();
        $departement = $em->getRepository('EPLEElectionBundle:RefDepartement')->find($departement_numero);
        if(null != $idTypeEtab && !empty($idTypeEtab)){
            $typeEtab = $em->getRepository('EPLEElectionBundle:RefTypeEtablissement')->find($idTypeEtab);
        } else {
            $typeEtab = null;
        }

        // 014E exclus des EREA-ERPD dans les résultats ASS et ATE et PEE && (($typeEtab != null && $typeEtab->getCode() != RefTypeEtablissement::CODE_EREA_ERPD) || $typeEtab == null)
        $isEreaErpdExclus = false;
        if ($codeTypeElect == RefTypeElection::CODE_RP && (($typeEtab != null && $typeEtab->getCode () != RefTypeEtablissement::CODE_EREA_ERPD) || $typeEtab == null)) {
            $isEreaErpdExclus = true;
        }

        // on récupere la liste d'etablissements selon les criteres
        $liste_etablissement = $em->getRepository('EPLEElectionBundle:RefEtablissement')->findEtablissementParZone($departement,null, $typeEtab, null, null, true, '', null, $natEtab, $isEreaErpdExclus); // YME - HPQC DEFECT #242

        //$liste_etablissement = $em->getRepository('EPLEElectionBundle:RefEtablissement')->findEtablissementParZone($departement, $degre, $typeEtablissement, null, null, false,'', null, null, $natEtab, $idTypeElection, $idSousTypeElection, $campagne);

        foreach ($liste_etablissement as $key => $etab) {
            $tabBordEtab[$key]['Etab'] = $etab;
            // si c erea/erpd and pas de choix de type d'election alors on affiche que les parents
            if ($etab->getTypeEtablissement()->getCode() == RefTypeEtablissement::CODE_EREA_ERPD && $codeTypeElect == '' && (($typeEtab != null && $typeEtab->getCode () != RefTypeEtablissement::CODE_EREA_ERPD) || $typeEtab == null)) {
                $electionParent = array();
                $electionParent[] = $em->getRepository('EPLEElectionBundle:RefTypeElection')->find(RefTypeElection::ID_TYP_ELECT_PARENT);
                $tabBordEtab[$key]['EleEtab'] = $this->getEleInfosForEtab($etab, $user, $joursCalendaires, $electionParent, $sousTypeElection, $etatsAvancement, $indCarence, $indDeficit);
            } else {
                $tabBordEtab[$key]['EleEtab'] = $this->getEleInfosForEtab($etab, $user, $joursCalendaires, $listeElection, $sousTypeElection, $etatsAvancement, $indCarence, $indDeficit);
            }
        }

        $params = $this->getParametersTdbRecherche($request, $user);
        $params['maxMassValidationSelect'] = $maxMassValidationSelect;
        // Si aucune case n'est cochée
        if(empty($etatNonEff) && empty($etatSaisi) && empty($etatTransmis) && empty($etatValide) && empty($pvCarence) && empty($nvElect)){
            $params['afficheEleNull'] = true;
        } else {
            $params['afficheEleNull'] = false;
        }

        // Si on selectionne que les saisies non effectuées
        if(!empty($etatNonEff) && empty($etatSaisi) && empty($etatTransmis) && empty($etatValide) && empty($pvCarence) && empty($nvElect)){
            $params['nonEff'] = true;
            $params['afficheEleNull'] = true;
        }
        // si saisi non eff et (saisi ou trasmis ou valide), on affiche l'union
        if(!empty($etatNonEff) && (!empty($etatSaisi) || !empty($etatTransmis) || !empty($etatValide))){
            $params['afficheEleNull'] = true;
        }
        // si saisi non eff et carence ou pav on affiche rien car impossible
        if(!empty($etatNonEff) && (!empty($pvCarence) || !empty($nvElect))){
            $params['afficheEleNull'] = false;
        }

        // ECT recuperation des variables dans la session
        $nbTotalEtabAucunEnrSession = 'nbTotalEtabAucunEnrTab'.$departement_numero;
        $pourcentageCarenceSession = 'pourcentageCarenceTab'.$departement_numero;
        $pourcentageNewElectionSession = 'pourcentageNewElectionTab'.$departement_numero;
        $nbTotalCarenceSession = 'nbTotalCarenceTab'.$departement_numero;
        $nbTotalNewElectionSession = 'nbTotalNewElectionTab'.$departement_numero;

        $params['nbTotalEtabAucunEnr'] = $request->getSession()->get($nbTotalEtabAucunEnrSession);
        $params['nbTotalCarence'] = $request->getSession()->get($nbTotalCarenceSession);
        $params['nbTotalNewElection'] = $request->getSession()->get($nbTotalNewElectionSession);
        $params['pourcentageCarence'] = $request->getSession()->get($pourcentageCarenceSession);
        $params['pourcentageNewElection'] = $request->getSession()->get($pourcentageNewElectionSession);

        $params['departementSelectionne'] = $departement;
        $params['tabBordEtab'] = $tabBordEtab;

        return $this->render('EPLEElectionBundle:TableauDeBord:etabs_par_departement.html.twig', $params);
    }

    /**
     * YME - HPQC DEFECT 195
     */
    /*
    public function getEtablissementsByNumDepartementAction() {

        $params = array();

        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();

        // Récupération des champs du premier filtre
        $request = $this->get('request');
        $departement_numero = $request->request->get('departement_numero');
        $idTypeEtab = $request->request->get('idTypeEtab');
        $natEtab = $request->request->get('natEtab');
        $codeTypeElect = $request->request->get('codeTypeElect');
        $idSousTypeElect = $request->request->get('idSousTypeElect');

        // Récupération des champs du 2ème filtre
        $etatNonEff = $request->request->get('etatNonEff');
        $etatSaisi = $request->request->get('etatSaisi');
        $etatTransmis = $request->request->get('etatTransmis');
        $etatValide = $request->request->get('etatValide');
        $pvCarence = $request->request->get('pvCarence');
        $nvElect = $request->request->get('nvElect');

        $idTypesElect = array();
        if($codeTypeElect == RefTypeElection::CODE_PE){
            $idTypesElect[] = RefTypeElection::ID_TYP_ELECT_PARENT;
        }elseif ($codeTypeElect == RefTypeElection::CODE_RP){
            $idTypesElect[] = RefTypeElection::ID_TYP_ELECT_ASS_ATE;
            $idTypesElect[] = RefTypeElection::ID_TYP_ELECT_PEE;
        }

        $etatsAvancement = array();
        $ind_carence = false;
        $ind_deficit = false;
        if(!empty($etatNonEff)){
            $etatsAvancement[] = 'X';
        }
        if(!empty($etatSaisi)){
            $etatsAvancement[] = EleEtablissement::ETAT_SAISIE;
        }
        if(!empty($etatTransmis)){
            $etatsAvancement[] = EleEtablissement::ETAT_TRANSMISSION;
        }
        if(!empty($etatValide)){
            $etatsAvancement[] = EleEtablissement::ETAT_VALIDATION;
        }
        if(!empty($pvCarence)){
            $ind_carence = true;
        }
        if(!empty($nvElect)){
            $ind_deficit = true;
        }

        $tabBordEtab = $this->getEleInfosForEtabsTdb($departement_numero, null, $user, $idTypeEtab, $natEtab, $idTypesElect, $idSousTypeElect, $etatsAvancement, $ind_carence, $ind_deficit);

        $params = $this->getParametersTdbRecherche($request, $user);

        // ECT recuperation des variables dans la session
        $nbTotalEtabAucunEnrSession = 'nbTotalEtabAucunEnrTab'.$departement_numero;
        $pourcentageCarenceSession = 'pourcentageCarenceTab'.$departement_numero;
        $pourcentageNewElectionSession = 'pourcentageNewElectionTab'.$departement_numero;
        $nbTotalCarenceSession = 'nbTotalCarenceTab'.$departement_numero;
        $nbTotalNewElectionSession = 'nbTotalNewElectionTab'.$departement_numero;

        $params['nbTotalEtabAucunEnr'] = $request->getSession()->get($nbTotalEtabAucunEnrSession);
        $params['nbTotalCarence'] = $request->getSession()->get($nbTotalCarenceSession);
        $params['nbTotalNewElection'] = $request->getSession()->get($nbTotalNewElectionSession);
        $params['pourcentageCarence'] = $request->getSession()->get($pourcentageCarenceSession);
        $params['pourcentageNewElection'] = $request->getSession()->get($pourcentageNewElectionSession);

        $departement = $em->getRepository('EPLEElectionBundle:RefDepartement')->find($departement_numero);
        $params['departementSelectionne'] = $departement;
        $params['tabBordEtab'] = $tabBordEtab;

        return $this->render('EPLEElectionBundle:TableauDeBord:etabs_par_departement.html.twig', $params);

    }
    */


    private function getParametersRecherche(\Symfony\Component\HttpFoundation\Request $request, $user){

        $em = $this->getDoctrine()->getManager();
        $profilsLimitEtab = array(RefProfil::CODE_PROFIL_IEN, RefProfil::CODE_PROFIL_CE, RefProfil::CODE_PROFIL_DE);

        $params = array();
        $datasSearch = array();
        $listeSte = array();

        $datasSearch['academie'] = $em->getRepository('EPLEElectionBundle:RefAcademie')->find($request->getSession()->get('select_aca'));
        $datasSearch['departement'] = $em->getRepository('EPLEElectionBundle:RefDepartement')->find($request->getSession()->get('select_dept'));
        $datasSearch['typeEtablissement'] = $em->getRepository('EPLEElectionBundle:RefTypeEtablissement')->find($request->getSession()->get('select_typeEtablissement'));
        $datasSearch['natureEtablissement'] = $request->getSession()->get('select_natureEtablissement');
        $datasSearch['typeElection'] = $request->getSession()->get('select_typeElection');
        $datasSearch['sousTypeElection'] = $request->getSession()->get('select_sousTypeElection');

        // On recupere la liste des sous-type d'election
        $sousTypeElectionParams =  $em->getRepository('EPLEElectionBundle:RefSousTypeElection')->getSousTypesElections();
        $typeElectionParams = $em->getRepository('EPLEElectionBundle:RefTypeElection')->getTypesElections();
        $listeSte = $sousTypeElectionParams + $typeElectionParams;

        $form = $this->createForm(new TdbEtabType($user, $listeSte), $datasSearch);

        // a la validation du formulaire
        if ($request->getMethod() == 'POST') {
            $form->bind($request);
            if ($form->isValid()) {
                $datasForm = $form->getData();
                $datasSearch['natureEtablissement'] = $datasForm['natureEtablissement'];
                $datasSearch['typeEtablissement'] = $datasForm['typeEtablissement'];
                $datasSearch['typeElection'] = $datasForm['typeElection'];
                $datasSearch['sousTypeElection'] = $datasForm['sousTypeElection'];
                $datasSearch['academie'] = $datasForm['academie'];
                $datasSearch['departement'] = $datasForm['departement'];
            } else {
                $arrayRequest = $request->request->all();
                $arrayTdbEtabType = $arrayRequest['tdbEtabType'];
                $datasSearch['academie'] = $em->getRepository('EPLEElectionBundle:RefAcademie')->find($arrayTdbEtabType['academie']);
                $datasSearch['departement'] = $em->getRepository('EPLEElectionBundle:RefDepartement')->find($arrayTdbEtabType['departement']);
                $datasSearch['typeEtablissement'] = $em->getRepository('EPLEElectionBundle:RefTypeEtablissement')->find($arrayTdbEtabType['typeEtablissement']);
                $datasSearch['natureEtablissement'] = $em->getRepository('EPLEElectionBundle:RefZoneNature')->find($arrayTdbEtabType['natureEtablissement']);
                $datasSearch['typeElection'] = $em->getRepository('EPLEElectionBundle:RefTypeElection')->find($arrayTdbEtabType['typeElection']);
                $datasSearch['sousTypeElection'] = $em->getRepository('EPLEElectionBundle:RefSousTypeElection')->find($arrayTdbEtabType['sousTypeElection']);
            }

        }

        // echo $datasSearch['academie']->getCode();die();
        if (!empty($datasSearch['departement'])) {
            $zone = $datasSearch['departement'];
        } elseif (!empty($datasSearch['academie'])) {
            $zone = $datasSearch['academie'];
        } else {
            $zone = 'nationale';
        }

        if(!empty($datasSearch['typeEtablissement'])) {
            // Si le type d'etablissement et 1er degre, le type d'election est forcement PE
            if($datasSearch['typeEtablissement']->getId() == RefTypeEtablissement::ID_TYP_1ER_DEGRE) {
                $datasSearch['typeElection'] = RefTypeElection::CODE_PE;
            }
            //On recup un objet refTypeEtab
            $typeEtab = $datasSearch['typeEtablissement'];
        } else {
            $typeEtab = null;
        }

        if (!empty($datasSearch['typeElection'])) {
            // On récupere le code de type election pe ou rp
            $codeTypeElect = $datasSearch['typeElection'];
        } else {
            $codeTypeElect = null;
        }

        if (!empty($datasSearch['sousTypeElection'])) {
            //On récupere le code sous type Election
            $idSousTypeElect = $datasSearch['sousTypeElection'];
        } else {
            $idSousTypeElect = null;
        }

        if (!empty($datasSearch['natureEtablissement'])) {
            //On récupere le code nature
            $codeNatEtab = $datasSearch['natureEtablissement'];
        } else {
            $codeNatEtab = null;
        }

        $params['typeEtab']=$typeEtab;
        $params['zone']=$zone;
        $params['codeNatureEtab'] = $codeNatEtab;
        $params['codeTypeElect'] = $codeTypeElect;
        $params['idSousTypeElect'] = $idSousTypeElect;

        // Mise à jour des variables de session
        $request->getSession()->set('select_aca', ($datasSearch['academie'] instanceof RefAcademie) ? $datasSearch['academie']->getIdZone() : null);
        $request->getSession()->set('select_dept', ($datasSearch['departement'] instanceof RefDepartement) ? $datasSearch['departement']->getIdZone() : null);
        $request->getSession()->set('select_typeEtablissement', ($datasSearch['typeEtablissement'] instanceof RefTypeEtablissement) ? $datasSearch['typeEtablissement']->getId() : null);
        $request->getSession()->set('select_natureEtablissement', $codeNatEtab);
        $request->getSession()->set('select_typeElection', $codeTypeElect);
        $request->getSession()->set('select_sousTypeElection', $idSousTypeElect);
        // Serialisation des paramètres et mise en session
        //$request->getSession()->set('params', serialize($params));

        if($codeTypeElect == null || $codeTypeElect == RefTypeElection::CODE_RP)
            $params['info'] = $this->container->getParameter('info');

        $params['form'] = $form->createView();
        return $params;

    }

    private function getParametersTdbRecherche(\Symfony\Component\HttpFoundation\Request $request, $user){

        $em = $this->getDoctrine()->getManager();
        $params = array();

        $form = $this->createForm(new TdbZoneEtabType($user));

        $params['form'] = $form->createView();
        return $params;

    }

    /**
     * YME - HPQC DEFECT #195
     * @param unknown $departement_numero
     * @param unknown $user
     * @return Ambigous <multitype:, unknown>
     */
    private function getEleInfosForEtabsTdb($departement_numero, $uais, $user, $idTypeEtab = null, $natEtab = null, $idTypesElect = null, $idSousTypeElect = null,  $etatsAvancement = null, $ind_carence = null, $ind_deficit = null){

        $em = $this->getDoctrine()->getManager();
        $joursCalendaires = $this->container->getParameter('jours_calendaires');

        $tabBord = array();
        $k = 0;
        $campagneIds = array();

        $listeTypeElection = $user->getPerimetre()->getTypeElections();
        foreach ($listeTypeElection as $typeElection) {
            $campagne = $em->getRepository('EPLEElectionBundle:EleCampagne')->getLastCampagne($typeElection);
            $campagneIds[] = $campagne->getId();
        }

        // Récupération des EleEtabs
        $datas = $em->getRepository('EPLEElectionBundle:EleEtablissement')->findEtbsByCampagneZone($campagneIds, $departement_numero, $uais, $idTypeEtab, $natEtab, $idTypesElect, $idSousTypeElect,  $etatsAvancement, $ind_carence, $ind_deficit);

        foreach($datas as $data){

            $afficheEleEtab = true;
            $eleEtab = array();

            // Pas d'élection RP pour les établissements du premier degré
            if($data['degre'] == '1' && ($data['id_type_election'] == RefTypeElection::ID_TYP_ELECT_ASS_ATE || $data['id_type_election'] == RefTypeElection::ID_TYP_ELECT_PEE)){
                $afficheEleEtab = false;
            }

            if($afficheEleEtab){

                $refEtablissement = new RefEtablissement();

                $refEtablissement->setUai($data['uai']);
                $refEtablissement->setLibelle($data['libelle']);
                $refEtablissement->setActif($data['actif']);

                $typeEtablissement = new RefTypeEtablissement();
                $typeEtablissement->setDegre($data['degre']);

                $refEtablissement->setTypeEtablissement($typeEtablissement);

                $typeElection = new RefTypeElection();
                $typeElection->setId($data['id_type_election']);
                $typeElection->setCode($data['code_type_election']);
                $eleEtab['typeElection'] = $typeElection;

                $campagne = new EleCampagne($typeElection);
                $campagne->setId($data['id_campagne']);
                $campagne->setArchivee($data['archivee'] == 1 ? true : false);
                $campagne->setAnneeDebut($data['annee_debut']);
                $campagne->setAnneeFin($data['annee_fin']);
                $eleEtab['campagne'] = $campagne;

                if(null == $data['id']){
                    $eleEtab['eleEtablissement'] = null;
                }else{
                    $eleEtablissement = new EleEtablissement();
                    $eleEtablissement->setId($data['id']);
                    $eleEtablissement->setValidation($data['validation']);
                    $eleEtablissement->setEtablissement($refEtablissement);
                    $eleEtablissement->setCampagne($campagne);
                    $eleEtab['eleEtablissement'] = $eleEtablissement;
                }



                // Détermine si la campagne est ouverte à la saisie et / à la validation pour cet ele_etab
                $date_debut_saisie = new \DateTime($data['date_debut_saisie']);
                $date_fin_saisie = new \DateTime($data['date_fin_saisie']);
                $date_debut_validation = new \DateTime($data['date_debut_validation']);
                $date_fin_validation = new \DateTime($data['date_fin_validation']);
                $today = new \DateTime();
                $today->setTime(0, 0, 0);
                $decalage = false;
                // TODO YME A CORRIGER 145940
                foreach ($user->getPerimetre()->getAcademies() as $academie){
                    if($academie->getCode() == RefAcademie::CODE_ACA_MAYOTTE || $academie->getCode() == RefAcademie::CODE_ACA_REUNION){
                        $decalage = true;
                        break;
                    }
                }

                if (!$decalage){
                    $campagneOpenSaisie = (null != $data['date_debut_saisie']) && (null != $data['date_fin_saisie']) && ($date_debut_saisie <= $today && $date_fin_saisie >= $today);
                    $campagneOpenValidation = (null != $data['date_debut_validation']) && (null != $data['date_fin_validation']) && ($date_debut_validation <= $today && $date_fin_validation >= $today);
                }else{
                    $campagneOpenSaisie = (null != $data['date_debut_saisie']) && (null != $data['date_fin_saisie']) && ($date_debut_saisie->modify('-'.$joursCalendaires.' day') <= $today && $date_fin_saisie->modify('-'.$joursCalendaires.' day') >= $today);
                    $campagneOpenValidation = ($date_debut_validation->modify('-'.$joursCalendaires.' day') <= $today && $date_fin_validation->modify('-'.$joursCalendaires.' day') >= $today);
                }

                $eleEtab['campagneOpenSaisie'] = $campagneOpenSaisie;
                $eleEtab['campagneOpenValidation'] = $campagneOpenValidation;

                // Détermine si l'établissement est dans le périmètre utilisateur
                switch ($user->getProfil()->getCode()) {
                    case RefProfil::CODE_PROFIL_CE:
                    case RefProfil::CODE_PROFIL_DE:
                    case RefProfil::CODE_PROFIL_IEN:
                        foreach($user->getPerimetre()->getEtablissements() as $etablissement){
                            if($etablissement->getUai() == $data['uai']){
                                $etabInScope = true;
                                break;
                            }
                        }
                        break;

                    case RefProfil::CODE_PROFIL_DSDEN:
                    case RefProfil::CODE_PROFIL_RECT:
                        foreach($user->getPerimetre()->getDepartements() as $departement){
                            if($departement->getNumero() == $departement_numero){
                                $etabInScope = true;
                                break;
                            }
                        }

                    default:
                        $etabInScope = true;
                        break;
                }


                // Test de saisie possible des résultats des élections pour un ele_etablissement
                $saisie = $etabInScope && (null == $data['id'] || EleEtablissement::ETAT_SAISIE == $data['validation']);
                if($saisie){
                    // Test type d'élection
                    if ($data['id_type_election'] == RefTypeElection::ID_TYP_ELECT_PARENT) {
                        $saisie = in_array('ROLE_SAISIE_RES_PAR', $user->getRoles());
                    } else {
                        $saisie = in_array('ROLE_SAISIE_RES_PE', $user->getRoles());
                    }
                }

                // Saisie possible que pour les établissements du premier degré pour les élections de parents.
                // Un DSDEN peut saisir les resultats de l'election parents d'eleves a la place d'un directeur d'ecole
                // pour une campagne modifiable en periode de validation
                // Un rectorat peut saisir les resultats des elections ASS/ATE et PEE � la place d'un chef d'etablissement
                // pour une campagne modifiable en periode de validation

                if($saisie){

                    $saisie =
                        ( $campagneOpenSaisie
                            && ($user->getProfil()->getCode() != RefProfil::CODE_PROFIL_DSDEN && $user->getProfil()->getCode() != RefProfil::CODE_PROFIL_RECT)
                            && ((( $data['id_type_election'] == RefTypeElection::ID_TYP_ELECT_ASS_ATE || $data['id_type_election'] == RefTypeElection::ID_TYP_ELECT_PEE ) && $data['degre'] == '2' ) || $data['id_type_election'] == RefTypeElection::ID_TYP_ELECT_PARENT)
                        )
                        ||
                        ($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DSDEN
                            && $data['post_editable'] == '1'
                            && $campagneOpenValidation
                            && ($data['id_type_election'] == RefTypeElection::ID_TYP_ELECT_PARENT))
                        ||
                        ($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_RECT
                            && $data['post_editable'] == '1'
                            && $campagneOpenValidation
                            && (RefTypeElection::ID_TYP_ELECT_ASS_ATE == $data['id_type_election'] || RefTypeElection::ID_TYP_ELECT_PEE == $data['id_type_election'])
                            && $data['degre'] == '2');
                }

                $eleEtab['saisiePossible'] = $saisie;


                // Affichage de la case à cocher validation des résultats
                // La validation n'est disponible que pour les résultats transmis
                $validation = $etabInScope && $campagneOpenValidation && (EleEtablissement::ETAT_TRANSMISSION == $data['validation']);
                if($validation){
                    if ($data['id_type_election'] == RefTypeElection::ID_TYP_ELECT_PARENT) {
                        $validation = in_array('ROLE_VALID_RES_PAR', $user->getRoles());
                    } else {
                        $validation = in_array('ROLE_VALID_RES_PE', $user->getRoles());
                    }
                }

                $eleEtab['validationPossible'] = $validation;
                $eleEtab['validation'] = $data['validation'];


                // Label en attente de nouvelles elections
                $enAttenteNllesElec = ($data['id_type_election'] == RefTypeElection::ID_TYP_ELECT_ASS_ATE || $data['id_type_election'] == RefTypeElection::ID_TYP_ELECT_PEE);

                // Etablissement du second degré
                if($enAttenteNllesElec){
                    $enAttenteNllesElec = ($data['degre'] == '2');
                }

                // Cas de déficit de candidats
                if($enAttenteNllesElec){
                    $enAttenteNllesElec = ($data['ind_deficit'] == 1);
                }

                // Saisie transmise
                if($enAttenteNllesElec){
                    $enAttenteNllesElec = (EleEtablissement::ETAT_TRANSMISSION == $data['validation']);
                }

                $eleEtab['en_attente_nlles_elec'] = $enAttenteNllesElec;


                // Test de saisie d'élection en cas de déficit de candidats
                $canSaisieNouvelleElection = $enAttenteNllesElec;
                if((null != $data['id']) && $canSaisieNouvelleElection){

                    // Test périmètre et ouverture campagne
                    $canSaisieNouvelleElection = $etabInScope && $campagneOpenSaisie;

                    // Test rôle
                    if($canSaisieNouvelleElection){
                        $canSaisieNouvelleElection = ($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DSDEN || $user->getProfil()->getCode() == RefProfil::CODE_PROFIL_RECT);
                    }

                }

                $eleEtab['canSaisieNouvelleElection'] = $canSaisieNouvelleElection;

                // Récupération des alertes
                $eleEtab['Deficit'] = false;
                $eleEtab['Carence'] = false;
                if(EleEtablissement::ETAT_TRANSMISSION == $data['validation']){
                    $eleEtab['Deficit'] = ($data['code_type_alerte'] == RefTypeAlerte::CODE_DEFICIT);
                    $eleEtab['Carence'] = ($data['code_type_alerte'] == RefTypeAlerte::CODE_CARENCE);
                }

                // Affichage de la case à cocher envoi de courriel
                $envoiCourriel = $campagneOpenSaisie && ($data['actif'] == 1);

                if($envoiCourriel){
                    $envoiCourriel = (null == $data['id'] || EleEtablissement::ETAT_SAISIE == $data['validation']);
                }

                // Création du lien sous-type election
                /*
                if($envoiCourriel){
                    $tabBord[$key]['id_type_election_courriel'] = '';
                    if (null != $data['id_sous_type_election']){
                        $eleEtablissement['id_type_election_courriel'] .= '#';
                    }
                    $eleEtablissement['id_type_election_courriel'] .= $data['id_type_election'];
                }*/

                $eleEtab['envoiCourrielPossible'] = $envoiCourriel;

                // Test tirage au sort possible (IEN)
                $saisiePVTirageAuSortPossible = $etabInScope && $campagneOpenSaisie && ($eleEtab['Deficit'] || $eleEtab['Carence']);
                if ($saisiePVTirageAuSortPossible){
                    $saisiePVTirageAuSortPossible = ($data['ind_tirage_sort'] == 0);
                }

                if ($saisiePVTirageAuSortPossible){
                    // Test type election et role saisie tirage au sort
                    if ($data['id_type_election'] == RefTypeElection::ID_TYP_ELECT_PARENT) {
                        $saisiePVTirageAuSortPossible = in_array('ROLE_SAISIE_TS_PAR', $user->getRoles());
                    } else {
                        $saisiePVTirageAuSortPossible = in_array('ROLE_SAISIE_TS_PE', $user->getRoles()); // rôle non attribué mais pour anticiper selon ECT
                    }
                }

                if ($saisiePVTirageAuSortPossible) {
                    $saisiePVTirageAuSortPossible = ($data['ind_carence'] == 1 || $data['ind_deficit'] == 1);
                }

                if ($saisiePVTirageAuSortPossible) {
                    $saisiePVTirageAuSortPossible = (EleEtablissement::ETAT_TRANSMISSION == $data['validation']);
                }

                $eleEtab['saisiePVTirageAuSortPossible'] = $saisiePVTirageAuSortPossible;



                if(!empty($tabBord[$k]['Etab'])){

                    //echo $tabBord[$k]['Etab']->getUai().' / '.$refEtablissement->getUai();

                    if($tabBord[$k]['Etab']->getUai() != $refEtablissement->getUai()){
                        // Changement d'établissement
                        //echo ' -> Changement </br>';
                        $k++;
                        $tabBord[$k]['Etab'] = $refEtablissement;
                        $tabBord[$k]['EleEtab'] = array();
                    }
                    //$tabBord[$k]['EleEtab'][] = $eleEtab;
                    $this->addSousTypesElections($data, $eleEtab, $tabBord[$k]['EleEtab']);
                }else{
                    // Cas particulier Premier établissement
                    $tabBord[$k]['Etab'] = $refEtablissement;
                    $tabBord[$k]['EleEtab'] = array();
                    //$tabBord[$k]['EleEtab'][] = $eleEtab;
                    $this->addSousTypesElections($data, $eleEtab, $tabBord[$k]['EleEtab']);
                }

            }

        }

        return $tabBord;

    }

    /**
     * Cas particulier des EREA-ERPD pour le tableau de bord par département
     * @param unknown $eleEtab
     * @param unknown $array
     * @return unknown
     */
    private function addSousTypesElections($data, $eleEtab, &$array){

        $em = $this->getDoctrine()->getManager();

        if(null == $eleEtab['eleEtablissement']
            && $data['id_type_election'] == RefTypeElection::ID_TYP_ELECT_ASS_ATE
            && $data['id_type_etablissement'] == RefTypeEtablissement::ID_TYP_EREA_ERPD){
            $sousTypesElection = $em->getRepository('EPLEElectionBundle:RefSousTypeElection')->findSousTypesElectionsByRefTypeElection($data['id_type_election']);

            foreach($sousTypesElection as $sousTypeElection){
                $eleEtab['hasSousType'] = '#';
                $eleEtab['typeElection'] = $sousTypeElection;
                $array[] = $eleEtab;
            }
        }else if(null != $data['id_sous_type_election']){
            $sousTypeElection = new RefSousTypeElection();
            $sousTypeElection->setId($data['id_sous_type_election']);
            $sousTypeElection->setCode($data['code_sous_type_election']);
            $eleEtab['hasSousType'] = '#';
            $eleEtab['typeElection'] = $sousTypeElection;
            $array[] = $eleEtab;
        }else{
            $array[] = $eleEtab;
        }

        return $array;
    }

}