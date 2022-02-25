<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use App\Form\EventListener\DisableDepartementSubscriber;

class CommuneType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('codePostal', 'text', array(
                    'label' => '* Code postal',
                    'required' => true,
                    'trim' => true,
                    'error_bubbling' => true,
                    'max_length' => '5',
                ))
                ->add('libelle', 'text', array(
                    'label' => '* Commune',
                    'trim' => true,
                    'required' => false,
                    'error_bubbling' => true
                ))
                ->add('departement', 'entity', array(
                    'label' => '* Département',
                    'multiple' => false,
                    'class' => 'EPLEElectionBundle:RefDepartement',
                    'query_builder' => function(\Doctrine\ORM\EntityRepository $er) {
                return $er->createQueryBuilder('d')->orderBy('d.numero', 'ASC');
            },
                    'required' => true,
                    'property' => 'libelle'));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\RefCommune'
        ));
    }

    public function getName() {
        return 'commune_type';
    }

}
