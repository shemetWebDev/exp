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
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Webmozart\Assert\Assert as AssertAssert;

class AdsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Название обязательно']),
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'Название не должно превышать 255 символов',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Описание обязательно']),
                ],
            ])
            ->add('price', MoneyType::class, [
                'currency' => 'EUR',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Цена обязательна']),
                    new Assert\Positive(['message' => 'Цена должна быть положительной']),
                ],
            ])
            ->add('region', ChoiceType::class,[
                'choices' => [
                    'Île-de-France (Иль-де-Франс)'=>'Île-de-France',
                    "Hauts-de-France (О-де-Франс)" => "Hauts-de-France",
                    "Grand Est (Гранд-Эст)" =>"Grand Est",
                    "Normandie (Нормандия)" =>"Normandie",
                    "Bretagne (Бретань)"=>"Bretagne",
                    "Pays de la Loire (Пеи-де-ла-Луар)"=>"Pays de la Loire",
                    "Centre-Val de Loire (Центр — Долина Луары)"=>"Centre-Val de Loire",
                    "Bourgogne-Franche-Comte (Бургундия — Франш-Конте)"=>"Bourgogne-Franche-Comte",
                    "Auvergne-Rhône-Alpes (Овернь — Рона — Альпы)"=>"Auvergne-Rhône-Alpes",
                    "Nouvelle-Aquitaine (Новая Аквитания)"=>"Nouvelle-Aquitaine",
                    "Occitanie (Окситания)"=>"Occitanie",
                    "Provence-Alpes-Côte d'Azur (Прованс — Альпы — Лазурный Берег)"=>"Provence-Alpes-Cote d'Azur",
                    "Corse (Корсика)"=>"Corse",
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Регион обязателен'])
                ]
            ])

            ->add('category', ChoiceType::class,[
                'choices' => [
                    'Недвижимость'=>'Недвижимость',
                    'Ищу работу'=>'Ищу работу',
                    'Ищу жилье'=>'Ищу жилье',
                    'Работа'=>'Работа',
                    'Продажа авто'=>'Продажа авто',
                    'Ремонт'=>'Ремонт',
                    'Красота'=>'Красота',
                    'Рестораны'=>'Рестораны',
                    'Мебель'=>'Мебель',
                    'Вещи'=>'Вещи',
                    'Животные'=>'Животные',
                    'Перевозки'=>'Перевозки',
                    'Прочее'=>'Прочее',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Категория обязательна'])
                ]
            ])
            ->add('city', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Город обязателен']),
                    new Assert\Length([
                        'max' => 100,
                        'maxMessage' => 'Город не должен превышать 100 символов',
                    ]),
                ],
            ])
            ->add('poste_code', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Почтовый индекс обязателен']),
                    new Assert\Length([
                        'max' => 10,
                        'maxMessage' => 'Почтовый индекс не должен превышать 10 символов',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ads::class,
        ]);
    }
}
