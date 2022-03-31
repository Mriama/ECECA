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
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class CourrielLibreType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options = null) {

        $builder->add('contacts_academie', TextType::class, array(
                    'label' => 'Contacts en académie',
        			'required' => false,
                    'trim' => true,
        			'attr' => array(
        							'placeholder' => 'tapez le nom de l\'académie'
    				),
                    'error_bubbling' => true))
                 ->add('code_academie', HiddenType::class, array(
                    'data' => '')
                    )
                 ->add('contacts_departementaux', TextType::class, array(
                    'label' => 'Contacts départementaux',
        			'required' => false,
                    'trim' => true,
        			'attr' => array(
        							'placeholder' => 'tapez le nom du département'
    				),
                    'error_bubbling' => true))
                 ->add('numero_departement', HiddenType::class, array(
                    'data' => '')
                    )
                 ->add('contacts_etablissements', TextType::class, array(
                    'label' => 'Etablissements',
        			'required' => false,
                    'trim' => true,
        			'attr' => array(
        							'placeholder' => 'tapez l\'UAI ou le nom de l\'établissement'
    				),
                    'error_bubbling' => true))
                 ->add('uai_etablissement', HiddenType::class, array(
                    'data' => '')
                    )
                 ->add('objet', TextType::class, array(
                    'label' => '* Objet du courriel',
                    'required' => true,
                    'trim' => true,
                    'error_bubbling' => true))
                ->add('message', TextareaType::class, array(
                    'label' => '* Message',
                    'required' => true,
                    'trim' => true,
                    'error_bubbling' => true))
        ;
    }

    public function getBlockPrefix() {
        return 'ececa_saisie_courriel_libre';
    }

}
