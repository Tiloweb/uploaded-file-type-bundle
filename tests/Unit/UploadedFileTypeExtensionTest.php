<?php

declare(strict_types=1);

namespace Tiloweb\UploadedFileTypeBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormFactoryBuilder;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tiloweb\UploadedFileTypeBundle\Form\UploadedFileTypeExtension;
use Tiloweb\UploadedFileTypeBundle\Tests\App\TestFilesystem;
use Tiloweb\UploadedFileTypeBundle\UploadedFileTypeService;

final class UploadedFileTypeExtensionTest extends TestCase
{
    private TestFilesystem $filesystem;
    private UploadedFileTypeService $service;
    private FormFactory $formFactory;

    protected function setUp(): void
    {
        $this->filesystem = new TestFilesystem();

        $locator = $this->createMock(ContainerInterface::class);
        $locator->method('has')
            ->willReturnCallback(static fn (string $id): bool => $id === 'test.filesystem');
        $locator->method('get')
            ->willReturnCallback(fn (string $id) => $id === 'test.filesystem' ? $this->filesystem : null);

        $this->service = new UploadedFileTypeService(
            [
                'default' => [
                    'filesystem' => 'test.filesystem',
                    'base_uri' => 'https://cdn.example.com',
                    'path' => '/uploads',
                ],
            ],
            $locator,
        );

        $extension = new UploadedFileTypeExtension($this->service);

        $this->formFactory = (new FormFactoryBuilder())
            ->addTypeExtension($extension)
            ->getFormFactory();
    }

    public function test_extends_file_type(): void
    {
        $extendedTypes = UploadedFileTypeExtension::getExtendedTypes();

        self::assertContains(FileType::class, [...$extendedTypes]);
    }

    public function test_form_with_upload_option(): void
    {
        $form = $this->createTestForm();

        $config = $form->get('image')->getConfig();

        self::assertSame('default', $config->getOption('upload'));
        self::assertFalse($config->getOption('mapped'));
    }

    public function test_form_without_upload_option(): void
    {
        $form = $this->formFactory->createBuilder(FormType::class, new TestEntity())
            ->add('image', FileType::class)
            ->getForm();

        $config = $form->get('image')->getConfig();

        self::assertFalse($config->hasOption('upload') && $config->getOption('upload') !== null);
    }

    public function test_form_view_contains_url_when_entity_has_value(): void
    {
        $entity = new TestEntity();
        $entity->setImage('https://cdn.example.com/uploads/existing.jpg');

        $form = $this->createTestForm($entity);
        $view = $form->createView();

        self::assertArrayHasKey('url', $view['image']->vars);
        self::assertSame('https://cdn.example.com/uploads/existing.jpg', $view['image']->vars['url']);
        self::assertFalse($view['image']->vars['required']);
    }

    public function test_form_view_does_not_contain_url_when_entity_has_no_value(): void
    {
        $entity = new TestEntity();

        $form = $this->createTestForm($entity);
        $view = $form->createView();

        self::assertArrayNotHasKey('url', $view['image']->vars);
    }

    public function test_custom_filename_callback_is_set(): void
    {
        $callbackCalled = false;
        $customCallback = static function (UploadedFile $file, mixed $item) use (&$callbackCalled): string {
            $callbackCalled = true;

            return 'custom_'.$file->getClientOriginalName();
        };

        $form = $this->formFactory->createBuilder(FormType::class, new TestEntity())
            ->add('image', FileType::class, [
                'upload' => 'default',
                'filename' => $customCallback,
            ])
            ->getForm();

        $config = $form->get('image')->getConfig();
        $filenameOption = $config->getOption('filename');

        self::assertIsCallable($filenameOption);

        // Create a temp file to test the callback
        $tempFile = $this->createTempFile('test content');
        $uploadedFile = new UploadedFile($tempFile, 'original.txt', 'text/plain', null, true);

        // Call the callback directly
        $result = $filenameOption($uploadedFile, new TestEntity());

        self::assertSame('custom_original.txt', $result);
        self::assertTrue($callbackCalled);

        unlink($tempFile);
    }

    public function test_delete_previous_option_is_true_by_default(): void
    {
        $form = $this->createTestForm();
        $config = $form->get('image')->getConfig();

        self::assertTrue($config->getOption('delete_previous'));
    }

    public function test_delete_previous_option_can_be_disabled(): void
    {
        $form = $this->formFactory->createBuilder(FormType::class, new TestEntity())
            ->add('image', FileType::class, [
                'upload' => 'default',
                'delete_previous' => false,
            ])
            ->getForm();

        $config = $form->get('image')->getConfig();

        self::assertFalse($config->getOption('delete_previous'));
    }

    public function test_default_filename_generates_unique_name(): void
    {
        $form = $this->createTestForm();
        $config = $form->get('image')->getConfig();
        $filenameCallback = $config->getOption('filename');

        $tempFile = $this->createTempFile('test content');
        $uploadedFile = new UploadedFile($tempFile, 'test-image.jpg', 'image/jpeg', null, true);

        $filename1 = $filenameCallback($uploadedFile, new TestEntity());
        $filename2 = $filenameCallback($uploadedFile, new TestEntity());

        // Filenames should be different (contain unique hash)
        self::assertNotSame($filename1, $filename2);
        // Should contain original name part (with dashes sanitized)
        self::assertStringContainsString('test', $filename1);
        // Should have correct extension
        self::assertStringEndsWith('.jpg', $filename1);

        unlink($tempFile);
    }

    private function createTestForm(?TestEntity $entity = null): FormInterface
    {
        return $this->formFactory->createBuilder(FormType::class, $entity ?? new TestEntity())
            ->add('name', TextType::class)
            ->add('image', FileType::class, [
                'upload' => 'default',
            ])
            ->getForm();
    }

    private function createTempFile(string $content): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        if ($tempFile === false) {
            throw new RuntimeException('Could not create temp file');
        }

        file_put_contents($tempFile, $content);

        return $tempFile;
    }
}

/**
 * Test entity for form tests.
 */
class TestEntity
{
    private string $name = '';
    private ?string $image = null;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;

        return $this;
    }
}
