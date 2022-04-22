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
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CourrielType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options = null) {
        $copies = null;
        if($options != null && isset($options['copies']) && !empty($options["copies"])) {
            $copies = array_flip($options['copies']);
        }
        $builder->add('objet', TextType::class, array(
            'label' => '* Objet',
            'required' => true,
            'trim' => true,
            'error_bubbling' => true))
            ->add('message', TextareaType::class, array(
                'label' => '* Message',
                'required' => true,
                'trim' => true,
                'error_bubbling' => true))
            ->add('choix_copies', ChoiceType::class, array(
                'required' => false,
                'multiple' => true,
                'expanded' => true,
                'choices' => $copies,
                'label' => 'Mettre en copie'))
        ;
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => null,
            'copies' => [],
        ));
    }

    public function getBlockPrefix() {
        return 'ececa_saisie_courriel';
    }

}
