<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class EleFichierType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder
		->add('file', 'file', array('required' => true, 'label'=>'Fichier'))
		;
	}

	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
		$resolver->setDefaults(array(
				'data_class' => 'App\Entity\EleFichier'
		));
	}

	public function getName()
	{
		return 'sdz_blogbundle_imagetype';
	}
}