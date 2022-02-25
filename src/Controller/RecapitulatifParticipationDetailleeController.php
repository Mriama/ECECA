<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\ElePrioritaire;
use App\Entity\EleConsolidation;
use App\Entity\EleParticipation;
use App\Entity\EleEtablissement;
use App\Entity\EleResultat;
use App\Entity\RefAcademie;
use App\Entity\RefDepartement;
use App\Entity\RefEtablissement;
use App\Entity\RefTypeElection;
use App\Entity\RefTypeEtablissement;
use App\Entity\RefProfil;
use App\Utils\EpleUtils;
use App\Utils\EcecaExportUtils;
use App\Model\RecapitulatifParticipationEtabTypeModel;
use App\Form\RecapitulatifParticipationEtabType;

class RecapitulatifParticipationDetailleeController extends AbstractController
{

    public function indexAction(\Symfony\Component\HttpFoundation\Request $request, $codeUrlTypeElect)
    {
        // reset session
        $request->getSession()->set('recap_part_detail_campagne_annee_deb', null);
        $request->getSession()->set('recap_part_detail_niveau', 'departement');
        $request->getSession()->set('recap_part_detail_type_etab', null);

        if (false === $this->get('security.context')->isGranted('ROLE_STATS_EDU_PRIO')) {
            throw new AccessDeniedException();
        }

        $user = $this->get('security.context')
            ->getToken()
            ->getUser();

        $params = $this->getParametresStatistiques($request, $codeUrlTypeElect, $user);

        return $this->render('EPLEElectionBundle:RecapitulatifParticipation:indexRecapitulatifParticipationDetaillee.html.twig', $params);
    }

