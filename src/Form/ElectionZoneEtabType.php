<?php

namespace App\Form;

use App\Entity\RefTypeElection;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
							'choice_label' => 'code',
							'empty_data' => '-- Choix --'
					));
 							
    }
    
    public function configureOptions(OptionsResolver $resolver) {
    	$resolver->setDefaults(array(
    			'data_class' => 'App\Model\ElectionZoneEtabModel'
    	));
    }

    public function getBlockPrefix() {
        return 'ElectionZoneEtabType';
    }
}
