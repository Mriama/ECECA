<?php
/*
 * CourrielType.php
 * Formulaire d'envoi de courriel générique
 * Contient un objet, un message et une case à cocher avec l'email de l'utilisateur connecté
 * 
 * 
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class CourrielType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options = null) {
        $copies = null;
        if($options != null && isset($options['data']) && !empty($options["data"])) {
            $copies = $options['data'];
        }
        $builder->add('objet', 'text', array(
                    'label' => '* Objet',
                    'required' => true,
                    'trim' => true,
                    'error_bubbling' => true))
                ->add('message', 'textarea', array(
                    'label' => '* Message',
                    'required' => true,
                    'trim' => true,
                    'error_bubbling' => true))
                ->add('choix_copies', 'choice', array(
                    'required' => false,
                    'multiple' => true,
                    'expanded' => true,
                    'choices' => $copies,
                    'label' => 'Mettre en copie'))
        ;
    }

    public function getName() {
        return 'ececa_saisie_courriel';
    }

}