    private function getParametresStatistiques($request, $codeUrlTypeElect, $user)
    {
        $em = $this->getDoctrine()->getManager();
        $params = array();
        $campagneAnneeDeb = null;
        $niveau = 'departement';
        $typeEtab = null;
        $listPrio = array();
        $codeUrlTypeEtab = 'tous';

        /**
         * **** Récupération du type d'election *****
         */
        $typeElection = $em->getRepository('EPLEElectionBundle:RefTypeElection')->find(RefTypeElection::getIdRefTypeElectionByCodeUrl($codeUrlTypeElect));
        if (empty($typeElection)) {
            throw $this->createNotFoundException('Le type d\'élection n\'a pas été trouvé.');
        }

        $params['typeElect'] = $typeElection;

        $cze_current = new RecapitulatifParticipationEtabTypeModel($typeElection);
        // Statistiques par type d'éducation prioritaire
        $form = $this->createForm(new RecapitulatifParticipationEtabType($user), $cze_current);

        if ($request->getMethod() == 'POST') {
            $form->bind($request);
            if ($form->isValid()) {
                $options_recherche = $form->getData();

                $request->getSession()->remove('recap_part_detail_campagne_annee_deb');
                $request->getSession()->remove('recap_part_detail_niveau');
                $request->getSession()->remove('recap_part_detail_type_etab');

                $campagneAnneeDeb = $options_recherche->getCampagne()->getAnneeDebut();
                $niveau = $options_recherche->getNiveau();
                if ($options_recherche->getTypeEtablissement() != null) {
                    $typeEtab = $options_recherche->getTypeEtablissement();
                } else {
                    $typeEtab = null;
                }

                // Mise en place des variables en session pour l'export excel
                $request->getSession()->set('recap_part_detail_campagne_annee_deb', $campagneAnneeDeb);
                $request->getSession()->set('recap_part_detail_niveau', $niveau);
                $request->getSession()->set('recap_part_detail_type_etab', null != $typeEtab ? $typeEtab->getId() : null);
            }
        } else {
            $campagneAnneeDeb = $request->getSession()->get('recap_part_detail_campagne_annee_deb');
            $niveau = $request->getSession()->get('recap_part_detail_niveau');
            $typeEtab = $em->getRepository('EPLEElectionBundle:RefTypeEtablissement')->find($request->getSession()->get('recap_part_detail_type_etab'));
        }

        /**
         * **** Récupération de la dernière campagne et de la campagne anterieure *****
         */
        if ($campagneAnneeDeb == null) {
            $campagne = $em->getRepository('EPLEElectionBundle:EleCampagne')->getLastCampagne($typeElection);
        } else {
            $listeCampagne = $em->getRepository('EPLEElectionBundle:EleCampagne')->getCampagneParTypeElectionAnneeDebut($typeElection, $campagneAnneeDeb);
            $campagne = (! empty($listeCampagne)) ? $listeCampagne[0] : null;
        }

        if (empty($campagne)) {
            throw $this->createNotFoundException('La campagne pour ce type d\'élection n\'a pas été trouvé.');
        }

        $params['campagne'] = $campagne;

        $listeCampagnePrec = $em->getRepository('EPLEElectionBundle:EleCampagne')->getCampagneParTypeElectionAnneeDebut($typeElection, $campagne->getAnneeDebut() - 1);
        $campagnePrec = (! empty($listeCampagnePrec)) ? $listeCampagnePrec[0] : null;
        $params['campagnePrec'] = $campagnePrec;

        // Récuperer la liste des informations à afficher 
        // mantis 225881
        $listParticipationPrio = $em->getRepository('EPLEElectionBundle:EleEtablissement')->findParticipationDetailleeParTypeZoneEtTypePrioritaire($campagne->getId(), $campagnePrec->getId(), $niveau, (null != $typeEtab ? $typeEtab->getId() : null), $user->getPerimetre(), $user);

        $listEtabConso = array();

        if (! empty($listParticipationPrio)) {
            // Col.1 idCampagne
            // Col.2 libelle 
            // Col.3 type prioritaire
            // Col.4 nbInscrits
            // Col.5 nbVotants
            // Col.6 nbExprimes
            // Col.7 %

            //$listParticipationPrio contient pour chaque type prioritaire les sommes correspondantes.
            //Si un type prioritaire n'existe pas il n'est pas retourné par le tableau.

            $libelle = null;
            $code = null;
            foreach ($listParticipationPrio as $line) {
                $rappel = 0;
                if ($libelle != $line['libelle']) {
                    // Nouveau département -> Nouveau groupe
                    $libelle = $line['libelle'];
                    $code = null;
                }


                if($code != $line['code']){
                    $code = $line['code'];
                    if ($line['idCampagne'] == $campagnePrec->getId()) {
                        $listEtabConso[$libelle][$code]['inscrits'] = '';
                        $listEtabConso[$libelle][$code]['votants'] = '';
                        $listEtabConso[$libelle][$code]['exprimes'] = '';
                        $listEtabConso[$libelle][$code]['p'] = '';
                        $listEtabConso[$libelle][$code]['rappel'] = round(floatval($line['p']), 2) . ' %';
                        $listEtabConso[$libelle][$code]['variation'] = '';
                    }else{
                        $listEtabConso[$libelle][$code]['inscrits'] = $line['sumInscrits'];
                        $listEtabConso[$libelle][$code]['votants'] = $line['sumVotants'];
                        $listEtabConso[$libelle][$code]['exprimes'] = $line['sumExprimes'];
                        $listEtabConso[$libelle][$code]['p'] = round(floatval($line['p']), 2) . ' %';
                    }
                }else{
                    $listEtabConso[$line['libelle']][$line['code']]['rappel'] = round(floatval($line['p']), 2) . ' %';
                    $rappel = round(floatval($line['p']), 2);
                }

                if (empty($listEtabConso[$line['libelle']][$line['code']]['rappel'])) {
                    $listEtabConso[$line['libelle']][$line['code']]['rappel'] = '';
                }

                // Calcul de la variation
                if ($rappel > 0) {
                    $listEtabConso[$line['libelle']][$line['code']]['variation'] = round($listEtabConso[$line['libelle']][$line['code']]['p'] - $rappel, 2);
                } else {
                    $listEtabConso[$line['libelle']][$line['code']]['variation'] = '';
                }
            }
        }

        // Ecriture des totaux
        if (! empty($listEtabConso)) {
            foreach ($listEtabConso as $libelle => $zone) {
                $totalInscrits = 0;
                $totalVotants = 0;
                $totalExprimes = 0;
                foreach ($zone as $prio) {

                    $totalInscrits += $prio['inscrits'];
                    $totalVotants += $prio['votants'];
                    $totalExprimes += $prio['exprimes'];
                }
                $listEtabConso[$libelle]['Total ' . $libelle]['inscrits'] = $totalInscrits;
                $listEtabConso[$libelle]['Total ' . $libelle]['votants'] = $totalVotants;
                $listEtabConso[$libelle]['Total ' . $libelle]['exprimes'] = $totalExprimes;
                $listEtabConso[$libelle]['Total ' . $libelle]['p'] = '';
                $listEtabConso[$libelle]['Total ' . $libelle]['rappel'] = '';
                $listEtabConso[$libelle]['Total ' . $libelle]['variation'] = '';
            }
        }

        //E18 Reforme territoriale : Gestion des fusions d'academies : merge des données
        if($niveau != 'departement') {
            $academies = array_keys($listEtabConso);
            $newListConso = array();
            $acadHideRappelAnterieur = array();
            $isProfilDepartement =
                $user->getProfil()->getCode() == RefProfil::CODE_PROFIL_IEN ||
                $user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DSDEN ||
                $user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DE ||
                $user->getProfil()->getCode() == RefProfil::CODE_PROFIL_CE;
            $campagneDebut = new \DateTime($campagneAnneeDeb . "-01-01");
            foreach ($academies as $academie) {
                $acadObj = $em->getRepository('EPLEElectionBundle:RefAcademie')->findOneBy(array("libelle" => $academie));
                if ($acadObj->getDateDesactivation() <= $campagneDebut && $acadObj->getAcademieFusion() != null) {
                    $acadFusion = $em->getRepository('EPLEElectionBundle:RefAcademie')->find($acadObj->getAcademieFusion()->getCode());
                    if (isset($newListConso[$acadFusion->getLibelle()]) && !empty($newListConso[$acadFusion->getLibelle()])) {
                        //Merge des données rattachées à la meme académie de fusion
                        $newListConso[$acadFusion->getLibelle()] = $this->mergeStatParticipationEduPrio($newListConso[$acadFusion->getLibelle()], $listEtabConso[$acadObj->getLibelle()], $acadFusion->getLibelle());
                    } else {
                        $newListConso[$acadFusion->getLibelle()] = $listEtabConso[$acadObj->getLibelle()];
                        //Transformation de la ligne Total
                        $newListConso[$acadFusion->getLibelle()]['Total ' . $acadFusion->getLibelle()] = $listEtabConso[$acadObj->getLibelle()]['Total ' . $acadObj->getLibelle()];
                        unset($newListConso[$acadFusion->getLibelle()]['Total ' . $acadObj->getLibelle()]);
                    }
                    $acadHideRappelAnterieur[$acadFusion->getLibelle()] = $isProfilDepartement ? false : $acadFusion->getDateActivation()->format('Y') == $campagneAnneeDeb;
                } else {
                    $newListConso[$acadObj->getLibelle()] = $listEtabConso[$acadObj->getLibelle()];
                    $acadHideRappelAnterieur[$acadObj->getLibelle()] = $isProfilDepartement ? false : $acadObj->getDateActivation()->format('Y') == $campagneAnneeDeb;
                }
            }
            uksort($newListConso, 'strnatcasecmp');
            $listEtabConso = $newListConso;
            $params["acadHideRappelAnterieur"] = $acadHideRappelAnterieur;
        }

        $params['codeUrlTypeEtab'] = $codeUrlTypeEtab;
        $params['campagneAnneeDeb'] = $campagneAnneeDeb;
        $params['listEtabConso'] = $listEtabConso;
        $params['niveau'] = $niveau;
        $params['typeEtab'] = $typeEtab;
        $params['form'] = $form->createView();
        return $params;
    }

