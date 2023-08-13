<?php

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Url;

class UrlParserFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('linkUrl', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Url([
                        'message' => 'Указана неверная ссылка.',
                    ])
                ],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Го'
            ]);
    }
}