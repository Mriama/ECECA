<?php

namespace App\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ElectionZoneEtabType extends ZoneEtabType {
    public function buildForm(FormBuilderInterface $builder, array $options) {
		$refTypeElection = $options['data']->getTypeElection();
		
    	parent::buildForm($builder, $options);
    	
    	$builder->add('typeElection', EntityType::class, array(
							'label' => '* Type d\'élection',
							'multiple' => false,
							'class' => RefTypeElection::class,
							'query_builder' => function(EntityRepository $er) use ($refTypeElection) {
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
							'empty_data' => '-- Choix --'
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
