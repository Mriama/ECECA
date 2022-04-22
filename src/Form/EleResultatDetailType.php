<?php

namespace App\Form;

use App\Form\EleResultatType;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use App\Form\DataTransformer\OrganisationToIdTransformer;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EleResultatDetailType extends EleResultatType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        parent::buildForm($builder, $options);

        $builder->add('libelle', TextType::class, array (
            'required' => true,
            'trim' => true,
            'error_bubbling' => true
        ));

        $transformer = new OrganisationToIdTransformer($options['em']);

        // add a normal text field, but add your transformer to it
        $builder->add(
            $builder->create('organisation', HiddenType::class)
                ->addModelTransformer($transformer)
        );

    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\EleResultatDetail',
            'em' => null
        ));
    }

    public function getBlockPrefix() {
        return 'EleResultatDetailType';
    }
}
