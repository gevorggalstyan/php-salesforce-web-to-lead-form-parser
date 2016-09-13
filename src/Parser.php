<?php

namespace GevorgGalstyan\SFW2LParser;

class Parser
{
    public function __construct()
    {

    }

    public static function get_label($html, $nodes, $index, $node)
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
        if (!is_file($html_file) || !is_readable($html_file)) {
            throw new \Exception($html_file . ' is not a readable file');
        }
        $html = new \simple_html_dom();
        $html->load_file('web-to-lead-full.html');
        $forms = $html->find('form[method=POST]');
        if (sizeof($forms) == 0) {
            throw new \Exception($html_file . ' must contain a form with POST method');
        }
        if (sizeof($forms) > 1) {
            throw new \Exception($html_file . ' must contain only one form');
        }

        $form = $forms[0];

        if (!isset($form->action)) {
            throw new \Exception($html_file . ' must contain form with non-empty action attribute');
        }

        $oid = $form->find('input[name=oid]');

        if (!isset($oid) || $oid == '' || !isset($oid->value) || $oid->value == '') {
            throw new \Exception($html_file . ' must contain oid field');
        }

        $data_structure = [];
        $data_structure['action'] = $form->action;
        $data_structure['oid'] = $oid->value;
        $data_structure['fields'] = [];

        $nodes = $html->find('input, select, textarea');


        foreach ($nodes as $index => $node) {
            if ($node->type != 'hidden' && $node->type != 'submit') {
                $data_structure['fields'][$node->name] = [
                    'tag' => $node->tag
                ];
                switch ($node->tag) {
                    case 'input':
                        $data_structure['fields'][$node->name]['label'] = self::get_label($html, $nodes, $index, $node);
                        break;
                    case 'select':
                        $data_structure['fields'][$node->name]['label'] = self::get_label($html, $nodes, $index, $node);
                        $data_structure['fields'][$node->name]['multiple'] = ($node->multiple == 'multiple');
                        $options = $node->find('option');
                        $options_array = [];
                        foreach ($options as $option) {
                            $options_array[] = [
                                'value' => $option->value,
                                'text' => $option->innertext];
                        }
                        $data_structure['fields'][$node->name]['options'] = [$options_array];
                        break;
                    case 'textarea':
                        $data_structure['fields'][$node->name]['label'] = self::get_label($html, $nodes, $index, $node);
                        break;
                    default:
                        // This cannot happen
                }
            }
        }
        return $data_structure;
    }
}