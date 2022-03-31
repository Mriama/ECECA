<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;



class IdentificationType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options) {

        /*
         * $this->createFormBuilder( isset($lastUsername) ? array('login' => $lastUsername) : array() )			
         * 
         */
        
        $builder->add('login', TextType::class,
								array('label'  => '* Votre identifiant',
										'required' => true,
										'trim' => true,
										'error_bubbling' => true,
										'attr' => array('autofocus' => 'autofocus'),
										'invalid_message' => 'L\'identifiant est obligatoire.'))
						->add('password', PasswordType::class,
								array('label'  => '* Votre mot de passe',
										'required' => true,
										'error_bubbling' => true,
										'invalid_message' => 'Le mot de passe est obligatoire.'))
										->getForm();

    }
    
    public function getBlockPrefix() {
        return 'IdentificationType';
    }
}