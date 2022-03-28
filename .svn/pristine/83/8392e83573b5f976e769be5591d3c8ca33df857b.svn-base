<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Doctrine\ORM\EntityManagerInterface;

class EleEtablissementType extends AbstractType {
    
    protected $em;
    
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options) {

		$builder->add('participation', new EleParticipationType($options["data"]->getCampagne()->getTypeElection()), array('label' => '* Participation'))
				->add('resultats', 'collection', array(
					    'type'   => new EleResultatType(),
					    'options'  => array('required'  => false)))
				->add('resultatsDetailles', 'collection', array(
					    'type'   => new EleResultatDetailType($this->em),
					    'options'  => array('required'  => false),
				        'allow_add'    => true,
				        'allow_delete' => true,
				        ));
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver) {
    	$resolver->setDefaults(array(
    			'data_class' => 'App\Entity\EleEtablissement'
    	));
    }

    public function getName() {
        return 'EleEtablissementType';
    }
}
