<?php

require_once(__DIR__.'/lib/vendor/autoload.php');

$this->helpers['markdown'] = 'BetterMarkdown\\Helper\\Markdown';

$this->module('cockpit')->extend([

    // override core markdown function
    'markdown' => function($content, $extra = true) {

        return $this->app->helper('markdown')->text($content);

    },

]);
