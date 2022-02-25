<?php

namespace App\Form;

use App\Entity\RefTypeElection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class EleParticipationType extends AbstractType {
    protected $class;

    public function __construct($class)
    {
        $this->refTypeElection = $class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder->add('nbInscrits', 'integer', array (
            'label'  => '* Nombre d\'inscrits',
            'required' => true,
            'trim' => true,
            'error_bubbling' => true,
            'attr' => array('min' =>0)
        ))
            ->add('nbVotants', 'integer', array (
                'label'  => '* Nombre de votants',
                'required' => true,
                'trim' => true,
                'error_bubbling' => true,
                'attr' => array('min' =>0)
            ))
            ->add('nbNulsBlancs', 'integer', array (
                'label'  => '* Nombre de bulletins nuls ou blancs',
                'required' => true,
                'trim' => true,
                'error_bubbling' => true,
                'attr' => array('min' =>0)
            ))
            ->add('nbExprimes', 'integer', array (
                'label'  => '* Nombre de suffrages exprimés',
                'required' => false,
                'trim' => true,
                'error_bubbling' => true,
                'attr' => array('min' =>0)
            ))
            ->add('nbSiegesPourvoir', 'integer', array (
                'label'  => '* Nombre de sièges à pourvoir',
                'required' => true,
                'trim' => true,
                'error_bubbling' => true,
                'attr' => array('min' =>0)
            ))
            ->add('nbSiegesPourvus', 'integer', array (
                'label'  => 'Nombre de sièges pourvus',
                'required' => false,
                'trim' => true,
                'error_bubbling' => true,
                'attr' => array('min' =>0)
            ));
        if(($this->refTypeElection != null && $this->refTypeElection->getId() == RefTypeElection::ID_TYP_ELECT_PARENT)) {
            $builder->add('modaliteVote', 'entity', array(
                'label' => 'Modalité de vote',
                'multiple' => false,
                'class' => 'EPLEElectionBundle:RefModaliteVote',
                'query_builder' => function (\Doctrine\ORM\EntityRepository $er) {
                    $qb = $er->createQueryBuilder('rmv');
                    $qb->orderBy('rmv.id', 'ASC');
                    return $qb;
                },
                'required' => true,
                'property' => 'libelle',
                'empty_value' => 'Votre sélection'));
        }
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\EleParticipation'
        ));
    }

    public function getName() {
        return 'EleParticipationType';
    }
}
