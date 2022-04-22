<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EleResultatType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder->add('nbCandidats', IntegerType::class, array (
            'label'  => '* Nombre de candidats',
            'required' => true,
            'trim' => true,
            'error_bubbling' => true,
            'attr' => array('min' =>0)
        ))
            ->add('nbVoix', IntegerType::class, array (
                'label'  => '* Nombre de voix obtenues',
                'required' => true,
                'trim' => true,
                'error_bubbling' => true,
                'attr' => array('min' =>0)
            ))
            ->add('nbSieges', IntegerType::class, array (
                'label'  => '* Nombre de sièges obtenus',
                'required' => true,
                'trim' => true,
                'error_bubbling' => true,
                'disabled' => false,
                'attr' => array('min' =>0)
            ))
            ->add('nbSiegesSort', IntegerType::class, array (
                'label'  => '* Nombre sièges tirage au sort',
                'required' => true,
                'trim' => true,
                'error_bubbling' => true,
                'disabled' => false,
                'attr' => array('min' =>0)
            ));
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\EleResultat'
        ));
    }

    public function getBlockPrefix() {
        return 'EleResultatType';
    }
}
