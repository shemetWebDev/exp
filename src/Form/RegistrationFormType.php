<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'Username',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a username',
                    ]),
                    new Length([
                        'max' => 150,
                        'maxMessage' => 'Username cannot be longer than {{ limit }} characters',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter an email',
                    ]),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Password',
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a password',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('city', TextType::class, [
                'label' => 'City (France only)',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter your city',
                    ]),
                    new Length([
                        'max' => 100,
                        'maxMessage' => 'City name cannot be longer than {{ limit }} characters',
                    ]),
                ],
            ])
            ->add('code_post', TextType::class, [
                'label' => 'Postal Code (France only)',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter your postal code',
                    ]),
                    new Regex([
                        'pattern' => '/^\d{5}$/',
                        'message' => 'Postal code must be a 5-digit number',
                    ]),
                ],
            ])
            ->add('number_phone', TelType::class, [
                'label' => 'Phone Number',
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 20,
                        'maxMessage' => 'Phone number cannot be longer than {{ limit }} characters',
                    ]),
                ],
            ])
            ->add('region', TextType::class, [
                'label' => 'Region',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter your region',
                    ]),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Region cannot be longer than {{ limit }} characters',
                    ]),
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'You should agree to our terms.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}