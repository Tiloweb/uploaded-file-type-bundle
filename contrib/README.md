# Symfony Recipe Contribution

This directory contains the Symfony Flex recipe for this bundle.

## How to Submit the Recipe

1. **Fork the recipes-contrib repository**
   ```bash
   git clone https://github.com/symfony/recipes-contrib.git
   ```

2. **Copy the recipe files**
   ```bash
   cp -r tiloweb/uploaded-file-type-bundle/2.0 recipes-contrib/tiloweb/uploaded-file-type-bundle/
   ```

3. **Create a Pull Request**
   - Go to https://github.com/symfony/recipes-contrib
   - Create a new Pull Request with your changes
   - Follow the contribution guidelines

## Recipe Structure

```
tiloweb/uploaded-file-type-bundle/
└── 2.0/
    ├── manifest.json           # Bundle registration and file copying
    └── config/
        └── packages/
            └── uploaded_file_type.yaml  # Default configuration
```

## Testing the Recipe Locally

Before submitting, you can test the recipe locally:

```bash
# In your Symfony project
composer config extra.symfony.endpoint '["https://api.github.com/repos/YOUR_USERNAME/recipes-contrib/contents/index.json?ref=YOUR_BRANCH", "flex://defaults"]'
composer require tiloweb/uploaded-file-type-bundle
```

## Requirements for Official Recipes

- The package must be published on Packagist
- The package must have a stable release
- The recipe must follow Symfony's coding standards
- The configuration should provide sensible defaults
- Environment variables should be documented

## More Information

- [Symfony Recipes Documentation](https://symfony.com/doc/current/setup/flex.html)
- [Contributing to Recipes](https://github.com/symfony/recipes/blob/main/CONTRIBUTING.md)
