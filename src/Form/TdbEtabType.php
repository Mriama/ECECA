<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Security;

use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\RefProfil;
use App\Utils\RefUserPerimetre;
use App\Entity\RefTypeElection;
use Doctrine\ORM\EntityRepository;
use App\Entity\RefAcademie;
use App\Entity\RefDepartement;
use App\Entity\RefTypeEtablissement;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class TdbEtabType extends AbstractType {

    
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $user = $options['user'];
        $listeSte = $options['liste'];
        $academies = array();
        if(!empty($user->getPerimetre())){
            foreach ($user->getPerimetre()->getAcademies() as $academie) {
                $academies[] = $academie->getCode();
            }
        }
        $departements = array();
        if(!empty($user->getPerimetre())){
            foreach ($user->getPerimetre()->getDepartements() as $departement){
                $departements[] = $departement->getNumero();
            }
        }
        $degresUser = $user->getPerimetre() ? $user->getPerimetre()->getDegres() : null;
        $listeSousTypeElection = array();
        $this->listeSousTypeElection= $listeSte;
        $codeAcas = $academies;
        $numDepts = $departements;
        $listeSousTypeElection = $this->listeSousTypeElection;
        $degres = ($degresUser != null) ? $degresUser : null;
        $listNature = array('1ORD', 'APPL', 'SPEC');
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
            'empty_data' => 'Toutes'
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
                return $qb;
            },
            'choice_label' => 'libelle',
            'required' => false,
            'empty_data' => 'Tous'
        );
        // Paramètre par défaut pour le type d'établissement
        $typeEtablissementParams = array(
            'label' => 'Type d\'établissement',
            'multiple' => false,
            'class' => RefTypeEtablissement::class,
            'query_builder' => function(EntityRepository $er) use ($degres) {
                $qb = $er->createQueryBuilder('t');
                if (!empty($degres)) {
                    $qb->where('t.degre in (:degres)')
                            ->setParameter('degres', $degres);
                }
                $qb->orderBy('t.ordre', 'ASC');
                return $qb;
            },
            'required' => false,
            'choice_label' => 'code',
            'empty_data' => 'Tous'
            );

        $builder->add('academie', EntityType::class, $academieParams)
                ->add('departement', EntityType::class, $departementParams)
                ->add('typeEtablissement', EntityType::class, $typeEtablissementParams)
                ->add('natureEtablissement', ChoiceType::class, array(
                    'label' => 'Nature d\'établissement',
                    'choices'   => array(
                        '1ORD'   => '1ORD',
                        'APPL' => 'APPL',
                        'SPEC' => 'SPEC',
                    ),
                    'required' => false,
                    'empty_data' => 'Toutes',
                    'multiple'  => false,))
                ->add('typeElection', ChoiceType::class, array(
                    'label' => 'Type d\'élection',
                    'choices'   => array(
                        'PE'   => 'pe',
                        'RP' => 'rp',
                    ),
                    'required' => false,
                    'empty_data' => 'Tous',
                    'multiple'  => false,))
                ->add('sousTypeElection', ChoiceType::class, array(
                    'label' => 'Sous-type d\'élection',
                    'choices'   => $listeSousTypeElection,
                    'required' => false,
                    'empty_data' => 'Tous',
                    'multiple'  => false));
    }
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(array(
            'user',
            'liste',
        ));
    }

    public function getBlockPrefix() { // Attention : Si changement ici ne pas oublier de modifier dans eple.js et eple.css
        return 'tdbEtabType';
    }

}
