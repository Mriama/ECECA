<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ElectionZoneEtabType extends ZoneEtabType {
    public function buildForm(FormBuilderInterface $builder, array $options) {
		$refTypeElection = $options['data']->getTypeElection();
		
    	parent::buildForm($builder, $options);
    	
    	$builder->add('typeElection', 'entity', array(
							'label' => '* Type d\'élection',
							'multiple' => false,
							'class' => 'EPLEElectionBundle:RefTypeElection',
							'query_builder' => function(\Doctrine\ORM\EntityRepository $er) use ($refTypeElection) {
												$qb = $er->createQueryBuilder('t');
												if (!empty($refTypeElection)) {
													$qb->where('t.id = :typeEle')
														->setParameter('typeEle', $refTypeElection->getId());
												}
												$qb->orderBy('t.id', 'ASC');
												return $qb;
											},
							'required' => true,
							'property' => 'code',
							'empty_value' => '-- Choix --'
					));
 							
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver) {
    	$resolver->setDefaults(array(
    			'data_class' => 'App\Model\ElectionZoneEtabModel'
    	));
    }

    public function getName() {
        return 'ElectionZoneEtabType';
    }
}
