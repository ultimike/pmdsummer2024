# Markdown Easy Entities
Installs and configures Markdown Easy module for Drupal and creates a content
type and block type ready for Markdown content.

## Configuring Drupal for Recipes

Ensure that your project is using the composer/installers Composer plugin
version 2.3 or later (via `composer show composer/installers`)

Ensure that you are using Drush version 13 or later (via `drush --version`)

## Installing this Recipe

`composer require drupal/markdown_easy_entities`

## Applying this Recipe

From your project root.
Run`drush recipe recipes/markdown_easy_entities`
Run `drush cr`

## Unpacking this Recipe

To unpack this recipe's dependencies to your site's composer.json, in the root
of your project run:

`composer unpack drupal/markdown_easy_entities`
