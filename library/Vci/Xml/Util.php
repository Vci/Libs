<?php

namespace Vci\Xml;


/**
 * Class Util 
 * @author David Hazel <dhazel@gmail.com>
 */
class Util
{
    public static $defaultNodeName = 'item';
    public static $defaultRootNodeName = 'resultset';

    /**
     * rawFromArray
     * @param $input 
     *  The value to convert to xml
     * @return {string} A raw xml string without any root node or doctype
     *  declaration.
     * @author David Hazel <dhazel@gmail.com>
     **/
    public static function rawFromArray( $input, $rootNodeName = NULL )
    {
        if ( $rootNodeName === NULL ) {
            $rootNodeName = static::$defaultRootNodeName;
        }
        if ( is_numeric($rootNodeName) ) {
            throw new Exception(sprintf(
                'cannot parse into xml. remainder : %s',
                print_r($input,true)));
        }
        $rootNodeName = htmlentities($rootNodeName);
        if ( !(is_array($input) || is_object($input)) ) {
            $newNode = static::makeNode($input, $rootNodeName);
        } else {
            $nodeContents = '';
            foreach ( $input as $key => $value ) {
                if ( is_numeric($key) ) {
                    $key = static::$defaultNodeName;
                }
                $nodeContents = sprintf(
                    '%s%s',
                    $nodeContents,
                    self::rawFromArray($value, $key));
            }
            $newNode = static::appendChild($nodeContents, $rootNodeName);
        }
        return $newNode;
    }

    protected static function appendChild( $contents, $name = NULL ) {
        if ( empty($name) ) {
            $name = static::$defaultNodeName;
        }
        if ( $contents !== 0 && empty($contents) ) {
            $newNode = "\n<$name/>";
        } else {
            $newNode = "\n<$name>$contents\n</$name>";
        }   
        return $newNode;
    }   
    
    protected static function makeNode( $contents, $name = NULL ) {
        return static::appendChild(htmlentities($contents), $name);
    }


    /*
     * Setter for defaultNodeName
     * 
     * @param string defaultNodeName
     */
    public static function setDefaultNodeName( $defaultNodeName )
    {
        static::$defaultNodeName = $defaultNodeName;
    }

    /*
     * Setter for defaultRootNodeName
     * 
     * @param string defaultRootNodeName
     */
    public static function setDefaultRootNodeName( $defaultRootNodeName )
    {
        static::$defaultRootNodeName = $defaultRootNodeName;
    }
    
}

