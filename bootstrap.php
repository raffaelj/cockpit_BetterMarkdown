<?php
/**
 * Cache, task lists and ToC support for markdown conversion with Cockpit CMS v1
 *
 * @version   0.2.0
 * @author    Raffael Jesche
 * @license   MIT
 *
 * @see       https://github.com/raffaelj/cockpit_BetterMarkdown
 * @see       https://github.com/agentejo/cockpit/
 */

require_once(__DIR__.'/lib/vendor/autoload.php');

$this->helpers['markdown'] = 'BetterMarkdown\\Helper\\Markdown';

$this->module('cockpit')->extend([

    // override core markdown function
    'markdown' => function($content, $extra = true) {

        return $this->app->helper('markdown')->text($content);

    },

]);
