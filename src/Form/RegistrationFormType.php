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
                'label' => 'Имя',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Введите имя',
                    ]),
                    new Length([
                        'max' => 150,
                        'maxMessage' => 'Имя не может быть длиннее {{ limit }} символов',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Введите email',
                    ]),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Пароль',
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Введите пароль',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Пароль должен содержать минимум {{ limit }} символов',
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('city', TextType::class, [
                'label' => 'Город (только Франция)',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Введите название города',
                    ]),
                    new Length([
                        'max' => 100,
                        'maxMessage' => 'Название города не может быть длиннее {{ limit }} символов',
                    ]),
                ],
            ])
            ->add('code_post', TextType::class, [
                'label' => 'Почтовый код (только Франция)',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Введите почтовый код',
                    ]),
                    new Regex([
                        'pattern' => '/^\d{5}$/',
                        'message' => 'Почтовый код должен состоять из 5 цифр',
                    ]),
                ],
            ])
            ->add('number_phone', TelType::class, [
                'label' => 'Телефон',
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 20,
                        'maxMessage' => 'Номер телефона не может быть длиннее {{ limit }} символов',
                    ]),
                ],
            ])
            ->add('region', TextType::class, [
                'label' => 'Регион',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Введите регион',
                    ]),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Название региона не может быть длиннее {{ limit }} символов',
                    ]),
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'label' => 'Я соглашаюсь с правилами сервиса',
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'Вы должны согласиться с правилами сервиса',
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
