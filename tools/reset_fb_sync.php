<?php
global $wpdb;
$sql = "UPDATE {$wpdb->postmeta} SET meta_value = '0' WHERE meta_key = 'facebook_sync' AND meta_value = '1'";
$wpdb->query($sql);
echo "done";
