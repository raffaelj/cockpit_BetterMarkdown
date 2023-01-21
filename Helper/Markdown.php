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

        $config = \array_replace_recursive(
            $default,
            $this->app->retrieve('config/bettermarkdown', [])
        );

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
                $this->parser = new \ParsedownTasks();
                $this->parser->options = \array_merge(
                    $this->parser->options ?? [],
                    $config['toc'] ?? [],
                    $config['tasks'] ?? []
                );
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
                $_toc = $this->parser->contentsList('array');

                if ($this->config['cached_toc_format'] == 'tree') {
                    $_toc = $this->buildTreeFromToc($_toc);
                }

                if ($rebuild && $this->app->filestorage->has($cachepathToc)) {
                    $this->app->filestorage->delete($cachepathToc);
                }

                $toc = \json_encode($_toc);

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
