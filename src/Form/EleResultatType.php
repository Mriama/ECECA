<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class EleResultatType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options) {

		$builder->add('nbCandidats', 'integer', array (
				    			'label'  => '* Nombre de candidats',
				    			'required' => true,
				    			'trim' => true,
				    			'error_bubbling' => true,
								'attr' => array('min' =>0)
				    		))
	    		->add('nbVoix', 'integer', array (
	    						'label'  => '* Nombre de voix obtenues',
	    						'required' => true,
	    						'trim' => true,
	    						'error_bubbling' => true,
								'attr' => array('min' =>0)
	    		))
				->add('nbSieges', 'integer', array (
			    				'label'  => '* Nombre de sièges obtenus',
			    				'required' => true,
			    				'trim' => true,
			    				'error_bubbling' => true,
								'read_only' => false,
								'attr' => array('min' =>0)
				))
	    		->add('nbSiegesSort', 'integer', array (
			    				'label'  => '* Nombre sièges tirage au sort',
			    				'required' => true,
			    				'trim' => true,
			    				'error_bubbling' => true,
	    						'read_only' => false,
								'attr' => array('min' =>0)
	    		));  
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver) {
    	$resolver->setDefaults(array(
    			'data_class' => 'App\Entity\EleResultat'
    	));
    }

    public function getName() {
        return 'EleResultatType';
    }
}
