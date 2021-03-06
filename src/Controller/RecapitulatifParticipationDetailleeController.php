<?php
namespace App\Controller;

use App\Entity\RefProfil;
use App\Entity\EleCampagne;
use App\Entity\RefAcademie;
use App\Entity\RefTypeElection;
use App\Entity\EleEtablissement;
use App\Entity\RefTypeEtablissement;
use Doctrine\Persistence\ManagerRegistry;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\RecapitulatifParticipationEtabType;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Model\RecapitulatifParticipationEtabTypeModel;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class RecapitulatifParticipationDetailleeController extends AbstractController
{
    private $request;
    private $doctrine;

    public function __construct(RequestStack $request, ManagerRegistry $doctrine) {
        $this->request = $request->getCurrentRequest();
        $this->doctrine = $doctrine;
    }

    public function indexAction($codeUrlTypeElect)
    {
        // reset session
        $this->request->getSession()->set('recap_part_detail_campagne_annee_deb', null);
        $this->request->getSession()->set('recap_part_detail_niveau', 'departement');
        $this->request->getSession()->set('recap_part_detail_type_etab', null);

        if (false === $this->isGranted('ROLE_STATS_EDU_PRIO')) {
            throw new AccessDeniedException();
        }

        $user = $this->getUser();
        $params = $this->getParametresStatistiques($codeUrlTypeElect, $user);

        return $this->render('recapitulatifParticipation/indexRecapitulatifParticipationDetaillee.html.twig', $params);
    }

    private function getParametresStatistiques($codeUrlTypeElect, $user)
    {
        $em = $this->doctrine->getManager();
        $params = array();
        $campagneAnneeDeb = null;
        $niveau = 'departement';
        $typeEtab = null;
        $listPrio = array();
        $codeUrlTypeEtab = 'tous';

        /**
         * **** R??cup??ration du type d'election *****
         */
        $idTypeElect = RefTypeElection::getIdRefTypeElectionByCodeUrl($codeUrlTypeElect);
        $typeElection = $idTypeElect != null ? $em->getRepository(RefTypeElection::class)->find($idTypeElect) : null;
        if (empty($typeElection)) {
            throw $this->createNotFoundException('Le type d\'??lection n\'a pas ??t?? trouv??.');
        }

        $params['typeElect'] = $typeElection;

        $cze_current = new RecapitulatifParticipationEtabTypeModel($typeElection);
        // Statistiques par type d'??ducation prioritaire
        $form = $this->createForm(RecapitulatifParticipationEtabType::class, $cze_current, ['user'=>$user]);

        if ($this->request->getMethod() == 'POST') {
            $form->handleRequest($this->request);
            if ($form->isSubmitted() && $form->isValid()) {
                $options_recherche = $form->getData();

                $this->request->getSession()->remove('recap_part_detail_campagne_annee_deb');
                $this->request->getSession()->remove('recap_part_detail_niveau');
                $this->request->getSession()->remove('recap_part_detail_type_etab');

                $campagneAnneeDeb = $options_recherche->getCampagne()->getAnneeDebut();
                $niveau = $options_recherche->getNiveau();
                if ($options_recherche->getTypeEtablissement() != null) {
                    $typeEtab = $options_recherche->getTypeEtablissement();
                } else {
                    $typeEtab = null;
                }

                // Mise en place des variables en session pour l'export excel
                $this->request->getSession()->set('recap_part_detail_campagne_annee_deb', $campagneAnneeDeb);
                $this->request->getSession()->set('recap_part_detail_niveau', $niveau);
                $this->request->getSession()->set('recap_part_detail_type_etab', null != $typeEtab ? $typeEtab->getId() : null);
            }
        } else {
            $campagneAnneeDeb = $this->request->getSession()->get('recap_part_detail_campagne_annee_deb');
            $niveau = $this->request->getSession()->get('recap_part_detail_niveau');
            if($this->request->getSession()->get('recap_part_detail_type_etab') != null)
                $typeEtab = $em->getRepository(RefTypeEtablissement::class)->find($this->request->getSession()->get('recap_part_detail_type_etab'));
            else
                $typeEtab = null;
        }

        /**
         * **** R??cup??ration de la derni??re campagne et de la campagne anterieure *****
         */
        if ($campagneAnneeDeb == null) {
            $campagne = $em->getRepository(EleCampagne::class)->getLastCampagne($typeElection);
        } else {
            $listeCampagne = $em->getRepository(EleCampagne::class)->getCampagneParTypeElectionAnneeDebut($typeElection, $campagneAnneeDeb);
            $campagne = (! empty($listeCampagne)) ? $listeCampagne[0] : null;
        }

        if (empty($campagne)) {
            throw $this->createNotFoundException('La campagne pour ce type d\'??lection n\'a pas ??t?? trouv??.');
        }

        $params['campagne'] = $campagne;

        $listeCampagnePrec = $em->getRepository(EleCampagne::class)->getCampagneParTypeElectionAnneeDebut($typeElection, $campagne->getAnneeDebut() - 1);
        $campagnePrec = (! empty($listeCampagnePrec)) ? $listeCampagnePrec[0] : null;
        $params['campagnePrec'] = $campagnePrec;

        // R??cuperer la liste des informations ?? afficher 
        // mantis 225881
        $listParticipationPrio = $em->getRepository(EleEtablissement::class)->findParticipationDetailleeParTypeZoneEtTypePrioritaire($campagne->getId(), $campagnePrec->getId(), $niveau, (null != $typeEtab ? $typeEtab->getId() : null), $user->getPerimetre(), $user);

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
            //Si un type prioritaire n'existe pas il n'est pas retourn?? par le tableau.

            $libelle = null;
            $code = null;
            foreach ($listParticipationPrio as $line) {
                $rappel = 0;
                if ($libelle != $line['libelle']) {
                    // Nouveau d??partement -> Nouveau groupe
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
                        $listEtabConso[$libelle][$code]['rappel'] = round(floatval($line['p']), 2);
                        $listEtabConso[$libelle][$code]['variation'] = '';
                    }else{
                        $listEtabConso[$libelle][$code]['inscrits'] = $line['sumInscrits'];
                        $listEtabConso[$libelle][$code]['votants'] = $line['sumVotants'];
                        $listEtabConso[$libelle][$code]['exprimes'] = $line['sumExprimes'];
                        $listEtabConso[$libelle][$code]['p'] = round(floatval($line['p']), 2);
                    }
                }else{
                    $listEtabConso[$line['libelle']][$line['code']]['rappel'] = round(floatval($line['p']), 2);
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

                    $totalInscrits += intval($prio['inscrits']);
                    $totalVotants += intval($prio['votants']);
                    $totalExprimes += intval($prio['exprimes']);
                }
                $listEtabConso[$libelle]['Total ' . $libelle]['inscrits'] = $totalInscrits;
                $listEtabConso[$libelle]['Total ' . $libelle]['votants'] = $totalVotants;
                $listEtabConso[$libelle]['Total ' . $libelle]['exprimes'] = $totalExprimes;
                $listEtabConso[$libelle]['Total ' . $libelle]['p'] = '';
                $listEtabConso[$libelle]['Total ' . $libelle]['rappel'] = '';
                $listEtabConso[$libelle]['Total ' . $libelle]['variation'] = '';
            }
        }

        //E18 Reforme territoriale : Gestion des fusions d'academies : merge des donn??es
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
                $acadObj = $em->getRepository(RefAcademie::class)->findOneBy(array("libelle" => $academie));
                if ($acadObj->getDateDesactivation() <= $campagneDebut && $acadObj->getAcademieFusion() != null) {
                    $acadFusion = $em->getRepository(RefAcademie::class)->find($acadObj->getAcademieFusion()->getCode());
                    if (isset($newListConso[$acadFusion->getLibelle()]) && !empty($newListConso[$acadFusion->getLibelle()])) {
                        //Merge des donn??es rattach??es ?? la meme acad??mie de fusion
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

        foreach ($listEtabConso as $key => $etabConso) {
            foreach ($etabConso as $key2 => $prio) {
                if (is_float($prio['p']))
                    $prio['p'] .= ' %';
                if (is_float($prio['rappel']))
                    $prio['rappel'] .= ' %';
                $etabConso[$key2] = $prio;
            }
            $listEtabConso[$key] = $etabConso;
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
     * @param $codeUrlTypeElect
     */
    public function exportXLSAction($codeUrlTypeElect)
    {
        $user = $this->getUser();
        $params = $this->getParametresStatistiques($codeUrlTypeElect, $user);

        // R??cup??ration des param??tres
        $typeElection = $params['typeElect'];
        $niveau = $params['niveau'];
        $campagne = $params['campagne'];
        $campagnePrec = $params['campagnePrec'];
        $typeEtab = $params['typeEtab'];
        $listEtabConso = $params['listEtabConso'];

        // G??n??ration du fichier Excel
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

        // Cr??ation du titre
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('A1', $typeElection->getLibelle() . ' - Statistiques de participation (d??tail ??ducation prioritaire) par ' . $niveau);

        // R??capitulatif de la recherche
        // Campagne
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('A3', 'Campagne');
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('B3', $campagne->getAnneeDebut() . '-' . $campagne->getAnneeFin());

        // Type d'??tablissement
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('A4', 'Type d\'??tablissement');
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('B4', null != $typeEtab ? $typeEtab->getLibelle() : 'tous');

        $ligneEnteteTableau = 6;
        // Degr??
        if ($typeEtab != null) {
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A5', 'Degr??');
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('B5', $typeEtab->getDegre());
            $spreadsheet->setActiveSheetIndex(0)->getStyle('A5')->applyFromArray($styleArray);
            $spreadsheet->setActiveSheetIndex(0)->getStyle('B5')->getAlignment()->setHorizontal('left');
            $ligneEnteteTableau = 7;
        }

        // En-t??te du tableau
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('A' . $ligneEnteteTableau, $niveau == 'academie' ? 'Acad??mie' : 'D??partement');

        $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . $ligneEnteteTableau, 'Prioritaire');
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('C' . $ligneEnteteTableau, 'Inscrits');
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('D' . $ligneEnteteTableau, 'Votants');
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('E' . $ligneEnteteTableau, 'Exprim??s');
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('F' . $ligneEnteteTableau, "Votants\n/Inscrits");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('G' . $ligneEnteteTableau, "Rappel\n" . $campagnePrec->getAnneeDebut() . "-" . $campagnePrec->getAnneeFin());
        $spreadsheet->setActiveSheetIndex(0)->setCellValue('H' . $ligneEnteteTableau, 'Variation');

        // On boucle sur les entit??s
        $ligne = $ligneEnteteTableau + 1;
        foreach ($listEtabConso as $zone => $etabConso) {

            $length = count($etabConso);
            $spreadsheet->setActiveSheetIndex(0)->mergeCells('A' . $ligne . ':A' . ($ligne + $length - 1));
            $spreadsheet->setActiveSheetIndex(0)->setCellValue('A' . $ligne, $zone);

            foreach ($etabConso as $prioritaire => $data) {
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('B' . $ligne, $prioritaire);
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('C' . $ligne, $data['inscrits']);
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('D' . $ligne, $data['votants']);
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('E' . $ligne, $data['exprimes']);
                $spreadsheet->setActiveSheetIndex(0)->setCellValue('F' . $ligne, $data['p']);
                if(isset($params['acadHideRappelAnterieur']) && isset($params['acadHideRappelAnterieur'][$zone]) && $params['acadHideRappelAnterieur'][$zone] == true) {
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('G' . $ligne, "-");
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('H' . $ligne, "-");
                } else {
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('G' . $ligne, $data['rappel']);
                    $spreadsheet->setActiveSheetIndex(0)->setCellValue('H' . $ligne, $data['variation']);
                }
                $ligne ++;
            }
        }

        for ($i = $ligneEnteteTableau + 1; $i <= $ligne; $i ++) {
            $spreadsheet->setActiveSheetIndex(0)
                ->getStyle('B' . $i . ':H' . $i)
                ->getAlignment()
                ->setHorizontal('left');
        }

        // Activer la 1??re feuille
        $spreadsheet->setActiveSheetIndex(0);

        // Mise en forme de la feuille
        $sheet->getColumnDimension('A')->setWidth(35);
        $sheet->getColumnDimension('B')->setWidth(35);

        $spreadsheet->setActiveSheetIndex(0)
            ->getStyle('A1')
            ->applyFromArray($styleArray);
        $spreadsheet->setActiveSheetIndex(0)
            ->getStyle('A1')
            ->applyFromArray($styleArrayTitre);
        $spreadsheet->setActiveSheetIndex(0)
            ->getStyle('A3')
            ->applyFromArray($styleArray);
        $spreadsheet->setActiveSheetIndex(0)
            ->getStyle('A4')
            ->applyFromArray($styleArray);
        $spreadsheet->setActiveSheetIndex(0)
            ->getStyle('A' . $ligneEnteteTableau. ':H' . $ligneEnteteTableau)
            ->applyFromArray($styleArray);
        $spreadsheet->setActiveSheetIndex(0)
            ->getStyle('B' . $ligneEnteteTableau. ':H' . $ligneEnteteTableau)
            ->getAlignment()
            ->setHorizontal('center');
        $spreadsheet->setActiveSheetIndex(0)
            ->getStyle('B' . $ligneEnteteTableau. ':H' . $ligneEnteteTableau)
            ->getAlignment()
            ->setWrapText(true);

        // Cr??ation du writer
        $writer = new Xlsx($spreadsheet, 'Excel5');
        // Cr??ation du nom du fichier
        $fileName = 'Statistiques_Education_Prioritaire_Elections_' . $codeUrlTypeElect . '_par_' . $niveau . '_' . $campagne->getAnneeDebut() . '-' . $campagne->getAnneeFin();

        // Cr??er la r??ponse
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
     * Reforme territoriale: permet de fusionner les donn??es de deux academies qui fusionnent
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
                $mergeResult[$i]['inscrits'] += intval($item['inscrits']);
                $mergeResult[$i]['votants'] += intval($item['votants']);
                $mergeResult[$i]['exprimes'] += intval($item['exprimes']);
            } else {
                $mergeResult['Total'.' '. $libelle]['inscrits'] += intval($item['inscrits']);
                $mergeResult['Total'.' '. $libelle]['votants'] += intval($item['votants']);
                $mergeResult['Total'.' '. $libelle]['exprimes'] += intval($item['exprimes']);
            }
        }
        foreach ($data2 as $j => $item2) {
            if($j == "REP" || $j == "REP PLUS" || $j == "SANS OBJET") {
                $mergeResult[$j]['inscrits'] += intval($item2['inscrits']);
                $mergeResult[$j]['votants'] += intval($item2['votants']);
                $mergeResult[$j]['exprimes'] += intval($item2['exprimes']);
            } else {
                $mergeResult['Total'.' '. $libelle]['inscrits'] += intval($item2['inscrits']);
                $mergeResult['Total'.' '. $libelle]['votants'] += intval($item2['votants']);
                $mergeResult['Total'.' '. $libelle]['exprimes'] += intval($item2['exprimes']);
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
                $mergeResult[$type]['p'] = round($mergeResult[$type]['votants'] / $mergeResult[$type]['inscrits'] * 100, 2);
            }
            $mergeResult[$type]['rappel'] = round($mergeResult[$type]['rappel']/2, 2);
            $mergeResult[$type]['variation'] = $mergeResult[$type]['p'] != "" ? floatval($mergeResult[$type]['p']) - $mergeResult[$type]['rappel'] : floatval(0) - $mergeResult[$type]['rappel'];
        }

        return $mergeResult;
    }
}