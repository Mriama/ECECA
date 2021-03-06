<?php

namespace App\Controller;

use App\Entity\EleFichier;
use App\Entity\RefOrganisation;
use App\Entity\EleCampagne;
use App\Entity\EleResultat;
use App\Entity\RefTypeElection;
use App\Form\EleFichierType;
use App\Utils\EcecaExportUtils;
use App\Entity\EleEtablissement;
use App\Entity\EleParticipation;
use App\Entity\RefEtablissement;
use App\Entity\RefSousTypeElection;
use Doctrine\Persistence\ManagerRegistry;
use Qipsius\TCPDFBundle\Controller\TCPDFController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Routing\Annotation\Route;

class PVController extends AbstractController {

    private $request;
    private $doctrine;

    public function __construct(RequestStack $request, ManagerRegistry $doctrine) {
        $this->request = $request->getCurrentRequest();
        $this->doctrine = $doctrine;
    }

    /**
     * Affiche le formulaire d'upload de fichier
     *
     * @param string $codeUrlTypeElect
     * @param string $etablissementUai
     */
    public function indexAction($codeUrlTypeElect, $etablissementUai){

        $params = $this->getParametersForUploadFichier($codeUrlTypeElect, $etablissementUai);

        $electEtablissement = $params['electEtablissement'];

        // L'utilisateur ne peut pas uploader de PV signé si les résultats n'ont pas été transmis
        $user = $this->getUser();
        if(!$user->canUploadPVSigne($electEtablissement->getEtablissement()) || $electEtablissement->isSaisi()){
            throw new AccessDeniedException();
        }

        $params['erreurs']= $this->getParameter('erreurs');
        return $this->render('pv/upload.html.twig', $params);
    }


    /**
     *
     * @param $codeUrlTypeElect
     * @param $etablissementUai
     */
    public function uploadAction($codeUrlTypeElect, $etablissementUai){

        $em = $this->doctrine->getManager();

        $params = $this->getParametersForUploadFichier($codeUrlTypeElect, $etablissementUai);
        $electEtablissement = $params['electEtablissement'];

        // L'utilisateur ne peut pas uploader de PV signé si les résultats n'ont pas été transmis
        $user = $this->getUser();
        if(!$user->canUploadPVSigne($electEtablissement->getEtablissement()) || $electEtablissement->isSaisi()){
            throw new AccessDeniedException();
        }

        $form = $this->createForm(EleFichierType::class, EleFichier::class);

        if ($this->request->getMethod() == 'POST') {
            $form->handleRequest($this->request);
            if ($form->isSubmitted() && $form->isValid()) {

                $datasFichier = $form->getData();

                //  [YME] - 08/09/2014 mantis 0123949 modifiée -> on garde le même fichier mais on le renomme
                /*
                // Test présence fichier
				if (null != $electEtablissement->getFichier()){
				    //Suppression du fichier dans le système
				    //mantis 0123949
					//unlink($electEtablissement->getFichier()->getWebPath());
				    //$params['fileExistsError'] = true;
				    //$params['erreurs']= $this->getParameter('erreurs');
				    //return $this->render('pv/upload.html.twig', $params);
				}else{
    				$electEtablissement->setFichier($datasFichier);
    				$em->persist($electEtablissement); // Met à jour le lien fichier/eleEtablissement
    				$em->flush();
				} */

                $prefix = $electEtablissement->getEtablissement()->getUai().'_'.$electEtablissement->getCampagne()->getTypeElection()->getCode().'_'.$electEtablissement->getCampagne()->getAnneeDebut().'-'.$electEtablissement->getCampagne()->getAnneeFin().'_';

                $datasFichier->setPrefix($prefix);

                if (null != $electEtablissement->getFichier()){
                    $tmpFile = $electEtablissement->getFichier();
                    $electEtablissement->setFichier(null);
                    $em->remove($tmpFile);
                    $em->flush();
                }

                $electEtablissement->setFichier($datasFichier);
                $em->persist($electEtablissement); // Met à jour le lien fichier/eleEtablissement
                $em->flush();

            } else{
                // Erreur d'upload (validation.yml)
                $err_tmp = explode(':', $form->getErrors());
                $params['msgErr'] = $err_tmp[2];
                $params['erreurs']= $this->getParameter('erreurs');
                return $this->render('pv/upload.html.twig', $params);
            }

            return $this->redirect ( $this->generateUrl ( 'ECECA_resultats_etablissement' , array('codeUrlTypeElect'=>$codeUrlTypeElect, 'uai'=>$etablissementUai, 'fileUpload'=>true)));
        }
    }

