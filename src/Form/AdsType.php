<?php

namespace App\Form;

use App\Entity\Ads;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class AdsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Заголовок',
                'attr' => [
                    'placeholder' => 'Коротко о товаре или услуге',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Название обязательно']),
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'Название не должно превышать 255 символов',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Описание',
                'attr' => [
                    'placeholder' => 'Подробно опишите состояние, характеристики, условия...',
                    'rows' => 6,
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Описание обязательно']),
                ],
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Цена, €',
                'currency' => 'EUR',
                'attr' => [
                    'placeholder' => 'Например, 100',
                    'inputmode' => 'decimal',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Цена обязательна']),
                    new Assert\Positive(['message' => 'Цена должна быть положительной']),
                ],
            ])
            ->add('region', ChoiceType::class, [
                'label' => 'Регион',
                'placeholder' => 'Выберите регион',
                'choices' => [
                    'Île-de-France (Иль-де-Франс)' => 'Île-de-France',
                    'Hauts-de-France (О-де-Франс)' => 'Hauts-de-France',
                    'Grand Est (Гранд-Эст)' => 'Grand Est',
                    'Normandie (Нормандия)' => 'Normandie',
                    'Bretagne (Бретань)' => 'Bretagne',
                    'Pays de la Loire (Пеи-де-ла-Луар)' => 'Pays de la Loire',
                    'Centre-Val de Loire (Центр — Долина Луары)' => 'Centre-Val de Loire',
                    'Bourgogne-Franche-Comte (Бургундия — Франш-Конте)' => 'Bourgogne-Franche-Comte',
                    'Auvergne-Rhône-Alpes (Овернь — Рона — Альпы)' => 'Auvergne-Rhône-Alpes',
                    'Nouvelle-Aquitaine (Новая Аквитания)' => 'Nouvelle-Aquitaine',
                    'Occitanie (Окситания)' => 'Occitanie',
                    "Provence-Alpes-Côte d'Azur (Прованс — Альпы — Лазурный Берег)" => "Provence-Alpes-Cote d'Azur",
                    'Corse (Корсика)' => 'Corse',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Регион обязателен']),
                ],
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Категория',
                'placeholder' => 'Выберите категорию',
                'choices' => [
                    'Недвижимость' => 'Недвижимость',
                    'Ищу работу' => 'Ищу работу',
                    'Ищу жилье' => 'Ищу жилье',
                    'Работа' => 'Работа',
                    'Продажа авто' => 'Продажа авто',
                    'Ремонт' => 'Ремонт',
                    'Красота' => 'Красота',
                    'Рестораны' => 'Рестораны',
                    'Мебель' => 'Мебель',
                    'Вещи' => 'Вещи',
                    'Животные' => 'Животные',
                    'Перевозки' => 'Перевозки',
                    'Прочее' => 'Прочее',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Категория обязательна']),
                ],
            ])
            ->add('city', TextType::class, [
                'label' => 'Город',
                'attr' => [
                    'placeholder' => 'Например, Nice',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Город обязателен']),
                    new Assert\Length([
                        'max' => 100,
                        'maxMessage' => 'Город не должен превышать 100 символов',
                    ]),
                ],
            ])
            ->add('poste_code', TextType::class, [ // оставить как в сущности; можно переименовать позже
                'label' => 'Почтовый индекс',
                'attr' => [
                    'placeholder' => 'Например, 06000',
                    'inputmode' => 'numeric',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Почтовый индекс обязателен']),
                    new Assert\Length([
                        'max' => 10,
                        'maxMessage' => 'Почтовый индекс не должен превышать 10 символов',
                    ]),
                ],
            ])
            ->add('images', FileType::class, [
                'label' => 'Фотографии (до 5 шт.)',
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'attr' => [
                    'accept' => 'image/jpeg,image/png,image/webp',
                ],
                'help' => 'JPG, PNG или WEBP, до 6 МБ. Максимум 5 фото.',
                'constraints' => [
                    new Assert\Count([
                        'max' => 5,
                        'maxMessage' => 'Можно загрузить не более 5 фото',
                    ]),
                    new Assert\All([
                        new Assert\File([
                            'maxSize' => '6M',
                            'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                            'mimeTypesMessage' => 'Разрешены только JPG, PNG и WEBP (до 6 МБ).',
                        ]),
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ads::class,
        ]);
    }
}
