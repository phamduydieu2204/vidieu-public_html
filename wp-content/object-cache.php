<?php
/**
 * Disabled Object Cache Drop-In
 *
 * This file prevents Redis or any external object cache from being used.
 * The server does not have Redis service available.
 */

// Prevent direct access
defined('ABSPATH') || exit;

// Return false for all cache support checks
function wp_cache_supports($feature) {
    return false;
}

// Dummy cache functions that do nothing
function wp_cache_add($key, $value, $group = '', $expiration = 0) {
    return false;
}

function wp_cache_close() {
    return true;
}

function wp_cache_decr($key, $offset = 1, $group = '') {
    return false;
}

function wp_cache_delete($key, $group = '') {
    return false;
}

function wp_cache_flush() {
    return false;
}

function wp_cache_flush_group($group) {
    return false;
}

function wp_cache_get($key, $group = '', $force = false, &$found = null) {
    $found = false;
    return false;
}

function wp_cache_incr($key, $offset = 1, $group = '') {
    return false;
}

function wp_cache_init() {
    return;
}

function wp_cache_replace($key, $value, $group = '', $expiration = 0) {
    return false;
}

function wp_cache_set($key, $value, $group = '', $expiration = 0) {
    return false;
}

function wp_cache_switch_to_blog($blog_id) {
    return;
}

function wp_cache_add_global_groups($groups) {
    return;
}

function wp_cache_add_non_persistent_groups($groups) {
    return;
}