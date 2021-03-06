<?php
namespace App\Controller;

use DateTime;
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
use App\Form\CampagneZoneEtabType;
use App\Entity\RefTypeEtablissement;
use Doctrine\Persistence\ManagerRegistry;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class StatistiqueController extends AbstractController
{

    private $request;
    private $doctrine;

    public function __construct(RequestStack $request, ManagerRegistry $doctrine) {
        $this->request = $request->getCurrentRequest();
        $this->doctrine = $doctrine;
    }

    /**
     * Fonction permettant d'afficher les statistiques d'une année n et n-1 en
     * fonction des données sélectionnées par l'utilisateur dans le formulaire associé
     *
     * @param string $codeUrlTypeElect
     * @throws AccessDeniedException
     */
    public function indexAction($codeUrlTypeElect)
    {
        if (false === $this->isGranted('ROLE_STATS')) {
            throw new AccessDeniedException();
        }
        $em = $this->doctrine->getManager();
        // reset session
        $this->request->getSession()->remove('campagne_annee_deb');
        $this->request->getSession()->remove('select_campagne');
        $this->request->getSession()->remove('select_academie');
        $this->request->getSession()->remove('select_departement');
        $this->request->getSession()->remove('select_commune');
        $this->request->getSession()->remove('select_etablissement');
        $this->request->getSession()->remove('select_choix_etab');
        $this->request->getSession()->remove('select_type_etablissement');

        $user = $this->getUser();
        $profilsLimitEtab = array(RefProfil::CODE_PROFIL_IEN, RefProfil::CODE_PROFIL_CE, RefProfil::CODE_PROFIL_DE);

        //DE/CE/IEN : Selection par defaut des données ) afficher
        if (in_array($user->getProfil()->getCode(), $profilsLimitEtab)) {
            $academies = $user->getPerimetre()->getAcademies();
            $this->request->getSession()->set('select_academie', $academies[0]->getCode());

            $departements = $user->getPerimetre()->getDepartements();
            $this->request->getSession()->set('select_departement', $departements[0]->getNumero());

            $communes = $user->getPerimetre()->getCommunes();
            $communeSelection = $this->request->getSession()->get('select_commune') != null ? $this->request->getSession()->get('select_commune') :  $communes[0]->getId();
            $this->request->getSession()->set('select_commune', $communeSelection);

            $etablissements = $user->getPerimetre()->getEtablissements();
            $etablissementSelection = $this->request->getSession()->get('select_etablissement') != null ? $this->request->getSession()->get('select_etablissement') : $etablissements[0]->getUai();
            $this->request->getSession()->set('select_etablissement', $etablissementSelection);

            $this->request->getSession()->set('select_choix_etab', true);
        }

        $params = $this->getParametresStatistiques($user, $codeUrlTypeElect);

        $params['warning'] = $this->getParameter('warning');

        // Evol message info pour le nombre total des établissements exclu EREA/ERPD
        $typeElectionId = RefTypeElection::getIdRefTypeElectionByCodeUrl($codeUrlTypeElect);
        $typeElection = $em->getRepository(RefTypeElection::class)->find($typeElectionId);
        if (($typeElectionId == RefTypeElection::ID_TYP_ELECT_ASS_ATE || $typeElectionId == RefTypeElection::ID_TYP_ELECT_PEE)
            && ($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DGESCO
                || $user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DSDEN
                || $user->getProfil()->getCode() == RefProfil::CODE_PROFIL_RECT
                || $user->getProfil()->getCode() == RefProfil::CODE_PROFIL_CE)) {
            $params['info'] = $this->getParameter('info');
            $params['typeElectCode'] = $typeElection->getCode();
        }
        return $this->render('statistiques/index.html.twig', $params);
    }

    public function rechercheAction($codeUrlTypeElect)
    {
        $user = $this->getUser();
        $params = $this->getParametresStatistiques($user, $codeUrlTypeElect);
        $params['warning'] = $this->getParameter('warning');

        return $this->render('statistiques/index.html.twig', $params);
    }

    /**
     * Récupère les données pour l'affichage des statistiques
     *
     * @param $user
     * @param $codeUrlTypeElect
     */
    private function getParametresStatistiques($user, $codeUrlTypeElect)
    {
        $em = $this->doctrine->getManager();

        // RECT -> placer l'académie en session
        if ($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_RECT) {
            $academies = $user->getPerimetre()->getAcademies();
            $idTypeElect = RefTypeElection::getIdRefTypeElectionByCodeUrl($codeUrlTypeElect);
            $typeElection = $idTypeElect != null ? $em->getRepository(RefTypeElection::class)->find($idTypeElect) : null;
            $lastCampange = $em->getRepository(EleCampagne::class)->getLastCampagne($typeElection->getId());
            $dateDebutLaseCampagne = $lastCampange->getAnneeDebut();
            $checkChildAcad = $em->getRepository(RefAcademie::class)->countchildAcademies( $academies[0]->getCode());
            //check if the  Aca is not deactivated in the last campagne
            if($academies[0]->getDateDesactivation() <= new \DateTime( $dateDebutLaseCampagne . "-01-01") ){
                //check the parent of the deactivated Aca
                if($academies[0]->getAcademieFusion() != null){
                    $this->request->getSession()->set('select_academie', $academies[0]->getAcademieFusion()->getCode());
                }
                //check if the eactivated Aca has child
            }elseif( $checkChildAcad >= 0 ){
                $this->request->getSession()->set('select_academie', $academies[0]->getCode());
            }else{
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


        $params = array();
        $campagneAnneeDeb = $this->request->getSession()->get('campagne_annee_deb');
        $datasSearch = array();

        $idRefTypeElect = RefTypeElection::getIdRefTypeElectionByCodeUrl($codeUrlTypeElect);
        $typeElection = $idRefTypeElect != null ? $em->getRepository(RefTypeElection::class)->find($idRefTypeElect) : null;
        if (empty($typeElection)) {
            throw $this->createNotFoundException('Le type d\'élection n\'a pas été trouvé.');
        }
        $params['typeElect'] = $typeElection;

        $datasSearch['typeElect'] = $typeElection;
        $datasSearch['campagne'] =  null;
        $datasSearch['academie'] = null;
        $datasSearch['departement'] = null;
        $datasSearch['typeEtablissement'] = null;
        $datasSearch['choix_etab'] = null;
        $datasSearch['commune'] = null;
        $datasSearch['etablissement'] = null;

        if($this->request->getSession()->get('select_campagne'))
            $datasSearch['campagne'] =  $em->getRepository(EleCampagne::class)->find($this->request->getSession()->get('select_campagne'));
        if($this->request->getSession()->get('select_academie'))
            $datasSearch['academie'] = $em->getRepository(RefAcademie::class)->find($this->request->getSession()->get('select_academie'));
        if($this->request->getSession()->get('select_departement'))
            $datasSearch['departement'] = $em->getRepository(RefDepartement::class)->find($this->request->getSession()->get('select_departement'));
        if($this->request->getSession()->get('select_type_etablissement'))
            $datasSearch['typeEtablissement'] = $em->getRepository(RefTypeEtablissement::class)->find($this->request->getSession()->get('select_type_etablissement'));
        if($this->request->getSession()->get('select_choix_etab'))
            $datasSearch['choix_etab'] = $this->request->getSession()->get('select_choix_etab');
        if($this->request->getSession()->get('select_commune'))
            $datasSearch['commune'] = $em->getRepository(RefCommune::class)->find($this->request->getSession()->get('select_commune'));
        if($this->request->getSession()->get('select_etablissement'))
            $datasSearch['etablissement'] = $em->getRepository(RefEtablissement::class)->find($this->request->getSession()->get('select_etablissement'));

        $datasSearch['user'] = $user;
        $form = $this->createForm(
            CampagneZoneEtabType::class,
            null,
            $datasSearch
        );
        if ($this->request->getMethod() == 'POST') {
            $form->handleRequest($this->request);
            if ($form->isSubmitted() && $form->isValid()) {

                $datasForm = $form->getData();

                /* Variable $datasForm prend les parametres renvoyes par $form et a priori saisis par user */
                /* Variable $datasSearch prend le relai, elle prend les donnees renvoyees par $datasForm */
                $datasSearch['campagne'] = $datasForm['campagne'];
                $datasSearch['academie'] = $datasForm['academie'];
                $datasSearch['departement'] = $datasForm['departement'];
                $datasSearch['typeEtablissement'] = $datasForm['typeEtablissement'];
                $datasSearch['choix_etab'] = $datasForm['choix_etab'];
                $datasSearch['commune'] = $datasForm['commune'];
                $datasSearch['etablissement'] = $datasForm['etablissement'];

            }else{
                /* Sinon, au cas où le user n'a rien saisi, $datasSearch va chercher dans BD, */
                /*ce cas renvoie un resultat global d'une campagne quelque soit la session    */

                // YME : rustine pour DGESCO, à corriger
                $arrayRequest = $this->request->request->all();

                //Contient ["academie"] (code); ["departement"] (numero); ["typeEtablissement"] (id); ["etatSaisie"]; ["choix_etab"]; ["commune"] (id); ["etablissement"] (uai); ["_token"]
                $arrayResultatZoneEtabType = $arrayRequest['campagneZoneEtabType'];
                $datasSearch['campagne'] = $em->getRepository(EleCampagne::class)->find($arrayResultatZoneEtabType['campagne']);
                $datasSearch['academie'] = $em->getRepository(RefAcademie::class)->find($arrayResultatZoneEtabType['academie']);
                $datasSearch['departement'] = $em->getRepository(RefDepartement::class)->find($arrayResultatZoneEtabType['departement']);
                $datasSearch['commune'] = $em->getRepository(RefCommune::class)->find($arrayResultatZoneEtabType['commune']);
                $datasSearch['typeEtablissement'] = $em->getRepository(RefTypeEtablissement::class)->find($arrayResultatZoneEtabType['typeEtablissement']);
                $datasSearch['choix_etab'] = (array_key_exists('choix_etab', $arrayResultatZoneEtabType) && $arrayResultatZoneEtabType['choix_etab'] == "1") ? true : false;
                $datasSearch['etablissement'] = $em->getRepository(RefEtablissement::class)->find($arrayResultatZoneEtabType['etablissement']);

            }

            // Année de début de la campagne
            $campagneAnneeDeb = $datasSearch['campagne']->getAnneeDebut();

            // si demande de liste d'établissement sans précision, on restreint à la zone du User

            /*Ajouter un filtre du type d'élection *********/
            if ($datasSearch['choix_etab']) {
                $zoneUser = ($user->getIdZone() != null) ? EpleUtils::getZone($em, $user->getIdZone()) : null;
                if ($zoneUser instanceof RefDepartement) {
                    $datasSearch['academie'] = $zoneUser->getAcademie();
                    $datasSearch['departement'] = $zoneUser;
                } else
                    if ($zoneUser instanceof RefAcademie) {
                        $datasSearch['academie'] = $zoneUser;
                    }
            }
        }

        //EVOL 014E exclus les EREA-ERPD des statistiques ASS et ATE et PEE
        $typeEtablissement = $datasSearch['typeEtablissement'];
        $isEreaErpdExclus = false;
        if((RefTypeElection::ID_TYP_ELECT_ASS_ATE == $typeElection->getId() || RefTypeElection::ID_TYP_ELECT_PEE == $typeElection->getId())
            && (($typeEtablissement != null && !empty($typeEtablissement) && $typeEtablissement->getCode() != RefTypeEtablissement::CODE_EREA_ERPD) || $typeEtablissement == null)) {
            $isEreaErpdExclus = true;
        }

        if ($datasSearch['typeEtablissement'] != null && !empty($datasSearch['typeEtablissement'])) {
            $codeUrlTypeEtab = $datasSearch['typeEtablissement']->getCodeUrlById();

        } else {
            $codeUrlTypeEtab = 'tous';
        }
        $params['codeUrlTypeEtab'] = $codeUrlTypeEtab;

        // Campagne et campagne antérieure
        if ($campagneAnneeDeb == null) {
            $campagne = $em->getRepository(EleCampagne::class)->getLastCampagne($typeElection);
        } else {
            $listeCampagne = $em->getRepository(EleCampagne::class)->getCampagneParTypeElectionAnneeDebut($typeElection, $campagneAnneeDeb);
            $campagne = (! empty($listeCampagne)) ? $listeCampagne[0] : null;
        }
        if (empty($campagne)) {
            throw $this->createNotFoundException('La campagne pour ce type d\'élection n\'a pas été trouvé.');
        }
        $params['campagne'] = $campagne;

        $listeCampagnePrec = $em->getRepository(EleCampagne::class)->getCampagneParTypeElectionAnneeDebut($typeElection, $campagne->getAnneeDebut() - 1);
        $campagnePrec = (! empty($listeCampagnePrec)) ? $listeCampagnePrec[0] : null;
        $params['campagnePrec'] = $campagnePrec;

        // Type de zone
        /* Ajouter un filtre du type d'élection*/
        if (!empty($datasSearch['commune'])) {
            $params['commune'] = $datasSearch['commune'];
            $params['dept'] = $datasSearch['departement'];
            $zone = $datasSearch['commune'];
        } elseif (!empty($datasSearch['departement'])) {
            $params['dept'] = $datasSearch['departement'];
            $zone = $datasSearch['departement'];
        } elseif (!empty($datasSearch['academie'])) {
            $params['aca'] = $datasSearch['academie'];
            $zone = $datasSearch['academie'];
        } else {
            $params['nationale'] = true;
            $zone = null;
        }


        // Choix par établissement : recherche dans EleEtablissement
        if ($datasSearch['choix_etab'] && !empty($datasSearch['etablissement'])) {

            $params['etablissement'] = $datasSearch['etablissement'];
            // 	0167880 on affiche que les données validés            
            $eleEtablissement = $em->getRepository(EleEtablissement::class)->getEleEtablissementGlobale($campagne, $datasSearch['etablissement'], null, 'V');
            $params['electEtablissement'] = $eleEtablissement;

            if (! empty($campagnePrec)) {
                // 	0167880 on affiche que les données validés
                $eleEtablissementPrec = $em->getRepository(EleEtablissement::class)->getEleEtablissementGlobale($campagnePrec, $datasSearch['etablissement'], null, 'V');
                if ($datasSearch['etablissement']->getTypeEtablissement()->getId() == RefTypeEtablissement::ID_TYP_EREA_ERPD
                    && (RefTypeElection::ID_TYP_ELECT_ASS_ATE == $typeElection->getId() || RefTypeElection::ID_TYP_ELECT_PEE == $typeElection->getId())) { // YME 0145664
                    $eleEtablissementPrec = null;
                }
            } else {
                $eleEtablissementPrec = null;
            }

            $params['electEtablissementPrec'] = $eleEtablissementPrec;

            // Résultats de l'établissement (année n et année n-1)
            $params['listeResultat'] = $this->getListeResultatsAnneeCouranteAnneePrec($params['electEtablissement'], $params['electEtablissementPrec']);
        } else {  // Pas d'établissement précisé
            // Ajout du parametre null a la place de $user, pour mettre en bonne ordre les parametres
            $params['nbEtabParZone'] = $em->getRepository(RefEtablissement::class)->getNbEtabParTypeEtablissementZoneCommune($datasSearch['typeEtablissement'], $zone, $zone, false, true, $user, $campagne, $isEreaErpdExclus);
            $params['nbEtabSaisieParZone'] = $em->getRepository(EleEtablissement::class)->getNbEleEtabParCampagne($campagne, $zone, 'S', $datasSearch['typeEtablissement'], $user, $isEreaErpdExclus);
            $params['nbEtabSaisieValideeParZone'] = $em->getRepository(EleEtablissement::class)->getNbEleEtabParCampagne($campagne, $zone, 'V', $datasSearch['typeEtablissement'], $user, $isEreaErpdExclus);
            $params['electZone'] = $em->getRepository(EleEtablissement::class)->getConsolidationByCampagneTypeEtabZoneEtatSaisie($em, $campagne, $datasSearch['typeEtablissement'], $zone, 'V', $user, $isEreaErpdExclus);//$eleEtablissementExclureEREA;

            if ($params['electZone'] != null) {
                if ($params['electZone']->getNbEtabTotal() == null) {
                    $params['electZone']->setNbEtabTotal($params['nbEtabParZone']);
                }

                if ($params['electZone']->getNbEtabExprimes() == null) {
                    $params['electZone']->setNbEtabExprimes(0);
                }
            }

            if (! empty($campagnePrec)) {
                $params['electZonePrec'] = $em->getRepository(EleEtablissement::class)->getConsolidationByCampagneTypeEtabZoneEtatSaisie($em, $campagnePrec, $datasSearch['typeEtablissement'], $zone, 'V', $user, $isEreaErpdExclus);
            } else {
                $params['electZonePrec'] = null; //$eleEtablissementPrecExclureEREA
            }

            /**
             * **** Récupération de l'ensemble des résultats par zone *****
             */
            $params['listeResultat'] = $this->getListeResultatsAnneeCouranteAnneePrec($params['electZone'], $params['electZonePrec']);
        }

        //Calcul du nombre de sieges totale dans la repartition detaille
        $total = 0;
        $totalPrec = 0;
        foreach ($params["listeResultat"] as $res) {
            if($res["resultat"] != null) {
                $total += intval(intval($res["resultat"]->getNbSieges()) + intval($res["resultat"]->getNbSiegesSort()));
            }
            if($res["resultatPrec"] != null) {
                $totalPrec += intval(intval($res["resultatPrec"]->getNbSieges()) + intval($res["resultatPrec"]->getNbSiegesSort()));
            }
        }
        $params["totalSiegeElect"] = $total;
        $params["totalSiegeElectPrec"] = $totalPrec;

        // Mise à jour Session
        $this->request->getSession()->set('campagne_annee_deb', $campagneAnneeDeb);
        $this->request->getSession()->set('select_campagne', ($datasSearch['campagne'] instanceof EleCampagne) ? $datasSearch['campagne']->getId() : null);
        $this->request->getSession()->set('select_academie', ($datasSearch['academie'] instanceof RefAcademie) ? $datasSearch['academie']->getIdZone() : null);
        $this->request->getSession()->set('select_departement', ($datasSearch['departement'] instanceof RefDepartement) ? $datasSearch['departement']->getIdZone() : null);
        $this->request->getSession()->set('select_type_etablissement', ($datasSearch['typeEtablissement'] instanceof RefTypeEtablissement) ? $datasSearch['typeEtablissement']->getId() : null);
        $this->request->getSession()->set('select_choix_etab', $datasSearch['choix_etab']);
        $this->request->getSession()->set('select_commune', ($datasSearch['commune'] instanceof RefCommune) ? $datasSearch['commune']->getId() : null);
        $this->request->getSession()->set('select_etablissement', ($datasSearch['etablissement'] instanceof RefEtablissement) ? $datasSearch['etablissement']->getUai() : null);
        $params['form'] = $form->createView();

        switch ($user->getProfil()->getCode()){
            case RefProfil::CODE_PROFIL_DGESCO:
                $params['acad_date_activation'] = $datasSearch['academie'] == null ? "" : $datasSearch['academie']->getDateActivation()->format('Y');
                break;
            case RefProfil::CODE_PROFIL_RECT:
                $params['acad_date_activation'] = $datasSearch['academie'] == null ? "" : $datasSearch['academie']->getDateActivation()->format('Y');
                break;

            default:
                break;
        }
        return $params;
    }

    /**
     * Cette fonction renvoie une liste de résultats en associant les résultats de l'année n avec les résultats de l'année n-1
     *
     * @param EleEtablissement ou EleConsolidation $elect
     * @param EleEtablissement ou EleConsolidation $electPrec
     * @return array EleResulat
     * @codeCoverageIgnore
     */
    private function getListeResultatsAnneeCouranteAnneePrec($elect, $electPrec)
    {
        $listeResultat = array();
        $resultatsAnneeCourante = false;

        if (! empty($elect)) {
            foreach ($elect->getResultats() as $key => $resultat) {
                //SESAM 0313887 : Calcul du nombre réel de siège attribué
                if ($resultat->getNbSieges() != null && $resultat->getNbCandidats() != null) {
                    $resultat->setNbSieges(min($resultat->getNbSieges(), $resultat->getNbCandidats()));
                }
                $listeResultat[$key]['resultat'] = $resultat;
                $listeResultat[$key]['resultatPrec'] = null;
                $resultatsAnneeCourante = true;
            }
        }

        /**
         * **** Comparaison des organisations de l'année antérieur si données existantes *****
         */
        if (! empty($electPrec)) {
            foreach ($electPrec->getResultats() as $resultatPrec) {
                $organisation_supplementaire = true;
                //SESAM 0313887 : Calcul du nombre réel de siège attribué
                if ($resultatPrec->getNbSieges() != null && $resultatPrec->getNbCandidats() != null) {
                    $resultatPrec->setNbSieges(min($resultatPrec->getNbSieges(), $resultatPrec->getNbCandidats()));
                }
                if ($resultatsAnneeCourante) {
                    foreach ($listeResultat as $key => $value) {
                        if ($resultatPrec->getOrganisation()->getId() == $listeResultat[$key]['resultat']->getOrganisation()->getId()) {
                            $listeResultat[$key]['resultatPrec'] = $resultatPrec;
                            $organisation_supplementaire = false;
                            break;
                        }
                    }
                }
                if ($organisation_supplementaire == true) {
                    $indice = sizeof($listeResultat) + 1;
                    $listeResultat[$indice]['resultat'] = null;
                    $listeResultat[$indice]['resultatPrec'] = $resultatPrec;
                }
            }
        }

        return $listeResultat;
    }

    /**
     * Fonction permettant l'édition au format Excel des résultats d'une zone ou d'un établissement
     *
     * @param string $codeUrlTypeElect
     * @param string $anneeCampagne
     * @throws AccessDeniedException
     */
    public function exportStatistiquesXLSAction($codeUrlTypeElect)
    {
        if (false === $this->isGranted('ROLE_STATS')) {
            throw new AccessDeniedException();
        }

        $user = $this->getUser();

        // Récupération des données de statistiques
        $params = $this->getParametresStatistiques($user, $codeUrlTypeElect);

        $params['ligne'] = 1;
        $params['complet'] = false;

        // **************** Création de l'objet Excel *********************//
        $spreadsheet = new Spreadsheet();

        // Export
        $this->generateStatistiquesXLS($params, $spreadsheet);

        // Création du writer
        $writer = new Xlsx($spreadsheet, 'Excel5');

        // Création du nom du fichier
        $fileName = EcecaExportUtils::generateFileName('Statistiques', $params);

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
     * Fonction permettant la génération des résultats d'une zone ou d'un établissement
     * Export détaillé avec affichage des statistiques des établissements.
     * @param array $data : tableau array contenant les données à exporter
     * @return $ligne
     */
    private function generateStatistiquesXLS($data, &$spreadsheet)
    {
        $em = $this->doctrine->getManager();
        // Données à exporter
        // $Data contient le tableau de données à exporter passé en paramètre

        $campagne = $data['campagne'];
        $codeUrlTypeEtab = $data['codeUrlTypeEtab'];
        $ligne = $data['ligne'];
        $typeElect = $data['typeElect'];

        $displayPrecData = (isset($data["acad_date_activation"])
            && $data["acad_date_activation"] == $campagne->getAnneeDebut()
            && (!isset($data["dept"]) || empty($data['dept'])))
            ? false : true;
        $sheet = $spreadsheet->getActiveSheet();
        $styleArray = array(
            'font' => array(
                'bold' => true
            ),
        );
        $styleArrayTitre = array(
            'font' => array(
                'color' => array(
                    'rgb' => 'CC33CC'
                )
            )
        );

        // **************** Génération du titre *********************//
        $isZone = false;
        if (! empty($data['electZone'])) {
            $isZone = true;
            $elect = $data['electZone'];
            $electPrec = $data['electZonePrec'];
            if (! empty($data['nationale'])) {
                $libelle_recherche = 'Nationales';
                $filtre_aca = 'Toutes';
                $filtre_dept = 'Tous';
                $filtre_comm = 'Toutes';
            } elseif (! empty($data['commune'])) {
                $libelle_recherche = $data['commune']->getLibelle();

                //Determine l'académie de l'établissement (avant ou après fusion de certaines académie)
                $filtre_aca = $data['commune']->getDepartement()->getAcademie();
                $dateDebutCampagne = new Datetime($campagne->getAnneeDebut() . "-01-01");
                if($filtre_aca->getDateDesactivation() <= $dateDebutCampagne) {
                    $filtre_aca = $filtre_aca->getAcademieFusion();
                }
                if($filtre_aca != null) {
                    $filtre_aca = $filtre_aca->getLibelle();
                }

                $filtre_dept = $data['commune']->getDepartement()->getLibelle();
                $filtre_comm = $data['commune']->getLibelle();
            }
            elseif (! empty($data['dept'])) {
                $libelle_recherche = $data['dept']->getLibelle();

                //Determine l'académie de l'établissement (avant ou après fusion de certaines académie)
                $filtre_aca = $data['dept']->getAcademie();
                $dateDebutCampagne = new Datetime($campagne->getAnneeDebut() . "-01-01");
                if($filtre_aca->getDateDesactivation() <= $dateDebutCampagne) {
                    $filtre_aca = $filtre_aca->getAcademieFusion();
                }
                if($filtre_aca != null) {
                    $filtre_aca = $filtre_aca->getLibelle();
                }

                $filtre_dept = $data['dept']->getLibelle();
                $filtre_comm = 'Toutes';
            } elseif (! empty($data['aca'])) {
                $libelle_recherche = "";

                //Determine l'académie de l'établissement (avant ou après fusion de certaines académie)
                $filtre_aca = $data['aca'];
                $dateDebutCampagne = new Datetime($campagne->getAnneeDebut() . "-01-01");
                if($filtre_aca->getDateDesactivation() <= $dateDebutCampagne) {
                    $filtre_aca = $filtre_aca->getAcademieFusion();
                }
                if($filtre_aca != null) {
                    $filtre_aca = $filtre_aca->getLibelle();
                    $libelle_recherche = $filtre_aca;
                }

                $filtre_dept = 'Tous';
                $filtre_comm = 'Toutes';
            }
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A' . $ligne, $typeElect->getLibelle() . ' - Statistiques ' . $libelle_recherche);
        }  else {
            $elect = $data['electEtablissement'];
            $electPrec = $data['electEtablissementPrec'];

            if($elect != null){
                $libelle_recherche = 'de l\'établissement ' . $elect->getEtablissement()->getLibelle() . ' (' . $elect->getEtablissement()->getUai() . ')';
            }else if($electPrec != null){
                $libelle_recherche = 'de l\'établissement ' . $electPrec->getEtablissement()->getLibelle() . ' (' . $electPrec->getEtablissement()->getUai() . ')';
            }

            if ($data['complet']) {
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('A' . $ligne, 'Statistiques ' . $libelle_recherche);
            } else {
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('A' . $ligne, $typeElect->getLibelle() . ' - Statistiques ' . $libelle_recherche);
            }
        }
        $sheet->getStyle('A' . $ligne)->applyFromArray($styleArrayTitre);

        // **************** Génération du descriptif *********************//
        /***************Recherche par un établissement précis***************/
        $ligne += 2;
        $nomZone = '';
        if (! empty($data['etablissement'])) {

            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A3', 'Description d\'école ou d\'établissement');
            // Emplacement ligne campagne
            // Descriptif établissement
            $etablissement = $data['etablissement'];
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 1), 'Unité Administrative Immatriculée');
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 2), 'Libellé');
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 3), 'Type d\'établissement');

            $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 1), $etablissement->getUai());
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 2), $etablissement->getLibelle());
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 3), $etablissement->getTypeEtablissement()->getLibelle());

            // Degré
            // BBL Defect 260 HPQC
            if ($etablissement->getTypeEtablissement() != null) {
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 4), 'Degré');
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 4), $etablissement->getTypeEtablissement()->getDegre());
                $ligne++;
            }

            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 4), 'Commune');
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 5), 'Catégorie');
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 6), 'Contact');

            $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 4), $etablissement->getCommune()
                    ->getLibelle() . ' (' . $etablissement->getCommune()
                    ->getDepartement()
                    ->getLibelle() . ')');
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 5), (null == $etablissement->getTypePrioritaire() ? 'N/A' : $etablissement->getTypePrioritaire()
                ->getCode()));
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 6), $etablissement->getContact());

            // Récupération des données pour le titre du fichier
            $nomZone = $etablissement->getUai();

            $ligne += 9;

        } else {
            // SESAM 0347673 : Ajout du champs "Commune" pour toutes les elections
            // Emplacement ligne campagne
            $case_libelle_campagne = 'A3';
            $case_valeur_campagne = 'B3';
            // Descriptif Zone != Etablissement
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 1), 'Academie');
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 2), 'Département');
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 3), 'Commune');
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 4), 'Type d\'établissement');

            $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 1), $filtre_aca);
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 2), $filtre_dept);
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 3), $filtre_comm);
            // defect 268
            if($codeUrlTypeEtab == RefTypeEtablissement::CODE_URL_2ND_DGRE) {
                $typeEtablissement = $em->getRepository(RefTypeEtablissement::class)->find(RefTypeEtablissement::ID_TYP_2ND_DEGRE);
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 4), ($typeEtablissement->getLibelle()));
            } else {
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 4), ($codeUrlTypeEtab != 'tous' && $elect->getTypeEtablissement() != null ? $elect->getTypeEtablissement()->getLibelle() : ucwords($codeUrlTypeEtab)));
            }

            // Degré
            if ($codeUrlTypeEtab != 'tous' && $elect->getTypeEtablissement() != null ) {
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 5), 'Degré');
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 5), $elect->getTypeEtablissement()->getDegre());
                $ligne++;
            }

            // Récupération des données pour le titre
            $nomZone = $libelle_recherche;

            $ligne += 8;


            // **************** Ligne campagne ***************************//
            $spreadsheet->setActiveSheetIndex(0)->setCellValue($case_libelle_campagne, 'Campagne');
            $spreadsheet->setActiveSheetIndex(0)->setCellValue($case_valeur_campagne, $campagne->getAnneeDebut() . '-' . $campagne->getAnneeFin());
        }

        // **************** Génération de la participation *******************//
        if ( ((null != $elect) && $elect->getParticipation())  || ((null != $electPrec) && $electPrec->getParticipation())) { // YME 0145590

            $participation = (null != $elect ? $elect->getParticipation() : null);
            $participationPrec = (null != $electPrec ? $electPrec->getParticipation() : null);

            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A' . ($ligne - 1), 'Participation');

            if ($typeElect->getId() == RefTypeElection::ID_TYP_ELECT_PARENT) {
                if($isZone) {
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . $ligne, 'Modalité de vote');
                    $sheet->getStyle('B' . $ligne)->applyFromArray($styleArray);
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('C' . $ligne, 'Rappel ' . ($campagne->getAnneeDebut() - 1) . ' - ' . ($campagne->getAnneeFin() - 1));
                    $sheet->getStyle('C' . $ligne)->applyFromArray($styleArray);
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('D' . $ligne, 'Variation');
                    $sheet->getStyle('D' . $ligne)->applyFromArray($styleArray);

                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 1), 'Vote ' . RefModaliteVote::LIBELLE_MODALITE_VOTE_URNE_CORRESPONDANCE);
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 2), 'Vote ' . RefModaliteVote::LIBELLE_MODALITE_VOTE_CORRESPONDANCE);

                    if ($participation != null) {
                        $totalModalite = $elect->getNbEtabExprimes();
                        if($totalModalite != 0) {
                            $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 1), round($participation->getConsolidationVoteUrneCorrespondance()/$totalModalite*100, 2) . '%');
                            $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 2), round($participation->getConsolidationVoteCorrespondance()/$totalModalite*100, 2) . '%');
                        } else {
                            $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 1), '-');
                            $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 2), '-');
                        }
                    } else {
                        $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 1), '-');
                        $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 2), '-');
                    }

                    if ($participationPrec != null && $displayPrecData) {
                        $totalModalitePrec = $electPrec->getNbEtabExprimes();
                        if($totalModalitePrec != 0) {
                            $spreadsheet->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 1), round($participationPrec->getConsolidationVoteUrneCorrespondance() / $totalModalitePrec * 100, 2) . '%');
                            $spreadsheet->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 2), round($participationPrec->getConsolidationVoteCorrespondance() / $totalModalitePrec * 100, 2) . '%');
                        } else {
                            $spreadsheet->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 1), '-');
                            $spreadsheet->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 2), '-');
                        }
                    } else {
                        $spreadsheet->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 1), '-');
                        $spreadsheet->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 2), '-');
                    }
                    if ($participation != null && $participationPrec != null && $displayPrecData && $totalModalite != 0 && $totalModalitePrec != 0) {
                        $spreadsheet->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 1), round(($participation->getConsolidationVoteUrneCorrespondance() / $totalModalite * 100) - ($participationPrec->getConsolidationVoteUrneCorrespondance() / $totalModalitePrec * 100),2). '%');
                        $spreadsheet->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 2), round(($participation->getConsolidationVoteCorrespondance()/$totalModalite*100) - ($participationPrec->getConsolidationVoteCorrespondance()/$totalModalitePrec*100), 2). '%');
                    } else {
                        $spreadsheet->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 1), '-');
                        $spreadsheet->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 2), '-');
                    }
                    $ligne += 4;
                } elseif(!$isZone && $elect->getIndCarence() == 0) {
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('A' . $ligne, 'Modalité de vote');
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . $ligne, $participation->getModaliteVote() != null ? $participation->getModaliteVote()->getLibelle() : "-");
                    $ligne += 2;
                }
            }

            $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . $ligne, 'Résultats Bruts');
            $sheet->getStyle('B' . $ligne)->applyFromArray($styleArray);
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('C' . $ligne, 'Rappel ' . ($campagne->getAnneeDebut() - 1) . ' - ' . ($campagne->getAnneeFin() - 1));
            $sheet->getStyle('C' . $ligne)->applyFromArray($styleArray);
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('D' . $ligne, 'Variation');
            $sheet->getStyle('D' . $ligne)->applyFromArray($styleArray);


            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 1), 'Nombre d\'inscrits');
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 2), 'Nombre de votants');
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 4), 'Nombre de suffrages exprimés');
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 3), 'Nombre de votes blancs ou nuls');
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 5), 'Taux de participation');

            if ($participation != null) {
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 1), $participation->getNbInscrits());
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 2), $participation->getNbVotants());
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 4), $participation->getNbExprimes());
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 3), $participation->getNbNulsBlancs());
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 5), number_format($participation->getTaux(), 2, '.', ',') . '%');
            } else {
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 1), '-');
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 2), '-');
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 4), '-');
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 3), '-');
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 5), '-');
            }

            if ($participationPrec != null && $displayPrecData) {
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 1), $participationPrec->getNbInscrits());
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 2), $participationPrec->getNbVotants());
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 4), $participationPrec->getNbExprimes());
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 3), $participationPrec->getNbNulsBlancs());
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 5), number_format($participationPrec->getTaux(), 2, '.', ',') . '%');
            } else {
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 1), '-');
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 2), '-');
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 3), '-');
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 4), '-');
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 5), '-');
            }

            if ($participation != null && $participationPrec != null && $displayPrecData) {
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 1), $participation->getNbInscrits() - $participationPrec->getNbInscrits());
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 2), $participation->getNbVotants() - $participationPrec->getNbVotants());
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 4), $participation->getNbExprimes() - $participationPrec->getNbExprimes());
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 3), $participation->getNbNulsBlancs() - $participationPrec->getNbNulsBlancs());
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 5), number_format((number_format($participation->getTaux(), 2, '.', ',') - number_format($participationPrec->getTaux(), 2, '.', ',')),2, '.', ',') . '%');
            } else {
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 1), '-');
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 2), '-');
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 3), '-');
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 4), '-');
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 5), '-');
            }

            $ligne = $ligne + 8;

            // *************** Génération des résultats *******************//
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A' . ($ligne - 1), 'Résultats');

            // NB. Le quotient éléctoral est affiché par défaut avec une virgule par excel.
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . $ligne, 'Résultats Bruts');
            $sheet->getStyle('B' . $ligne)->applyFromArray($styleArray);
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('C' . $ligne, 'Rappel ' . ($campagne->getAnneeDebut() - 1) . ' - ' . ($campagne->getAnneeFin() - 1));
            $sheet->getStyle('C' . $ligne)->applyFromArray($styleArray);
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('D' . $ligne, 'Variation');
            $sheet->getStyle('D' . $ligne)->applyFromArray($styleArray);

            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 1), 'Nombre de sièges à pourvoir');
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 2), 'Nombre de sièges pourvus');
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 3), 'Pourcentage de sièges pourvus');

            if (! empty($data['etablissement'])) {
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 4), 'Quotient électoral');
            }

            if ($participation != null) {
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 1), $participation->getNbSiegesPourvoir());
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 2), $participation->getNbSiegesPourvus());
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 3), number_format($participation->getTauxSieges(), 2, '.', ',') . '%');
                if (! empty($data['etablissement'])) {
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 4), number_format($participation->getQuotient(), 2, '.', ','));
                }
            } else {
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 1), '-');
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 2), '-');
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 3), '-');
                if (! empty($data['etablissement'])) {
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 4), '-');
                }
            }

            if ($participationPrec != null && $displayPrecData) {
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 1), $participationPrec->getNbSiegesPourvoir());
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 2), $participationPrec->getNbSiegesPourvus());
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 3), number_format($participationPrec->getTauxSieges(), 2, '.', ',') . '%');
                if (! empty($data['etablissement'])) {
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 4), number_format($participationPrec->getQuotient(), 2, '.', ','));
                }
            } else {
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 1), '-');
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 2), '-');
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 3), '-');
                if (! empty($data['etablissement'])) {
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 4), '-');
                }
            }

            if ($participation != null && $participationPrec != null && $displayPrecData) {
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 1), $participation->getNbSiegesPourvoir() - $participationPrec->getNbSiegesPourvoir());
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 2), $participation->getNbSiegesPourvus() - $participationPrec->getNbSiegesPourvus());
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 3), number_format((number_format($participation->getTauxSieges(), 2, '.', ',') - number_format($participationPrec->getTauxSieges(), 2, '.', ',')), 2, '.', ',') . '%');
                if (! empty($data['etablissement'])) {
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 4), number_format((number_format($participation->getQuotient(), 2, '.', ',') - number_format($participationPrec->getQuotient(), 2, '.', ',')), 2, '.', ','));
                }
            } else {
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 1), '-');
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 2), '-');
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 3), '-');
                if (! empty($data['etablissement'])) {
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 4), '-');
                }
            }
            $ligne += 7;
        } else {
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A' . $ligne, 'Aucun résultat disponible');
        }

        // ********************** Génération de la répartition détaillée des sièges ***********************//

        if ($elect || $electPrec) {
            if (sizeof($data['listeResultat']) != 0){
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('A' . ($ligne - 1), 'Répartition détaillée des sièges');

                $spreadsheet->setActiveSheetIndex(0)
                    ->setCellValue('A' . $ligne, 'Organisation')
                    ->setCellValue('B' . $ligne, 'Nombre de suffrages')
                    ->setCellValue('C' . $ligne, '%')
                    ->setCellValue('D' . $ligne, 'Rappel ' . ($campagne->getAnneeDebut() - 1) . ' - ' . ($campagne->getAnneeFin() - 1))
                    ->setCellValue('E' . $ligne, 'Variation')
                    ->setCellValue('F' . $ligne, 'Nb sièges')
                    ->setCellValue('G' . $ligne, '%')
                    ->setCellValue('H' . $ligne, 'Rappel ' . ($campagne->getAnneeDebut() - 1) . ' - ' . ($campagne->getAnneeFin() - 1))
                    ->setCellValue('I' . $ligne, 'Variation');

                $sheet->getStyle('B' . $ligne)->applyFromArray($styleArray);
                $sheet->getStyle('C' . $ligne)->applyFromArray($styleArray);
                $sheet->getStyle('D' . $ligne)->applyFromArray($styleArray);
                $sheet->getStyle('E' . $ligne)->applyFromArray($styleArray);
                $sheet->getStyle('F' . $ligne)->applyFromArray($styleArray);
                $sheet->getStyle('G' . $ligne)->applyFromArray($styleArray);
                $sheet->getStyle('H' . $ligne)->applyFromArray($styleArray);
                $sheet->getStyle('I' . $ligne)->applyFromArray($styleArray);
                $ligne ++;
            }

            foreach($data['listeResultat'] as $res){

                if ($res['resultat'] != null) {

                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('A' . $ligne, $res['resultat']->getOrganisation()
                        ->getLibelle());

                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . $ligne, $res['resultat']->getNbVoix());

                    $tmp = '-';
                    if ($elect->getNbVoixTotal() != 0) {
                        $tmp = number_format(($res['resultat']->getNbVoix() / $elect->getNbVoixTotal() * 100), 2, '.', ',') . '%';
                    }
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('C' . $ligne, $tmp);

                    $tmp = '-';
                    if (null != $electPrec && $electPrec->getNbVoixTotal() != 0 && ! empty($res['resultatPrec']) && $displayPrecData) {
                        $tmp = number_format(($res['resultatPrec']->getNbVoix() / $electPrec->getNbVoixTotal() * 100), 2, '.', ',') . '%';
                    }
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('D' . $ligne, $tmp);

                    $tmp = '-';
                    if ($elect->getNbVoixTotal() != 0 && null != $electPrec && $electPrec->getNbVoixTotal() != 0 && ! empty($res['resultatPrec']) && $displayPrecData) {
                        $tmp = number_format((number_format(($res['resultat']->getNbVoix() / $elect->getNbVoixTotal() * 100), 2, '.', ',') - number_format(($res['resultatPrec']->getNbVoix() / $electPrec->getNbVoixTotal() * 100), 2, '.', ',')), 2, '.', ',') . '%';
                    }
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('E' . $ligne, $tmp);

                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('F' . $ligne, ($res['resultat']->getNbSieges() + $res['resultat']->getNbSiegesSort()));

                    $tmp = '-';
                    if ($data["totalSiegeElect"] != 0) {
                        $tmp = number_format((($res['resultat']->getNbSieges() + $res['resultat']->getNbSiegesSort()) / $data["totalSiegeElect"] * 100), 2, '.', ',') . '%';
                    }
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('G' . $ligne, $tmp);

                    $tmp = '-';
                    if (null != $electPrec && $data["totalSiegeElectPrec"] != 0 && ! empty($res['resultatPrec']) && $displayPrecData) {
                        $tmp = number_format((($res['resultatPrec']->getNbSieges() + $res['resultatPrec']->getNbSiegesSort()) / $data["totalSiegeElectPrec"] * 100), 2, '.', ',') . '%';
                    }
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('H' . $ligne, $tmp);

                    $tmp = '-';
                    if ($data["totalSiegeElect"] && null != $electPrec && $data["totalSiegeElectPrec"] != 0 && ! empty($res['resultatPrec']) && $displayPrecData) {
                        $tmp = number_format((number_format((($res['resultat']->getNbSieges() + $res['resultat']->getNbSiegesSort()) / $data["totalSiegeElect"] * 100), 2, '.', ',') - number_format((($res['resultatPrec']->getNbSieges() + $res['resultatPrec']->getNbSiegesSort()) / $data["totalSiegeElectPrec"] * 100), 2, '.', ',')), 2, '.', ',') . '%';
                    }
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('I' . $ligne, $tmp);
                } elseif($res['resultatPrec']) {
                    // Pas de statistique sur la campagne en cours

                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('A' . $ligne, $res['resultatPrec']->getOrganisation()->getLibelle());

                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . $ligne, '-');

                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('C' . $ligne, '-');

                    $tmp = '-';
                    if (null != $electPrec && $electPrec->getNbVoixTotal() != 0 && ! empty($res['resultatPrec']) && $displayPrecData) {
                        $tmp = number_format(($res['resultatPrec']->getNbVoix() / $electPrec->getNbVoixTotal() * 100), 2, '.', ',') . '%';
                    }
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('D' . $ligne, $tmp);

                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('E' . $ligne, '-');

                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('F' . $ligne, '-');

                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('G' . $ligne, '-');

                    $tmp = '-';
                    if (null != $electPrec && $data["totalSiegeElectPrec"] != 0 && ! empty($res['resultatPrec']) && $displayPrecData) {
                        $tmp = number_format((($res['resultatPrec']->getNbSieges() + $res['resultatPrec']->getNbSiegesSort()) / $data["totalSiegeElectPrec"] * 100), 2, '.', ',') . '%';
                    }
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('H' . $ligne, $tmp);

                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('I' . $ligne, '-');
                }

                $ligne ++;
            }
        } else {
            $msg_erreur_resultat_vide = 'Aucun résultat détaillé pour ';
            if (! empty($data['etablissement'])) {
                $msg_erreur_resultat_vide .= 'cet établissement';
            } else {
                $msg_erreur_resultat_vide .= 'cette zone';
            }
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A' . $ligne, $msg_erreur_resultat_vide);
        }

        if (sizeof($data['listeResultat']) != 0) {
            // Ligne total
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 1), 'TOTAL TOUTES ORGANISATIONS');
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 1), (null != $elect ? $elect->getNbVoixTotal() : '-'));
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('F' . ($ligne + 1), (null != $elect ? $elect->getNbSiegesTotal() : '-'));
        }
        // Activer la 1ère feuille
        $spreadsheet->setActiveSheetIndex(0);

        $sheet->getColumnDimension('A')->setWidth(45);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(20);
        $sheet->getColumnDimension('H')->setWidth(20);
        $sheet->getColumnDimension('I')->setWidth(20);

        $sheet->getStyle('A1:A' . ($ligne+1))->applyFromArray($styleArray);
        $sheet->getStyle('B1:B' . ($ligne+1))->getAlignment()->setHorizontal('center');
        $sheet->getStyle('C1:C' . ($ligne+1))->getAlignment()->setHorizontal('center');
        $sheet->getStyle('D1:D' . ($ligne+1))->getAlignment()->setHorizontal('center');
        $sheet->getStyle('E1:E' . ($ligne+1))->getAlignment()->setHorizontal('center');
        $sheet->getStyle('F1:F' . ($ligne+1))->getAlignment()->setHorizontal('center');
        $sheet->getStyle('G1:G' . ($ligne+1))->getAlignment()->setHorizontal('center');
        $sheet->getStyle('H1:H' . ($ligne+1))->getAlignment()->setHorizontal('center');
        $sheet->getStyle('I1:I' . ($ligne+1))->getAlignment()->setHorizontal('center');

        return $ligne;
    }
}