    /**
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param unknown $codeUrlTypeElect
     * @return unknown
     */
    public function exportXLSAction(\Symfony\Component\HttpFoundation\Request $request, $codeUrlTypeElect)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')
            ->getToken()
            ->getUser();
        $params = $this->getParametresStatistiques($request, $codeUrlTypeElect, $user);

        // Récupération des paramètres
        $typeElection = $params['typeElect'];
        $niveau = $params['niveau'];
        $campagne = $params['campagne'];
        $campagnePrec = $params['campagnePrec'];
        $typeEtab = $params['typeEtab'];
        $listEtabConso = $params['listEtabConso'];

        // Génération du fichier Excel
        $phpExcelObject = $this->get('phpexcel')->createPHPExcelObject();
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

        // Création du titre
        $phpExcelObject->setActiveSheetIndex(0)->setCellValue('A1', $typeElection->getLibelle() . ' - Statistiques de participation (détail éducation prioritaire) par ' . $niveau);

        // Récapitulatif de la recherche
        // Campagne
        $phpExcelObject->setActiveSheetIndex(0)->setCellValue('A3', 'Campagne');
        $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B3', $campagne->getAnneeDebut() . '-' . $campagne->getAnneeFin());

        // Type d'établissement
        $phpExcelObject->setActiveSheetIndex(0)->setCellValue('A4', 'Type d\'établissement');
        $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B4', null != $typeEtab ? $typeEtab->getLibelle() : 'tous');

