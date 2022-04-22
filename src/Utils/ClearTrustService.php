<?php

namespace App\Utils;

use App\Entity\RefUser;
use App\Entity\RefTypeElection;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Utils\ClearTrustSoapClient;

/**
 * Service clearTrust
 * Permet l'authentification par analyse des en-têtes HTTP
 * @author Atos/a176206
 *
 */
class ClearTrustService{

    const APP_NAME = "ECECA";
    const ETAB_N_UAJ = "ETAB_N1_UAJ";
    const ETAB_DSDEN = "ETAB_DSDEN";

    const FONCT_ADM_IEN = 'IEN1D';
    const FONCT_ADM_DIR = 'DIR';

    const IEN_LOGIN = 'IEN';
    const CE_LOGIN = 'CE';
    const DE_LOGIN = 'DE';
    const DSDEN_LOGIN = 'DSDEN';
    const ECECA_ACA = 'ECECA-ACA';
    const ECECA_ADC = 'ECECA-ADC';
    const ADMIN_LOGIN = 'DGESCO';

    private $em; //EntityManager
    private $refUserPerimetreService;
    private $container; // Service container
    private $logger;

    public function __construct(ManagerRegistry $doctrine, RefUserPerimetre $refUserPerimetreService, ContainerInterface $container,
                                LoggerInterface $connexionLogger) {
        $this->em = $doctrine->getManager();
        $this->refUserPerimetreService = $refUserPerimetreService;
        $this->container = $container;
        $this->logger = $connexionLogger;
    }

