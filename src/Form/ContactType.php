<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use App\Entity\RefDepartement;
use App\Entity\RefAcademie;

class ContactType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options = null)
    {
        $contactId = 0;
        $zone = null;
        if (isset ($options ['data']) and $options ['data'] instanceof \App\Model\ContactModel) {
            $mc = $options ['data'];
            $contactId = $mc->getContact()->getId();
            $zone = ($mc->getDepartement() == null) ? $mc->getAcademie() : $mc->getDepartement();
        }

        if ($contactId === 0 and $zone instanceof RefDepartement) {
            $builder->add('departement', 'entity', array(
                'label' => '* Département',
                'multiple' => false,
                'class' => 'EPLEElectionBundle:RefDepartement',
                'query_builder' => function (\Doctrine\ORM\EntityRepository $er) use ($mc) {
                    return $er->queryBuilderRefDepartementSansContactByRefTypeElection($mc->getContact()->getTypeElection());
                },
                'required' => true,
                'read_only' => false,
                'property' => 'libelle'
            ));
        } else if ($contactId === 0 and $zone instanceof RefAcademie) {
            $builder->add('academie', 'entity', array(
                'label' => '* Académie',
                'multiple' => false,
                'class' => 'EPLEElectionBundle:RefAcademie',
                'query_builder' => function (\Doctrine\ORM\EntityRepository $er) use ($mc) {
                    return $er->queryBuilderFindRefAcademieSansContactByRefTypeElection($mc->getContact()->getTypeElection());
                },
                'required' => true,
                'read_only' => true,
                'error_bubbling' => true,
                'property' => 'libelle'
            ));
        } else {
            $labelZone = ($zone instanceof RefAcademie) ? 'Académie' : 'Département';
            $builder->add(($zone instanceof RefAcademie) ? 'academie' : 'departement', 'entity', array(
                'label' => '* ' . $labelZone,
                'multiple' => false,
                'class' => ($zone instanceof RefAcademie) ? 'EPLEElectionBundle:RefAcademie' : 'EPLEElectionBundle:RefDepartement',
                'query_builder' => function (\Doctrine\ORM\EntityRepository $er) {
                    return $er->createQueryBuilder('z')->orderBy('z.libelle', 'ASC');
                },
                'required' => true,
                'read_only' => true,
                'disabled' => true,
                'error_bubbling' => true,
                'property' => 'libelle'
            ));
        }

        $builder->add('nom', 'text', array(
            'label' => ' Nom',
            'max_length' => 50,
            'required' => false,
// 				'trim' => true,
// 				'error_bubbling' => true,
            'property_path' => 'contact.nom'
        ))->add('prenom', 'text', array(
            'label' => ' Prénom',
            'max_length' => 50,
            'required' => false,
// 				'trim' => true,
// 				'error_bubbling' => true,
            'property_path' => 'contact.prenom'
        ));

        if ($mc->getDepartement() != null) {
            if ($mc->getContact()->getTypeElection()->getId() === 3) {
                $builder->add('email1', 'text', array(
                    'label' => '* Adresse électronique 1er degré', // modifié dans la vue
                    'max_length' => 255,
                    'trim' => true,
                    'error_bubbling' => true,
                    'required' => false,
                    'property_path' => 'contact.email1'
                ))->add('email2', 'text', array(
                    'label' => '* Adresse électronique 2nd degré', // modifié dans la vue
                    'max_length' => 255,
                    'trim' => true,
                    'required' => false,
                    'error_bubbling' => true,
                    'property_path' => 'contact.email2'
                ));
            } else {
                $builder->add('email1', 'text', array(
                    'label' => '* Adresse électronique 2nd degré', // modifié dans la vue
                    'max_length' => 255,
                    'trim' => true,
                    'required' => true,
                    'error_bubbling' => true,
                    'property_path' => 'contact.email1'
                ));
            }
        } else {
            $builder->add('email1', 'text', array(
                'label' => '* Adresse électronique', // modifié dans la vue
                'max_length' => 255,
                'trim' => true,
                'required' => true,
                'error_bubbling' => true,
                'property_path' => 'contact.email1'
            ));
        }

        // Si on est dans un contexte departemantale pour une election parent
        if ($mc->getDepartement() != null && $mc->getContact()->getTypeElection()->getId() === 3) {
            $builder->add ( 'telephone', 'text', array (
                'label' => 'Téléphone',
                'max_length' => 20,
                'required' => false,
                'trim' => true,
                'error_bubbling' => true,
                'property_path' => 'contact.telephone'
            ) );

        } else {
            $builder->add ( 'telephone', 'text', array (
                'label' => '* Téléphone',
                'max_length' => 20,
                'required' => true,
                'trim' => true,
                'error_bubbling' => true,
                'property_path' => 'contact.telephone'
            ) );
        }


	}
	public function setDefaultOptions(OptionsResolverInterface $resolver) {
		$resolver->setDefaults ( array (
				'data_class' => 'App\Model\ContactModel' 
		) );
	}
	public function getName() {
		return 'eple_edit_contact';
	}
}
