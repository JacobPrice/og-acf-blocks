<?php
function recursive_block_search($blocks, $block_name)
    {
        $found = array_filter($blocks, function ($block) use ($block_name) {
            return $block['blockName'] == $block_name;
        });
        if ($found) {
            return array_shift($found);
        }
        foreach ($blocks as $block) {
            if (isset($block['innerBlocks'])) {
                $found = recursive_block_search($block['innerBlocks'], $block_name);
                if ($found) {
                    return $found;
                }
            }
        }
        return false;
    }