    public function login(Request $request) {
        $this->logger->info('CleartrustService.login() - DEBUT');

        //Récupération du header
        $httpHeader = $request->headers;

        //Le vecteur transmis contient les informations suivantes
        $FrEduRneResp = $httpHeader->get('FrEduRneResp'); //Etablissements en responsabilité de l’agent connecté Multi-valué
        $FrEduResDel = $httpHeader->get('FrEduResDel'); //Délégation/attribution de l’agent connecté  ouvrant des droits d’accès à une ressource d’une application pour un ou plusieurs établissements	Multi-valué
        $FrEduFonctAdm = $httpHeader->get('FrEduFonctAdm'); //Fonction administrative de l’agent connecté correspondant à un profil particulier	Monovalué
        $FrEduGestResp  = $httpHeader->get('FrEduGestResp'); //Etablissements dont l’agent connecté à la responsabilité de gestion	Multi-valué
        $ctemail = $httpHeader->get('ctemail');//Mél de l’agent connecté	Monovalué
        $codaca = $httpHeader->get('codaca');//Code académique de l’agent connecté	Monovalué
        $FrEduURLretour = $httpHeader->get('FrEduURLretour');//URL à utiliser pour retourner sur le portail appelant de l’académie sans déconnexion	Monovalué
        $ctgrps = $httpHeader->get('ctgrps'); //Ensemble des groupes LDAP applicatifs dans lesquels est inscrit l’agent connecté	Multi-valué

        $this->logger->info('CleartrustService.login() - FrEduRneResp - '.$FrEduRneResp);
        $this->logger->info('CleartrustService.login() - FrEduResDel - '.$FrEduResDel);
        $this->logger->info('CleartrustService.login() - FrEduFonctAdm - '.$FrEduFonctAdm);
        $this->logger->info('CleartrustService.login() - FrEduGestResp - '.$FrEduGestResp);
        $this->logger->info('CleartrustService.login() - ctemail - '.$ctemail);
        $this->logger->info('CleartrustService.login() - codaca - '.$codaca);
        $this->logger->info('CleartrustService.login() - FrEduURLretour - '.$FrEduURLretour);
        $this->logger->info('CleartrustService.login() - ctgrps - '.$ctgrps);

        // Liste des établissements en responsabilité
        $listeEtablissementsResponsabilite = array();

        // Liste des départements en responsabilité (DSDEN uniquement)
        $listeDepartementsResponsabilite = array();

        // Liste code aca depuis le fichier parameters.yml
        $listeCodesAca = array();

        // Liste des emails DGESCO depuis le fichier parameters.yml
        $listeMailsDgesco = array();

        // Login de l'utilisateur à connecter
        $login = null;

        // Type d'élection autorisée
        $typesElectionAutorises = array();

        $wsdl_location = $this->container->getParameter("wsdl_location");
        $wsdl_uri = $this->container->getParameter("wsdl_uri");

        // Consommation du WS par appel
        try {
            $this->logger->info('CleartrustService.login() - Appel du composant de sécurité');
            $soapClient = new ClearTrustSoapClient($wsdl_location);
            $soapClient->setWsdlUri($wsdl_uri);

            // Cas d’un personnel en rectorat (ctgrps contient ECECA-ACA)
            // Cas d’un personnel de l’administration (ctgrps contient ECECA-ADC)
            if(null != $ctgrps && 0 != strcmp("", $ctgrps) && (preg_match("`ECECA\-ACA`", $ctgrps) || preg_match("`ECECA\-ADC`", $ctgrps))){
                $headers = array(
                    'ctgrps' => $ctgrps,
                    'codaca' => $codaca
                );
                $utilisateur = new getUtilisateur();
                foreach ( $headers as $key => $value )
                {
                    $httpHeader = new headerWrapper();
                    $httpHeader->key = $key;
                    $httpHeader->value = $value;
                    $utilisateur->httpHeaders [] = $httpHeader;
                }

                $utilisateurWrapper = $soapClient->getUtilisateur($utilisateur)->utilisateur;

                foreach($utilisateurWrapper->groupes as $groupe) {
                    // si le ctgrps est multivalué
                    if (is_array($groupe)) {
                        foreach($groupe as $grp) {
                            if(0 == strcmp(self::ECECA_ACA, $grp)) {
                                // Recuperer les liste codes aca et mails depuis parameters.yml
                                $listeCodesAca = explode("_", $this->container->getParameter('liste_code_aca_dgesco'));
                                $listeMailsDgesco = explode(",", $this->container->getParameter('liste_mail_dgesco'));

                                // 0170915 si le grp est code aca et le codaca dans la liste des codes aca et le ctemail dans la liste des mails DGESCO alors le compte prend le profil d'administrateur DGESCO
                                if (in_array(substr($codaca, 1), $listeCodesAca) && in_array($ctemail, $listeMailsDgesco))
                                    $login = self::ADMIN_LOGIN;
                                else
                                    $login = $utilisateurWrapper->academie;
                                break;
                            } else if(0 == strcmp(self::ECECA_ADC, $grp)) {
                                $login = self::ADMIN_LOGIN;
                            }
                        }
                    } else {// si le ctgrps est mono valué
                        if(0 == strcmp(self::ECECA_ACA, $groupe)) {
                            // Recuperer les liste codes aca et mails depuis parameters.yml
                            $listeCodesAca = explode("_", $this->container->getParameter('liste_code_aca_dgesco'));
                            $listeMailsDgesco = explode(",", $this->container->getParameter('liste_mail_dgesco'));

                            // 0170915 si le grp est code aca et le codaca dans la liste des codes aca et le ctemail dans la liste des mails DGESCO alors le compte prend le profil d'administrateur DGESCO
                            if (in_array(substr($codaca, 1), $listeCodesAca) && in_array($ctemail, $listeMailsDgesco))
                                $login = self::ADMIN_LOGIN;
                            else
                                $login = $utilisateurWrapper->academie;
                            break;

                        } else if(0 == strcmp(self::ECECA_ADC, $groupe)) {

                            $login = self::ADMIN_LOGIN;
                        }
                    }
                }
            }

            // Cas d’un chef d’établissement et de son adjoint (FrEduFonctAdm = DIR et FrEduResp contient $PU$)
            if(null != $FrEduFonctAdm && 0 == strcmp(self::FONCT_ADM_DIR, $FrEduFonctAdm)){
                $login = self::CE_LOGIN;

                array_push($typesElectionAutorises, RefTypeElection::ID_TYP_ELECT_ASS_ATE);
                array_push($typesElectionAutorises, RefTypeElection::ID_TYP_ELECT_PEE);
                array_push($typesElectionAutorises, RefTypeElection::ID_TYP_ELECT_PARENT);

                //Utiliser la méthode getEtablissementResp (FrEduRneResp, ETAB_N_UAJ)
                if(null != $FrEduRneResp && 0 != strcmp('', $FrEduRneResp)) {
                    $headers = array(
                        'FrEduRneResp' => $FrEduRneResp
                    );
                    $etab = new getEtablissementsResp();
                    foreach ( $headers as $key => $value )
                    {
                        $httpHeader = new headerWrapper();
                        $httpHeader->key = $key;
                        $httpHeader->value = $value;
                        $etab->httpHeaders [] = $httpHeader;
                    }
                    $etab->nomApp = self::APP_NAME;
                    $etab->typeExtraction = self::ETAB_N_UAJ;

                    $etablissementWrapper = $soapClient->getEtablissementsResp($etab)->etablissements;

                    foreach($etablissementWrapper as $tabEtablissement)
                    {
                        // YME - 167107 - CE
                        if(is_array($tabEtablissement)) {
                            foreach($tabEtablissement as $etablissement) {
                                if(null != $etablissement->codeTTY && 0 != strcmp('', $etablissement->codeTTY)) {
                                    if(in_array($etablissement->codeTTY, array('LP', 'LYC', 'CLG', 'EREA', 'ERPD'))) {
                                        //Placer l'établissement dans la liste des établissements en responsabilité de l'utilisateur
                                        $listeEtablissementsResponsabilite[] = $etablissement->codeRne;
                                    }
                                }
                            }
                        }
                        else {
                            if(null != $tabEtablissement->codeTTY && 0 != strcmp('', $tabEtablissement->codeTTY)) {
                                if(in_array($tabEtablissement->codeTTY, array('LP', 'LYC', 'CLG', 'EREA', 'ERPD'))) {
                                    //Placer l'établissement dans la liste des établissements en responsabilité de l'utilisateur
                                    $listeEtablissementsResponsabilite[] = $tabEtablissement->codeRne;
                                }
                            }
                        }
                    }
                }

                //Utiliser la méthode getEtablissementResDel (FrEduResDel, ETAB_N_UAJ, ECECA)
                $headers = array(
                    'FrEduResDel' => $FrEduResDel
                );
                $etab = new getEtablissementsDeleg();
                foreach ( $headers as $key => $value ) {
                    $httpHeader = new headerWrapper();
                    $httpHeader->key = $key;
                    $httpHeader->value = $value;
                    $etab->httpHeaders [] = $httpHeader;
                }

                $etab->typeExtraction = self::ETAB_N_UAJ;
                $etablissementWrapper = $soapClient->getEtablissementsDeleg($etab)->etablissements;

                foreach($etablissementWrapper as $tabEtablissement) {
                    // YME - 167107
                    if(is_array($tabEtablissement)) {
                        foreach($tabEtablissement as $etablissement) {
                            if(null != $etablissement->codeTTY && 0 != strcmp('', $etablissement->codeTTY)) {
                                if(in_array($etablissement->codeTTY, array('LP', 'LYC', 'CLG', 'EREA', 'ERPD'))) {
                                    //Placer l'établissement dans la liste des établissements en responsabilité de l'utilisateur
                                    $listeEtablissementsResponsabilite[] = $etablissement->codeRne;
                                }
                            }
                        }
                    }
                    else {
                        if(null != $tabEtablissement->codeTTY && 0 != strcmp('', $tabEtablissement->codeTTY)) {
                            if(in_array($tabEtablissement->codeTTY, array('LP', 'LYC', 'CLG', 'EREA', 'ERPD'))) {
                                //Placer l'établissement dans la liste des établissements en responsabilité de l'utilisateur
                                $listeEtablissementsResponsabilite[] = $tabEtablissement->codeRne;
                            }
                        }
                    }
                }
            }


            // Cas d’un IEN (FrEduFonctAdm = IEN1D)
            if(null != $FrEduFonctAdm && 0 == strcmp(self::FONCT_ADM_IEN, $FrEduFonctAdm)) {
                $login = self::IEN_LOGIN;

                array_push($typesElectionAutorises, RefTypeElection::ID_TYP_ELECT_PARENT);

                //Utiliser la méthode getEtablissementResp (FrEduRneResp, ETAB_N_UAJ)
                if(null != $FrEduRneResp && 0 != strcmp('', $FrEduRneResp)) {
                    $headers = array(
                        'FrEduRneResp' => $FrEduRneResp
                    );
                    $etab = new getEtablissementsResp();
                    foreach ( $headers as $key => $value )
                    {
                        $httpHeader = new headerWrapper();
                        $httpHeader->key = $key;
                        $httpHeader->value = $value;
                        $etab->httpHeaders [] = $httpHeader;
                    }
                    $etab->nomApp = self::APP_NAME;
                    $etab->typeExtraction = self::ETAB_N_UAJ;

                    $etablissementWrapper = $soapClient->getEtablissementsResp($etab)->etablissements;

                    foreach($etablissementWrapper as $tabEtablissement) {
                        foreach($tabEtablissement as $etablissement) {
                            if(null != $etablissement->codeTTY && 0 != strcmp('', $etablissement->codeTTY)) {
                                if(in_array($etablissement->codeTTY, array('1ORD', 'APPL', 'SPEC', 'IEN'))) {
                                    //Placer l'établissement dans la liste des établissements en responsabilité de l'utilisateur
                                    $listeEtablissementsResponsabilite[] = $etablissement->codeRne;
                                }
                            }
                        }
                    }
                }
            }


            // Cas d'un directeur d'école (FrEduResDEL contient ECECA|/redirectionhub)
            // Cas d’un personnel ayant reçu délégation du chef d’établissement (FrEduResDEL contient ECECA|/redirectionhub)
            if(0 != strcmp(self::FONCT_ADM_DIR, $FrEduFonctAdm) && null != $FrEduResDel && preg_match("`ECECA(.*)redirectionhub`", $FrEduResDel) && $this->checkDateFrEduResDel($FrEduResDel)) {
                $headers = array(
                    'FrEduResDel' => $FrEduResDel
                );

                $etab = new getEtablissementsDeleg();
                foreach ( $headers as $key => $value ) {
                    $httpHeader = new headerWrapper();
                    $httpHeader->key = $key;
                    $httpHeader->value = $value;
                    $etab->httpHeaders [] = $httpHeader;
                }

                $etab->nomApp = self::APP_NAME;
                $etab->typeExtraction = self::ETAB_N_UAJ;

                $etablissementWrapper = $soapClient->getEtablissementsDeleg($etab)->etablissements;

                $isDE = false;

                foreach($etablissementWrapper as $tabEtablissement) {

                    // YME - 167107
                    if(is_array($tabEtablissement)){
                        foreach($tabEtablissement as $etablissement) {
                            if(null != $etablissement->codeTTY && 0 != strcmp('', $etablissement->codeTTY))
                            {
                                // Mantis 0171865 Attention aux types d'etablissement 1er degré
                                if(in_array($etablissement->codeTTY, array('1ORD', 'APPL', 'SPEC')))
                                {
                                    $isDE = true;
                                    //Placer l'établissement dans la liste des établissements en responsabilité de l'utilisateur
                                    $listeEtablissementsResponsabilite[] = $etablissement->codeRne;
                                }
                                else if(in_array($etablissement->codeTTY, array('LP', 'LYC', 'CLG', 'EREA', 'ERPD')))
                                {
                                    //Placer l'établissement dans la liste des établissements en responsabilité de l'utilisateur
                                    $listeEtablissementsResponsabilite[] = $etablissement->codeRne;
                                }
                            }
                        }
                    } else {
                        if(null != $tabEtablissement->codeTTY && 0 != strcmp('', $tabEtablissement->codeTTY)) {
                            // Mantis 0171865 Attention aux types d'etablissement 1er degré
                            if(in_array($tabEtablissement->codeTTY, array('1ORD', 'APPL', 'SPEC'))) {
                                $isDE = true;
                                //Placer l'établissement dans la liste des établissements en responsabilité de l'utilisateur
                                $listeEtablissementsResponsabilite[] = $tabEtablissement->codeRne;
                            }
                            else if(in_array($tabEtablissement->codeTTY, array('LP', 'LYC', 'CLG', 'EREA', 'ERPD'))) {
                                //Placer l'établissement dans la liste des établissements en responsabilité de l'utilisateur
                                $listeEtablissementsResponsabilite[] = $tabEtablissement->codeRne;
                            }
                        }
                    }
                }

                if($isDE) {
                    $login = self::DE_LOGIN;
                } else {
                    $login = self::CE_LOGIN;
                }

                if(empty($listeEtablissementsResponsabilite)){
                    $message = "Pas d'établissement en responsabilité pour ce profil (".$login.")";
                    $this->logger->error($message);
                    throw new \Exception($message);

                }
            }


            // Cas des personnels en DSDEN désignés par le DASEN (FrEduGestResp se termine par 805$ECECA)
            if(null != $FrEduGestResp && 0 != strcmp('', $FrEduGestResp)) {
                $tblChainesGestResp = explode(',', $FrEduGestResp);
                $validFrEduGestResp = "";
                foreach ($tblChainesGestResp as $chaineGestResp) {
                    if (preg_match("`805(.*)ECECA$`", $chaineGestResp)  && $this->checkDateFrEduGestResp($chaineGestResp)) {
                        $validFrEduGestResp .= $chaineGestResp.",";
                    }
                }
                if (null != $validFrEduGestResp && 0 != strcmp('', $validFrEduGestResp)) {
                    $validFrEduGestResp = substr($validFrEduGestResp, 0, -1);
                    $headers = array(
                        'FrEduGestResp' => $validFrEduGestResp
                    );
                    $etab = new getEtablissementsGest();
                    foreach ( $headers as $key => $value ) {
                        $httpHeader = new headerWrapper();
                        $httpHeader->key = $key;
                        $httpHeader->value = $value;
                        $etab->httpHeaders [] = $httpHeader;
                    }
                    $etab->nomGroupe = self::APP_NAME;
                    $etab->typeExtraction = self::ETAB_DSDEN;

                    $etablissementWrapper = $soapClient->getEtablissementsGest($etab)->etablissements;

                    foreach($etablissementWrapper as $tabEtablissement) {
                        // YME - 167107
                        if(is_array($tabEtablissement)) {
                            foreach($tabEtablissement as $etablissement) {
                                array_push($listeEtablissementsResponsabilite, $etablissement->codeRne);
                            }
                        }
                        else {
                            array_push($listeEtablissementsResponsabilite, $tabEtablissement->codeRne);
                        }
                    }

                    if(!empty($listeEtablissementsResponsabilite)) {
                        // Définir la liste des départements parmi les établissements
                        $login = self::DSDEN_LOGIN;
                        $listeDepartementsResponsabilite = $this->getDepartements($listeEtablissementsResponsabilite);
                    }
                    else {
                        $message = "Pas d'établissement en responsabilité pour ce profil (DSDEN)";
                        $this->logger->error($message);
                        throw new \Exception($message);
                    }
                }
            }

        }
        catch(ComposantSecuriteException $e)
        {
            throw new \Exception("Erreur du composant de sécurité : ".$e->getMessage());
        }
        catch(\SoapFault $s)
        {
            throw new \Exception("Erreur SOAP : ".$s->getMessage());
        }

        // Test sur le login trouvé
        if(null === $login || 0 == strcmp('', $login)){
            $message = "L'utilisateur n'a pas pu être identifié avec les headers HTTP envoyés";
            $this->logger->error($message);
            throw new \Exception($message);
        }

        $this->logger->info('CleartrustService.login() - Profil identifié : '.$login);

        // Liste des utilisateurs à garder en base
        // CE, DE IEN, DSDEN, DGESCO et les académies (AIX, ORL, BOR, ....)

        // Création de l'entité utilisateur
        // YME - 167107 - Rectorat
        $utilisateur = $this->em->getRepository(RefUser::class)->findOneByLogin($this->mapCodeAcademie($login));

        if(null === $utilisateur){
            $message = 'CleartrustService.login() - Utilisateur '.$login.' non trouvé en base';
            $this->logger->error($message);
            throw new \Exception($message);
        }

        // Mise en place du périmètre utilisateur
        $perimetre = $this->refUserPerimetreService->setPerimetreForUser($utilisateur, $listeEtablissementsResponsabilite, $typesElectionAutorises, $listeDepartementsResponsabilite);
        $utilisateur->setPerimetre($perimetre);
        $this->logger->info('CleartrustService.login() - FIN');

        return array($utilisateur, $listeEtablissementsResponsabilite, $typesElectionAutorises, $ctemail, $FrEduURLretour, $listeDepartementsResponsabilite);
    }

