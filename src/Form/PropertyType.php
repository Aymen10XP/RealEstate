<?php

namespace App\Form;

use App\Entity\Property;
use App\Entity\Owner;
use App\Entity\Tenant;
use App\Repository\OwnerRepository;
use App\Repository\TenantRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class PropertyType extends AbstractType
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isManager = $this->security->isGranted('ROLE_MANAGER');

        $builder
            ->add('address', TextType::class, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Enter full address']
            ])
            ->add('city', TextType::class, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Enter city']
            ])
            ->add('state', TextType::class, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Enter state']
            ])
            ->add('zipCode', TextType::class, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Enter zip code']
            ])
            ->add('monthlyRent', NumberType::class, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Enter monthly rent']
            ])
            ->add('bedrooms', IntegerType::class, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Enter number of bedrooms']
            ])
            ->add('bathrooms', IntegerType::class, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Enter number of bathrooms']
            ])
            ->add('squareFeet', IntegerType::class, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Enter square footage']
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 5, 'placeholder' => 'Enter property description']
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Available' => 'available',
                    'Occupied' => 'occupied',
                    'Under Maintenance' => 'maintenance',
                ],
                'attr' => ['class' => 'form-control']
            ])
        ;

        // Only show owner field for managers
        if ($isManager) {
            $builder->add('owner', EntityType::class, [
                'class' => Owner::class,
                'choice_label' => function (Owner $owner) {
                    return $owner->getFullName() . ' (' . $owner->getEmail() . ')';
                },
                'attr' => ['class' => 'form-control'],
                'placeholder' => 'Select an owner',
                'query_builder' => function (OwnerRepository $repository) {
                    return $repository->createQueryBuilder('o')
                        ->orderBy('o.firstName', 'ASC');
                },
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Property::class,
        ]);
    }
}