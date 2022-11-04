<?php

namespace BetterMarkdown\Helper;

class Markdown extends \Lime\Helper {

    protected $parser;

    public $config;

    public function initialize() {

        $default = [
            'parser'            => 'extended',
            'cache'             => true,
            'cached_toc_format' => 'flat',
        ];

        $config = \array_replace_recursive($default, $this->app->retrieve('config/bettermarkdown', []));

        $this->config = $config;

        switch ($config['parser']) {

            case 'parsedown':
                $this->parser = new \Parsedown();
                break;

            case 'extra':
                $this->parser = new \ParsedownExtra();
                break;

            case 'extended':
            default:
                $this->parser = new ParsedownCheckbox();
                $this->parser->options = \array_merge($this->parser->options, $config['toc'] ?? []);
                break;

        }

    }

    public function text($text) {

        return $this->render($text, !$this->config['cache']);

    }

    // cache rendered md files
    public function render($text, $rebuild = false) {

        $hash = \md5($text);

        $cachepath    = "tmp:///{$hash}.md.html";
        $cachepathToc = "tmp:///{$hash}.md.toc.json";

        if ($rebuild || !$this->app->filestorage->has($cachepath)) {

            $html = $this->parser->text($text);

            if ($rebuild && $this->app->filestorage->has($cachepath)) {
                $this->app->filestorage->delete($cachepath);
            }

            $this->app->filestorage->write($cachepath, $html);

            if ($this->config['parser'] == 'extended') {

                // store also toc as json in tmp folder
                $toc = $this->parser->contentsList('array');

                if ($this->config['cached_toc_format'] == 'tree') {
                    $toc = \json_encode($this->buildTreeFromToc($toc));
                }

                if ($rebuild && $this->app->filestorage->has($cachepathToc)) {
                    $this->app->filestorage->delete($cachepathToc);
                }

                $this->app->filestorage->write($cachepathToc, $toc);
            }

            return $html;

        }

        return $this->app->filestorage->read($cachepath);

    }

    public function buildTreeFromToc($toc) {

        if (empty($toc)) return $toc;

        // reformat toc from ParsedownToC
        foreach ($toc as &$v) {

            $v['depth'] = (int) substr($v['level'], 1, 2);
            $v['url']   = '#'.$v['id'];

            // replace e. g. `text` with `title` to match existing menu template
            if (isset($this->config['tree_toc']['replace_keys']) && \is_array($this->config['tree_toc']['replace_keys'])) {
                foreach ($this->config['tree_toc']['replace_keys'] as $orig => $replace) {
                    $v[$replace] = $v[$orig];
                    unset($v[$orig]);
                }
            }

            // unset some keys to keep the data small and clean
            if (isset($this->config['tree_toc']['unset_keys']) && \is_array($this->config['tree_toc']['unset_keys'])) {
                foreach ($this->config['tree_toc']['unset_keys'] as $key) {
                    unset($v[$key]);
                }
            }

        }
        unset($v);

        // inspired by: https://stackoverflow.com/a/14963270
        $d           = $toc[0]['depth'] -1;
        $tmp         = [];
        $parent      = &$tmp;
        $parents[$d] = &$parent;

        foreach ($toc as $k => $v) {

            // same/increasing depth
            if ($d <= $v['depth']) {
                $child = $v; unset($child['depth']);
                $parent['children'][] = $child;
            }

            // increasing depth
            if ($d < $v['depth']) {
                $parents[$d] = &$parent;
            }

            // decreasing depth
            if ($d > $v['depth']) {
                $parent = &$parents[$v['depth']-1];

                $child = $v; unset($child['depth']);
                $parent['children'][] = $child;
            }

            // look ahead and prepare parent in increasing
            if (isset($toc[$k+1]) && $toc[$k+1]['depth'] > $v['depth']) {
                $last_insert_idx = \count($parent['children'])-1;
                $parent = &$parent['children'][$last_insert_idx];
            }
            $d = $v['depth'];

        }

        return $tmp['children'];
    }

}

// Because of the weird extension behaviour of Parsedown, I had to copy/paste the original ParsedownCheckbox 
// class with a namespace modification. So running `composer update` will not effect that class without
// manual adjustments.
// source: https://github.com/leblanc-simon/parsedown-checkbox
// author: Simon Leblanc, MIT Licensed

use \ParsedownToC as ParsedownExtra;

/**
 * This file is part of the ParsedownCheckbox package.
 *
 * (c) Simon Leblanc <contact@leblanc-simon.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class ParsedownCheckbox extends ParsedownExtra
{
    const VERSION = '0.2.0';

    public function __construct()
    {
        parent::__construct();

        array_unshift($this->BlockTypes['['], 'Checkbox');
    }

    protected function blockCheckbox($line)
    {
        $text = trim($line['text']);
        $begin_line = substr($text, 0, 4);
        if ('[ ] ' === $begin_line) {
            return [
                'handler' => 'checkboxUnchecked',
                'text' => substr(trim($text), 4),
            ];
        }

        if ('[x] ' === $begin_line) {
            return [
                'handler' => 'checkboxChecked',
                'text' => substr(trim($text), 4),
            ];
        }
    }

    protected function blockListComplete(array $block)
    {
        foreach ($block['element']['elements'] as &$li_element) {
            foreach ($li_element['handler']['argument'] as $text) {
                $begin_line = substr(trim($text), 0, 4);
                if ('[ ] ' === $begin_line) {
                    $li_element['attributes'] = ['class' => 'parsedown-task-list parsedown-task-list-open'];
                } elseif ('[x] ' === $begin_line) {
                    $li_element['attributes'] = ['class' => 'parsedown-task-list parsedown-task-list-close'];
                }
            }
        }

        return $block;
    }

    protected function blockCheckboxContinue(array $block)
    {
    }

    protected function blockCheckboxComplete(array $block)
    {
        $block['element'] = [
            'rawHtml' => $this->{$block['handler']}($block['text']),
            'allowRawHtmlInSafeMode' => true,
        ];

        return $block;
    }

    protected function checkboxUnchecked($text)
    {
        if ($this->markupEscaped || $this->safeMode) {
            $text = self::escape($text);
        }

        return '<input type="checkbox" disabled /> ' . $this->format($text);
    }

    protected function checkboxChecked($text)
    {
        if ($this->markupEscaped || $this->safeMode) {
            $text = self::escape($text);
        }

        return '<input type="checkbox" checked disabled /> ' . $this->format($text);
    }

    /**
     * Formats the checkbox label without double escaping.
     * @param string $text the string to format
     * @return string the formatted text
     */
    protected function format($text)
    {
        // backup settings
        $markup_escaped = $this->markupEscaped;
        $safe_mode = $this->safeMode;

        // disable rules to prevent double escaping.
        $this->setMarkupEscaped(false);
        $this->setSafeMode(false);

        // format line
        $text = $this->line($text);

        // reset old values
        $this->setMarkupEscaped($markup_escaped);
        $this->setSafeMode($safe_mode);

        return $text;
    }
}
