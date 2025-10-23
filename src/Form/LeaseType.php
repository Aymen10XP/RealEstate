<?php

namespace App\Form;

use App\Entity\Lease;
use App\Entity\Property;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LeaseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('property', EntityType::class, [
                'class' => Property::class,
                'choice_label' => function(Property $property) {
                    return $property->getAddress() . ' - ' . $property->getCity();
                },
                'placeholder' => 'Select a property',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('tenant', EntityType::class, [
                'class' => User::class,
                'choice_label' => function(User $user) {
                    return $user->getFirstName() . ' ' . $user->getLastName() . ' (' . $user->getEmail() . ')';
                },
                'query_builder' => function ($repository) {
                    return $repository->createQueryBuilder('u')
                        ->andWhere('JSON_CONTAINS(u.roles, :role) = 1')
                        ->setParameter('role', '"ROLE_TENANT"');
                },
                'placeholder' => 'Select a tenant',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('startDate', DateType::class, [
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('endDate', DateType::class, [
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('monthlyRent', MoneyType::class, [
                'currency' => 'USD',
                'attr' => ['class' => 'form-control'],
                'help' => 'Monthly rental amount',
            ])
            ->add('securityDeposit', MoneyType::class, [
                'currency' => 'USD',
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'help' => 'Security deposit amount (optional)',
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Active' => 'active',
                    'Expired' => 'expired',
                    'Terminated' => 'terminated',
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('terms', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => 'Enter lease terms and conditions...'
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Lease::class,
        ]);
    }
}