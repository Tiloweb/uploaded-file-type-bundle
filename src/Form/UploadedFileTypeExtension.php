<?php

declare(strict_types=1);

namespace Tiloweb\UploadedFileTypeBundle\Form;

use Exception;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Tiloweb\UploadedFileTypeBundle\UploadedFileTypeService;

use function is_string;
use function sprintf;

use const PATHINFO_FILENAME;
use const UPLOAD_ERR_OK;

/**
 * Extends FileType to handle automatic file uploads.
 *
 * @author Thibault HENRY <thibault@henry.pro>
 */
final class UploadedFileTypeExtension extends AbstractTypeExtension
{
    private PropertyAccessorInterface $propertyAccessor;

    public function __construct(
        private readonly UploadedFileTypeService $uploadedFileTypeService,
    ) {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    public static function getExtendedTypes(): iterable
    {
        return [FileType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined([
            'upload',
            'filename',
            'delete_previous',
        ]);

        $resolver->setDefault('filename', $this->getDefaultFilenameCallback());
        $resolver->setDefault('mapped', false);
        $resolver->setDefault('delete_previous', true);

        $resolver->setAllowedTypes('upload', ['string', 'null']);
        $resolver->setAllowedTypes('filename', ['callable']);
        $resolver->setAllowedTypes('delete_previous', ['bool']);

        $resolver->setInfo('upload', 'The upload configuration name to use');
        $resolver->setInfo('filename', 'A callable to generate the filename: fn(UploadedFile $file, mixed $item): string');
        $resolver->setInfo('delete_previous', 'Whether to delete the previous file when uploading a new one');
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if (!isset($options['upload'])) {
            return;
        }

        $data = $form->getParent()?->getData();
        if ($data === null) {
            return;
        }

        try {
            $url = $this->propertyAccessor->getValue($data, $form->getName());
        } catch (Exception) {
            return;
        }

        if (is_string($url) && $url !== '') {
            $view->vars['url'] = $url;
            $view->vars['upload_configuration'] = $options['upload'];
            $view->vars['required'] = false;
        }
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!isset($options['upload'])) {
            return;
        }

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event): void {
                $uploadedFile = $event->getData();
                $item = $event->getForm()->getParent()?->getData();

                if (!$uploadedFile instanceof UploadedFile) {
                    return;
                }

                if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
                    return;
                }

                $formConfig = $event->getForm()->getConfig();
                $configuration = $formConfig->getOption('upload');
                $filenameCallback = $formConfig->getOption('filename');
                $deletePrevious = $formConfig->getOption('delete_previous');

                $filename = $filenameCallback($uploadedFile, $item);

                // Delete previous file if configured
                if ($deletePrevious && $item !== null) {
                    $this->deletePreviousFile($event->getForm(), $item, $configuration);
                }

                $url = $this->uploadedFileTypeService->upload(
                    $filename,
                    $uploadedFile,
                    $configuration,
                );

                if ($url !== null && $item !== null) {
                    try {
                        $this->propertyAccessor->setValue($item, $event->getForm()->getName(), $url);
                    } catch (Exception) {
                        // Property might not be writable
                    }
                }
            },
            priority: -10,
        );
    }

    private function deletePreviousFile(FormInterface $form, mixed $item, ?string $configuration): void
    {
        try {
            $previousUrl = $this->propertyAccessor->getValue($item, $form->getName());

            if (is_string($previousUrl) && $previousUrl !== '') {
                $this->uploadedFileTypeService->delete($previousUrl, $configuration);
            }
        } catch (Exception) {
            // Ignore errors during deletion
        }
    }

    private function getDefaultFilenameCallback(): callable
    {
        return static function (UploadedFile $file, mixed $item): string {
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $guessedExtension = $file->guessClientExtension();
            $extension = $guessedExtension ?? ($file->getClientOriginalExtension() ?: 'bin');

            // Generate a unique hash
            $hash = bin2hex(random_bytes(8));

            // Sanitize the original filename
            $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName) ?? 'file';
            $safeName = mb_strtolower(trim($safeName, '_'));

            if ($safeName === '') {
                $safeName = 'file';
            }

            return sprintf('%s_%s.%s', $safeName, $hash, $extension);
        };
    }
}
