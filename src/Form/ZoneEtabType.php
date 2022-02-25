<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use App\Entity\RefProfil;
use App\Entity\RefTypeEtablissement;

class ZoneEtabType extends AbstractType {

    protected $academies;
    protected $user;
    protected $departements;
    protected $communes;
    protected $etablissementsUser;
    protected $isLimitedToEtabs; // N'afficher que la liste des établissements du périmètre utilisateur
    protected $degresUser; // N'afficher que les types d'établissement correspondant au degrés de l'utilisateur
    // protected $idTypeEtab; /* N'afficher que les "colleges", "lycees", "LP", "EREA" pour la statistique générale appliquée à tous les types d'élection */

    public function __construct($academies = null) {

        $profilsLimitEtab = array(RefProfil::CODE_PROFIL_IEN, RefProfil::CODE_PROFIL_CE, RefProfil::CODE_PROFIL_DE);

        $this->academies = array();
        if ($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_RECT) {
            $this->academies[] = $user->getIdZone();
        } else if($user->getProfil()->getCode() == RefProfil::CODE_PROFIL_DGESCO && $academies != null) {
            $this->academies = $academies;
        } else{
            foreach ($user->getPerimetre()->getAcademies() as $academie) {
                $this->academies[] = $academie->getCode();
            }
        }


        $this->departements = array();
        foreach ($user->getPerimetre()->getDepartements() as $departement) {
            $this->departements[] = $departement->getNumero();
        }

        $this->etablissementsUser = $user->getPerimetre()->getEtablissements();
        $this->isLimitedToEtabs = in_array($user->getProfil()->getCode(), $profilsLimitEtab);
        $this->degresUser = $user->getPerimetre()->getDegres();
        // $this->idTypeEtab = array(RefTypeEtablissement::ID_TYP_COLLEGE, RefTypeEtablissement::ID_TYP_LYCEE, RefTypeEtablissement::ID_TYP_LYC_PRO, RefTypeEtablissement::ID_TYP_EREA_ERPD);
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {

        $codeAcas = $this->academies;
        $numDepts = $this->departements;
        // $limiteTypeEtab = $this->idTypeEtab;


        $degres = ($this->degresUser != null) ? $this->degresUser : null;
        $uais = array();
        if (null != $this->etablissementsUser) {
            foreach ($this->etablissementsUser as $etablissement) {
                array_push($uais, $etablissement->getUai());
            }
        }
        $uais = ($uais != null) ? implode(",", $uais) : null;


        // Paramètre par défaut pour l'académie
        $academieParams = array(
            'label' => 'Académie',
            'multiple' => false,
            'required' => false,
            'class' => 'EPLEElectionBundle:RefAcademie',
            'query_builder' => function(\Doctrine\ORM\EntityRepository $er) use ($codeAcas) {
                $qb = $er->createQueryBuilder('aca');
                if (!empty($codeAcas)) {
                    $qb->add('where', $qb->expr()->in('aca.code', '?1'));
                    $qb->orderBy('aca.libelle', 'ASC');
                    $qb->setParameter(1, $codeAcas);
                }

                return $qb;
            },
            'property' => 'libelle',
            'empty_value' => 'Toutes'
        );
        $departementParams = array(
            'label' => 'Département',
            'multiple' => false,
            'class' => 'EPLEElectionBundle:RefDepartement',
            'query_builder' => function(\Doctrine\ORM\EntityRepository $er) use ($codeAcas, $numDepts) {
                $qb = $er->createQueryBuilder('dept');
                if (!empty($numDepts)) {
                    $qb->add('where', $qb->expr()->in('dept.numero', '?1'));
                    $qb->setParameter(1, $numDepts);
                } else if (!empty($codeAcas)) {
                    $qb->join('dept.academie', 'aca');
                    $qb->add('where', $qb->expr()->in('aca.code', '?1'));
                    $qb->setParameter(1, $codeAcas);
                }
                $qb->add('orderBy', 'dept.libelle ASC');
                return $qb;
            },
            'property' => 'libelle',
            'required' => false,
            'empty_value' => 'Tous');
        // Paramètre par défaut pour la commune
        $isLimited = $this->isLimitedToEtabs;
        $communeParams = array(
            'label' => 'Commune',
            'multiple' => false,
            'class' => 'EPLEElectionBundle:RefCommune',
            'query_builder' => function(\Doctrine\ORM\EntityRepository $er) use ($codeAcas, $numDepts, $isLimited) {
                $qb = $er->createQueryBuilder('comm');
                if (!empty($numDepts)) {
                    $qb->join('comm.departement', 'dept');
                    $qb->add('where', $qb->expr()->in('dept.numero', '?1'));
                    $qb->setParameter(1, $numDepts);
                } else if (!empty($codeAcas) && $isLimited) {
                    $qb->join('comm.departement', 'dept');
                    $qb->join('dept.academie', 'aca');
                    $qb->add('where', $qb->expr()->in('aca.code', '?1'));
                    $qb->setParameter(1, $codeAcas);
                } else {
                    // ECT : rustine pour DGESCO pour éviter de charger toutes les communes au depart
                    $qb->add('where', $qb->expr()->eq('comm.id', '?1'));
                    $qb->setParameter(1, 0);
                }
                //Ne renvoie que les communes qui ont un établissement

                $qb->getQuery()->useResultCache(true);
                //$qb->orderBy('comm.libelle', 'ASC');

                return $qb;
            },
            'required' => false,
            'property' => 'libelle',
            'empty_value' => 'Toutes');

        // Paramètre par défaut pour l'établissement
        $etablissementParams = array(
            'label' => 'Liste d\'établissements',
            'multiple' => false,
            'class' => 'EPLEElectionBundle:RefEtablissement',
            'query_builder' => function(\Doctrine\ORM\EntityRepository $er) use ($codeAcas, $numDepts, $isLimited) {
                $qb = $er->createQueryBuilder('etab');
                if (!empty($numDepts)) {
                    $qb->join('etab.commune', 'comm');
                    $qb->join('comm.departement', 'dept');
                    $qb->add('where', $qb->expr()->in('dept.numero', '?1'));
                    $qb->setParameter(1, $numDepts);
                } else if (!empty($codeAcas) && $isLimited) {
                    $qb->join('etab.commune', 'comm');
                    $qb->join('comm.departement', 'dept');
                    $qb->join('dept.academie', 'aca');
                    $qb->add('where', $qb->expr()->in('aca.code', '?1'));
                    $qb->setParameter(1, $codeAcas);
                } else {
                    // ECT : rustine pour DGESCO pour éviter de charger toutes les communes au depart
                    $qb->add('where', $qb->expr()->eq('etab.uai', '?1'));
                    $qb->setParameter(1, 0);
                }
                //$qb->orderBy('etab.uai', 'ASC');
                //$qb->useResultCache(true);
                return $qb;
            },
            'required' => false,
            'property' => 'libelle',
            'empty_value' => 'Tous');

        // Paramètre par défaut pour le type d'établissement
        $typeEtablissementParams = array(
            'label' => 'Type d\'établissement',
            'multiple' => false,
            'class' => 'EPLEElectionBundle:RefTypeEtablissement',
            'query_builder' => function(\Doctrine\ORM\EntityRepository $er) use ($degres) {
                $qb = $er->createQueryBuilder('t');
                if (!empty($degres)) {
                    $qb->where('t.degre in (:degres)')
                        // BBL defect HPQC 243, 217, 228
                        // ->andWhere('t.id in (:idTypeEtab)')
                        // ->setParameter('idTypeEtab', $limiteTypeEtab)
                        ->setParameter('degres', $degres);
                }
                $qb->orderBy('t.ordre', 'ASC'); // EVOL 013E RG_CONSULT_5_1 RG_CONSULT_5_2
                return $qb;
            },
            'required' => false,
            'property' => 'code',
            'empty_value' => 'Tous');

        //Modification du select en fonction des propriétés de l'utilisateur
//        if ($codeAca != null) {
//            // L'utilisateur est affecté à une académie
//            $academieParams['empty_value'] = false;
//            $academieParams['disabled'] = true;
//        }
//        if ($numDept != null) {
//            // L'utilisateur est affecté à un département
//            $departementParams['empty_value'] = false;
//            $departementParams['disabled'] = true;
//        }
//        if ($idComm != null) {
//            // L'utilisateur est affecté à une commune
//            $communeParams['empty_value'] = false;
//            $communeParams['disabled'] = true;
//        }
        // Limiter à la liste des établissements
        // TODO changer pour restreindre la liste de base

//        if ($this->isLimitedToEtabs) {
//            $academieParams['disabled'] = true;
//            $academieParams['empty_value'] = '';
//            $departementParams['disabled'] = true;
//            $departementParams['empty_value'] = '';
//            $communeParams['disabled'] = true;
//            $communeParams['empty_value'] = '';
//            /*
//              $etablissementParams['query_builder'] =
//              function(\Doctrine\ORM\EntityRepository $er) use ($uais) {
//              $qb = $er->createQueryBuilder('etab');
//              if (null != $uais) {
//              $qb->where('etab.uai in (:uais)')
//              ->setParameter('uais', $uais);
//              }
//              $qb->orderBy('etab.uai', 'ASC');
//              return $qb;
//              }; */
//        }

        $degresLabel = ($degres != null) ? implode(",", $degres) : null;
        $labelRechParEtab = 'Recherche ';
        if (!empty($degresLabel)) {
            if (strpos($degresLabel, '1') !== false && strpos($degresLabel, '2') !== false) {
                $labelRechParEtab = $labelRechParEtab . 'par école, par établissement';
            } else {
                if (strpos($degresLabel, '1') !== false) {
                    $labelRechParEtab = $labelRechParEtab . 'par école';
                }
                if (strpos($degresLabel, '2') !== false) {
                    $labelRechParEtab = $labelRechParEtab . 'par établissement';
                }
            }
        } else {
            $labelRechParEtab = $labelRechParEtab . 'par école, par établissement';
        }

        $builder->add('academie', 'entity', $academieParams) //entity
        ->add('departement', 'entity', $departementParams) //entity
        ->add('typeEtablissement', 'entity', $typeEtablissementParams)
            ->add('commune', 'entity', $communeParams) //entity
            ->add('etablissement', 'entity', $etablissementParams) //entity
            ->add('choix_etab', 'checkbox', array('required' => false, 'label' => $labelRechParEtab));
    }

    public function getName() { // Attention : Si changement ici ne pas oublier de modifier dans eple.js et eple.css
        return 'zoneetabtype';
    }

}
