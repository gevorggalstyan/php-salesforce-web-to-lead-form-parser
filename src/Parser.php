<?php

namespace GevorgGalstyan\SFW2LParser;

//require_once '../vendor/autoload.php';

class Parser
{
    public function __construct()
    {

    }

    private function get_label($html, $nodes, $index, $node)
    {
        $label = $html->find('label[for=' . $node->name . ']');
        if (isset($label) && sizeof($label) > 0) {
            return $label[0]->innertext;
        } else {
            $label = $node->title;
            if (!$label) {
                if ($index > 1) {
                    $ppos = strpos(
                            $html->innertext,
                            $nodes[$index - 1]->outertext) + strlen($nodes[$index - 1]->outertext);
                    $len = strpos($html->innertext, $nodes[$index]->outertext) - $ppos;
                    return trim(preg_replace('/:|\s\s+/', ' ',
                        str_get_html(substr($html->innertext, $ppos, $len))->plaintext));
                }
            } else {
                return $label;
            }
        }
        return NULL;
    }

    public static function parse($html_file)
    {
        $html = new \simple_html_dom();
        $html->load_file('web-to-lead-full.html');
        $forms = $html->find('form');
        $form_method = 'POST';
        foreach ($forms as $form) {
            if (!isset($form_action) &&
                isset($form->action) &&
                isset($form->method) &&
                $form->method === $form_method
            ) {
                $form_action = $form->action;
            }
        }
        if (!isset($form_action)) {
            throw new \Exception('Form action URL not found');
        }
        $nodes = $html->find('input, select, textarea');
        $data_structure = [];
        foreach ($nodes as $index => $node) {
            if ($node->type != 'hidden' && $node->type != 'submit') {
                $data_structure[$node->name] = [
                    'tag' => $node->tag
                ];
                switch ($node->tag) {
                    case 'input':
                        $data_structure[$node->name]['label'] = self::get_label($html, $nodes, $index, $node);
                        break;
                    case 'select':
                        $data_structure[$node->name]['label'] = self::get_label($html, $nodes, $index, $node);
                        $options = $node->find('option');
                        $options_array = [];
                        foreach ($options as $option) {
                            $options_array[] = [
                                'value' => $option->value,
                                'text' => $option->innertext];
                        }
                        $data_structure[$node->name]['options'] = [$options_array];
                        break;
                    case 'textarea':
                        $data_structure[$node->name]['label'] = self::get_label($html, $nodes, $index, $node);
                        break;
                    default:
                        // This cannot happen
                }
            }
        }
        return $data_structure;
    }
}