<?php

namespace App\Form;

//Form
use App\Entity\EleCampagne;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use App\Entity\RefTypeElection;
use App\Model\ContactModel;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TypeElectionType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options=null) {
        $refTypeElection = $options['data']['typeElection'] ?? null;

        $builder->add('typeElection', EntityType::class, array(
            'label' =>'* Type d\'élection',
            'multiple' => false,
            'class' => RefTypeElection::class,
            'query_builder' => function(EntityRepository $er) {
                $qb = $er->createQueryBuilder('te');
                $qb->orderBy('te.id', 'ASC');
                return $qb;
            },
            'data' => $refTypeElection,
            'required' => true,
            'placeholder' => '-- Choix --',
            'choice_label' => 'code'));

        if (isset($options['data']['typeZone'])) {
            $typesZones = ContactModel::getChoixTypesZones();
            $builder->add('typeZone', ChoiceType::class, array(
                'label'		=> '* Type de zone',
                'choices'   => $typesZones,
                'required'  => true,));
        }
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => null,
            'typeElection' => null,
            'typeZone' => null,
        ));
    }
    public function getBlockPrefix() {
        return 'choixtypeelect';
    }

}
