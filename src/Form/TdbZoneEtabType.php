<?php

namespace App\Form;

use App\Entity\EleEtablissement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;


class TdbZoneEtabType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        parent::buildForm($builder, $options);


        $builder->add('etatAvancement', ChoiceType::class, array(
            'label' => 'Avancement des saisies',
            'multiple' => true,
            'expanded' => true,
            'choices' => array(
                'Non effectuées' => EleEtablissement::ETAT_NONEFF,
                'Enregistrées' => EleEtablissement::ETAT_SAISIE
            ),
            'required' => false)
        );

        
        $builder->add('pvCarence', CheckboxType::class, array(
            'required' => false,
            'label' => 'PV de carence')
        );
        
        $builder->add('nvElect', CheckboxType::class, array(
            'required' => false,
            'label' => 'Nouvelles élections à organiser')
        );
        
        $builder->add('statutPv', ChoiceType::class, array(
            'label' => 'Statut des saisies',
            'multiple' => true,
            'expanded' => true,
            'choices' => array(
                'Transmis' => EleEtablissement::ETAT_TRANSMISSION,
                'Validés' => EleEtablissement::ETAT_VALIDATION
            ),
            'required' => false)
        );
        

    }
    public function getBlockPrefix() {
        return 'tdbZoneEtabType';
    }

}
