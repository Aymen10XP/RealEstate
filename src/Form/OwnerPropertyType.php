<?php

namespace App\Form;

use App\Entity\Property;
use App\Entity\Manager;
use App\Repository\ManagerRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OwnerPropertyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('address', TextType::class, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Enter full address'],
                'label' => 'Property Address'
            ])
            ->add('city', TextType::class, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Enter city'],
                'label' => 'City'
            ])
            ->add('state', TextType::class, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Enter state'],
                'label' => 'State'
            ])
            ->add('zipCode', TextType::class, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Enter zip code'],
                'label' => 'Zip Code'
            ])
            ->add('monthlyRent', NumberType::class, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Enter monthly rent'],
                'label' => 'Monthly Rent ($)',
                'scale' => 2,
            ])
            ->add('bedrooms', IntegerType::class, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Enter number of bedrooms', 'min' => 0],
                'label' => 'Bedrooms',
            ])
            ->add('bathrooms', IntegerType::class, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Enter number of bathrooms', 'min' => 0],
                'label' => 'Bathrooms',
            ])
            ->add('squareFeet', IntegerType::class, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Enter square footage', 'min' => 0],
                'label' => 'Square Feet',
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 5, 'placeholder' => 'Enter property description...'],
                'label' => 'Description'
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Available' => 'available',
                    'Occupied' => 'occupied',
                    'Under Maintenance' => 'maintenance',
                ],
                'attr' => ['class' => 'form-control'],
                'label' => 'Property Status'
            ])
            ->add('manager', EntityType::class, [
                'class' => Manager::class,
                'choice_label' => function (Manager $manager) {
                    return $manager->getFullName() . ' (' . $manager->getEmail() . ')';
                },
                'attr' => ['class' => 'form-control'],
                'label' => 'Assign Manager (Optional)',
                'required' => false,
                'placeholder' => 'No manager assigned',
                'query_builder' => function (ManagerRepository $repository) {
                    return $repository->createQueryBuilder('m')
                        ->orderBy('m.firstName', 'ASC');
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Property::class,
        ]);
    }
}