<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use App\Entity\EleEtablissement;

class TdbZoneEtabType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        parent::buildForm($builder, $options);


        $builder->add('etatAvancement', 'choice', array(
            'label' => 'Avancement des saisies',
            'multiple' => true,
            'expanded' => true,
            'choices' => array(
                EleEtablissement::ETAT_NONEFF => 'Non effectuées',
                EleEtablissement::ETAT_SAISIE => 'Enregistrées'
            ),
            'required' => false)
        );

        
        $builder->add('pvCarence', 'checkbox', array(
            'required' => false,
            'label' => 'PV de carence')
        );
        
        $builder->add('nvElect', 'checkbox', array(
            'required' => false,
            'label' => 'Nouvelles élections à organiser')
        );
        
        $builder->add('statutPv', 'choice', array(
            'label' => 'Statut des saisies',
            'multiple' => true,
            'expanded' => true,
            'choices' => array(
                EleEtablissement::ETAT_TRANSMISSION => 'Transmis',
                EleEtablissement::ETAT_VALIDATION => 'Validés'
            ),
            'required' => false)
        );
        

    }
    public function getName() {
        return 'tdbZoneEtabType';
    }

}
