UploadedFileType Bundle
=======================

Don't handle the upload, storage, and access logic of your entities images !
Just point where you want to upload the file, and only store the URL of it.


Installation
============

Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Applications that use Symfony Flex
----------------------------------

Open a command console, enter your project directory and execute:

```console
$ composer require tiloweb/uploaded-filetype-bundle
```

Applications that don't use Symfony Flex
----------------------------------------

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require tiloweb/uploaded-file-type-bundle
```

### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
// config/bundles.php

return [
    // ...
    Tiloweb\UploadedFileTypeBundle\UploadedFileTypeBundle::class => ['all' => true],
];
```

Configuration
=============

### Step 1 : Configure your filesystem
Use the [OneUp FlySystem](https://github.com/1up-lab/OneupFlysystemBundle) bundle to configure the filesystem you want to work with.

### Step 2 : create a default configuration

```yaml
# config/package/uploaded_file_type.yml
uploaded_file_type:
  configurations:
    default:
      filesystem: 'oneup_flysystem.your_filesystem'
      base_uri: 'https://www.exemple.com/upload'
      path: '/image'
```

You can create many configurations, here, you will use the `default` configuration :

- ``filesystem`` : the alias to the OneUp FlySystem you want to use.
- ``base_uri`` : the URL to access to the root of your filesystem.
- ``path`` : The folder you want to upload your file to.

Usage
=====

Simple as 🦆 !

Juste create a Form with a FileType field, with the option `upload` :

```php
public function buildForm(FormBuilderInterface $builder, array $options)
{
    $builder
        ->add('image', FileType::class, [
            'upload' => 'default'
        ])
    ;
}
```

When the form will be submited, the file will be uploaded by the filesystem of the ``default`` configuration, the URL of the file will be constructed and stored in your ``$image`` field.

You are able to change the naming strategy of your file once stored on your filesystem. To do so, you can add a ``filename`` option to your `FileType` pointing to an enclosure taking 2 parameters : 

1. ``UploadedFile $file`` will contain the file uploaded by through form
2. ``UploadedFile $item`` will contain the data object of your form.

By default, the naming strategy is :

```php
public function buildForm(FormBuilderInterface $builder, array $options)
{
    $builder
        ->add('image', FileType::class, [
            'upload' => 'default',
            'filename' => function(UploadedFile $file, $item) {
                $filename = $file->getClientOriginalName();
    
                $filename = str_replace(
                    '.' . $file->guessClientExtension(),
                    '.' . md5(microtime().rand(0, 1000)) . '.' . $file->guessClientExtension(),
                    $filename
                );
    
                return $filename;
            }
        ])
    ;
}
```


Example
=======

Here, you will create a form in order to create a ``Retail`` entity with a logo that you want to store on the server.

```php
<?php
# src/Form/RetailType.php

namespace App\Form;

use App\Entity\Retail;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RetailType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('label', TextType::class)
            ->add('logo', FileType::class, [
                'upload' => 'default'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Retail::class,
        ]);
    }
}

```

```php
<?php
# src/Entity/Retail.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Retail
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $label = '';

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $logo = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): self
    {
        $this->logo = $logo;

        return $this;
    }
}


```


```php
<?php
# src/Entity/Retail.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 */
class Retail
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @Assert\NotBlank
     * @ORM\Column(type="string", length=255)
     */
    private string $label = '';

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $logo = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): self
    {
        $this->logo = $logo;

        return $this;
    }
}
```

```php
<?php

namespace App\Controller;

use App\Entity\Retail;
use App\Form\RetailType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    /**
     * @Route("/{retail}", name="app_edit")
     */
    public function form(Retail $retail, Request $request)
    {
        $form = $this->createForm(RetailType::class, $retail);
        $form->handleRequest($this->request);

        if($form->isSubmitted() && $form->isValid()) {
            // ...
        }

        // ...
    }
}

```

```yaml
# config/packages/oneup_flysystem.yaml
# Read the documentation: https://github.com/1up-lab/OneupFlysystemBundle
oneup_flysystem:
  adapters:
    default_adapter:
      local:
        location: '%kernel.project_dir%/public/upload'
        
  filesystems:
    default_filesystem:
      adapter: default_adapter
      alias: League\Flysystem\Filesystem

```

```yaml
# config/packages/uploaded_file_type.yaml
uploaded_file_type:
  configurations:
    retail:
      filesystem: 'oneup_flysystem.default_filesystem'
      base_uri: 'https://www.example.com/upload'
      path: '/retail'
```

If you submit the form after having selected on your computer the image ``logo.png``, the file will be stored in ``public/upload/retail/logo.ee2a6cd0ed54b0f9e625698ae909d7ff.png`` and the ``Retail::$logo`` will store the URL `https://www.example.com/upload/retail/logo.ee2a6cd0ed54b0f9e625698ae909d7ff.png`.

Reporting an issue or a feature request
=======================================

Issues and feature requests are tracked in the [Github issue tracker](https://github.com/Tiloweb/UploadedFileType/issues).

When reporting a bug, it may be a good idea to reproduce it in a basic project
built using the [symfony/website-skeleton](https://symfony.com/doc/current/setup.html#creating-symfony-applications)
to allow developers of the bundle to reproduce the issue by simply cloning it
and following some steps.
