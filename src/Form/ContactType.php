<?php

namespace App\Form;

use App\Entity\RefAcademie;
use App\Entity\RefDepartement;
use App\Model\ContactModel;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options = null)
    {
        $contactId = 0;
        $zone = null;
        if (isset ($options ['data']) and $options ['data'] instanceof ContactModel) {
            $mc = $options ['data'];
            $contactId = $mc->getContact()->getId();
            $zone = ($mc->getDepartement() == null) ? $mc->getAcademie() : $mc->getDepartement();
        }

        if ($contactId === 0 and $zone instanceof RefDepartement) {
            $builder->add('departement', EntityType::class, array(
                'label' => '* Département',
                'multiple' => false,
                'class' => RefDepartement::class,
                'query_builder' => function (EntityRepository $er) use ($mc) {
                    return $er->queryBuilderRefDepartementSansContactByRefTypeElection($mc->getContact()->getTypeElection());
                },
                'required' => true,
                'disabled' => false,
                'choice_label' => 'libelle'
            ));
        } else if ($contactId === 0 and $zone instanceof RefAcademie) {
            $builder->add('academie', EntityType::class, array(
                'label' => '* Académie',
                'multiple' => false,
                'class' => RefAcademie::class,
                'query_builder' => function (EntityRepository $er) use ($mc) {
                    return $er->queryBuilderFindRefAcademieSansContactByRefTypeElection($mc->getContact()->getTypeElection());
                },
                'required' => true,
                'error_bubbling' => true,
                'choice_label' => 'libelle'
            ));
        } else {
            $labelZone = ($zone instanceof RefAcademie) ? 'Académie' : 'Département';
            $builder->add(($zone instanceof RefAcademie) ? 'academie' : 'departement', EntityType::class, array(
                'label' => '* ' . $labelZone,
                'multiple' => false,
                'class' => ($zone instanceof RefAcademie) ? RefAcademie::class : RefDepartement::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('z')->orderBy('z.libelle', 'ASC');
                },
                'required' => true,
                'disabled' => true,
                'error_bubbling' => true,
                'choice_label' => 'libelle'
            ));
        }

        $builder->add('nom', TextType::class, array(
            'label' => ' Nom',
            'attr' => ['maxlength' => 50],
            'required' => false,
// 				'trim' => true,
// 				'error_bubbling' => true,
            'property_path' => 'contact.nom'
        ))->add('prenom', TextType::class, array(
            'label' => ' Prénom',
            'attr' => ['maxlength' => 50],
            'required' => false,
// 				'trim' => true,
// 				'error_bubbling' => true,
            'property_path' => 'contact.prenom'
        ));

        if ($mc->getDepartement() != null) {
            if ($mc->getContact()->getTypeElection()->getId() === 3) {
                $builder->add('email1', TextType::class, array(
                    'label' => '* Adresse électronique 1er degré', // modifié dans la vue
                    'attr' => ['maxlength' => 255],
                    'trim' => true,
                    'error_bubbling' => true,
                    'required' => false,
                    'property_path' => 'contact.email1'
                ))->add('email2', TextType::class, array(
                    'label' => '* Adresse électronique 2nd degré', // modifié dans la vue
                    'attr' => ['maxlength' => 255],
                    'trim' => true,
                    'required' => false,
                    'error_bubbling' => true,
                    'property_path' => 'contact.email2'
                ));
            } else {
                $builder->add('email1', TextType::class, array(
                    'label' => '* Adresse électronique 2nd degré', // modifié dans la vue
                    'attr' => ['maxlength' => 255],
                    'trim' => true,
                    'required' => true,
                    'error_bubbling' => true,
                    'property_path' => 'contact.email1'
                ));
            }
        } else {
            $builder->add('email1', TextType::class, array(
                'label' => '* Adresse électronique', // modifié dans la vue
                'attr' => ['maxlength' => 255],
                'trim' => true,
                'required' => true,
                'error_bubbling' => true,
                'property_path' => 'contact.email1'
            ));
        }

        // Si on est dans un contexte departemantale pour une election parent
        if ($mc->getDepartement() != null && $mc->getContact()->getTypeElection()->getId() === 3) {
            $builder->add ( 'telephone', TextType::class, array (
                'label' => 'Téléphone',
                'attr' => ['maxlength' => 20],
                'required' => false,
                'trim' => true,
                'error_bubbling' => true,
                'property_path' => 'contact.telephone'
            ) );

        } else {
            $builder->add ( 'telephone', TextType::class, array (
                'label' => '* Téléphone',
                'attr' => ['maxlength' => 20],
                'required' => true,
                'trim' => true,
                'error_bubbling' => true,
                'property_path' => 'contact.telephone'
            ) );
        }


    }
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults ( array (
            'data_class' => 'App\Model\ContactModel'
        ) );
    }
    public function getBlockPrefix() {
        return 'eple_edit_contact';
    }
}
