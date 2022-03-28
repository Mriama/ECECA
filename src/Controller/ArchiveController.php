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
use App\Entity\RefUser;
use App\Utils\RefUserPerimetre;
use App\Entity\RefTypeEtablissement;
use App\Model\CampagneZoneEtabModel;
use App\Form\ArchiveCampagneZoneEtabType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Controller\BaseController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class ArchiveController extends BaseController
{

    /**
     * Fonction permettant d'afficher les statistiques d'une année n et n-1 en
     * fonction des données sélectionnées par l'utilisateur dans le formulaire associé
     *
     * @param Request $request
     * @param string $codeUrlTypeElect
     * @throws AccessDeniedException
     */

    public function indexAction(Request $request, $codeUrlTypeElect, RefUserPerimetre $refUserPerimetre, ParameterBagInterface $parameters)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        if ($user->getProfil()->getCode() != RefProfil::CODE_PROFIL_DGESCO) { // archive disponible uniquement pour l'administration centrale
            throw new AccessDeniedException();
        }
        // reset session
        $request->getSession()->remove('campagne_annee_deb');
        $request->getSession()->remove('select_campagne');
        $request->getSession()->remove('select_academie');
        $request->getSession()->remove('select_departement');
        $request->getSession()->remove('select_commune');
        $request->getSession()->remove('select_etablissement');
        $request->getSession()->remove('select_choix_etab');
        $request->getSession()->remove('select_type_etablissement');

        $params = $this->getParametresStatistiques($request, $user, $codeUrlTypeElect);
        $params['warning'] = $this->getParameter('warning');

        return $this->render('archives/index.html.twig', $params);
    }

    public function rechercheAction(Request $request, $codeUrlTypeElect, ParameterBagInterface $parameters)
    {
        $user = $this->get('security.context')->getToken()->getUser();

        $params = $this->getParametresStatistiques($request, $user, $codeUrlTypeElect);
        $params['warning'] = $this->getParameter('warning');

        return $this->render('archives/index.html.twig', $params);
    }

    /**
     * Récupère les données pour l'affichage des statistiques
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param unknown $user
     * @param unknown $codeUrlTypeElect
     * @return multitype:boolean NULL Ambigous <\App\Entity\RefDepartement, \App\Entity\RefAcademie> unknown \App\Entity\RefDepartement Ambigous <NULL, unknown>
     */
    private function getParametresStatistiques(\Symfony\Component\HttpFoundation\Request $request, $user, $codeUrlTypeElect)
    {
        $em = $this->getDoctrine()->getManager();
        $params = array();
        $campagneAnneeDeb = $request->getSession()->get('campagne_annee_deb');
        $codeUrlTypeEtab = 'tous';

        $typeElection = $em->getRepository(RefTypeElection::class)->find(RefTypeElection::getIdRefTypeElectionByCodeUrl($codeUrlTypeElect));
        if (empty($typeElection)) {
            throw $this->createNotFoundException('Le type d\'élection n\'a pas été trouvé.');
        }
        $params['typeElect'] = $typeElection;
        if($request->getSession()->get('select_type_etablissement') != null){
            $typeEtab = $em->getRepository(RefTypeEtablissement::class)->find($request->getSession()->get('select_type_etablissement'));
        }else{
            $typeEtab = null;
        }
        if($request->getSession()->get('select_commune') != null){
            $commune = $em->getRepository(RefCommune::class)->find($request->getSession()->get('select_commune'));
        }else{
            $commune = null;
        }
        if($request->getSession()->get('select_etablissement') != null){
            $refEtab = $$em->getRepository(RefEtablissement::class)->find($request->getSession()->get('select_etablissement'));
        }else{
            $refEtab = null;
        }
        $cze_current = new CampagneZoneEtabModel(
            $typeElection,
            $typeEtab,
            $request->getSession()->get('select_choix_etab'),
            $commune,
            $refEtab
        );
        // Données par défaut commenter par faky
        // $cze_current->setAcademie($em->getRepository(RefAcademie::class)->find($request->getSession()->get('select_academie')));
        // $cze_current->setDepartement($em->getRepository(RefDepartement::class)->find($request->getSession()->get('select_departement')));

        // Type établissement
        $codeUrlTypeEtab = (null == $cze_current->getTypeEtablissement() ? 'Tous' : $cze_current->getTypeEtablissement()->getCodeUrlById());

        $datasSearch = array();

        $datasSearch['typeElect'] = $typeElection;
        // $datasSearch['campagne'] =  $em->getRepository(EleCampagne::class)->find($request->getSession()->get('select_campagne'));
        // $datasSearch['academie'] = $em->getRepository(RefAcademie::class)->find($request->getSession()->get('select_academie'));
        // $datasSearch['departement'] = $em->getRepository(RefDepartement::class)->find($request->getSession()->get('select_departement'));
        // $datasSearch['typeEtablissement'] = $em->getRepository(RefTypeEtablissement::class)->find($request->getSession()->get('select_type_etablissement'));
        // $datasSearch['choix_etab'] = $request->getSession()->get('select_choix_etab');
        // $datasSearch['commune'] = $em->getRepository(RefCommune::class)->find($request->getSession()->get('select_commune'));
        // $datasSearch['etablissement'] = $em->getRepository(RefEtablissement::class)->find($request->getSession()->get('select_etablissement'));
        $datasSearch['campagne'] = null;
        $datasSearch['academie'] = null;
        $datasSearch['departement'] = null;
        $datasSearch['typeEtablissement'] = null;
        $datasSearch['choix_etab'] = null;
        $datasSearch['commune'] = null;
        $datasSearch['etablissement'] = null;
        $datasSearch["user"] = $user;
        $form = $this->createForm(ArchiveCampagneZoneEtabType::class, null, $datasSearch);

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {

                $datasForm = $form->getData();

                $datasSearch['campagne'] = $datasForm['campagne'];
                $datasSearch['academie'] = $datasForm['academie'];
                $datasSearch['departement'] = $datasForm['departement'];
                $datasSearch['typeEtablissement'] = $datasForm['typeEtablissement'];
                $datasSearch['choix_etab'] = $datasForm['choix_etab'];
                $datasSearch['commune'] = $datasForm['commune'];
                $datasSearch['etablissement'] = $datasForm['etablissement'];


            }else{

                // YME : rustine pour DGESCO, à corriger
                $arrayRequest = $request->request->all();

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
            if ($datasSearch['choix_etab'] && empty($datasSearch['commune'])) {
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

        if (isset($datasSearch['typeEtablissement']) && $datasSearch['typeEtablissement'] != null && !empty($datasSearch['typeEtablissement'])) {
            $codeUrlTypeEtab = $datasSearch['typeEtablissement']->getCodeUrlById();
        } else {
            $codeUrlTypeEtab = 'tous';
        }
        $params['codeUrlTypeEtab'] = $codeUrlTypeEtab;

        // Campagne et campagne antérieure
        if ($campagneAnneeDeb == null) {
            $campagne = $em->getRepository(EleCampagne::class)->getLastCampagneArchivee($typeElection);
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
        if (!empty($datasSearch['departement'])) {
            $params['dept'] = $datasSearch['departement'];
            $zone = $datasSearch['departement'];
        } elseif (!empty($datasSearch['academie'])) {
            $params['aca'] = $datasSearch['academie'];
            $zone = $datasSearch['academie'];
        } else {
            $params['nationale'] = true;
            $zone = null;
        }

        //Statistiques générales des 2 dernières campagnes
        //$params['nbEtabExprDetailles'] = $em->getRepository(EleConsolidation::class)->getNbEtabExprWithTypeEtabFromEleConsolidationByCampagneZoneTypeEtab($campagne, $zone, $datasSearch['typeEtablissement'], $user->getPerimetre());

        //Choix par établissement : recherche dans EleEtablissement
        if (isset($datasSearch['choix_etab']) && $datasSearch['choix_etab'] && !empty($datasSearch['etablissement'])) {


            $params['etablissement'] = $datasSearch['etablissement'];
            $eleEtablissement = $em->getRepository(EleEtablissement::class)->getEleEtablissementGlobale($campagne, $datasSearch['etablissement']);


            if(null != $eleEtablissement && !$eleEtablissement->isValide()){ // mantis 123016
                $eleEtablissement = null;
            }

            $params['electEtablissement'] = $eleEtablissement;

            $params['nbEtabParZone'] = sizeof($params['etablissement']);

            if (! empty($campagnePrec)) {
                $params['electEtablissementPrec'] = $em->getRepository(EleEtablissement::class)->getEleEtablissementGlobale($campagnePrec, $datasSearch['etablissement']);
            } else {
                $params['electEtablissementPrec'] = null;
            }

            // Résultats de l'établissement (année n et année n-1)
            $params['listeResultat'] = $this->getListeResultatsAnneeCouranteAnneePrec($params['electEtablissement'], $params['electEtablissementPrec']);

        } else {

            // Choix par zone : recherche dans EleParticipation

            $params['nbEtabParZone'] = $em->getRepository(RefEtablissement::class)->getNbEtabParTypeEtablissementZoneCommune($datasSearch['typeEtablissement'], $zone, null, false, true, $user);
            $params['nbEtabSaisieParZone'] = $em->getRepository(EleEtablissement::class)->getNbEleEtabParCampagne($campagne, $zone, 'S', $datasSearch['typeEtablissement'], $user);
            $params['nbEtabSaisieValideeParZone'] = $em->getRepository(EleEtablissement::class)->getNbEleEtabParCampagne($campagne, $zone, 'V', $datasSearch['typeEtablissement'], $user);
            //$params['electZone'] = $em->getRepository(EleConsolidation::class)->getEleConsolidationGlobale($campagne, $datasSearch['typeEtablissement'], $zone, $user);
            // mantis 0213287
            $params['electZone'] = $em->getRepository(EleEtablissement::class)->getConsolidationByCampagneTypeEtabZoneEtatSaisie($em, $campagne, $datasSearch['typeEtablissement'], $zone, 'V', $user);

            if ($params['electZone']->getNbEtabTotal() == null) {
                $params['electZone']->setNbEtabTotal($params['nbEtabParZone']);
            }

            if ($params['electZone']->getNbEtabExprimes() == null) {
                $params['electZone']->setNbEtabExprimes(0);
            }

            if (! empty($campagnePrec)) {
                // $params['electZonePrec'] = $em->getRepository(EleConsolidation::class)->getEleConsolidationGlobale($campagnePrec, $datasSearch['typeEtablissement'], $zone, $user);
                // mantis 0213287
                $params['electZonePrec'] = $em->getRepository(EleEtablissement::class)->getConsolidationByCampagneTypeEtabZoneEtatSaisie($em, $campagnePrec, $datasSearch['typeEtablissement'], $zone, 'V', $user);
            } else
                $params['electZonePrec'] = null;

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
        $request->getSession()->set('campagne_annee_deb', $campagneAnneeDeb);
        $request->getSession()->set('select_campagne', ($datasSearch['campagne'] instanceof EleCampagne) ? $datasSearch['campagne']->getId() : null);
        $request->getSession()->set('select_academie', ($datasSearch['academie'] instanceof RefAcademie) ? $datasSearch['academie']->getIdZone() : null);
        $request->getSession()->set('select_departement', ($datasSearch['departement'] instanceof RefDepartement) ? $datasSearch['departement']->getIdZone() : null);

        $request->getSession()->set('select_type_etablissement', ($datasSearch['typeEtablissement'] instanceof RefTypeEtablissement) ? $datasSearch['typeEtablissement']->getId() : null);
        $request->getSession()->set('select_choix_etab', $datasSearch['choix_etab']);
        $request->getSession()->set('select_commune', ($datasSearch['commune'] instanceof RefCommune) ? $datasSearch['commune']->getId() : null);
        $request->getSession()->set('select_etablissement', ($datasSearch['etablissement'] instanceof RefEtablissement) ? $datasSearch['etablissement']->getUai() : null);

        // Mantis 0213287
        // mise en session des params
        // $request->getSession()->set('params_archives', $params);


        $formatAcademiesForDegesco = null;
        if($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DGESCO) {
            $formatAcademiesForDegesco = array();
            $dateFinCampagne = $campagne != null ? new Datetime($campagne->getAnneeDebut() . "-12-31") : null;
            $dateDebutCampagne = $campagne != null ? new Datetime($campagne->getAnneeDebut() . "-01-01") : null;
            foreach ($user->getPerimetre()->getAcademies() as $academie) {
                if($dateDebutCampagne!= null && $dateFinCampagne!= null && $academie->getDateActivation() > $dateDebutCampagne) {
                    $fusionChild = $em->getRepository(RefAcademie::class)->getchildnewAcademies($academie->getCode());
                    foreach ($fusionChild as $child) {
                        if(!in_array($child->getCode(), $formatAcademiesForDegesco)) {
                            $formatAcademiesForDegesco[] = $child->getCode();
                        }
                    }
                } else {
                    $formatAcademiesForDegesco[] = $academie->getCode();
                }
            }
        }
        $datasSearch['user'] = $user;
        $datasSearch['academies'] = $formatAcademiesForDegesco;
        $form = $this->createForm(ArchiveCampagneZoneEtabType::class, null, $datasSearch);

        if($zone != null && $zone instanceof RefAcademie) {
            $params['hidePrecResult'] = $datasSearch['academie']->getDateActivation()->format('Y') == $datasSearch['campagne']->getAnneeDebut();
        }
        $params['form'] = $form->createView();

        return $params;
    }

    /**
     * Cette fonction renvoie une liste de résultats en associant les résultats de l'année n avec les résultats de l'année n-1
     *
     * @param
     *            EleEtablissement ou EleConsolidation $elect
     * @param
     *            EleEtablissement ou EleConsolidation $electPrec
     * @return array EleResulat
     *         @codeCoverageIgnore
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
     * Fonction permettant la génération d'un PDF à partir du twig résultat
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $codeUrlTypeElect
     * @param string $anneeCampagne
     * @throws AccessDeniedException
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function exportStatistiquesPDFAction(\Symfony\Component\HttpFoundation\Request $request, $codeUrlTypeElect)
    {
        $user = $this->get('security.context')
            ->getToken()
            ->getUser();

        if ($user->getProfil()->getCode() != RefProfil::CODE_PROFIL_DGESCO) { // archive disponible uniquement pour l'administration centrale
            throw new AccessDeniedException();
        }

        $params = $this->getParametresStatistiques($request, $user, $codeUrlTypeElect);

        $fileName = EcecaExportUtils::generateFileName('Archives', $params);

        $pdf = $this->get("white_october.tcpdf")->create('L');
        $pdf->SetAuthor('ECECA');
        $pdf->SetTitle($fileName);
        $pdf->SetSubject('');
        $pdf->SetKeywords('');
        $pdf->setPrintHeader(false);
        $pdf->AddPage();
        $response = new Response();
        $params['warning'] = $this->getParameter('warning');

        $this->render('archives/exportPDFStatistiques.html.twig', $params, $response);
        $html = $response->getContent();
        $pdf->writeHTML($html, true, 0, true, 0);
        $pdf->lastPage();
        $response = new Response($pdf->Output($fileName . '.pdf', 'D'));
        $response->headers->set('Content-Type', 'application/pdf');

        return $response;
    }

    /**
     * Fonction permettant la génération d'un PDF à partir du twig résultat
     * Export complet des établissements classés par code postal
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $codeUrlTypeElect
     * @param string $anneeCampagne
     * @throws AccessDeniedException
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function exportStatistiquesPDFCompletAction(\Symfony\Component\HttpFoundation\Request $request, $codeUrlTypeElect)
    {
        $user = $this->get('security.context')
            ->getToken()
            ->getUser();
        if ($user->getProfil()->getCode() != RefProfil::CODE_PROFIL_DGESCO) { // archive disponible uniquement pour l'administration centrale
            throw new AccessDeniedException();
        }

        $em = $this->getDoctrine()->getManager();

        $params = $this->getParametresStatistiques($request, $user, $codeUrlTypeElect);

        $typeEtabForm = $em->getRepository(RefTypeEtablissement::class)->find($request->getSession()
            ->get('select_type_etablissement'));
        $deptForm = $em->getRepository(RefDepartement::class)->find($request->getSession()
            ->get('select_departement'));
        $lstElectEtab = $em->getRepository(EleEtablissement::class)->findByCampagneTypeEtabZone($params['campagne'], $typeEtabForm, $deptForm, true);

        /**
         * **** Récupération de l'ensemble des résultats par établissement *****
         */
        $params['listeResultat'] = $this->getListeResultatsAnneeCouranteAnneePrec($params['electEtablissement'], $params['electEtablissementPrec']);

        $fileName = EcecaExportUtils::generateFileName('Archives', $params);

        $pdf = $this->get("white_october.tcpdf")->create('L');
        $pdf->SetAuthor('ECECA');
        $pdf->SetTitle($fileName);
        $pdf->SetSubject('');
        $pdf->SetKeywords('');
        $pdf->setPrintHeader(false);
        $pdf->AddPage();
        $response = new Response();
        $params['warning'] = $this->getParameter('warning');
        $this->render('archives/exportPDFStatistiquesComplet.html.twig', $params, $response);
        $html = $response->getContent();
        $pdf->writeHTML($html, true, 0, true, 0);

        foreach ($lstElectEtab as $electEtab) {
            $etab = $em->getRepository(RefEtablissement::class)->find($electEtab['uai']);
            $params['etablissement'] = $etab;

            $params['electEtablissement'] = $em->getRepository(EleEtablissement::class)->getEleEtablissementGlobale($params['campagne'], $etab);
            if (! empty($params['campagnePrec'])) {
                $params['electEtablissementPrec'] = $em->getRepository(EleEtablissement::class)->getEleEtablissementGlobale($params['campagnePrec'], $etab);
            } else {
                $params['electEtablissementPrec'] = null;
            }
            $params['listeResultat'] = $this->getListeResultatsAnneeCouranteAnneePrec($params['electEtablissement'], $params['electEtablissementPrec']);

            $pdf->AddPage();
            $response = new Response();
            $params['warning'] = $this->getParameter('warning');
            $this->render('archives/exportPDFStatistiquesCompletDetailEtablissement.html.twig', $params, $response);
            $html = $response->getContent();
            $pdf->writeHTML($html, true, 0, true, 0);
        }

        $pdf->lastPage();
        $response = new Response($pdf->Output($fileName . '.pdf', 'D'));
        $response->headers->set('Content-Type', 'application/pdf');

        return $response;
    }

    /**
     * Fonction permettant l'édition au format Excel des résultats d'une zone ou d'un établissement
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $codeUrlTypeElect
     * @param string $anneeCampagne
     * @throws AccessDeniedException
     * @return file xls
     */
    public function exportStatistiquesXLSAction(\Symfony\Component\HttpFoundation\Request $request, $codeUrlTypeElect)
    {
        $user = $this->get('security.context')
            ->getToken()
            ->getUser();
        if ($user->getProfil()->getCode() != RefProfil::CODE_PROFIL_DGESCO) { // archive disponible uniquement pour l'administration centrale
            throw new AccessDeniedException();
        }

        // Récupération des données de statistiques
        $params = $this->getParametresStatistiques($request, $user, $codeUrlTypeElect);

        $params['ligne'] = 1;
        $params['complet'] = false;

        // **************** Création de l'objet Excel *********************//
        $phpExcelObject = $this->get('phpexcel')->createPHPExcelObject();

        // Export
        $this->generateStatistiquesXLS($params, $phpExcelObject);

        // Création du writer
        $writer = $this->get('phpexcel')->createWriter($phpExcelObject, 'Excel5');

        // Création du nom du fichier
        $fileName = EcecaExportUtils::generateFileName('Archives', $params);

        // Créer la réponse
        $response = $this->get('phpexcel')->createStreamedResponse($writer);
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment;filename=' . $fileName . '.xls');
        return $response;
    }

    private function generateStatistiquesXLS($data, &$phpExcelObject)
    {

        // Données à exporter
        $campagne = $data['campagne'];
        $codeUrlTypeEtab = $data['codeUrlTypeEtab'];
        $ligne = $data['ligne'];
        $typeElect = $data['typeElect'];
        $displayPrecData = (isset($data["hidePrecResult"])) ? !$data["hidePrecResult"]  : true;

        $sheet = $phpExcelObject->getActiveSheet();
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
            } elseif (! empty($data['dept'])) {
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
            }
            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('A' . $ligne, $typeElect->getLibelle() . ' - Archives ' . $libelle_recherche);
        } else {
            $elect = $data['electEtablissement'];
            $electPrec = $data['electEtablissementPrec'];
            $libelle_recherche = 'de l\'établissement ' . $elect->getEtablissement()->getLibelle() . ' (' . $elect->getEtablissement()->getUai() . ')';
            if ($data['complet']) {
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('A' . $ligne, 'Archives ' . $libelle_recherche);
            } else {
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('A' . $ligne, $typeElect->getLibelle() . ' - Archives ' . $libelle_recherche);
            }
        }
        $sheet->getStyle('A' . $ligne)->applyFromArray($styleArrayTitre);

        // **************** Génération du descriptif *********************//
        $ligne += 2;
        $nomZone = '';
        if (! empty($data['etablissement'])) {

            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('A3', 'Description d\'école ou d\'établissement');
            // Emplacement ligne campagne
            //$case_libelle_campagne = 'A' . $ligne;
            //$case_valeur_campagne = 'B' . $ligne;
            // Descriptif établissement
            $etablissement = $data['etablissement'];
            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 1), 'Unité Administrative Immatriculée');
            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 2), 'Libellé');
            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 3), 'Type d\'établissement');
            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 4), 'Commune');
            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 5), 'Catégorie');
            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 6), 'Contact');

            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 1), $etablissement->getUai());
            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 2), $etablissement->getLibelle());
            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 3), $etablissement->getTypeEtablissement()
                ->getLibelle());
            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 4), $etablissement->getCommune()
                    ->getLibelle() . ' (' . $etablissement->getCommune()
                    ->getDepartement()
                    ->getLibelle() . ')');
            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 5), (null == $etablissement->getTypePrioritaire() ? 'N/A' : $etablissement->getTypePrioritaire()
                ->getCode()));
            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 6), $etablissement->getContact());

            // Récupération des données pour le titre du fichier
            $nomZone = $etablissement->getUai();

            $ligne += 9;

        } else {
            // Emplacement ligne campagne
            $case_libelle_campagne = 'A3';
            $case_valeur_campagne = 'B3';
            // Descriptif Zone != Etablissement
            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 1), 'Academie');
            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 2), 'Département');
            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 3), 'Type d\'établissement');
            // $phpExcelObject->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 5), 'Nombre d\'établissements');
            // $phpExcelObject->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 6), 'Nombre d\'établissements exprimés');

            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 1), $filtre_aca);
            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 2), $filtre_dept);
            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 3), ucwords($codeUrlTypeEtab));
            // $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 5), $elect->getNbEtabTotal());
            // $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 6), $elect->getNbEtabExprimes());

            // Récupération des données pour le titre
            $nomZone = $libelle_recherche;

            $ligne += 8;


            // **************** Ligne campagne ***************************//
            $phpExcelObject->setActiveSheetIndex(0)->setCellValue($case_libelle_campagne, 'Campagne');
            $phpExcelObject->setActiveSheetIndex(0)->setCellValue($case_valeur_campagne, $campagne->getAnneeDebut() . '-' . $campagne->getAnneeFin());
        }

        // **************** Génération de la participation *******************//
        if (null != $elect && ($elect->getParticipation() || $electPrec->getParticipation())) {

            $participation = $elect->getParticipation();
            $participationPrec = (null != $electPrec ? $electPrec->getParticipation() : null);

            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('A' . ($ligne - 1), 'Participation');
            if ($typeElect->getId() == RefTypeElection::ID_TYP_ELECT_PARENT) {
                if($isZone) {
                    $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . $ligne, 'Modalité de vote');
                    $sheet->getStyle('B' . $ligne)->applyFromArray($styleArray);
                    $phpExcelObject->setActiveSheetIndex(0)->setCellValue('C' . $ligne, 'Rappel ' . ($campagne->getAnneeDebut() - 1) . ' - ' . ($campagne->getAnneeFin() - 1));
                    $sheet->getStyle('C' . $ligne)->applyFromArray($styleArray);
                    $phpExcelObject->setActiveSheetIndex(0)->setCellValue('D' . $ligne, 'Variation');
                    $sheet->getStyle('D' . $ligne)->applyFromArray($styleArray);

                    $phpExcelObject->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 1), 'Vote ' . RefModaliteVote::LIBELLE_MODALITE_VOTE_URNE_CORRESPONDANCE);
                    $phpExcelObject->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 2), 'Vote ' . RefModaliteVote::LIBELLE_MODALITE_VOTE_CORRESPONDANCE);

                    if ($participation != null) {
                        $totalModalite = $elect->getNbEtabExprimes();
                        if($totalModalite != 0) {
                            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 1), round($participation->getConsolidationVoteUrneCorrespondance()/$totalModalite*100, 2) . '%');
                            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 2), round($participation->getConsolidationVoteCorrespondance()/$totalModalite*100, 2) . '%');
                        } else {
                            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 1), '-');
                            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 2), '-');
                        }
                    } else {
                        $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 1), '-');
                        $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 2), '-');
                    }

                    if ($participationPrec != null && $displayPrecData) {
                        $totalModalitePrec = $electPrec->getNbEtabExprimes();
                        if($totalModalitePrec != 0) {
                            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 1), round($participationPrec->getConsolidationVoteUrneCorrespondance() / $totalModalitePrec * 100, 2) . '%');
                            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 2), round($participationPrec->getConsolidationVoteCorrespondance() / $totalModalitePrec * 100, 2) . '%');
                        } else {
                            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 1), '-');
                            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 2), '-');
                        }
                    } else {
                        $phpExcelObject->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 1), '-');
                        $phpExcelObject->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 2), '-');
                    }
                    if ($participation != null && $participationPrec != null && $displayPrecData && $totalModalite != 0 && $totalModalitePrec != 0) {
                        $phpExcelObject->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 1), round(($participation->getConsolidationVoteUrneCorrespondance() / $totalModalite * 100) - ($participationPrec->getConsolidationVoteUrneCorrespondance() / $totalModalitePrec * 100),2). '%');
                        $phpExcelObject->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 2), round(($participation->getConsolidationVoteCorrespondance()/$totalModalite*100) - ($participationPrec->getConsolidationVoteCorrespondance()/$totalModalitePrec*100), 2). '%');
                    } else {
                        $phpExcelObject->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 1), '-');
                        $phpExcelObject->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 2), '-');
                    }
                    $ligne += 4;
                } else {
                    $phpExcelObject->setActiveSheetIndex(0)->setCellValue('A' . $ligne, 'Modalité de vote');
                    $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . $ligne, $participation->getModaliteVote != null ? $participation->getModaliteVote()->getLibelle() : "-");
                    $ligne += 2;
                }
            }

            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . $ligne, 'Résultats Bruts');
            $sheet->getStyle('B' . $ligne)->applyFromArray($styleArray);
            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('C' . $ligne, 'Rappel ' . ($campagne->getAnneeDebut() - 1) . ' - ' . ($campagne->getAnneeFin() - 1));
            $sheet->getStyle('C' . $ligne)->applyFromArray($styleArray);
            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('D' . $ligne, 'Variation');
            $sheet->getStyle('D' . $ligne)->applyFromArray($styleArray);

            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 1), 'Nombre d\'inscrits');
            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 2), 'Nombre de votants');
            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 4), 'Nombre de suffrages exprimés');
            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 3), 'Nombre de votes blancs ou nuls');
            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 5), 'Taux de participation');

            if ($participation != null) {
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 1), $participation->getNbInscrits());
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 2), $participation->getNbVotants());
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 4), $participation->getNbExprimes());
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 3), $participation->getNbNulsBlancs());
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 5), number_format($participation->getTaux(), 2, '.', ',') . '%');
            } else {
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 1), '-');
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 2), '-');
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 4), '-');
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 3), '-');
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 5), '-');
            }

            if ($participationPrec != null && $displayPrecData) {
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 1), $participationPrec->getNbInscrits());
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 2), $participationPrec->getNbVotants());
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 4), $participationPrec->getNbExprimes());
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 3), $participationPrec->getNbNulsBlancs());
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 5), number_format($participationPrec->getTaux(), 2, '.', ',') . '%');
            } else {
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 1), '-');
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 2), '-');
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 3), '-');
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 4), '-');
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 5), '-');
            }

            if ($participation != null && $participationPrec != null && $displayPrecData) {
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 1), $participation->getNbInscrits() - $participationPrec->getNbInscrits());
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 2), $participation->getNbVotants() - $participationPrec->getNbVotants());
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 4), $participation->getNbExprimes() - $participationPrec->getNbExprimes());
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 3), $participation->getNbNulsBlancs() - $participationPrec->getNbNulsBlancs());
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 5), number_format((number_format($participation->getTaux(), 2, '.', ',') - number_format($participationPrec->getTaux(), 2, '.', ',')),2, '.', ',') . '%');
            } else {
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 1), '-');
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 2), '-');
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 3), '-');
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 4), '-');
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 5), '-');
            }

            $ligne = $ligne + 8;

            // *************** Génération des résultats *******************//
            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('A' . ($ligne - 1), 'Résultats');
            // NB. Le quotient éléctoral est affiché par défaut avec une virgule par excel.
            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . $ligne, 'Résultats Bruts');
            $sheet->getStyle('B' . $ligne)->applyFromArray($styleArray);
            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('C' . $ligne, 'Rappel ' . ($campagne->getAnneeDebut() - 1) . ' - ' . ($campagne->getAnneeFin() - 1));
            $sheet->getStyle('C' . $ligne)->applyFromArray($styleArray);
            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('D' . $ligne, 'Variation');
            $sheet->getStyle('D' . $ligne)->applyFromArray($styleArray);

            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 1), 'Nombre de sièges à pourvoir');
            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 2), 'Nombre de sièges pourvus');
            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 3), 'Pourcentage de sièges pourvus');
            if (! empty($data['etablissement'])) {
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 4), 'Quotient électoral');
            }

            if ($participation != null) {
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 1), $participation->getNbSiegesPourvoir());
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 2), $participation->getNbSiegesPourvus());
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 3), number_format($participation->getTauxSieges(), 2, '.', ',') . '%');
                if (! empty($data['etablissement'])) {
                    $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 4), number_format($participation->getQuotient(), 2, '.', ','));
                }
            } else {
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 1), '-');
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 2), '-');
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 3), '-');
                if (! empty($data['etablissement'])) {
                    $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 4), '-');
                }
            }

            if ($participationPrec != null && $displayPrecData) {
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 1), $participationPrec->getNbSiegesPourvoir());
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 2), $participationPrec->getNbSiegesPourvus());
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 3), number_format($participationPrec->getTauxSieges(), 2, '.', ',') . '%');
                if (! empty($data['etablissement'])) {
                    $phpExcelObject->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 4), number_format($participationPrec->getQuotient(), 2, '.', ','));
                }
            } else {
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 1), '-');
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 2), '-');
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 3), '-');
                if (! empty($data['etablissement'])) {
                    $phpExcelObject->setActiveSheetIndex(0)->setCellValue('C' . ($ligne + 4), '-');
                }
            }

            if ($participation != null && $participationPrec != null && $displayPrecData) {
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 1), $participation->getNbSiegesPourvoir() - $participationPrec->getNbSiegesPourvoir());
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 2), $participation->getNbSiegesPourvus() - $participationPrec->getNbSiegesPourvus());
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 3), number_format((number_format($participation->getTauxSieges(), 2, '.', ',') - number_format($participationPrec->getTauxSieges(), 2, '.', ',')), 2, '.', ',') . '%');
                if (! empty($data['etablissement'])) {
                    $phpExcelObject->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 4), number_format((number_format($participation->getQuotient(), 2, '.', ',') - number_format($participationPrec->getQuotient(), 2, '.', ',')), 2, '.', ','));
                }
            } else {
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 1), '-');
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 2), '-');
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 3), '-');
                if (! empty($data['etablissement'])) {
                    $phpExcelObject->setActiveSheetIndex(0)->setCellValue('D' . ($ligne + 4), '-');
                }
            }
            $ligne += 7;
        } else {
            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('A' . $ligne, 'Aucun résultat disponible');
        }

        // ********************** Génération de la répartition détaillée des sièges ***********************//

        if ($elect || $electPrec) {
            if (sizeof($data['listeResultat']) != 0){
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('A' . ($ligne - 1), 'Répartition détaillée des sièges');
                $phpExcelObject->setActiveSheetIndex(0)
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
            }

            $ligne ++;

            for ($i = 0; $i < sizeof($data['listeResultat']); $i ++) {

                $res = null;
                if (isset($data['listeResultat'][$i])) {
                    $res = $data['listeResultat'][$i];
                }

                $totalResultat = false;

                if ($res['resultat'] != null) {
                    $totalResultat = true;

                    $phpExcelObject->setActiveSheetIndex(0)->setCellValue('A' . $ligne, $res['resultat']->getOrganisation()
                        ->getLibelle());

                    $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . $ligne, $res['resultat']->getNbVoix());

                    $tmp = '-';
                    if ($elect->getNbVoixTotal() != 0) {
                        $tmp = number_format(($res['resultat']->getNbVoix() / $elect->getNbVoixTotal() * 100), 2, '.', ',') . '%';
                    }
                    $phpExcelObject->setActiveSheetIndex(0)->setCellValue('C' . $ligne, $tmp);

                    $tmp = '-';
                    if (null != $electPrec && $electPrec->getNbVoixTotal() != 0 && ! empty($res['resultatPrec']) && $displayPrecData) {
                        $tmp = number_format(($res['resultatPrec']->getNbVoix() / $electPrec->getNbVoixTotal() * 100), 2, '.', ',') . '%';
                    }
                    $phpExcelObject->setActiveSheetIndex(0)->setCellValue('D' . $ligne, $tmp);

                    $tmp = '-';
                    if ($elect->getNbVoixTotal() != 0 && null != $electPrec && $electPrec->getNbVoixTotal() != 0 && ! empty($res['resultatPrec']) && $displayPrecData) {
                        $tmp = number_format((number_format(($res['resultat']->getNbVoix() / $elect->getNbVoixTotal() * 100), 2, '.', ',') - number_format(($res['resultatPrec']->getNbVoix() / $electPrec->getNbVoixTotal() * 100), 2, '.', ',')), 2, '.', ',') . '%';
                    }
                    $phpExcelObject->setActiveSheetIndex(0)->setCellValue('E' . $ligne, $tmp);

                    $phpExcelObject->setActiveSheetIndex(0)->setCellValue('F' . $ligne, ($res['resultat']->getNbSieges() + $res['resultat']->getNbSiegesSort()));

                    $tmp = '-';
                    if ($data["totalSiegeElect"] != 0) {
                        $tmp = number_format((($res['resultat']->getNbSieges() + $res['resultat']->getNbSiegesSort()) / $data["totalSiegeElect"] * 100), 2, '.', ',') . '%';
                    }
                    $phpExcelObject->setActiveSheetIndex(0)->setCellValue('G' . $ligne, $tmp);

                    $tmp = '-';
                    if (null != $electPrec && $data["totalSiegeElectPrec"] != 0 && ! empty($res['resultatPrec']) && $displayPrecData) {
                        $tmp = number_format((($res['resultatPrec']->getNbSieges() + $res['resultatPrec']->getNbSiegesSort()) / $data["totalSiegeElectPrec"] * 100), 2, '.', ',') . '%';
                    }
                    $phpExcelObject->setActiveSheetIndex(0)->setCellValue('H' . $ligne, $tmp);

                    $tmp = '-';
                    if ($data["totalSiegeElect"] && null != $electPrec && $data["totalSiegeElectPrec"] != 0 && ! empty($res['resultatPrec']) && $displayPrecData) {
                        $tmp = number_format((number_format((($res['resultat']->getNbSieges() + $res['resultat']->getNbSiegesSort()) / $data["totalSiegeElect"] * 100), 2, '.', ',') - number_format((($res['resultatPrec']->getNbSieges() + $res['resultatPrec']->getNbSiegesSort()) / $data["totalSiegeElectPrec"] * 100), 2, '.', ',')), 2, '.', ',') . '%';
                    }
                    $phpExcelObject->setActiveSheetIndex(0)->setCellValue('I' . $ligne, $tmp);
                } elseif($res['resultatPrec']) {
                    // Pas de statistique sur la campagne en cours

                    $phpExcelObject->setActiveSheetIndex(0)->setCellValue('A' . $ligne, $res['resultatPrec']->getOrganisation()->getLibelle());
                    $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . $ligne, '-');

                    $phpExcelObject->setActiveSheetIndex(0)->setCellValue('C' . $ligne, '-');

                    $tmp = '-';
                    if (null != $electPrec && $electPrec->getNbVoixTotal() != 0 && ! empty($res['resultatPrec']) && $displayPrecData) {
                        $tmp = number_format(($res['resultatPrec']->getNbVoix() / $electPrec->getNbVoixTotal() * 100), 2, '.', ',') . '%';
                    }
                    $phpExcelObject->setActiveSheetIndex(0)->setCellValue('D' . $ligne, $tmp);

                    $phpExcelObject->setActiveSheetIndex(0)->setCellValue('E' . $ligne, '-');

                    $phpExcelObject->setActiveSheetIndex(0)->setCellValue('F' . $ligne, '-');

                    $phpExcelObject->setActiveSheetIndex(0)->setCellValue('G' . $ligne, '-');

                    $tmp = '-';
                    if (null != $electPrec && $data["totalSiegeElectPrec"] != 0 && ! empty($res['resultatPrec']) && $displayPrecData) {
                        $tmp = number_format((($res['resultatPrec']->getNbSieges() + $res['resultatPrec']->getNbSiegesSort()) / $data["totalSiegeElectPrec"] * 100), 2, '.', ',') . '%';
                    }
                    $phpExcelObject->setActiveSheetIndex(0)->setCellValue('H' . $ligne, $tmp);

                    $phpExcelObject->setActiveSheetIndex(0)->setCellValue('I' . $ligne, '-');
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
            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('A' . $ligne, $msg_erreur_resultat_vide);
        }

        if (sizeof($data['listeResultat']) != 0) {
            if ($totalResultat !=null) {
                // Ligne total
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('A' . ($ligne + 1), 'TOTAL TOUTES ORGANISATIONS');
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . ($ligne + 1), $elect->getNbVoixTotal());
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('F' . ($ligne + 1), $elect->getNbSiegesTotal());
            }
        }
        // Activer la 1ère feuille
        $phpExcelObject->setActiveSheetIndex(0);

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