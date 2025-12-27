# UploadedFileType Bundle

[![CI](https://github.com/Tiloweb/uploaded-file-type-bundle/actions/workflows/ci.yml/badge.svg)](https://github.com/Tiloweb/uploaded-file-type-bundle/actions/workflows/ci.yml)
[![Latest Stable Version](https://poser.pugx.org/tiloweb/uploaded-file-type-bundle/v/stable)](https://packagist.org/packages/tiloweb/uploaded-file-type-bundle)
[![Total Downloads](https://poser.pugx.org/tiloweb/uploaded-file-type-bundle/downloads)](https://packagist.org/packages/tiloweb/uploaded-file-type-bundle)
[![License](https://poser.pugx.org/tiloweb/uploaded-file-type-bundle/license)](https://packagist.org/packages/tiloweb/uploaded-file-type-bundle)
[![PHP Version](https://img.shields.io/packagist/php-v/tiloweb/uploaded-file-type-bundle.svg)](https://packagist.org/packages/tiloweb/uploaded-file-type-bundle)

A Symfony bundle that handles file uploads via forms and automatically stores the resulting URL in your entity. No more manual file handling in controllers!

## ✨ Features

- 🚀 **Zero-configuration file uploads** - Just add an option to your form field
- 📦 **Flysystem integration** - Works with any storage backend (local, S3, GCS, SFTP, etc.)
- 🔄 **Automatic URL storage** - The file URL is stored directly in your entity
- 🎯 **Custom naming strategies** - Full control over uploaded file names
- 🗑️ **Auto-cleanup** - Optionally delete previous files when uploading new ones
- 🎨 **Twig integration** - Preview uploaded images in your forms

## 📋 Requirements

| Version | PHP | Symfony |
|---------|-----|---------|
| 2.x     | ≥ 8.1 | 6.4, 7.x, 8.x |
| 1.x     | ≥ 8.1 | 6.x, 7.x |

## 📥 Installation

```bash
composer require tiloweb/uploaded-file-type-bundle
```

If you're not using [Symfony Flex](https://symfony.com/doc/current/setup/flex.html), add the bundle manually:

```php
// config/bundles.php
return [
    // ...
    Tiloweb\UploadedFileTypeBundle\UploadedFileTypeBundle::class => ['all' => true],
];
```

## ⚙️ Configuration

### Step 1: Configure Flysystem

First, configure your filesystem adapter using the [OneupFlysystemBundle](https://github.com/1up-lab/OneupFlysystemBundle):

```yaml
# config/packages/oneup_flysystem.yaml
oneup_flysystem:
    adapters:
        default_adapter:
            local:
                location: '%kernel.project_dir%/public/uploads'

    filesystems:
        default_filesystem:
            adapter: default_adapter
            alias: League\Flysystem\Filesystem
```

### Step 2: Configure Upload Destinations

```yaml
# config/packages/uploaded_file_type.yaml
uploaded_file_type:
    configurations:
        # Default configuration for general uploads
        default:
            filesystem: 'oneup_flysystem.default_filesystem'
            base_uri: 'https://example.com/uploads'
            path: '/files'

        # Separate configuration for user avatars
        avatars:
            filesystem: 'oneup_flysystem.default_filesystem'
            base_uri: 'https://example.com/uploads'
            path: '/avatars'

        # S3 configuration for large files
        documents:
            filesystem: 'oneup_flysystem.s3_filesystem'
            base_uri: 'https://cdn.example.com'
            path: '/documents'
```

### Configuration Options

| Option | Type | Required | Description |
|--------|------|----------|-------------|
| `filesystem` | string | ✅ | The Flysystem filesystem service ID |
| `base_uri` | string | ❌ | The base URL for accessing uploaded files |
| `path` | string | ❌ | The subdirectory path within the filesystem |

## 🚀 Usage

### Basic Usage

Simply add the `upload` option to any `FileType` field:

```php
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('image', FileType::class, [
                'upload' => 'default',  // Use the 'default' configuration
                'required' => false,
            ])
        ;
    }
}
```

That's it! When the form is submitted:
1. The file is uploaded to your configured filesystem
2. The URL is automatically stored in the `$image` property
3. No additional controller code needed!

### Custom Filename Strategy

Control how files are named when uploaded:

```php
$builder->add('avatar', FileType::class, [
    'upload' => 'avatars',
    'filename' => function (UploadedFile $file, User $user): string {
        return sprintf(
            'user_%d_%s.%s',
            $user->getId(),
            bin2hex(random_bytes(8)),
            $file->guessClientExtension() ?? 'bin'
        );
    },
]);
```

### Disable Auto-Cleanup

By default, the previous file is deleted when uploading a new one. Disable this behavior:

```php
$builder->add('document', FileType::class, [
    'upload' => 'documents',
    'delete_previous' => false,  // Keep old files
]);
```

### Available Form Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `upload` | string | - | The configuration name to use |
| `filename` | callable | auto | Custom filename generator |
| `delete_previous` | bool | `true` | Delete previous file on update |

## 📖 Complete Example

### Entity

```php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name = '';

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $image = null;

    // Getters and setters...
    
    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }
}
```

### Form

```php
namespace App\Form;

use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Product Name',
            ])
            ->add('image', FileType::class, [
                'upload' => 'default',
                'required' => false,
                'label' => 'Product Image',
                'constraints' => [
                    new Image([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
```

### Controller

```php
namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProductController extends AbstractController
{
    #[Route('/product/new', name: 'product_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($product);
            $em->flush();

            // $product->getImage() now contains the URL!
            // e.g., "https://example.com/uploads/files/product_a1b2c3d4.jpg"

            return $this->redirectToRoute('product_show', ['id' => $product->getId()]);
        }

        return $this->render('product/new.html.twig', [
            'form' => $form,
        ]);
    }
}
```

### Twig Template

The bundle provides a form theme that displays the current image:

```twig
{# templates/product/new.html.twig #}
{% extends 'base.html.twig' %}

{% block body %}
    <h1>New Product</h1>

    {{ form_start(form) }}
        {{ form_row(form.name) }}
        {{ form_row(form.image) }}  {# Shows current image if exists #}
        <button type="submit">Save</button>
    {{ form_end(form) }}
{% endblock %}
```

## 🎨 Customizing the Form Theme

Override the default template to customize how uploaded files are displayed:

```twig
{# templates/form/uploaded_file.html.twig #}
{% extends '@UploadedFileType/form.html.twig' %}

{% block file_widget %}
    {{ parent() }}
    
    {% if url is defined and url is not null %}
        <div class="uploaded-file-preview">
            {% if url matches '/\\.(jpg|jpeg|png|gif|webp)$/i' %}
                <img src="{{ url }}" alt="Preview" class="preview-image">
            {% else %}
                <a href="{{ url }}" target="_blank">View current file</a>
            {% endif %}
        </div>
    {% endif %}
{% endblock %}
```

Register your custom theme:

```yaml
# config/packages/twig.yaml
twig:
    form_themes:
        - 'form/uploaded_file.html.twig'
```

## 🔧 Advanced Usage

### Using the Service Directly

```php
use Tiloweb\UploadedFileTypeBundle\UploadedFileTypeService;

class MyService
{
    public function __construct(
        private UploadedFileTypeService $uploadService,
    ) {}

    public function uploadFile(UploadedFile $file): string
    {
        return $this->uploadService->upload(
            filename: 'custom-name.pdf',
            uploadedFile: $file,
            configuration: 'documents'
        );
    }

    public function deleteFile(string $url): bool
    {
        return $this->uploadService->delete($url, 'documents');
    }

    public function fileExists(string $url): bool
    {
        return $this->uploadService->exists($url, 'documents');
    }
}
```

### Cloud Storage Examples

#### Amazon S3

```yaml
# config/packages/oneup_flysystem.yaml
oneup_flysystem:
    adapters:
        s3_adapter:
            awss3v3:
                client: Aws\S3\S3Client
                bucket: '%env(AWS_S3_BUCKET)%'
                prefix: uploads

    filesystems:
        s3_filesystem:
            adapter: s3_adapter

# config/packages/uploaded_file_type.yaml
uploaded_file_type:
    configurations:
        s3:
            filesystem: 'oneup_flysystem.s3_filesystem'
            base_uri: 'https://%env(AWS_S3_BUCKET)%.s3.%env(AWS_REGION)%.amazonaws.com'
            path: '/uploads'
```

#### Google Cloud Storage

```yaml
oneup_flysystem:
    adapters:
        gcs_adapter:
            googlecloudstorage:
                client: Google\Cloud\Storage\StorageClient
                bucket: '%env(GCS_BUCKET)%'

    filesystems:
        gcs_filesystem:
            adapter: gcs_adapter

uploaded_file_type:
    configurations:
        gcs:
            filesystem: 'oneup_flysystem.gcs_filesystem'
            base_uri: 'https://storage.googleapis.com/%env(GCS_BUCKET)%'
            path: '/uploads'
```

## 🧪 Testing

```bash
# Run tests
composer test

# Run static analysis
composer phpstan

# Fix coding standards
composer cs-fix
```

## 🤝 Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This bundle is released under the [MIT License](LICENSE).

## 🙏 Credits

- [Thibault HENRY](https://henry.pro) - Creator
- [All Contributors](https://github.com/Tiloweb/uploaded-file-type-bundle/contributors)

## 🔗 Links

- [Documentation](https://github.com/Tiloweb/uploaded-file-type-bundle)
- [Issue Tracker](https://github.com/Tiloweb/uploaded-file-type-bundle/issues)
- [Packagist](https://packagist.org/packages/tiloweb/uploaded-file-type-bundle)
- [OneupFlysystemBundle](https://github.com/1up-lab/OneupFlysystemBundle)
- [Flysystem](https://flysystem.thephpleague.com/)
