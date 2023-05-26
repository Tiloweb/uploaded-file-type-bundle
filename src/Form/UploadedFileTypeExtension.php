<?php

namespace Tiloweb\UploadedFileTypeBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeExtensionInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Tiloweb\UploadedFileTypeBundle\UploadedFileTypeService;

class UploadedFileTypeExtension implements FormTypeExtensionInterface
{
    private UploadedFileTypeService $uploadedFileTypeService;

    public function __construct(
        UploadedFileTypeService $uploadedFileTypeService
    ) {
        $this->uploadedFileTypeService = $uploadedFileTypeService;
    }

    public static function getExtendedTypes(): iterable
    {
        return [FileType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined('upload');

        $resolver->setDefault('filename', function(UploadedFile $file, $item) {
            $filename = $file->getClientOriginalName();

            $filename = str_replace(
                '.' . $file->guessClientExtension(),
                '.' . md5(microtime().rand(0, 1000)) . '.' . $file->guessClientExtension(),
                $filename
            );

            return $filename;
        });

        $resolver->setDefault('mapped', false);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if(!isset($options['upload'])) {
            return;
        }

        $configuration = $options['upload'];

        $data = $form->getParent()->getData();

        if(!$data) {
            return;
        }

        $accessor = PropertyAccess::createPropertyAccessor();

        $url = $accessor->getValue($data, $form->getName());

        if($url) {
            $view->vars['url'] = $url;
            $view->vars['required'] = false;
        }
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function(FormEvent $event) {

                $uploadedFile = $event->getData();
                $item = $event->getForm()->getParent()->getData();

                if(!$uploadedFile || ($uploadedFile instanceof UploadedFile && $uploadedFile->getError() !== UPLOAD_ERR_OK)) {
                    return;
                }

                $filename = $event->getForm()->getConfig()->getOption('filename')($uploadedFile, $item);

                $url = $this->uploadedFileTypeService->upload(
                    $filename,
                    $uploadedFile,
                    $event->getForm()->getConfig()->getOption('upload')
                );

                if($url) {
                    $accessor = PropertyAccess::createPropertyAccessor();
                    $accessor->setValue($item, $event->getForm()->getName(), $url);
                }
            }
        );
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {

    }
}