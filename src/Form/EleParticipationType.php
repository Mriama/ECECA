<?php

namespace App\Form;

use App\Entity\RefModaliteVote;
use App\Entity\RefTypeElection;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EleParticipationType extends AbstractType {

    public function __construct($class) {}

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $refTypeElection = $options["typeElect"];
        $builder->add('nbInscrits', IntegerType::class, array (
            'label'  => '* Nombre d\'inscrits',
            'required' => true,
            'trim' => true,
            'error_bubbling' => true,
            'attr' => array('min' =>0)
        ))
            ->add('nbVotants', IntegerType::class, array (
                'label'  => '* Nombre de votants',
                'required' => true,
                'trim' => true,
                'error_bubbling' => true,
                'attr' => array('min' =>0)
            ))
            ->add('nbNulsBlancs', IntegerType::class, array (
                'label'  => '* Nombre de bulletins nuls ou blancs',
                'required' => true,
                'trim' => true,
                'error_bubbling' => true,
                'attr' => array('min' =>0)
            ))
            ->add('nbExprimes', IntegerType::class, array (
                'label'  => '* Nombre de suffrages exprimés',
                'required' => false,
                'trim' => true,
                'error_bubbling' => true,
                'attr' => array('min' =>0)
            ))
            ->add('nbSiegesPourvoir', IntegerType::class, array (
                'label'  => '* Nombre de sièges à pourvoir',
                'required' => true,
                'trim' => true,
                'error_bubbling' => true,
                'attr' => array('min' =>0)
            ))
            ->add('nbSiegesPourvus', IntegerType::class, array (
                'label'  => 'Nombre de sièges pourvus',
                'required' => false,
                'trim' => true,
                'error_bubbling' => true,
                'attr' => array('min' =>0)
            ));
        if(($refTypeElection != null && $refTypeElection->getId() == RefTypeElection::ID_TYP_ELECT_PARENT)) {
            $builder->add('modaliteVote', EntityType::class, array(
                'label' => 'Modalité de vote',
                'multiple' => false,
                'class' => RefModaliteVote::class,
                'query_builder' => function (EntityRepository $er) {
                    $qb = $er->createQueryBuilder('rmv');
                    $qb->orderBy('rmv.id', 'ASC');
                    return $qb;
                },
                'required' => true,
                'choice_label' => 'libelle',
                'placeholder' => 'Votre sélection'));
        }
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\EleParticipation',
            'typeElect' => null
        ));
    }

    public function getBlockPrefix() {
        return 'EleParticipationType';
    }
}
