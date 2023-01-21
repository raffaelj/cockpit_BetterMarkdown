<?php
/**
 * Add task list (checkbox) support to Parsedown, ParsedownExtra and ParsedownToc
 */

if (class_exists('ParsedownToc')) {
    class_alias('ParsedownToc', 'ParsedownTasksParentAlias');
} elseif (class_exists('ParsedownExtra')) {
    class_alias('ParsedownExtra', 'ParsedownTasksParentAlias');
} else {
    class_alias('Parsedown', 'ParsedownTasksParentAlias');
}

class ParsedownTasks extends ParsedownTasksParentAlias {

    const VERSION = '0.1.0';

    public $options = [
        'classUnchecked' => '',
        'classChecked'   => '',
    ];

    public function __construct($options = null) {

        if (is_callable('parent::__construct')) {
            parent::__construct();
        }

        if ($options && is_array($options)) {
            $this->options = array_merge($this->options, $options);
        }

        $this->BlockTypes['['][] = 'Task';

    }

    protected function blockTask($line, $block) {

        if ($block) return;

        $beginLine = substr($line['text'], 0, 4);

        $isCheckboxChecked   = '[x] ' === $beginLine;
        $isCheckboxUnchecked = '[ ] ' === $beginLine;

        $isCheckbox = $isCheckboxChecked || $isCheckboxUnchecked;

        if (!$isCheckbox) return;

        return [
            'text'    => substr($line['text'], 3),
            'checked' => $isCheckboxChecked,
        ];

    }

    protected function blockTaskContinue($line, $block) {}

    protected function blockTaskComplete($block) {

        $checkedAttr = $block['checked'] ? ' checked' : '';

        $block['markup']  = '<input type="checkbox" disabled'.$checkedAttr.' />';
        $block['markup'] .= $this->line($block['text']);

        return $block;

    }

    /**
     * Add css classes to `li` elements of task lists
     */
    protected function blockListComplete(array $Block) {

        if (empty($this->options['classUnchecked']) && empty($this->options['classChecked'])) {
            return $Block;
        }

        foreach ($Block['element']['text'] as &$liElement) {

            foreach ($liElement['text'] as $text) {

                $beginLine = substr($text, 0, 4);

                $isCheckboxChecked   = '[x] ' === $beginLine;
                $isCheckboxUnchecked = '[ ] ' === $beginLine;

                $isCheckbox = $isCheckboxChecked || $isCheckboxUnchecked;

                if (!$isCheckbox) continue;

                if ($isCheckboxChecked   && empty($this->options['classChecked']))   continue;
                if ($isCheckboxUnchecked && empty($this->options['classUnchecked'])) continue;

                $classes = '';

                if (!empty($liElement['attributes']['class'])) {
                    $classes .= ' ';
                }

                $classes .= $isCheckboxChecked ? $this->options['classChecked'] : $this->options['classUnchecked'];

                $liElement['attributes']['class'] = $classes;

            }
        }

        return $Block;

    }

}
