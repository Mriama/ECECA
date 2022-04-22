<?php

namespace App\Controller;

use App\Entity\RefContact;
use App\Utils\EpleUtils;
use App\Entity\RefProfil;
use App\Form\CourrielType;
use App\Entity\EleCampagne;
use App\Entity\RefAcademie;
use App\Entity\RefDepartement;
use App\Entity\RefTypeElection;
use App\Form\CourrielLibreType;
use App\Utils\EcecaExportUtils;
use App\Entity\RefEtablissement;
use App\Entity\RefSousTypeElection;
use App\Entity\RefTypeEtablissement;
use Doctrine\Persistence\ManagerRegistry;
use Swift_Mailer;
use Swift_Message;
use Symfony\Component\HttpFoundation\RequestStack;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class CourrielController extends AbstractController {

    private $request;
    private $doctrine;

    public function __construct(RequestStack $request, ManagerRegistry $doctrine) {
        $this->request = $request->getCurrentRequest();
        $this->doctrine = $doctrine;
    }

    /**
     * Relance : envois de courriels pour motiver la saisie
     * @param integer $typeElectionId : identifiant type élection
     * @param $idZone : identifiant de zone
     * @throws AccessDeniedException
     */
    public function relanceAction($typeElectionId, $idZone) {
        if (false === $this->isGranted('ROLE_CONTACT_CE')) {
            throw new AccessDeniedException();
        }

        $params = array();
        $em = $this->doctrine->getManager();
        $this->request->getSession()->getBag('attributes')->set('id_typeElection', $typeElectionId);
        $this->request->getSession()->getBag('attributes')->set('id_zone', $idZone);

        $typeElection = $em->getRepository(RefTypeElection::class)->find($typeElectionId);

        // Identification de l'expéditeur
        $user = $this->getUser();
        $params['expediteur'] = null;
        $params["copies"] = array();

        if($this->request->getSession()->get('cte_mail') != null && $this->request->getSession()->get('cte_mail') != "") {
            $params['expediteur'] = $this->request->getSession()->get('cte_mail');
            $params["copies"][] = "Me mettre en copie (" . $params['expediteur'] . ")";
        } else {
            $params['expediteur'] = $this->getParameter('mailer_from');
        }
        $this->request->getSession()->getBag('attributes')->set('expediteur', $params['expediteur']);

        //Identification des personnes a mettre en copie
        $contacts = null;
        //Mise en copie des contacts departementaux et academiques concernés
        if($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DSDEN) {
            $contact = $em->getRepository(RefContact::class)->findUniqueContactByZoneAndTypeElection($idZone, $typeElection);
            if(!empty($contact[0]) && $contact[0]->getEmail1() != "" && !in_array($contact[0]->getEmail1(), $params['copies'])) {
                $params['copies'][] = $contact[0]->getEmail1();
            }
            if (!empty($contact[0]) && $contact[0]->getTypeElection()->getId() == RefTypeElection::ID_TYP_ELECT_PARENT && $contact[0]->getEmail2() != "" && !in_array($contact[0]->getEmail2(), $params['copies'])) {
                $params['copies'][] = $contact[0]->getEmail2();
            }

            $zone = EpleUtils::getZone($em, $idZone);
            if ($zone instanceof RefDepartement) {
                $zoneAca = EpleUtils::getAcademieCodeFromDepartement($zone);
                $contactAca = $em->getRepository(RefContact::class)->findUniqueContactByZoneAndTypeElection($zoneAca, $typeElection);
                if(!empty($contactAca[0]) && $contactAca[0]->getEmail1() != "" && !in_array($contactAca[0]->getEmail1(), $params['copies'])) {
                    $params['copies'][] = $contactAca[0]->getEmail1();
                }
            }
        }

        //Mise en copie des contacts academiques concernés
        elseif ($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_RECT) {
            $contact = $em->getRepository(RefContact::class)->findUniqueContactByZoneAndTypeElection($idZone, $typeElection);
            if(!empty($contact[0]) && $contact[0]->getEmail1() != "" && !in_array($contact[0]->getEmail1(), $params['copies'])) {
                $params['copies'][] = $contact[0]->getEmail1();
            }
        }

        //Identification des établissements qui n'ont pas encore saisi leurs résultats
        $zone = EpleUtils::getZone($em, $idZone);
        // $listeEtabs = $em->getRepository(RefEtablissement::class)->findEtablissementsARelancer($campagne, $zone, $user);
        // 	0167467 Optimisation pour accés à la page de relance
        if ($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DGESCO && $zone instanceof RefAcademie) {
            $params['nbEtabs'] = $this->request->getSession()->get('nbEtabArelancer'.$typeElectionId.$zone->getCode());
        } elseif (($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_RECT || $user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DSDEN) && $zone instanceof RefDepartement) {
            $params['nbEtabs'] = $this->request->getSession()->get('nbEtabArelancer'.$typeElectionId.$zone->getNumero());
        } else {
            $params['nbEtabs'] = $this->request->getSession()->get('nbEtabArelancer'.$typeElectionId);
        }

        $this->request->getSession()->getBag('attributes')->set('nbEtabArelancer', $params['nbEtabs']);

        // les params pour l'export XLS
        $params['typeElectionId'] = $typeElectionId;
        $params['idZone'] = $idZone;
        $params['canExportEtab'] = ($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_RECT || $user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DSDEN);

        // 014E retour depuis la page courriel
        if ($zone instanceof RefDepartement || ($zone instanceof RefAcademie && $user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DGESCO)) {
            $this->request->getSession()->set('tdbDeplieRetour', true);
        }
        $form = $this->createForm(CourrielType::class, null, ["copies" => $params['copies']]);
        $this->request->getSession()->set('choix_copies', $params['copies']);

        $params['form'] = $form->createView();

        return $this->render('courriel/relance.html.twig', $params);
    }

    public function massRelanceAction() {
        if (false === $this->isGranted('ROLE_CONTACT_CE')) {
            throw new AccessDeniedException();
        }
        $user = $this->getUser();

        ini_set('memory_limit','512M');
        //$user = $this->get('security.context')->getToken()->getUser();
        $params = array();
        $em = $this->doctrine->getManager();

        // Récupération des établissements à relancer
        $listeTypeElection = array();
        $uais = array();
        $nbEtabs = 0;
        $nbEtabsReel = 0;
        foreach($this->request->request as $key=>$value){
            if($key == "dept"){
                $params["dept"] = $value;
            } else if($key == "nbEtabRelance") {
                $nbEtabs = intval($value);
            } else {
                $pos_uai = strpos ($key, '_');
                $pos_election = strrpos ($key, '_');
                $uai =  substr($key, 0, $pos_uai);
                $idElection = substr($key, $pos_election+1, strlen($key));

                if(!empty($idElection) && !in_array($idElection, $listeTypeElection)) {
                    $listeTypeElection[] = $idElection;
                }
                // defect#265
                if (!empty($uai) && !in_array($uai, $uais)) {
                    $uais[] = $uai;
                    $nbEtabsReel++;
                }
            }
        }

        $this->request->getSession()->getBag('attributes')->set('massRelanceEtabs', $uais);
        $params['nbEtabs'] = $nbEtabs == $nbEtabsReel ? $nbEtabs : $nbEtabsReel;

        // Identification de l'expéditeur
        $params['expediteur'] = null;
        $params['copies'] = array();
        if($this->request->getSession()->get('cte_mail') != null && $this->request->getSession()->get('cte_mail') != "") {
            $params['expediteur'] = $this->request->getSession()->get('cte_mail');
            $params["copies"][] = "Me mettre en copie (" . $params['expediteur'] . ")";
        } else {
            $params['expediteur'] = $this->getParameter('mailer_from');
        }

        //Identification des contacts à mettre en copie
        $contacts = array();
        $contactsDep = array();
        $contactsAca = array();
        //Mise en copie des contacts departementaux et academiques concernés
        if($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DSDEN) {
            $zone = EpleUtils::getZone($em, $params["dept"]);
            foreach ($listeTypeElection as $idTypeElection) {
                $typeElection = $em->getRepository(RefTypeElection::class)->find($idTypeElection);
                $contactDepTmp = $em->getRepository(RefContact::class)->findUniqueContactByZoneAndTypeElection($params["dept"], $typeElection);
                if(!empty($contactDepTmp[0]) && $contactDepTmp[0]->getEmail1() != "" && !in_array($contactDepTmp[0]->getEmail1(), $contactsDep)) {
                    $contactsDep[] = $contactDepTmp[0]->getEmail1();
                }
                if (!empty($contactDepTmp[0]) && $contactDepTmp[0]->getTypeElection()->getId() == RefTypeElection::ID_TYP_ELECT_PARENT && $contactDepTmp[0]->getEmail2() != "" && !in_array($contactDepTmp[0]->getEmail2(), $contactsDep)) {
                    $contactsDep[] = $contactDepTmp[0]->getEmail2();
                }

                if ($zone instanceof RefDepartement) {
                    $zoneAca = EpleUtils::getAcademieCodeFromDepartement($zone);
                    $contactAcaTmp = $em->getRepository(RefContact::class)->findUniqueContactByZoneAndTypeElection($zoneAca, $typeElection);
                    if(!empty($contactAcaTmp[0]) && $contactAcaTmp[0]->getEmail1() != "" && !in_array($contactAcaTmp[0]->getEmail1(), $contactsAca)) {
                        $contactsAca[] = $contactAcaTmp[0]->getEmail1();
                    }
                }
            }
        }
        //Mise en copie des contacts academiques concernés
        elseif ($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_RECT) {
            foreach ($listeTypeElection as $idTypeElection) {
                $typeElection = $em->getRepository(RefTypeElection::class)->find($idTypeElection);
                $contactAcaTmp = $em->getRepository(RefContact::class)->findUniqueContactByZoneAndTypeElection($user->getIdZone(), $typeElection);
                if(!empty($contactAcaTmp[0]) && $contactAcaTmp[0]->getEmail1() != "" && !in_array($contactAcaTmp[0]->getEmail1(), $contactsAca)) {
                    $contactsAca[] = $contactAcaTmp[0]->getEmail1();
                }
            }
        }

        $contacts = array_merge($contactsDep, $contactsAca);
        $params['copies'] = array_merge($params['copies'], $contacts);

        $form = $this->createForm(CourrielType::class, null, ["copies" => $params['copies']]);
        $this->request->getSession()->set('choix_copies', $params['copies']);

        $params['form'] = $form->createView();

        // les params pour l'export XLS
        $params['canExportEtab'] = ($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_RECT || $user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DSDEN);
        $this->request->getSession()->set('tdbDeplieRetour', true);
        $etab = $em->getRepository(RefEtablissement::class)->find($uais[0]);
        $this->request->getSession()->set('dept_num', $etab->getCommune()->getDepartement()->getNumero());
        $this->request->getSession()->set('expediteur', $params['expediteur']);

        return $this->render('courriel/massRelance.html.twig', $params);
    }

    /**
     * Envoi des courriels
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */

    public function envoiAction(Swift_Mailer $mailer) {
        $user = $this->getUser();
        $em = $this->doctrine->getManager();

        $choix_copies = $this->request->getSession()->get('choix_copies');
        $form = $this->createForm(CourrielType::class, null, ["copies" => $choix_copies]);

        if ($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DGESCO ) {
            ini_set('memory_limit','512M');
            set_time_limit (60);
        }

        if ($this->request->getMethod() == 'POST') {

            $form->handleRequest($this->request);

            if ($form->isSubmitted() && $form->isValid()) {

                //Identification de la campagne
                $typeElectionId = $this->request->getSession()->getBag('attributes')->get('id_typeElection');
                if ($typeElectionId == RefSousTypeElection::ID_TYP_ELECT_A_ATTE || $typeElectionId == RefSousTypeElection::ID_TYP_ELECT_SS)
                    $typeElectionId = RefTypeElection::ID_TYP_ELECT_ASS_ATE;
                $campagne = $em->getRepository(EleCampagne::class)->getLastCampagne($typeElectionId);
                if (empty($campagne)) { throw $this->createNotFoundException('La campagne est inconnue (typeElectionId = '.$typeElectionId.').'); }

                //Identification de la zone
                $idZone = $this->request->getSession()->getBag('attributes')->get('id_zone');
                $zone = EpleUtils::getZone($em, $idZone);
                if (empty($zone)) { throw $this->createNotFoundException('La zone est inconnue ('. $idZone .').'); }

                // 0167453 recuperer les filtres dans le tdb
                $natureEtab = $this->request->getSession()->get('natureEtab');
                $typeEtab = $this->request->getSession()->get('typeEtab');

                // 014E exclus des EREA-ERPD dans les résultats ASS et ATE et PEE && (($typeEtab != null && $typeEtab->getCode() != RefTypeEtablissement::CODE_EREA_ERPD) || $typeEtab == null)
                $isEreaErpdExclus = false;
                if (($typeElectionId == RefTypeElection::ID_TYP_ELECT_ASS_ATE || $typeElectionId == RefTypeElection::ID_TYP_ELECT_PEE)
                    && (($typeEtab != null && $typeEtab->getCode () != RefTypeEtablissement::CODE_EREA_ERPD) || $typeEtab == null)) {
                    $isEreaErpdExclus = true;
                }

                $mailFrom = $this->request->getSession()->getBag('attributes')->get('expediteur');
                $nbEtabArelancer = $this->request->getSession()->getBag('attributes')->get('nbEtabArelancer');


                // envoi de mail par paquet pour eviter la saturation de la memoire serveur par 1000 mails
                $offset = 0;
                while ($nbEtabArelancer > $offset) {
                    //Identification des établissements qui n'ont pas encore saisi leurs résultats
                    $listeEtabs = $em->getRepository(RefEtablissement::class)->findEtablissementsARelancer($campagne, $zone, $typeEtab, $natureEtab, $isEreaErpdExclus, $offset);

                    $tabAdresses = array();
                    if ($listeEtabs != null) {
                        foreach ($listeEtabs as $etab) {
                            // BBL 167423 blinder la table des adresses mails
                            if ($etab->getContact() != null && $etab->getContact() != "") {
                                $tabAdresses [$etab->getContact()] = $etab->getContact();
                            }
                        }
                    }

                    // Création de l'e-mail à envoyer
                    if (!empty($tabAdresses)) {
                        $dataRequestArray = $form->getData();
                        $objet = $dataRequestArray['objet'];
                        $corps = $dataRequestArray['message'];

                        $courriel = (new Swift_Message())
                            ->setFrom($mailFrom)
                            ->setTo($tabAdresses)
                            ->setSubject($objet)
                            ->setBody($corps)
                        ;

                        if ($dataRequestArray['choix_copies']) {
                            $copies = array();
                            foreach ($dataRequestArray['choix_copies'] as $idContact) {
                                if(strpos($choix_copies[$idContact], "Me mettre en copie (") !== false) {
                                    $copies[] = str_replace("Me mettre en copie (", "", substr($choix_copies[$idContact], 0, -1));
                                } else {
                                    $copies[] = $choix_copies[$idContact];
                                }
                            }
                            $courriel->setCc($copies);
                        }

                        // Mettre en place un serveur smtp pour tester l'envoi de mails
                        $mailer->send($courriel);

                    } else {
                        // message d'erreur
                    }
                    $offset += 1000;
                }

                //Redirection vers le tableau de bord par défaut
                return $this->redirect($this->generateUrl('ECECA_tableau_bord'));
            }
        }
    }

    /**
     * Envoi des courriels
     *
     * @return RedirectResponse
     */

    public function massEnvoiAction(Swift_Mailer $mailer) {
        $em = $this->doctrine->getManager();
        $user = $this->getUser();
        $choix_copies = $this->request->getSession()->get('choix_copies');
        $form = $this->createForm(CourrielType::class, null, ["copies" => $choix_copies]);


        if ($this->request->getMethod() == 'POST') {
            $form->handleRequest($this->request);
            if ($form->isSubmitted() && $form->isValid()) {

                // Recherche des établissements à relancer
                $uais = $this->request->getSession()->getBag('attributes')->get('massRelanceEtabs');
                $tabAdresses = array();

                $etablissements = $em->getRepository(RefEtablissement::class)->findListEtablissementsByUais($uais);
                foreach ($etablissements as $etab){
                    // BBL 167423 blinder la table des adresses mails
                    if ($etab->getContact() != null && $etab->getContact() != "") {
                        array_push($tabAdresses, $etab->getContact());
                    }
                }

                // Expéditeur
                $mailFrom = $this->request->getSession()->getBag('attributes')->get('expediteur');

                // Création de l'e-mail à envoyer
                if (!empty($tabAdresses)) {
                    $dataRequestArray = $form->getData();
                    $objet = $dataRequestArray['objet'];
                    $corps = $dataRequestArray['message'];

                    $courriel = (new Swift_Message())
                        ->setFrom($mailFrom)
                        ->setTo($tabAdresses)
                        ->setSubject($objet)
                        ->setBody($corps)
                    ;

                    if ($dataRequestArray['choix_copies']) {
                        $copies = array();
                        foreach ($dataRequestArray['choix_copies'] as $idContact) {
                            if(strpos($choix_copies[$idContact], "Me mettre en copie (") !== false) {
                                $copies[] = str_replace("Me mettre en copie (", "", substr($choix_copies[$idContact], 0, -1));
                            } else {
                                $copies[] = $choix_copies[$idContact];
                            }
                        }
                        $courriel->setCc($copies);
                    }
                    $mailer->send($courriel);
                } else {
                    // message d'erreur
                }

                //Redirection vers le tableau de bord par défaut
                return $this->redirect($this->generateUrl('ECECA_tableau_bord'));
            }
        }
    }


    /**
     * Envoi de courriel libre
     * @param $codeUrlTypeElect
     * @throws AccessDeniedException
     * @return Response
     */
    public function envoiLibreAction(Swift_Mailer $mailer, $codeUrlTypeElect) {
        $em = $this->doctrine->getManager();
        if (false === $this->isGranted('ROLE_ENV_COUR_LIB_CE')) {
            throw new AccessDeniedException();
        }

        $user = $this->getUser();
        $joursCalendaires = $this->getParameter('jours_calendaires');

        // Récupération du type d'election
        $idTypeElect = RefTypeElection::getIdRefTypeElectionByCodeUrl($codeUrlTypeElect);
        $typeElection = $idTypeElect != null ? $em->getRepository(RefTypeElection::class)->find($idTypeElect) : null;
        if(empty($typeElection)){
            throw $this->createNotFoundException('Le type d\'élection n\'a pas été trouvé.');
        }

        // Campagne
        $campagne = $em->getRepository(EleCampagne::class)->getLastCampagne($typeElection->getId());
        if(empty($campagne)){
            throw $this->createNotFoundException('Les résultats de l\'établissement n\'ont pas été trouvés car la campagne est inconnue.');
        }

        $params = array();

        $params['alert']= $this->getParameter('alert');

        $params['typeElect'] = $typeElection;

        $form = $this->createForm(CourrielLibreType::class, null);
        $params['form'] = $form->createView();

        if ($this->request->getMethod() == 'POST') {
            $form->handleRequest($this->request);
            if ($form->isSubmitted() && $form->isValid()) {
                $dataRequestArray = $form->getData();

                $libelleAcademie = $dataRequestArray['contacts_academie'];
                $codeAcademie = $dataRequestArray['code_academie'];
                $libelleDepartement = $dataRequestArray['contacts_departementaux'];
                $numeroDepartement = $dataRequestArray['numero_departement'];
                $libelleEtablissement = $dataRequestArray['contacts_etablissements'];
                $uaiEtablissement = $dataRequestArray['uai_etablissement'];
                $objet = $dataRequestArray['objet'];
                $corps = $dataRequestArray['message'];

                // expediteur
                $expediteur = null;
                $idZone = null;
                $refUserPerimetre = $user->getPerimetre();
                if (sizeof($refUserPerimetre->getDepartements()) > 1) {
                    $academies = $refUserPerimetre->getAcademies();
                    $academieSelected = $academies[0];
                    $dateCampagneDebut = new \DateTime($campagne->getAnneeDebut() . '-01-01');
                    // 018E : Gérer fusion académie
                    if($academieSelected->getAcademieFusion() != null && $academieSelected->getDateDesactivation() <= $dateCampagneDebut) {
                        $academieSelected = $academieSelected->getAcademieFusion();
                    }
                    $idZone = $academieSelected->getCode();
                } else {
                    $departements = $refUserPerimetre->getDepartements();
                    // mantis 0146481 envoi de courriel libre
                    if($departements != null && !empty($departements)) {
                        $idZone = $departements[0]->getNumero();
                    }
                }
                if ($idZone === null) {
                    $expediteur = $this->getParameter('mailer_from');
                } else {
                    $contacts = $em->getRepository(RefContact::class)->findRefContactsByIdZoneTypeElection($idZone, $typeElection, $user);
                    if (!empty($contacts)) {
                        $expediteur = $contacts[0]->getEmail1();
                    } else {
                        $expediteur = $this->getParameter('mailer_from');
                    }
                }

                // destinataires
                $tabAdresses = array();

                // si academie seulement => recuperer les contacts de cette academie
                if (!empty($codeAcademie) && empty($numeroDepartement) && empty($uaiEtablissement) && empty($libelleEtablissement)) {
                    $contactsAcademie = $em->getRepository(RefContact::class)->findRefContactsByIdZoneTypeElection($codeAcademie, $typeElection);
                    foreach ($contactsAcademie as $contactAcademie) {
                        // BBL 167423 blinder la table des adresses mails
                        if ($contactAcademie->getEmail1() != null && $contactAcademie->getEmail1() != "")
                            array_push($tabAdresses, $contactAcademie->getEmail1());
                        if (!EpleUtils::isAcademie($contactAcademie->getIdZone()) &&
                            $typeElection->getId() == RefTypeElection::ID_TYP_ELECT_PARENT &&
                            !in_array($contactAcademie->getEmail2(), $tabAdresses) &&
                            $contactAcademie->getEmail2() != null &&
                            $contactAcademie->getEmail2() != ""
                        ) {
                            array_push($tabAdresses, $contactAcademie->getEmail2());
                        }
                    }
                }

                // si departement => recuperer les contacts du departement
                if (!empty($numeroDepartement) && empty($uaiEtablissement) && empty($libelleEtablissement)) {
                    $contactsDepartement = $em->getRepository(RefContact::class)->findRefContactsByIdZoneTypeElection($numeroDepartement, $typeElection);
                    foreach ($contactsDepartement as $contactDepartement) {
                        // BBL 167423 blinder la table des adresses mails
                        if ($contactDepartement->getEmail1() != null && $contactDepartement->getEmail1() != "")
                            array_push($tabAdresses, $contactDepartement->getEmail1());
                        if ($typeElection->getId() == RefTypeElection::ID_TYP_ELECT_PARENT &&
                            !in_array($contactDepartement->getEmail2(), $tabAdresses) &&
                            $contactDepartement->getEmail2() != null &&
                            $contactDepartement->getEmail2() != ""
                        ) {
                            array_push($tabAdresses, $contactDepartement->getEmail2());
                        }
                    }
                }

                // si etablissement => recuperer le contact de cet etablissement
                if (!empty($uaiEtablissement) && !empty($libelleEtablissement)) {
                    $etablissement = $em->getRepository(RefEtablissement::class)->find($uaiEtablissement);
                    if ($etablissement != null) {
                        // BBL 167423 blinder la table des adresses mails
                        if ($etablissement->getContact() != null && $etablissement->getContact() != "")
                            array_push($tabAdresses, $etablissement->getContact());
                    }
                }

                // mail
                if (!empty($tabAdresses)) {
                    $courriel = (new Swift_Message())
                        ->setFrom($expediteur)
                        ->setTo($tabAdresses)
                        ->setSubject($objet)
                        ->setBody($corps)
                    ;

                    $mailer->send($courriel);

                    $params['courriel_envoye'] = "Courriel envoyé";

                } else {
                    $params['courriel_non_envoye'] = "Courriel non envoyé : problème de destinataire";
                }
            }
        }
        return $this->render('courriel/courrielLibre.html.twig', $params);
    }

    /**
     * exportEtablissementsXLS : export XLS des établissements n'ayant pas d'adresses mail
     * @param integer $typeElectionId : identifiant type élection
     * @param $idZone : identifiant de zone
     * @throws AccessDeniedException
     */

    public function exportEtablissementsSansMailXLSAction($typeElectionId, $idZone) {
        $user = $this->getUser();
        if ($user->getProfil()->getCode() != RefProfil::CODE_PROFIL_RECT && $user->getProfil()->getCode() != RefProfil::CODE_PROFIL_DSDEN) {
            throw new AccessDeniedException();
        }

        $params = array();
        $em = $this->doctrine->getManager();

        //Identification de la campagne
        $campagne = $em->getRepository(EleCampagne::class)->getLastCampagne($typeElectionId);
        if (empty($campagne)) { throw $this->createNotFoundException('La campagne est inconnue.'); }

        //Identification des établissements qui n'ont pas encore saisi leurs résultats
        $zone = EpleUtils::getZone($em, $idZone);
        $listeEtabsSansMails = $em->getRepository(RefEtablissement::class)->findEtablissementsWithoutMail($campagne, $zone);

        // **************** Création de l'objet Excel *********************//
        $spreadsheet = new Spreadsheet();

        // Export
        $this->generateEtablissementsXLS($listeEtabsSansMails, $spreadsheet);

        // Création du writer
        $writer = new Xlsx($spreadsheet, 'Excel5');

        // Créer la réponse
        $response =  new StreamedResponse(
            function () use ($writer) {
                $writer->save('php://output');
            }
        );
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment;filename= ExportEtablissementsSansAdresseMail_' . $campagne->getAnneeDebut() . '-' . $campagne->getAnneeFin() . '.xls');
        return $response;

    }

    /**
     * exportEtablissementsXLS : export XLS des établissements n'ayant pas d'adresses mail
     * @throws AccessDeniedException
     */

    public function exportMasseEtablissementsSansMailXLSAction() {
        $user = $this->getUser();
        if ($user->getProfil()->getCode() != RefProfil::CODE_PROFIL_RECT && $user->getProfil()->getCode() != RefProfil::CODE_PROFIL_DSDEN) {
            throw new AccessDeniedException();
        }

        $params = array();
        $em = $this->doctrine->getManager();

        //Identification des établissements qui n'ont pas encore saisi leurs résultats
        $uais = $this->request->getSession()->getBag('attributes')->get('massRelanceEtabs');
        $listeEtab = $em->getRepository(RefEtablissement::class)->findListEtablissementsByUais($uais);
        $listeEtabsSansMails = array();
        foreach ($listeEtab as $etab) {
            if ($etab->getContact() == "")
                array_push($listeEtabsSansMails, $etab);
        }

        // **************** Création de l'objet Excel *********************//
        $spreadsheet = new Spreadsheet();

        // Export
        $this->generateEtablissementsXLS($listeEtabsSansMails, $spreadsheet);

        // Création du writer
        $writer = new Xlsx($spreadsheet, 'Excel5');
        // Créer la réponse
        $anneeScolaire = EcecaExportUtils::getAnneeScolaireEncours();
        $response =  new StreamedResponse(
            function () use ($writer) {
                $writer->save('php://output');
            }
        );
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment;filename= ExportEtablissementsSansAdresseMail_'. $anneeScolaire .'.xls');
        return $response;

    }


    /**
     * findAllAcademieAction retourne la liste de toutes les académies
     * à utiliser uniquement pour l'envoi de courriel libre
     * @return Response
     **/
    public function findAllAcademieAction() {
        $em = $this->doctrine->getManager();
        $typeElect = $this->request->request->get('typeElect');

        $lastCampagne = $em->getRepository(EleCampagne::class)->getLastCampagne($typeElect);
        $campagneDebut = new \DateTime($lastCampagne->getAnneeDebut(). "-01-01");
        $campagneFin = new \DateTime($lastCampagne->getAnneeDebut(). "-12-31");
        $liste_academies = $em->getRepository(RefAcademie::class)->findAll();
        $academies = array();
        foreach ($liste_academies as $key => $uneAcademie){
            $codeFusion = null;
            $display = true;
            if($uneAcademie->getAcademieFusion() != null && $uneAcademie->getDateDesactivation() <= $campagneDebut) {
                $codeFusion = $uneAcademie->getAcademieFusion()->getCode();
                $display = false;
            } else if($uneAcademie->getDateActivation() >= $campagneFin) {
                $display = false;
            }
            $academies[$key]['code'] = $uneAcademie->getCode();
            $academies[$key]['codeFusion'] = $codeFusion;
            $academies[$key]['display'] = $display;
            $academies[$key]['libelle'] = $uneAcademie->getLibelle();
        }
        $return = array('responseCode' => 200, 'academies' => $academies);
        $return = json_encode($return); // json encode the array
        return new Response($return, 200, array('Content-Type' => 'application/json'));
    }

    /**
     * findDepartementsByCodeAcademieAction retourne la liste des départements en fonction d'un code académie
     * à utiliser uniquement pour l'envoi de courriel libre
     * @return Response
     **/
    public function findDepartementsByCodeAcademieAction() {
        $em = $this->doctrine->getManager();
        $user = $this->getUser();
        $academie_code = $this->request->request->get('academie_code');

        $departements = array();
        //DSDEN multi-departements
        if ($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DSDEN) {
            $liste_departement = $user->getPerimetre()->getDepartements();
        } else {
            if ($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_RECT) {
                $academie_code = $user->getIdZone();
            }

            if (!empty($academie_code)) {
                $codesAca = array();
                array_push($codesAca, $academie_code);
                //018E : Check child from fusion
                $childrenAcademie = $em->getRepository(RefAcademie::class)->getchildnewAcademies($academie_code);
                if($childrenAcademie != null && !empty($childrenAcademie)) {
                    foreach ($childrenAcademie as $aca) {
                        array_push($codesAca, $aca->getCode());
                    }
                }
                $paramsSearchDept = array('academie' =>  $codesAca);
                $liste_departement = $em->getRepository(RefDepartement::class)->findBy($paramsSearchDept, array('libelle' => 'ASC'));
            } else {
                $liste_departement = $em->getRepository(RefDepartement::class)->findAll();
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
    }

    /**
     * findEtablissementsByZoneAndUaiOrLibelleAction retourne la liste des établissements
     * en fonction d'un (code academie ou numero departement) et (d'un uai ou libelle etablissement)
     * à utiliser uniquement pour l'envoi de courriel libre
     * @return Response
     */
    public function findEtablissementsByZoneAndUaiOrLibelleAction() {
        $em = $this->doctrine->getManager();
        $user = $this->getUser();
        $uai_or_libelle = $this->request->request->get('uai_or_libelle');
        $academie_code = $this->request->request->get('academie_code');
        $departement_numero = $this->request->request->get('departement_numero');

        //on recupere une liste des listes des établissements dans le périmetre du DSDEN
        if ($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DSDEN) {
            $liste_zone = array();
            $lst_departements = $user->getPerimetre()->getDepartements();
            foreach ($lst_departements as $departement){
                $lst_etablissement=$em->getRepository(RefEtablissement::class)->findEtablissementByZoneAndUaiOrLibelle($departement, $uai_or_libelle);
                array_push($liste_zone, $lst_etablissement);
            }
        } else {
            if ($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_RECT) {
                $academie_code = $user->getIdZone();
            }

            if ($departement_numero != null && !empty($departement_numero)) {
                $departement = $em->getRepository(RefDepartement::class)->find($departement_numero);
                $liste_etablissement = $em->getRepository(RefEtablissement::class)->findEtablissementByZoneAndUaiOrLibelle($departement, $uai_or_libelle);
            } else if ($academie_code != null && !empty($academie_code)) {
                //018E : Check child from fusion
                $liste_etablissement = array();
                $academie = $em->getRepository(RefAcademie::class)->find($academie_code);
                $childrenAcademie = $em->getRepository(RefAcademie::class)->getchildnewAcademies($academie_code);
                $liste_etablissementTmp = $em->getRepository(RefEtablissement::class)->findEtablissementByZoneAndUaiOrLibelle($academie, $uai_or_libelle);
                $liste_etablissement = array_merge($liste_etablissement, $liste_etablissementTmp);
                if ($childrenAcademie != null && !empty($childrenAcademie)) {
                    foreach ($childrenAcademie as $aca) {
                        $liste_etablissementTmp = $em->getRepository(RefEtablissement::class)->findEtablissementByZoneAndUaiOrLibelle($aca, $uai_or_libelle);
                        $liste_etablissement = array_merge($liste_etablissement, $liste_etablissementTmp);
                    }
                }
            } else {
                $liste_etablissement = $em->getRepository(RefEtablissement::class)->findEtablissementsByUaiOrLibelle($uai_or_libelle);
            }
        }

        $etablissements = array();
        $i = 0;
        // la liste globale de tous les établissements dans le périmetre du DSDEN
        if($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DSDEN){
            foreach ($liste_zone as $liste_etablissement){
                foreach ($liste_etablissement as $unEtablissement) {
                    if ($unEtablissement->getCommune() != null) {
                        if ($unEtablissement->getCommune()->getDepartement() != null) {
                            $etablissements[$i]['uai'] = $unEtablissement->getUai();
                            $etablissements[$i]['departement'] = $unEtablissement->getCommune()->getDepartement()->getNumero();
                            $etablissements[$i]['libelle'] = $unEtablissement->getLibelle();
                            $i++;
                        }
                    }
                }
            }
        }else  {
            foreach ($liste_etablissement as $unEtablissement) {
                if ($unEtablissement->getCommune() != null) {
                    if ($unEtablissement->getCommune()->getDepartement() != null) {
                        $etablissements[$i]['uai'] = $unEtablissement->getUai();
                        $etablissements[$i]['departement'] = $unEtablissement->getCommune()->getDepartement()->getNumero();
                        $etablissements[$i]['libelle'] = $unEtablissement->getLibelle();
                        $i++;
                    }
                }
            }
        }
        $return = array('responseCode' => 200, 'etablissements' => $etablissements);
        $return = json_encode($return); // json encode the array
        return new Response($return, 200, array('Content-Type' => 'application/json'));
    }

    /**
     * findEtablissementByUaiOrLibelleAction retourne la liste des etablissements en fonction d'un uai ou d'un libelle etablissement
     * à utiliser uniquement pour l'envoi de courriel libre
     * @return Response
     */
    public function findEtablissementByUaiOrLibelleAction() {
        $em = $this->doctrine->getManager();
        $user = $this->getUser();
        $uai_or_libelle = $this->request->request->get('uai_or_libelle');

        if ($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_RECT) {
            $academie_code = $user->getIdZone();
            //018E : Check child from fusion
            $liste_etablissement = array();
            $academie = $em->getRepository(RefAcademie::class)->find($academie_code);
            $childrenAcademie = $em->getRepository(RefAcademie::class)->getchildnewAcademies($academie_code);
            $liste_etablissementTmp = $em->getRepository(RefEtablissement::class)->findEtablissementByZoneAndUaiOrLibelle($academie, $uai_or_libelle);
            $liste_etablissement = array_merge($liste_etablissement, $liste_etablissementTmp);
            if($childrenAcademie != null && !empty($childrenAcademie)) {
                foreach ($childrenAcademie as $aca) {
                    $liste_etablissementTmp = $em->getRepository(RefEtablissement::class)->findEtablissementByZoneAndUaiOrLibelle($aca, $uai_or_libelle);
                    $liste_etablissement = array_merge($liste_etablissement, $liste_etablissementTmp);
                }
            }
        } else if ($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DSDEN) {//DSDEN multi-departements
            $liste_zone = array();
            $lst_departements = $user->getPerimetre()->getDepartements();
            foreach ($lst_departements as $departement){
                $lst_etablissement=$em->getRepository(RefEtablissement::class)->findEtablissementByZoneAndUaiOrLibelle($departement, $uai_or_libelle);
                array_push($liste_zone, $lst_etablissement);
            }
        } else {
            $liste_etablissement = $em->getRepository(RefEtablissement::class)->findEtablissementsByUaiOrLibelle($uai_or_libelle);
        }

        $etablissements = array();
        $i = 0;
        if($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DSDEN){// affichage de la liste globale des etablissements du périmetre du DSDEN
            foreach ($liste_zone as $liste_etablissement){
                foreach ($liste_etablissement as $unEtablissement) {
                    if ($unEtablissement->getCommune() != null) {
                        if ($unEtablissement->getCommune()->getDepartement() != null) {
                            $etablissements[$i]['uai'] = $unEtablissement->getUai();
                            $etablissements[$i]['departement'] = $unEtablissement->getCommune()->getDepartement()->getNumero();
                            $etablissements[$i]['libelle'] = $unEtablissement->getLibelle();
                            $i++;
                        }
                    }
                }
            }
        }else  {
            foreach ($liste_etablissement as $unEtablissement) {
                if ($unEtablissement->getCommune() != null) {
                    if ($unEtablissement->getCommune()->getDepartement() != null) {
                        $etablissements[$i]['uai'] = $unEtablissement->getUai();
                        $etablissements[$i]['departement'] = $unEtablissement->getCommune()->getDepartement()->getNumero();
                        $etablissements[$i]['libelle'] = $unEtablissement->getLibelle();
                        $i++;
                    }
                }
            }
        }


        $return = array('responseCode' => 200, 'etablissements' => $etablissements);
        $return = json_encode($return); // json encode the array
        return new Response($return, 200, array('Content-Type' => 'application/json'));
    }

    /**
     * Fonction permettant la génération des établissements
     * @param array $data : tableau array contenant les données à exporter
     * @return $ligne
     */
    private function generateEtablissementsXLS($data, &$spreadsheet)
    {
        $em = $this->doctrine->getManager();

        $sheet = $spreadsheet->getActiveSheet();
        $styleArray = array(
            'font' => array(
                'bold' => true
            )
        );

        $spreadsheet->setActiveSheetIndex(0)->setCellValue('A1', 'UAI/RNE');
        $sheet->getStyle('A1')->applyFromArray($styleArray);
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('B1', 'Libellé');
        $sheet->getStyle('B1')->applyFromArray($styleArray);
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('C1', 'Nature d\'établissement');
        $sheet->getStyle('C1')->applyFromArray($styleArray);
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('D1', 'Code postal');
        $sheet->getStyle('D1')->applyFromArray($styleArray);
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('E1', 'Commune');
        $sheet->getStyle('E1')->applyFromArray($styleArray);
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('F1', 'Département');
        $sheet->getStyle('F1')->applyFromArray($styleArray);

        $ligne = 2;
        if ($data != null && sizeof($data) > 0) {
            foreach($data as $etab){
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('A' . $ligne, $etab->getUai());
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . $ligne, $etab->getLibelle());
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('C' . $ligne, $etab->getUaiNature() != null ? $etab->getUaiNature()->getLibelleLong() : '');
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('D' . $ligne, $etab->getCommune()->getCodePostal()." ");
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('E' . $ligne, $etab->getCommune()->getLibelle());
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('F' . $ligne, $etab->getCommune()->getDepartement()->getLibelle() . " (" . $etab->getCommune()->getDepartement()->getNumero() . ")");
                $ligne ++;
            }
        }

        // Activer la 1ère feuille
        $spreadsheet->setActiveSheetIndex(0);

        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(50);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(20);

        return $ligne;
    }

}
