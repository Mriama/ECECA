<?php

namespace App\Form;

use App\Entity\RefUser;
use App\Entity\RefProfil;
use App\Entity\RefCommune;
use App\Entity\RefAcademie;
use App\Entity\RefDepartement;
use App\Entity\RefEtablissement;
use Doctrine\ORM\EntityRepository;
use App\Entity\RefTypeEtablissement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class ZoneEtabType extends AbstractType {

    protected $academies;
    protected $departements;
    protected $communes;
    protected $etablissementsUser;
    protected $isLimitedToEtabs; // N'afficher que la liste des établissements du périmètre utilisateur
    protected $degresUser; // N'afficher que les types d'établissement correspondant au degrés de l'utilisateur
    // protected $idTypeEtab; /* N'afficher que les "colleges", "lycees", "LP", "EREA" pour la statistique générale appliquée à tous les types d'élection */

    public function init(RefUser $user, $academies = null) {

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
        if(isset($options["user"])) {
            if(!isset($options["academies"])) {
                $options["academies"] = null;
            }
            $this->init($options["user"], $options["academies"]);
        }
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
            'class' => RefAcademie::class,
            'query_builder' => function(EntityRepository $er) use ($codeAcas) {
                $qb = $er->createQueryBuilder('aca');
                if (!empty($codeAcas)) {
                    $qb->add('where', $qb->expr()->in('aca.code', '?1'));
                    $qb->orderBy('aca.libelle', 'ASC');
                    $qb->setParameter(1, $codeAcas);
                }

                return $qb;
            },
            'choice_label' => 'libelle',
            'placeholder' => 'Toutes'
        );

        // Paramètre par défaut pour le département
        $departementParams = array(
            'label' => 'Département',
            'multiple' => false,
            'class' => RefDepartement::class,
            'query_builder' => function(EntityRepository $er) use ($codeAcas, $numDepts) {
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
            'choice_label' => 'libelle',
            'required' => false,
            'placeholder' => 'Tous');
        // Paramètre par défaut pour la commune
        $isLimited = $this->isLimitedToEtabs;
        $communeParams = array(
            'label' => 'Commune',
            'multiple' => false,
            'class' => RefCommune::class,
            'query_builder' => function(EntityRepository $er) use ($codeAcas, $numDepts, $isLimited) {
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

                $qb->getQuery()->enableResultCache(true);
                //$qb->orderBy('comm.libelle', 'ASC');

                return $qb;
            },
            'required' => false,
            'choice_label' => 'libelle',
            'placeholder' => 'Toutes');

        // Paramètre par défaut pour l'établissement
        $etablissementParams = array(
            'label' => 'Liste d\'établissements',
            'multiple' => false,
            'class' => RefEtablissement::class,
            'query_builder' => function(EntityRepository $er) use ($codeAcas, $numDepts, $isLimited) {
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
            'choice_label' => 'libelle',
            'placeholder' => 'Tous');

        // Paramètre par défaut pour le type d'établissement
        $typeEtablissementParams = array(
            'label' => 'Type d\'établissement',
            'multiple' => false,
            'class' => RefTypeEtablissement::class,
            'query_builder' => function(EntityRepository $er) use ($degres) {
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
            'choice_label' => 'code',
            'placeholder' => 'Tous');

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

        $builder->add('academie', EntityType::class, $academieParams) //entity
        ->add('departement', EntityType::class, $departementParams) //entity
        ->add('typeEtablissement', EntityType::class, $typeEtablissementParams)
            ->add('commune', EntityType::class, $communeParams) //entity
            ->add('etablissement', EntityType::class, $etablissementParams) //entity
            ->add('choix_etab', CheckboxType::class, array('required' => false, 'label' => $labelRechParEtab));
    }

    public function getBlockPrefix() { // Attention : Si changement ici ne pas oublier de modifier dans eple.js et eple.css
        return 'zoneetabtype';
    }

}
