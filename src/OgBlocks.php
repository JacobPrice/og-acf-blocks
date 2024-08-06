<?php

namespace OgBlocks;

use Generator;
use Timber\Timber;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class OgBlocks
{
    private static $instance = null;
    private array $config = [];
    private array $block_files = [];

    private function __construct(){}

    private static function get_instance()
    {
        if (self::$instance == null) {
            self::$instance = new OgBlocks();
            add_action('init', [self::$instance, 'register']);
            self::$instance::register_autoloader();
        }
        return self::$instance;
    }

    public static function init()
    {
        $instance = self::get_instance();
        return $instance;
    }

    public static function get_block_files()
    {
        $instance = self::get_instance();
        $blocks_dir = apply_filters('og/blocks/dir', get_stylesheet_directory() . '/blocks');
        if (!$blocks_dir)
            return [];

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
    private static function register_autoloader()
    {
        spl_autoload_register(function ($class) {
            $namespace = self::config('namespace');
            $dir_path = self::config('dir_path');
            $class = str_replace($namespace, '', $class);
            $class = str_replace('\\', '/', $class);
            $class = $dir_path . $class . '.php';
            if (file_exists($class)) {
                include $class;
            }
        });
    }
    public function register()
    {
        $blocks = self::get_block_files();
        foreach ($blocks as $block_file) {
            $block_data = wp_json_file_decode($block_file, ['associative' => true]);
            $this->register_fields_from_metadata($block_data);
            $this->add_block_category($block_data);
            $this->update_block_data($block_data, $block_file);
            $this->register_block($block_file);
        }

        $this->register_locations();

    }

    public function register_fields_from_metadata($metadata)
    {
        if (!isset($metadata['og']['fields'])) {
            return;
        }
        // we need to register the fields
        $fields = $metadata['og']['fields'];
        foreach ($fields as $key => $field) {
            if (!isset($field['type'])) {
                continue;
            }
            acf_add_local_field_group(
                [
                    'key' => $metadata['name'],
                    'title' => $metadata['title'],
                    'location' => [
                        [
                            [
                                'param' => 'block',
                                'operator' => '==',
                                'value' => $metadata['name'],
                            ],
                        ],
                    ],
                    'fields' => [
                        [
                            'key' => $key,
                            'label' => $field['label'],
                            'name' => $key,
                            'type' => $field['type'],
                            'instructions' => $field['instructions'] ?? '',
                            'required' => $field['required'] ?? 0,
                            'default_value' => $field['default_value'] ?? '',
                            'placeholder' => $field['placeholder'] ?? '',
                            'prepend' => $field['prepend'] ?? '',
                            'append' => $field['append'] ?? '',
                            'maxlength' => $field['maxlength'] ?? '',
                            'readonly' => $field['readonly'] ?? 0,
                            'disabled' => $field['disabled'] ?? 0,
                            'class' => $field['class'] ?? '',
                            'wrapper' => [
                                'width' => $field['width'] ?? '',
                                'class' => $field['wrapper_class'] ?? '',
                                'id' => $field['wrapper_id'] ?? '',
                            ],
                            'choices' => $field['choices'] ?? [],
                            'conditional_logic' => $field['conditional_logic'] ?? [],
                            'parent' => $field['parent'] ?? 0,
                            'layout' => $field['layout'] ?? 'vertical',
                            'button_label' => $field['button_label'] ?? 'Add Row',
                            'min' => $field['min'] ?? '',
                            'max' => $field['max'] ?? '',
                            'collapsed' => $field['collapsed'] ?? '',
                            'sub_fields' => $field['sub_fields'] ?? [],
                        ],
                    ],
                ]
            );
        }
    }

    public function register_locations()
    {
        add_filter('timber/locations', function ($paths) {
            $paths[] = [self::config('dir_path')];
            return $paths;
        });
    }
    private function add_block_category($block_data)
    {
        if (isset($block_data['category']))
            return;

        add_filter('block_categories_all', function ($categories) {
            return array_merge(
                $categories,
                [
                    [
                        'slug' => self::config('default_category'),
                        'title' => 'Custom Blocks',
                        'icon' => 'admin-settings'
                    ]
                ]
            );
        });
    }
    private static function make_class_name(string $block_file)
    {
        $namespace = self::config('namespace');

        $block_class = str_replace('.json', '', $block_file);
        $block_class = str_replace(self::config('dir_path'), '', $block_class);
        $block_class = str_replace('/', '\\', $block_class);
        $block_class = str_replace('block', 'Block', $block_class);
        $block_class = $namespace . $block_class;
        $block_class = ltrim($block_class, '\\');
        $block_class = '\\' . $block_class;

        return $block_class;
    }
    private function register_block(string $block_file)
    {
        return register_block_type($block_file);
    }

    private function update_block_data($block_data, $block_file)
    {
        add_filter('block_type_metadata', function ($metadata) use ($block_data, $block_file) {
            if (isset($metadata['name'], $block_data['name']) && $metadata['name'] === $block_data['name']) {
                $class_name = self::make_class_name($block_file);
                if (class_exists($class_name)) {
                    $metadata['acf']['renderCallback'] = (function ($block_data, $content, $is_preview, $post_id, $wp_block, $context, ...$args) use ($class_name, $block_file) {
                        $dir_path = self::config('dir_path');
                        $theme_path = get_stylesheet_directory();
                        $block_dir_path = str_replace($theme_path, '', $dir_path);

                        $rel_path = str_replace($dir_path, '', $block_file);
                        $rel_path = str_replace('block.json', '', $rel_path);

                        $block_css_uri = get_template_directory_uri() . $block_dir_path . $rel_path;
                        $block_css_path = get_template_directory() . $block_dir_path . $rel_path;

                        $slug = str_replace('/', '-', $block_data['name']);

                        $editor_css = $block_css_uri . 'style-editor.css';
                        $view_css = $block_css_uri . 'style.css';

                        $editor_js = $block_css_uri . 'script-editor.js';
                        $view_js = $block_css_uri . 'script.js';

                        if (is_admin()) {
                            add_action('enqueue_block_assets', function () use ($block_css_path, $slug, $editor_css, $editor_js) {
                                if (file_exists($block_css_path . 'style-editor.css')) {
                                    wp_enqueue_style($slug . '-editor', $editor_css, [], null, 'all');
                                }

                                if (file_exists($block_css_path . 'script-editor.js')) {
                                    wp_enqueue_script($slug . '-editor', $editor_js, [], null, true);
                                }
                            });
                        }
                        wp_enqueue_style($slug, $view_css, [], null, 'all');
                        wp_enqueue_script($slug, $view_js, [], null, true);


                        $editor_twig = $rel_path . 'editor.twig';
                        $view_twig = $rel_path . 'index.twig';

                        $view_twig = wp_normalize_path($this->config('dir_path') . $view_twig);
                        $editor_twig = wp_normalize_path($this->config('dir_path') . $editor_twig);
                        if (!file_exists($editor_twig)) {
                            $editor_twig = $view_twig;
                        }

                        $fields = array_filter($block_data['data'], function ($key) {
                            return strpos($key, '_') !== 0;
                        }, ARRAY_FILTER_USE_KEY);

                        $block_context = [
                            'fields' => $fields,
                            'block' => [
                                'metadata' => $block_data,
                                'content' => $content,
                                'is_preview' => $is_preview,
                                'post_id' => $post_id,
                                'wp' => $wp_block,
                                'context' => $context,
                                'rel_path' => $rel_path,
                                'dir_path' => $dir_path,
                                'slug' => $slug,
                                'twig_template' => is_admin() ? $editor_twig : $view_twig,
                                'css_classes' => trim(
                                    implode(
                                        ' ',
                                        [
                                            $block_data['align'] ? 'align' . $block_data['align'] : '',
                                            $block_data['className'] ?? '',
                                        ]
                                    )
                                ),
                                'unique_slug' => $slug . '-' . ($block_data['id'] ?? uniqid()),

                            ]
                        ];


                        $context = Timber::context($block_context);

                        $Block = new $class_name(
                            $context
                        );
                        $Block->render();
                    });
                }
                return $metadata;
            } else {
                return $metadata;
            }
        });
    }
}
