# Wp Blocks

## Installation
```bash
composer require jacobprice/wp-blocks
```
## Configuration

```php


use OgBlocks\OgBlocks;

$og_blocks = new OgBlocks();
$og_blocks::config([
    /**
     * Path
     * The path to the blocks directory
     * this is where the blocks will be loaded from
     */
    'dir_path' => __DIR__ . '/blocks',
    /**
     * Namespace
     * The namespace for the blocks
     * this will be used to register the blocks
     * and to load the blocks
     */
    'namespace' => 'Lpd\\Cleanguru\\Blocks',
    /**
     * Default Icon
     * If a block.json does not contain an icon
     * it will default to this icon,
     * can be an svg or a dashicon
    */
    'default_icon' => 'admin-settings',
    /**
     * Extending Twig
     * Add custom functions and filters to twig
     * @see https://timber.github.io/docs/v2/guides/extending-twig/
     */
    'twig' => [
        'functions' =>[

        ],
        'filters' => [

        ]
    ]
    /**
     * Timber Models
     * the post classmap for timber
     * @see https://timber.github.io/docs/v2/guides/class-maps/#the-post-class-map
     */
    'timber_models' => [
        'page' => \Lpd\Cleanguru\Models\Page::class
    ]
]);

$wp_blocks::init();
```
