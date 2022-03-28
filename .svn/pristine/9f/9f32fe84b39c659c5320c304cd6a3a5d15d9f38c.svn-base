<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;


class EtablissementType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options) {
	    $etablissement = $options['data'];	

		$builder->add('etab.uai', 'text', array (
				    			'max_length' => '8',
				    			'label'  => '* UAI',
				    			'required' => true,
				    			'trim' => true,
				    			'error_bubbling' => true,
				    			'read_only' => ($etablissement->getEtab()->getUai() != null) ?  true : false,
				    		))
	       	   ->add('etab.commune', new CommuneType(), array('label'=>'* Commune'))
			   ->add('commune', 'entity', array(
			    				'label' => '* Commune',
			    				'multiple' => false,
			    				'class' => 'EPLEElectionBundle:RefCommune',
			    				'required' => true,
			    				'property' => 'libelle',
			    				'empty_value' => 'Veuillez saisir un code postal',
			   				))
	           ->add('etab.typeEtablissement', 'entity', array(
			            		'label' => '* Type d\'établissement',
			            		'multiple' => false,
			            		'class' => 'EPLEElectionBundle:RefTypeEtablissement',
			            		'query_builder' => function(\Doctrine\ORM\EntityRepository $er) {
			            					return $er->createQueryBuilder('t')->orderBy('t.id', 'ASC');
			            				},
			            		'required' => true,
			            		'property' => 'code'
							))
	            ->add('etab.eclair', 'checkbox', array(
			            		'required' => false, 
			            		'label'  => 'Éclair',
							))
	            ->add('etab.libelle', 'text', array(
			            		'label' => '* Libellé',
			            		'required' => true,
			            		'trim' => true,
			            		'error_bubbling' => true
							))
	            ->add('etab.contact', 'email', array(
			            		'label' => '* Adresse électronique',
			            		'required' => true,
			            		'trim' => true,
			            		'error_bubbling' => true
							))
	            ->add('flagAddCommune', 'hidden', array(
	    						'data' => 'false'));  
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver) {
    	$resolver->setDefaults(array(
    			'data_class' => 'App\Model\Etablissementmodel'
    	));
    }

    public function getName() {
        return 'etabtype';
    }
}
