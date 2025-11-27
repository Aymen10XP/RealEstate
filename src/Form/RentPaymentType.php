<?php

namespace App\Form;

use App\Entity\RentPayment;
use App\Entity\Lease;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RentPaymentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('amount', NumberType::class, [
                'attr' => ['class' => 'form-control', 'readonly' => 'readonly'],
                'label' => 'Amount Due',
                'scale' => 2
            ])
            ->add('paymentMethod', ChoiceType::class, [
                'choices' => [
                    'Credit Card' => 'credit_card',
                    'Debit Card' => 'debit_card',
                    'Bank Transfer' => 'bank_transfer',
                    'Cash' => 'cash',
                    'Check' => 'check',
                ],
                'attr' => ['class' => 'form-control'],
                'label' => 'Payment Method',
                'placeholder' => 'Select payment method'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RentPayment::class,
        ]);
    }
}