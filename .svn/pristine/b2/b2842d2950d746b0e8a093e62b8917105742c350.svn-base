<?php

namespace App\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use App\Entity\EleEtablissement;

use App\Entity\RefProfil;

class ResultatZoneEtabType extends ZoneEtabType {
	
	protected $hasSousTypeElect;
    protected $user;
	
    public function __construct(TokenStorageInterface $tokenStorage, $hasSousTypeElect = null) {
    	$this->hasSousTypeElect = $hasSousTypeElect;
        $this->user = $tokenStorage->getToken()->getUser();
        parent::__construct($user);
        $this->user = $user;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        parent::buildForm($builder, $options);

// 		if (!$this->campagne->isFinished()) { // mantis 122046 le filtre avancement des saisies apparait tout le temps
        $builder->add('etatSaisie', 'choice', array(
            'label' => 'Avancement des saisies',
            'multiple' => true,
            'expanded' => true,
            'choices' => array(
                EleEtablissement::ETAT_SAISIE => 'Enregistrées',
                EleEtablissement::ETAT_TRANSMISSION => 'Transmises',
                EleEtablissement::ETAT_VALIDATION => 'Validées'
            ),
            'required' => true)
        );
        
        if( $this->hasSousTypeElect ){
	       	$builder ->add('sousTypeElection', 'entity', array(
	        		'label' => 'Sous-type d’élection',
	        		'multiple' => false,
	        		'class' => 'EPLEElectionBundle:RefSousTypeElection',
	        		'query_builder' => function(\Doctrine\ORM\EntityRepository $er){
	        			$qb = $er->createQueryBuilder('s');
	        			$qb->orderBy('s.id', 'ASC');
	        			return $qb;
	        		},
	        		'required' => false,
	        		'property' => 'code',
	        		'empty_value' => false));
	   		}
    }
    
    

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => null
        ));
    }

    public function getName() {
        return 'resultatZoneEtabType';
    }

}
