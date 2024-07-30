<?php

namespace OgBlocks;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

/**
 * Iterator
 * This class is used to iterate over the blocks
 * It is a wrapper around RecursiveIteratorIterator
 * It uses the file system to iterate over the blocks
 * // this class is made to be used here:
    //
    // $blocks_dir = self::config('dir_path');
    // if (!$blocks_dir)
    //     return;

    // if (is_dir($blocks_dir)) {
    //     $iterator = new RecursiveIteratorIterator(
    //         new RecursiveDirectoryIterator($blocks_dir, RecursiveDirectoryIterator::SKIP_DOTS),
    //         RecursiveIteratorIterator::SELF_FIRST
    //     );
 *
 *
 */


class BlocksFileTreeIterator
{

    public function __construct(protected string $path){}

    public function get_files()
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            yield $file;
        }
    }

    public function processFiles()
    {
        foreach ($this->getFiles() as $file) {
            if ($file->isDir()) {
                $this->handleDirectory($file);
            } elseif ($file->isFile()) {
                $this->handleFile($file);
            }
        }
    }

    protected function handleDirectory($dir)
    {
        echo "Directory: " . $dir->getPathname() . "\n";
        // Add your custom logic for directories here
    }

    protected function handleFile($file)
    {
        echo "File: " . $file->getPathname() . "\n";
        // Add your custom logic for files here
        switch ($file->getExtension()) {
            case 'php':
                $this->handlePhpFile($file);
                break;
            case 'js':
                $this->handleJsFile($file);
                break;
            case 'twig':
                $this->handleTwigFile($file);
                break;
            case 'css':
                $this->handleCssFile($file);
                break;
            default:
                $this->handleOtherFile($file);
                break;
        }
    }

    protected function handlePhpFile($file)
    {
        echo "Handling PHP file: " . $file->getPathname() . "\n";
        // Custom logic for PHP files
    }

    protected function handleJsFile($file)
    {
        echo "Handling JS file: " . $file->getPathname() . "\n";
        // Custom logic for JS files
    }

    protected function handleTwigFile($file)
    {
        echo "Handling Twig file: " . $file->getPathname() . "\n";
        // Custom logic for Twig files
    }

    protected function handleCssFile($file)
    {
        echo "Handling CSS file: " . $file->getPathname() . "\n";
        // Custom logic for CSS files
    }

    protected function handleOtherFile($file)
    {
        echo "Handling other file: " . $file->getPathname() . "\n";
        // Custom logic for other files
    }
}
