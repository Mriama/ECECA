<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ArchiveCampagneZoneEtabType extends ZoneEtabType {
    public function buildForm(FormBuilderInterface $builder, array $options) {

    	parent::buildForm($builder, $options);

    	$datas = $options['data'];
    	$refTypeElection = $datas['typeElect'];
    	
    	$builder->add('campagne', 'entity', array(
			    		'label' => '* Campagne',
			    		'multiple' => false,
			    		'class' => 'EPLEElectionBundle:EleCampagne',
			    		'query_builder' => function(\Doctrine\ORM\EntityRepository $er) use ($refTypeElection) {
			    			return $er->createQueryBuilder('c')
			    					  ->where('c.typeElection = :typeElection')
			    					  ->andWhere('c.archivee = true')
			    					  ->setParameter('typeElection', $refTypeElection)
			    					  ->groupBy('c.anneeDebut')
			    					  ->orderBy('c.anneeDebut', 'DESC')
			    					  ->setMaxResults(10);
			    		},
			    		'required' => true,
			    		'property' => 'anneesDebFinCampagne')
					    );
 							
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver) {
    	$resolver->setDefaults(array(
    			'data_class' => null //'App\Model\CampagneZoneEtabModel'
    	));
    }

    public function getName() {
        return 'campagneZoneEtabType';
    }
}
