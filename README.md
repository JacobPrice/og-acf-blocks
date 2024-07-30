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
```php

<?php

namespace OgBlocks;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;

class OgBlocks
{
    private static $instance = null;
    private array $config = [];
    private array $block_files = [];

    private function __construct()
    {
    }

    public static function init(array $config): self
    {
        $instance = self::getInstance();
        $instance->config = $config;
        return $instance;
    }

    public static function config(string $key)
    {
        $instance = self::getInstance();
        return $instance->getConfigValue($key);
    }

    public function register(): void
    {
        $this->registerBlocks();
        $this->registerTimberModels();
        $this->extendTwig();
    }

    private function registerBlocks(): void
    {
        foreach ($this->getBlockFiles() as $blockFile) {
            $this->registerBlock($blockFile);
        }
    }

    private function registerBlock(string $blockFile): void
    {
        $blockData = json_decode(file_get_contents($blockFile), true);
        $namespace = $this->config('namespace');
        $blockName = $namespace . '/' . $blockData['name'];

        register_block_type($blockFile, [
            'render_callback' => [$this, 'renderBlock'],
        ]);

        // Register block assets if they exist
        $this->registerBlockAssets($blockFile, $blockName);
    }

    public function renderBlock($attributes, $content, $block)
    {
        // Implement your block rendering logic here
        // You can use Timber for rendering if needed
    }

    private function registerBlockAssets(string $blockFile, string $blockName): void
    {
        $dir = dirname($blockFile);
        $urlBase = plugins_url('', $blockFile);

        if (file_exists($dir . '/style.css')) {
            wp_register_style(
                $blockName . '-style',
                $urlBase . '/style.css',
                [],
                filemtime($dir . '/style.css')
            );
        }

        if (file_exists($dir . '/script.js')) {
            wp_register_script(
                $blockName . '-script',
                $urlBase . '/script.js',
                ['wp-blocks', 'wp-element'],
                filemtime($dir . '/script.js'),
                true
            );
        }
    }

    private function registerTimberModels(): void
    {
        $models = $this->config('timber_models');
        if (is_array($models)) {
            add_filter('timber/post/classmap', function ($classmap) use ($models) {
                return array_merge($classmap, $models);
            });
        }
    }

    private function extendTwig(): void
    {
        $twig = $this->config('twig');
        if (is_array($twig)) {
            add_filter('timber/twig', function ($twig) {
                $this->addTwigFunctions($twig);
                $this->addTwigFilters($twig);
                return $twig;
            });
        }
    }

    private function addTwigFunctions($twig): void
    {
        $functions = $this->config('twig.functions');
        if (is_array($functions)) {
            foreach ($functions as $name => $function) {
                $twig->addFunction(new \Timber\Twig_Function($name, $function));
            }
        }
    }

    private function addTwigFilters($twig): void
    {
        $filters = $this->config('twig.filters');
        if (is_array($filters)) {
            foreach ($filters as $name => $filter) {
                $twig->addFilter(new \Timber\Twig_Filter($name, $filter));
            }
        }
    }

    private function getBlockFiles(): \Generator
    {
        $blocksDir = $this->config('dir_path');
        if (!$blocksDir || !is_dir($blocksDir)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($blocksDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getFilename() === 'block.json') {
                yield $file->getPathname();
            }
        }
    }

    private static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function getConfigValue(string $key)
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $nestedKey) {
            if (!isset($value[$nestedKey])) {
                return null;
            }
            $value = $value[$nestedKey];
        }

        return $value;
    }
}
```

This implementation includes the following improvements and features:

1. Use of the Singleton pattern for the OgBlocks class.
2. Implementation of an iterator (Generator) to traverse block.json files.
3. Methods for registering blocks, including asset registration.
4. Methods for extending Twig with custom functions and filters.
5. Method for registering Timber models.
6. Improved config retrieval method.

To use this class, you would initialize it as shown in your example:

```php
<?php

use OgBlocks\OgBlocks;

$blocks = OgBlocks::init([
    'dir_path' => dirname(__DIR__) . '/blocks',
    'namespace' => 'Lpd\\Cleanguru\\Blocks',
    'default_icon' => 'admin-settings',
    'twig' => [
        'functions' => [
            'test_function' => function () {
                return 'test';
            }
        ],
        'filters' => [
            'test_filter' => function ($string) {
                return $string . ' test';
            }
        ]
    ],
    'timber_models' => [
        'post' => 'Lpd\\Cleanguru\\Models\\Post',
        'page' => 'Lpd\\Cleanguru\\Models\\Page',
    ]
]);

$blocks->register();
```
```php

<?php

namespace OgBlocks;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Generator;

class OgBlocks
{
    private static $instance = null;
    private array $config = [];
    private array $block_files = [];

    private function __construct() {}

    private static function get_instance()
    {
        if (self::$instance == null) {
            self::$instance = new OgBlocks();
        }
        return self::$instance;
    }

    public static function init(array $config)
    {
        $instance = self::get_instance();
        $instance->config = $config;
        return $instance;
    }

    public static function config(string $key)
    {
        $instance = self::get_instance();
        if (strpos($key, '.') !== false) {
            $keys = explode('.', $key);
            $config = $instance->config;
            foreach ($keys as $key) {
                if (isset($config[$key])) {
                    $config = $config[$key];
                } else {
                    return null;
                }
            }
            return $config;
        }
        return $instance->config[$key] ?? null;
    }

    public static function get_block_files()
    {
        $instance = self::get_instance();
        $blocks_dir = self::config('dir_path');
        if (!$blocks_dir) return [];

        if (empty($instance->block_files)) {
            $instance->block_files = iterator_to_array($instance->get_block_files_generator($blocks_dir));
        }

        return $instance->block_files;
    }

    private function get_block_files_generator(string $directory): Generator
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getFilename() === 'block.json') {
                yield $file->getPathname();
            }
        }
    }

    public function register()
    {
        $blocks = self::get_block_files();
        foreach ($blocks as $block_file) {
            $block_data = json_decode(file_get_contents($block_file), true);
            if ($block_data) {
                $this->register_block($block_data, $block_file);
            }
        }
    }

    private function register_block(array $block_data, string $block_file)
    {
        $namespace = self::config('namespace');
        $default_icon = self::config('default_icon');

        if (!isset($block_data['icon'])) {
            $block_data['icon'] = $default_icon;
        }

        $block_name = $namespace . '/' . $block_data['name'];
        register_block_type($block_name, [
            'render_callback' => function ($attributes, $content, $block) use ($block_data, $block_file) {
                $context = Timber::context();
                $context['attributes'] = $attributes;
                $context['content'] = $content;
                $context['block'] = $block;
                return Timber::compile($block_data['render_template'], $context);
            },
            'attributes' => $block_data['attributes'] ?? [],
            'editor_script' => $block_data['editor_script'] ?? null,
            'editor_style' => $block_data['editor_style'] ?? null,
            'style' => $block_data['style'] ?? null,
            'script' => $block_data['script'] ?? null,
        ]);
    }
}
```