    /**
     * @param $login
     * @return string
     */
    private function mapCodeAcademie($login) {
        // Teste si le login est sur 3 caractères
        $login = strval($login);
        if (3 === strlen($login)) {
            $mapCodeAcademie = array(
                '001' => 'PAR',
                '002' => 'AIX',
                '003' => 'BES',
                '004' => 'BOR',
                '005' => 'CAE',
                '006' => 'CLE',
                '007' => 'DIJ',
                '008' => 'GRE',
                '009' => 'LIL',
                '010' => 'LYO',
                '011' => 'MON',
                '012' => 'NAY',
                '013' => 'POI',
                '014' => 'REN',
                '015' => 'STR',
                '016' => 'TOU',
                '017' => 'NAN',
                '018' => 'ORL',
                '019' => 'REI',
                '020' => 'AMI',
                '021' => 'ROU',
                '022' => 'LIM',
                '023' => 'NIC',
                '024' => 'CRE',
                '025' => 'VER',
                '026' => 'ANT',
                '027' => 'COR',
                '028' => 'REU',
                '031' => 'MAR',
                '032' => 'GUA',
                '033' => 'GUY',
                '041' => 'POL',
                '040' => 'NOU',
                '043' => 'MAO',
                '070' => 'NOR'
            );
            if (!empty($mapCodeAcademie[$login]))
                return $mapCodeAcademie[$login];
        }
        return $login;
    }

