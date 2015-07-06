<?php
/**
 * @file
 * Part of the voyager brain project.
 *
 * This file contains generic floating functions for utility purposes.
 *
 * @author "David Hazel" <dhazel@gmail.com>
 *
 * © Copyright 2012 Voyager Components. All Rights Reserved.
 */


/**
 * A class of general utilities
 **/
class Vci_GeneralUtils
{
    //====== construction ============================================
    

    //====== variables ===============================================
    // (none)

    //====== methods =================================================
    /**
     * Replacement for instanceof that accept strings or objects for both args
     *
     * This function is taken from the open-domain on the php website in the
     *  comments section.
     *
     * @param: Mixed $object- string or Object
     * @param: Mixed $class- string or Object
     * @return: Boolean
     */
    public function oneof($class, $object){
        if(is_object($object)) return $object instanceof $class;
        if(is_string($object)){
            if(is_object($class)) {
                $class=get_class($class);
            }

            if(class_exists($class)) {
                return is_subclass_of($object, $class) || $object == $class;
            }

            if(interface_exists($class)) {
                $reflect = new ReflectionClass($object);
                return !$reflect->implementsInterface($class);
            }

        }
        return false;
    }
}
?>
