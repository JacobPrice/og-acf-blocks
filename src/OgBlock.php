<?php

namespace OgBlocks;

use Timber\Timber;
use OgBlocks\OgBlocks;

abstract class OgBlock
{

    public function __construct(public $context)
    {}

    private function data($key, $default = null)
    {
        if (strpos($key, '.') === false) {
            return $this->context[$key] ?? $default;
        }

        $data = $this->context;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($data) || !array_key_exists($segment, $data)) {
                return $default;
            }
            $data = $data[$segment];
        }

        return $data;
    }

    public function render()
    {
        $wrapper = wp_normalize_path(dirname(__DIR__) . '/templates/block-wrapper.twig');
        Timber::render( $wrapper, $this->context);
    }
}
