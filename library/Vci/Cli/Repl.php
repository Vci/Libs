<?php
/**
 * @file
 * Part of the Voyager Components Libraries.
 *
 * @author "David Hazel" <dhazel@gmail.com>
 *
 * © Copyright 2013 Voyager Components. All Rights Reserved.
 */

namespace Vci\Cli;

use Boris\Boris;


/**
 * Starts a REPL, with tools to easily embed inside existing code for debugging
 **/
class Repl extends Boris
{
    /**
     * Starts the repl using the given config options
     * @param array $config 
     *  Example:
     *  <code>
     *      array(
     *          'locals' => array(
     *              'varname1' => 'value1',
     *              'varname2' => 'value2',
     *          ),
     *          'prompt' => 'promptstring> ',
     *      );
     *  </code>
     * @return void
     * @author David Hazel <dhazel@gmail.com>
     **/
    public static function go(Array $config = NULL)
    {
        if ( ! empty($config['prompt']) ) {
            $prompt = $config['prompt'];
        } else {
            $prompt = 'php> ';
        }
        $boris = new self($prompt);
        if ( ! empty($config['locals']) ) {
            foreach ( $config['locals'] as $key => $value ) {
                $boris->setLocal($key, $value);
            }
        }
        $boris->start();
    }
}
