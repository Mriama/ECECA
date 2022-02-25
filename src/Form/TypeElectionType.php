<?php

namespace App\Form;

//Form
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class TypeElectionType extends AbstractType {
	
	public function buildForm(FormBuilderInterface $builder, array $options=null) {
		$builder->add('typeElection', 'entity', array(
					'label' =>'* Type d\'élection',
					'multiple' => false,
					'class' => 'EPLEElectionBundle:RefTypeElection',
					'query_builder' => function(\Doctrine\ORM\EntityRepository $er) {
						return $er->createQueryBuilder('te')->orderBy('te.id', 'ASC');
					},
					'required' => true,
					'empty_value' => '-- Choix --',
					'property' => 'code'));
		
		if (isset($options['data']) and key_exists('typeZone', $options['data'])) {
			$typesZones = \App\Model\ContactModel::getChoixTypesZones();
			$builder->add('typeZone', 'choice', array(
							'label'		=> '* Type de zone',
							'choices'   => $typesZones,
							'required'  => true,));
		}
	}
	
	public function getName() {
		return 'choixtypeelect';
	}

}
