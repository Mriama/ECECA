<?php

namespace App\Form;

use App\Entity\RefDepartement;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommuneType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('codePostal', TextType::class, array(
                    'label' => '* Code postal',
                    'required' => true,
                    'trim' => true,
                    'error_bubbling' => true,
                    'max_length' => '5',
                ))
                ->add('libelle', TextType::class, array(
                    'label' => '* Commune',
                    'trim' => true,
                    'required' => false,
                    'error_bubbling' => true
                ))
                ->add('departement', EntityType::class, array(
                    'label' => '* Département',
                    'multiple' => false,
                    'class' => RefDepartement::class,
                    'query_builder' => function(EntityRepository $er) {
                return $er->createQueryBuilder('d')->orderBy('d.numero', 'ASC');
            },
                    'required' => true,
                    'choice_label' => 'libelle'));
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\RefCommune'
        ));
    }

    public function getBlockPrefix() {
        return 'commune_type';
    }
}