    /**
     * vérification des dates dans le FrEduGestResp
     * @param $FrEduGestResp
     * @return boolean
     */
    private function checkDateFrEduGestResp($chaineGestResp) {
        // multi gestResp : les chaines gestResp sont séparées par ,
        //$tblChainesGestResp = explode(',', $FrEduGestResp);
        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        $tblDetailsChaineGestResp = explode('$', $chaineGestResp);
        $dateDebut = \DateTime::createFromFormat('d/m/Y', $tblDetailsChaineGestResp[0]);
        $dateFin = \DateTime::createFromFormat('d/m/Y', $tblDetailsChaineGestResp[1]);
        $dateDebut->setTime(0, 0, 0);
        $dateFin->setTime(0, 0, 0);
        if ($dateDebut <= $today && $today <= $dateFin) {
            return true;
        }
        return false;
    }


    /**
     * vérification des dates dans le FrEduResDel
     * @param string $FrEduResDel
     * @return boolean
     */
    private function checkDateFrEduResDel($FrEduResDel) {
        // multi resdel : les chaines resdel sont séparées par ,
        $tblChainesResDel = explode(',', $FrEduResDel);
        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        foreach ($tblChainesResDel as $chaineResDel) {
            // une chaine resdel est de la forme : [nom_application]|[nom_ressource]|[date_debut]|[date_fin]|[uid_Délégant]|FrEduRneResp=[etabs]|[id_serveur]|[valeur]|
            $tblDetailsChaineResDel = explode('|', $chaineResDel);
            $dateDebut = \DateTime::createFromFormat('d/m/Y', $tblDetailsChaineResDel[2]);
            $dateFin = \DateTime::createFromFormat('d/m/Y', $tblDetailsChaineResDel[3]);
            $dateDebut->setTime(0, 0, 0);
            $dateFin->setTime(0, 0, 0);
            if ($dateDebut <= $today && $today <= $dateFin) {
                return true;
            }
        }
        return false;
    }

    private function getDepartements($listeEtablissementsResponsabilite) {
        $tmp = array();
        foreach($listeEtablissementsResponsabilite as $rne) {
            $numeroDepartement = substr($rne, 0, 3);
            switch ($numeroDepartement){
                case '620':
                    array_push($tmp, '2A');
                    break;
                case '720':
                    array_push($tmp, '2B');
                    break;
                case '971':
                    array_push($tmp, '9A');
                    array_push($tmp, '9F');
                    array_push($tmp, '9G');
                    break;
                case '972':
                    array_push($tmp, '9B');
                    break;
                case '973':
                    array_push($tmp, '9C');
                    break;
                case '974':
                    array_push($tmp, '9D');
                    break;
                case '976':
                    array_push($tmp, '9E');
                    break;
                default:
                    // retirer le 0 inutile dans numero departement
                    array_push($tmp, preg_replace('`^[0]*`', '', $numeroDepartement));
                    break;
            }
        }

        // Suppression des doublons
        $result = array();
        foreach($tmp as $dep){
            if(!in_array($dep, $result)){
                $result[] = $dep;
            }
        }

        return $result;
    }
}