    /**
     * Fonction permettant la génération d'un PV
     *
     * @param string $etablissementUai
     * @param string $codeUrlTypeElect
     * @throws AccessDeniedException
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function creerPVAction($codeUrlTypeElect, $etablissementUai, $statut){

        $params = array();
        $fileName = 'PV';
        if($statut == 'vierge'){
            $fileName .= '_vierge';
            $params['vierge'] = true;
        }

        $em = $this->doctrine->getManager();

        $joursCalendaires = $this->getParameter('jours_calendaires');
        $joursCalendairesIen = $this->getParameter('jours_calendaires_ien');
        $typeElectionId = RefTypeElection::getIdRefTypeElectionByCodeUrl($codeUrlTypeElect);
        $sousTypeElection = null;
        if (null == $typeElectionId || $codeUrlTypeElect == RefSousTypeElection::CODE_URL_A_ATTE || $codeUrlTypeElect == RefSousTypeElection::CODE_URL_SS) {
            $sousTypeElectionId = RefSousTypeElection::getIdRefSousTypeElectionByCodeUrl($codeUrlTypeElect);
            $sousTypeElection = $em->getRepository(RefSousTypeElection::class)->find($sousTypeElectionId);
            $typeElection = $sousTypeElection->getTypeElection();
            $typeElectionId = $typeElection->getId();
            $params['sousTypeElect'] = $sousTypeElection;
        } else {
            $typeElection = $em->getRepository(RefTypeElection::class)->find($typeElectionId);
        }

        if (empty($typeElection) && null != $sousTypeElection) { throw $this->createNotFoundException('Le type d\'élection '.$codeUrlTypeElect.' n\'a pas été trouvé.'); }

        $params['typeElect'] = $typeElection;

        $user = $this->getUser();
        if(!$user->canConsult($typeElection)){
            throw new AccessDeniedException();
        }

        $campagne = $em->getRepository(EleCampagne::class)->getLastCampagne($typeElectionId);
        if(empty($campagne)){
            throw $this->createNotFoundException('Les résultats de l\'établissement n\'ont pas été trouvés car la campagne est inconnue.');
        }
        $params['campagne'] = $campagne;

        $etablissement = $em->getRepository(RefEtablissement::class)->findOneByUai($etablissementUai);
        if(empty($etablissement)){
            throw $this->createNotFoundException('L\'établissement  n\'a pas été trouvé.');
        }

        $eleEtab = $em->getRepository(EleEtablissement::class)->getEleEtablissementGlobale($campagne, $etablissement, $sousTypeElection);
        if(!empty($eleEtab)){
            $eleEtablissement = $eleEtab;
        } else{
            $eleEtablissement = new EleEtablissement();
            $eleEtablissement->setCampagne($campagne);
            $eleEtablissement->setEtablissement($etablissement);
            $eleEtablissement->setParticipation(new EleParticipation());
        }

        $user = $this->getUser();
        // 014E calendrier IEN pour la saisie du tirage au sort
        if (!$user->canSaisieTirageAuSort($etablissement, $eleEtablissement, $campagne, $joursCalendaires, $joursCalendairesIen)) {
            if (!$user->canGetPVTirageAuSortInAndAfterValidation($etablissement, $eleEtablissement, $campagne, $joursCalendaires)) {
                if (!$user->canGetPVVierge($etablissement)) {
                    if (!$user->canGetPVRempli($etablissement)) {
                        throw new AccessDeniedException();
                    }
                }
            }
        }

        $listeOrganisation = $em->getRepository(RefOrganisation::class)->findBy(array('typeElection' => $typeElectionId,'obsolete' => false), array('detaillee'=>'asc','ordre' => 'asc','libelle' => 'asc'));
        $params['nb_organisation'] = sizeof($listeOrganisation);
        foreach ($listeOrganisation as $organisation) {
            $existe = false;
            foreach ($eleEtablissement->getResultats() as $resultat) {
                if ($resultat->getOrganisation()->getId() == $organisation->getId()) {
                    $existe = true;
                }
            }
            if (!$existe) {
                $eleResultat = new EleResultat();
                $eleResultat->setOrganisation($organisation);
                $eleResultat->setElectionEtab($eleEtablissement);
                $eleEtablissement->addResultat($eleResultat);
            }
        }

        $params['electEtablissement'] = $eleEtablissement;

        $params['warning']= $this->getParameter('warning');

        $fileName = EcecaExportUtils::generateFileName($fileName, $params);

        $pdfService = new TCPDFController('App\Utils\MyPDF');
        $pdf = $pdfService->create();
        $pdf->SetAuthor('ECECA');
        $pdf->SetTitle($fileName);
        $pdf->SetSubject('');
        $pdf->SetKeywords('');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->setFooterValue('2021-2');
        $pdf->setFontSubsetting(true);
        $pdf->SetFont('helvetica', '', 10, '', true);
        $pdf->AddPage();
        $response = new Response();
        $params['warning']= $this->getParameter('warning');

        if ($statut == 'carence') {
            $this->render('pv/exportPDFCarence.html.twig', $params, $response);
        } else if ($statut == 'tirageAuSort') {
            // mantis 146200 : suppression des eleAlertes au moment de l'enregistrement du nbSiegesTirageAuSort mais plus au téléchargement du PV de tirage au sort
            $this->render('pv/exportPDFTirageAuSort.html.twig', $params, $response);
        } else if($statut == 'pvApresTirageAuSort'){
            // EVOL 016E Nouveau PV tirage au sort
            $this->render('pv/exportPDFApresTirageAuSort.html.twig', $params, $response);
        } else {
            // mantis 146200 : suppression des eleAlertes au moment de l'enregistrement de la nouvelle élection mais plus au téléchargement du PV
            $this->render('pv/exportPDF.html.twig', $params, $response);
        }

        $html = $response->getContent();
        $pdf->writeHTML($html, true, 0, true, 0);
        $pdf->lastPage();
        $response = new Response($pdf->Output($fileName . '.pdf', 'D'));
        $response->headers->set('Content-Type', 'application/pdf');

        return $response;
    }

    /**
     * Récupère les paramètres génériques pour la manipulation des PVs
     *
     * @param string $codeUrlTypeElect
     * @param string $etablissementUai
     * @throws AccessDeniedException
     * @return array
     */
    private function getParametersForUploadFichier($codeUrlTypeElect, $etablissementUai){

        $em = $this->doctrine->getManager();

        $typeElectionId = RefTypeElection::getIdRefTypeElectionByCodeUrl($codeUrlTypeElect);
        $typeElection = $typeElectionId != null ? $em->getRepository(RefTypeElection::class)->find($typeElectionId) : null;
        if(empty($typeElection)){
            throw $this->createNotFoundException('Le type d\'élection n\'a pas été trouvé.');
        }
        $params['typeElect'] = $typeElection;

        $campagne = $em->getRepository(EleCampagne::class)->getLastCampagneNonArchive($typeElection);
        if(empty($campagne)){
            throw $this->createNotFoundException('Aucune campagne ouverte pour ce type d\'élection n\'a pas été trouvé.');
        }

        $etablissement = $em->getRepository(RefEtablissement::class)->findOneByUai($etablissementUai);
        if(empty($etablissement)){
            throw $this->createNotFoundException('L\'établissement  n\'a pas été trouvé.');
        }

        $elecEtablissement = $em->getRepository(EleEtablissement::class)->getEleEtablissementGlobale($campagne, $etablissement);

        $params['electEtablissement'] = $elecEtablissement;

        $form = $this->createForm(EleFichierType::class);
        $params['form'] = $form->createView();
        return $params;
    }
}