        $ligneEnteteTableau = 6;
        // Degré
        if ($typeEtab != null) {
            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('A5', 'Degré');
            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B5', $typeEtab->getDegre());
            $phpExcelObject->setActiveSheetIndex(0)->getStyle('A5')->applyFromArray($styleArray);
            $phpExcelObject->setActiveSheetIndex(0)->getStyle('B5')->getAlignment()->setHorizontal('left');
            $ligneEnteteTableau = 7;
        }

        // En-tête du tableau
        $phpExcelObject->setActiveSheetIndex(0)->setCellValue('A' . $ligneEnteteTableau, $niveau == 'academie' ? 'Académie' : 'Département');

        $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . $ligneEnteteTableau, 'Prioritaire');
        $phpExcelObject->setActiveSheetIndex(0)->setCellValue('C' . $ligneEnteteTableau, 'Inscrits');
        $phpExcelObject->setActiveSheetIndex(0)->setCellValue('D' . $ligneEnteteTableau, 'Votants');
        $phpExcelObject->setActiveSheetIndex(0)->setCellValue('E' . $ligneEnteteTableau, 'Exprimés');
        $phpExcelObject->setActiveSheetIndex(0)->setCellValue('F' . $ligneEnteteTableau, "Votants\n/Inscrits");
        $phpExcelObject->setActiveSheetIndex(0)->setCellValue('G' . $ligneEnteteTableau, "Rappel\n" . $campagnePrec->getAnneeDebut() . "-" . $campagnePrec->getAnneeFin());
        $phpExcelObject->setActiveSheetIndex(0)->setCellValue('H' . $ligneEnteteTableau, 'Variation');

        // On boucle sur les entités
        $ligne = $ligneEnteteTableau + 1;
        foreach ($listEtabConso as $zone => $etabConso) {

            $length = count($etabConso);
            $phpExcelObject->setActiveSheetIndex(0)->mergeCells('A' . $ligne . ':A' . ($ligne + $length - 1));
            $phpExcelObject->setActiveSheetIndex(0)->setCellValue('A' . $ligne, $zone);

            foreach ($etabConso as $prioritaire => $data) {
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('B' . $ligne, $prioritaire);
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('C' . $ligne, $data['inscrits']);
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('D' . $ligne, $data['votants']);
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('E' . $ligne, $data['exprimes']);
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue('F' . $ligne, $data['p']);
                if(isset($params['acadHideRappelAnterieur']) && isset($params['acadHideRappelAnterieur'][$zone]) && $params['acadHideRappelAnterieur'][$zone] == true) {
                    $phpExcelObject->setActiveSheetIndex(0)->setCellValue('G' . $ligne, "-");
                    $phpExcelObject->setActiveSheetIndex(0)->setCellValue('H' . $ligne, "-");
                } else {
                    $phpExcelObject->setActiveSheetIndex(0)->setCellValue('G' . $ligne, $data['rappel']);
                    $phpExcelObject->setActiveSheetIndex(0)->setCellValue('H' . $ligne, $data['variation']);
                }
                $ligne ++;
            }
        }

        for ($i = $ligneEnteteTableau + 1; $i <= $ligne; $i ++) {
            $phpExcelObject->setActiveSheetIndex(0)
                ->getStyle('B' . $i . ':H' . $i)
                ->getAlignment()
                ->setHorizontal('left');
        }

        // Activer la 1ère feuille
        $phpExcelObject->setActiveSheetIndex(0);

        // Mise en forme de la feuille
        $sheet->getColumnDimension('A')->setWidth(35);
        $sheet->getColumnDimension('B')->setWidth(35);

        $phpExcelObject->setActiveSheetIndex(0)
            ->getStyle('A1')
            ->applyFromArray($styleArray);
        $phpExcelObject->setActiveSheetIndex(0)
            ->getStyle('A1')
            ->applyFromArray($styleArrayTitre);
        $phpExcelObject->setActiveSheetIndex(0)
            ->getStyle('A3')
            ->applyFromArray($styleArray);
        $phpExcelObject->setActiveSheetIndex(0)
            ->getStyle('A4')
            ->applyFromArray($styleArray);
        $phpExcelObject->setActiveSheetIndex(0)
            ->getStyle('A' . $ligneEnteteTableau. ':H' . $ligneEnteteTableau)
            ->applyFromArray($styleArray);
        $phpExcelObject->setActiveSheetIndex(0)
            ->getStyle('B' . $ligneEnteteTableau. ':H' . $ligneEnteteTableau)
            ->getAlignment()
            ->setHorizontal('center');
        $phpExcelObject->setActiveSheetIndex(0)
            ->getStyle('B' . $ligneEnteteTableau. ':H' . $ligneEnteteTableau)
            ->getAlignment()
            ->setWrapText(true);

        // Création du writer
        $writer = $this->get('phpexcel')->createWriter($phpExcelObject, 'Excel5');

        // Création du nom du fichier
        $fileName = 'Statistiques_Education_Prioritaire_Elections_' . $codeUrlTypeElect . '_par_' . $niveau . '_' . $campagne->getAnneeDebut() . '-' . $campagne->getAnneeFin();

        // Créer la réponse
        $response = $this->get('phpexcel')->createStreamedResponse($writer);
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment;filename=' . $fileName . '.xls');
        return $response;
    }

    /**
     * Reforme territoriale: permet de fusionner les données de deux academies qui fusionnent
     * @param $data1
     * @param $data2
     */
    public function mergeStatParticipationEduPrio($data1, $data2, $libelle) {
        $mergeResult = array(
            "REP" => array(
                'inscrits'=> 0,
                'votants'=> 0,
                'exprimes'=> 0,
                'p'=> 0,
                'rappel'=> 0,
                'variation'=> 0,
            ),
            "REP PLUS" => array(
                'inscrits'=> 0,
                'votants'=> 0,
                'exprimes'=> 0,
                'p'=> 0,
                'rappel'=> 0,
                'variation'=> 0,
            ),
            "SANS OBJET" => array(
                'inscrits'=> 0,
                'votants'=> 0,
                'exprimes'=> 0,
                'p'=> 0,
                'rappel'=> 0,
                'variation'=> 0,
            ),
            "Total " . $libelle => array(
                'inscrits'=> 0,
                'votants'=> 0,
                'exprimes'=> 0,
                'p'=> 0,
                'rappel'=> 0,
                'variation'=> 0,
            )
        );

        foreach ($data1 as $i => $item) {
            if($i == "REP" || $i == "REP PLUS" || $i == "SANS OBJET") {
                $mergeResult[$i]['inscrits'] += $item['inscrits'];
                $mergeResult[$i]['votants'] += $item['votants'];
                $mergeResult[$i]['exprimes'] += $item['exprimes'];
            } else {
                $mergeResult['Total'.' '. $libelle]['inscrits'] += $item['inscrits'];
                $mergeResult['Total'.' '. $libelle]['votants'] += $item['votants'];
                $mergeResult['Total'.' '. $libelle]['exprimes'] += $item['exprimes'];
            }
        }
        foreach ($data2 as $j => $item2) {
            if($j == "REP" || $j == "REP PLUS" || $j == "SANS OBJET") {
                $mergeResult[$j]['inscrits'] += $item2['inscrits'];
                $mergeResult[$j]['votants'] += $item2['votants'];
                $mergeResult[$j]['exprimes'] += $item2['exprimes'];
            } else {
                $mergeResult['Total'.' '. $libelle]['inscrits'] += $item2['inscrits'];
                $mergeResult['Total'.' '. $libelle]['votants'] += $item2['votants'];
                $mergeResult['Total'.' '. $libelle]['exprimes'] += $item2['exprimes'];
            }
        }

        //Calcul des Pourcentages et variation
        foreach ($mergeResult as $type => $data) {
            $mergeResult[$type]['inscrits'] = $mergeResult[$type]['inscrits'] == 0 ? "" : $mergeResult[$type]['inscrits'];
            $mergeResult[$type]['votants'] = $mergeResult[$type]['votants'] == 0 ? "" : $mergeResult[$type]['votants'];
            $mergeResult[$type]['exprimes'] = $mergeResult[$type]['exprimes'] == 0 ? "" : $mergeResult[$type]['exprimes'];

            if($type == 'Total'.' '. $libelle || $mergeResult[$type]['inscrits'] == 0) {
                $mergeResult[$type]['p'] = "";
            } else {
                $mergeResult[$type]['p'] = round($mergeResult[$type]['votants'] / $mergeResult[$type]['inscrits'] * 100, 2) . " %";
            }
            $mergeResult[$type]['rappel'] = $mergeResult[$type]['rappel']/2 . " %";
            $mergeResult[$type]['variation'] = $mergeResult[$type]['p'] - $mergeResult[$type]['rappel'];
        }

        return $mergeResult;
    }
}