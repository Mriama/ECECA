<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use App\Entity\RefProfil;
use App\Entity\RefTypeElection;

class TdbEtabType extends AbstractType {

    protected $academies;
    protected $departements;
    protected $listeSousTypeElection;
    protected $degresUser;
    protected $user;

    public function __construct(TokenStorageInterface $tokenStorage, $listeSte = null) {

        $this->user = $tokenStorage->getToken()->getUser();
		$this->user = $user;
        $this->academies = array();
        foreach ($user->getPerimetre()->getAcademies() as $academie) {
            $this->academies[] = $academie->getCode();
        }

        $this->departements = array();
        foreach ($user->getPerimetre()->getDepartements() as $departement) {
            $this->departements[] = $departement->getNumero();
        }
        $this->degresUser = $user->getPerimetre()->getDegres();
        $this->listeSousTypeElection = array();
        
        $this->listeSousTypeElection= $listeSte;
        
        
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {

        $codeAcas = $this->academies;
        $numDepts = $this->departements;
        $listeSousTypeElection = $this->listeSousTypeElection;
        $degres = ($this->degresUser != null) ? $this->degresUser : null;
        $listNature = array('1ORD', 'APPL', 'SPEC');
        
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

        // Paramètre par défaut pour le département
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
                                        return $qb;
                                    },
            'property' => 'libelle',
            'required' => false,
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
                                                        ->setParameter('degres', $degres);
                                            }
                                            $qb->orderBy('t.ordre', 'ASC');
                                            return $qb;
                                        },
            'required' => false,
            'property' => 'code',
            'empty_value' => 'Tous'
                       );

        
        // Paramètre par défaut pour la nature d'établissement
       /*$natureEtablissementParams = array(
            'label' => 'Nature d\'établissement',
            'multiple' => false,
            'class' => 'EPLEElectionBundle:RefZoneNature',
            'query_builder' => function(\Doctrine\ORM\EntityRepository $er) use ($listNature) {
                $qb = $er->createQueryBuilder('n');
               if (!empty($listNature)) {
                    $qb->where('n.type_nature in ('.$listNature.')');
                }
                return $qb;
            },
            'required' => false,
            'property' => 'type_nature',
            'empty_value' => 'Tous');*/

        $builder->add('academie', 'entity', $academieParams) //entity
                ->add('departement', 'entity', $departementParams) //entity
                ->add('typeEtablissement', 'entity', $typeEtablissementParams) //entity
                //->add('natureEtablissement', 'entity', $natureEtablissementParams)
                ->add('natureEtablissement', 'choice', array(
                    'label' => 'Nature d\'établissement',
                    'choices'   => array(
                        '1ORD'   => '1ORD',
                        'APPL' => 'APPL',
                        'SPEC' => 'SPEC',
                ),
                    'required' => false,
                    'empty_value' => 'Toutes',
                    'multiple'  => false,)) //entity
                //->add('typeElection', 'entity', $typeElectionParams) //entity
                ->add('typeElection', 'choice', array(
                    'label' => 'Type d\'élection',
                    'choices'   => array(
                        'pe'   => 'PE',
                        'rp' => 'RP',
                    ),
                    'required' => false,
                    // BBL defect HPQC 226
                    'empty_value' => 'Tous',
                    'multiple'  => false,))
                //->add('sousTypeElection', 'entity', $sousTypeElectionParams);//entity
                ->add('sousTypeElection', 'choice', array(
                    'label' => 'Sous-type d\'élection',
                    'choices'   => $listeSousTypeElection,
                    'required' => false,
                    'empty_value' => 'Tous',
                    'multiple'  => false,));
    }
    
    public function getName() { // Attention : Si changement ici ne pas oublier de modifier dans eple.js et eple.css
        return 'tdbEtabType';
    }

}
