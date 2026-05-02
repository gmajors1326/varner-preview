<?php
/**
 * Block Template: Varner Inventory Editor
 * This is the anchor point for the React application.
 */

// Create a unique ID for the React mount point
$block_id = 'varner-editor-' . $block['id'];
?>

<div id="varner-inventory-app" class="varner-editor-block varner-inventory-app-mount"></div>

<style>
    .varner-editor-block {
        min-height: 800px;
        background: #f8fafc;
        border-radius: 0;
        overflow: hidden;
    }
</style>
