<?php
/*
 * NbSiegesTirageAuSortType.php
 * Formulaire de saisie du nombre de sièges pourvus par tirage au sort
 * 
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class NbSiegesTirageAuSortType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options = null) {

        $builder->add('nbSiegesTirageAuSort', TextType::class, array (
				    			'label'  => 'Nombre de sièges pourvus par tirage au sort',
				    			'required' => false,
				    			'trim' => true,
				    			'error_bubbling' => true,
								'attr' => array('min' =>0)
					));
    }

    public function getBlockPrefix() {
        return 'ececa_saisie_ts';
    }

}
