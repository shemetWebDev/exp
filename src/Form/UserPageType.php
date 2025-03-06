<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\UserPage;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints as Assert;

class UserPageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Заголовок обязательное поле']),
                    new Assert\Length([
                        'max' => 60,
                        'maxMessage' => 'Заголовок не должен превышать {{ limit }} символов',
                    ]),
                ],
                'label' => 'Заголовок страницы',
                'help' => 'Введите краткий заголовок вашей страницы или название бизнеса.',
                'attr' => ['placeholder' => 'Например, "Мой бизнес"'],
            ])
            ->add('slug', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'URL-адрес обязателен']),
                    new Assert\Regex([
                        'pattern' => '/^[a-z0-9-]+$/',
                        'message' => 'URL-адрес может содержать только маленькие буквы, цифры и дефисы.',
                    ]),
                ],
                'label' => 'URL-адрес',
                'help' => 'Укажите уникальный URL для вашего сайта. Используйте только латинские буквы, цифры и дефисы.',
                'attr' => ['placeholder' => 'Например, "moj-biznes"'],
            ])
            ->add('keywords', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Ключевые слова обязательны']),
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'Ключевые слова не должны превышать {{ limit }} символов',
                    ]),
                ],
                'label' => 'Ключевые слова',
                'help' => 'Введите ключевые слова, которые помогут улучшить поисковую оптимизацию вашего сайта.',
                'attr' => ['placeholder' => 'Например, "бизнес, услуги, магазин"'],
            ])
            ->add('subtitle', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Подзаголовок обязательное поле']),
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'Подзаголовок не должен превышать {{ limit }} символов',
                    ]),
                ],
                'label' => 'Подзаголовок',
                'help' => 'Введите короткое описание или слоган для вашей компании.',
                'attr' => ['placeholder' => 'Например, "Лучшие товары по доступным ценам"'],
            ])
            ->add('bannerImg', FileType::class, [
                'mapped' => false, 
                'required' => false,
                'constraints' => [
                    new Assert\Image([
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif'],
                        'mimeTypesMessage' => 'Загрузите допустимое изображение (jpeg, png, gif)',
                    ]),
                ],
                'label' => 'Изображение для баннера',
                'help' => 'Загрузите изображение для баннера на странице. Допустимые форматы: jpeg, png, gif.',
            ])
            ->add('image', FileType::class, [
                'mapped' => false, 
                'required' => false,
                'constraints' => [
                    new Assert\Image([
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif'],
                        'mimeTypesMessage' => 'Загрузите допустимое изображение (jpeg, png, gif)',
                    ]),
                ],
                'label' => 'Изображение для страницы',
                'help' => 'Загрузите изображение, которое будет отображаться на вашей странице.',
            ])
            ->add('advantageOne', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Преимущество 1 обязательно']),
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'Преимущество не должно превышать {{ limit }} символов',
                    ]),
                ],
                'label' => 'Преимущество 1',
                'help' => 'Опишите первое преимущество вашего бизнеса или продукта.',
                'attr' => ['placeholder' => 'Например, "Быстрая доставка"'],
            ])
            ->add('advantageTwoo', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Преимущество 2 обязательно']),
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'Преимущество не должно превышать {{ limit }} символов',
                    ]),
                ],
                'label' => 'Преимущество 2',
                'help' => 'Опишите второе преимущество вашего бизнеса или продукта.',
                'attr' => ['placeholder' => 'Например, "Качество гарантировано"'],
            ])
            ->add('advantageThree', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Преимущество 3 обязательно']),
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'Преимущество не должно превышать {{ limit }} символов',
                    ]),
                ],
                'label' => 'Преимущество 3',
                'help' => 'Опишите третье преимущество вашего бизнеса или продукта.',
                'attr' => ['placeholder' => 'Например, "Доступные цены"'],
            ])
            ->add('phone', TelType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Телефон обязателен']),
                    new Assert\Regex([
                        'pattern' => '/^\+?\d{1,3}?\d{10}$/',
                        'message' => 'Введите корректный номер телефона в международном формате',
                    ]),
                ],
                'label' => 'Телефон',
                'help' => 'Укажите номер телефона для связи. Введите в международном формате.',
                'attr' => ['placeholder' => '+7 900 123 45 67'],
            ])
            ->add('adress', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Адрес обязателен']),
                ],
                'label' => 'Адрес',
                'help' => 'Укажите адрес вашей компании.',
                'attr' => ['placeholder' => 'Например, "г. Москва, ул. Ленина, д. 1"'],
            ])
            ->add('email', EmailType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Email обязателен']),
                    new Assert\Email(['message' => 'Введите корректный email']),
                ],
                'label' => 'Email',
                'help' => 'Укажите рабочий email адрес.',
                'attr' => ['placeholder' => 'example@domain.com'],
            ])
            ->add('companyName', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Название компании обязательно']),
                ],
                'label' => 'Название компании',
                'help' => 'Укажите название вашей компании.',
                'attr' => ['placeholder' => 'Например, "ООО Пример"'],
            ])
            ->add('mapPosition', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Позиция на карте обязательна']),
                ],
                'label' => 'Позиция на карте',
                'help' => 'Укажите местоположение на карте или получите его с помощью сервиса (например, Google Maps).',
                'attr' => ['placeholder' => 'Координаты на карте'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserPage::class,
        ]);
    }
}
