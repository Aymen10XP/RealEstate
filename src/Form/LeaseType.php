<?php

namespace App\Form;

use App\Entity\Lease;
use App\Entity\Tenant;
use App\Entity\Property;
use App\Repository\TenantRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LeaseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $property = $options['property'];

        $builder
            ->add('tenant', EntityType::class, [
                'class' => Tenant::class,
                'choice_label' => function (Tenant $tenant) {
                    return $tenant->getFullName() . ' (' . $tenant->getEmail() . ')';
                },
                'attr' => ['class' => 'form-control'],
                'label' => 'Select Tenant',
                'placeholder' => 'Choose a tenant',
                'query_builder' => function (TenantRepository $repository) {
                    return $repository->createQueryBuilder('t')
                        ->orderBy('t.firstName', 'ASC');
                },
            ])
            ->add('startDate', DateType::class, [
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'label' => 'Lease Start Date'
            ])
            ->add('endDate', DateType::class, [
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'label' => 'Lease End Date'
            ])
            ->add('monthlyRent', NumberType::class, [
                'attr' => ['class' => 'form-control'],
                'label' => 'Monthly Rent',
                'data' => $property ? $property->getMonthlyRent() : null
            ])
            ->add('securityDeposit', NumberType::class, [
                'attr' => ['class' => 'form-control'],
                'label' => 'Security Deposit',
                'required' => false
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Active' => 'active',
                    'Expired' => 'expired',
                    'Terminated' => 'terminated',
                ],
                'attr' => ['class' => 'form-control'],
                'label' => 'Lease Status'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Lease::class,
            'property' => null,
        ]);

        $resolver->setAllowedTypes('property', ['null', Property::class]);
    }
}