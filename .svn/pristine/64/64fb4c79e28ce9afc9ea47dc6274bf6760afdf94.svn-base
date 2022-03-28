<?php

namespace App\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Doctrine\ORM\EntityManagerInterface;

use App\Form\DataTransformer\OrganisationToIdTransformer;
use App\Form\EleResultatType;

class EleResultatDetailType extends EleResultatType {
    
    protected $em;
    
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options) {
        parent::buildForm($builder, $options);
        
        $builder->add('libelle', 'text', array (
            'required' => true,
            'trim' => true,
            'error_bubbling' => true
            ));
      
        $transformer = new OrganisationToIdTransformer($this->em);
        
        // add a normal text field, but add your transformer to it
        $builder->add(
            $builder->create('organisation', 'hidden')
            ->addModelTransformer($transformer)
        );
        
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver) {
    	$resolver->setDefaults(array(
    			'data_class' => 'App\Entity\EleResultatDetail'
    	));
    }

    public function getName() {
        return 'EleResultatDetailType';
    }
}
