<?php
/*
 * CourrielLibreType.php
 * Formulaire d'envoi de courriel libre
 * Contient des contacts en académie, des contacts départementaux, des établissements, un objet, un message
 * 
 * 
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class CourrielLibreType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options = null) {

        $builder->add('contacts_academie', 'text', array(
                    'label' => 'Contacts en académie',
        			'required' => false,
                    'trim' => true,
        			'attr' => array(
        							'placeholder' => 'tapez le nom de l\'académie'
    				),
                    'error_bubbling' => true))
                 ->add('code_academie', 'hidden', array(
                    'data' => '')
                    )
                 ->add('contacts_departementaux', 'text', array(
                    'label' => 'Contacts départementaux',
        			'required' => false,
                    'trim' => true,
        			'attr' => array(
        							'placeholder' => 'tapez le nom du département'
    				),
                    'error_bubbling' => true))
                 ->add('numero_departement', 'hidden', array(
                    'data' => '')
                    )
                 ->add('contacts_etablissements', 'text', array(
                    'label' => 'Etablissements',
        			'required' => false,
                    'trim' => true,
        			'attr' => array(
        							'placeholder' => 'tapez l\'UAI ou le nom de l\'établissement'
    				),
                    'error_bubbling' => true))
                 ->add('uai_etablissement', 'hidden', array(
                    'data' => '')
                    )
                 ->add('objet', 'text', array(
                    'label' => '* Objet du courriel',
                    'required' => true,
                    'trim' => true,
                    'error_bubbling' => true))
                ->add('message', 'textarea', array(
                    'label' => '* Message',
                    'required' => true,
                    'trim' => true,
                    'error_bubbling' => true))
        ;
    }

    public function getName() {
        return 'ececa_saisie_courriel_libre';
    }

}
