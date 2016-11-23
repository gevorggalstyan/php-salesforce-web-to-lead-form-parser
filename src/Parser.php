<?php

namespace GevorgGalstyan\SFW2LParser;

class Parser
{
    public function __construct()
    {

    }

    public static function get_label($dom, $nodes, $index, $node)
    {
        $labels = $dom->getElementsByTagName('label');
        foreach ($labels as $label) {
            if ($label->getAttribute('for') === $node->getAttribute('name')) {
                return $label->nodeValue;
            } else {
                $lbl = $node->getAttribute('title');
                if ($lbl === '') {
                    if ($index > 1) {
                        return trim($nodes[$index - 1]->nodeValue);
                    }
                } else {
                    return $lbl;
                }
            }
        }
        return NULL;
    }

    public static function parse($html_file)
    {
        if (!is_file($html_file) || !is_readable($html_file)) {
            throw new \RuntimeException($html_file . ' is not a readable file');
        }

        $dom = new \DOMDocument();
        $dom->loadHTMLFile($html_file);
        $forms = $dom->getElementsByTagName('form');

        if (count($forms) === 0) {
            throw new \RuntimeException($html_file .
                ' must contain a form with POST method');
        }
        if (count($forms) > 1) {
            throw new \RuntimeException($html_file .
                ' must contain only one form');
        }

        $form = $forms[0];

        if ($form->getAttribute('action') === '') {
            throw new \RuntimeException($html_file .
                ' must contain form with non-empty action attribute');
        }

        $data_structure = [];
        $data_structure['action'] = $form->getAttribute('action');
        $data_structure['fields'] = [];

        $nodes = $form->childNodes;
        $formElements = ['input', 'select', 'textarea'];
        foreach ($nodes as $index => $node) {
            if (in_array($node->nodeName, $formElements, TRUE)) {
                $nodeType = $node->getAttribute('type');
                if ($nodeType !== 'hidden' && $nodeType !== 'submit') {
                    $data_structure['fields'][$node->getAttribute('name')] = [
                        'tag' => $node->nodeName
                    ];
                    $data_structure['fields'][$node->getAttribute('name')]['label'] =
                        self::get_label($dom, $nodes, $index, $node);

                    if ($node->nodeName === 'select') {
                        $data_structure['fields'][$node->getAttribute('name')]['multiple'] =
                            ($node->getAttribute('multiple') === 'multiple');
                        $options_array = [];
                        $options = $node->childNodes;
                        foreach ($options as $option) {
                            if ($option->nodeName === 'option') {
                                $options_array[] = [
                                    'value' => $option->getAttribute('value'),
                                    'text' => $option->nodeValue
                                ];
                            }
                        }
                        $data_structure['fields'][$node->getAttribute('name')]['options'] =
                            [$options_array];
                    }
                } elseif ($node->getAttribute('name') === 'oid') {
                    $data_structure['oid'] = $node->getAttribute('value');
                }
            }
        }

        if (!isset($data_structure['oid']) || $data_structure['oid'] == '') {
            throw new \RuntimeException($html_file . ' must contain oid field');
        }

        return $data_structure;
    }
}
