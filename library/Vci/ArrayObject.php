<?php
/**
 * @file
 * 
 * @author David Hazel
 * 
 * @copyright 2011 David Hazel
 */


/**
 * Adds some nice extensions onto the SPL ArrayObject class.
 *
 * I'm not sure what extra value this gives me by implementing it this way,
 *  but we'll see. It's an experiment.
 */
class Vci_ArrayObject extends ArrayObject {
    /**
     * Here we inherit the constructor functionality from the parent
     *  ArrayObject class.
     */
    function __construct($input = NULL, $flags = NULL, $iteratorClass = 'ArrayIterator'){
        // inherit the parent constructor functionality
        parent::__construct($input, $flags, $iteratorClass);
    }

    //= methods ==============================================================

    /**
     * Updates the array's elements, appending any new elements to the end
     *
     * This function kindly does nothing if $array is empty or null. Yes, very
     *  forgiving I know, I know.
     *
     * Note that I also made sure to maintain some semblance of immutability
     *  (ie: this object does not modify its own contents).
     *
     * @param array $array
     *  The array of new data
     * @return 
     *  The updated array
     */
    public function updateWith($array) {
        $updatedArray = new Vci_ArrayObject($this);
        if(!empty($array2)){
            foreach($array2 as $key => $value){
                $updatedArray[$key] = $value;
            }
        }
        return $updatedArray;
    }

    /**
     * Converts the array into a php SimpleXML object (useful for xpathing
     *  it, etc).
     *
     * This function has been taken and slightly modified from the php 
     *  documentation website.
     *
     * Note that this method recurses into objects, not just arrays. Something 
     *  might need to be improved here in the future.
     *
     * <code>
     *  $xml = $array->xml(new SimpleXMLElement('<root/>'));
     * </code>
     *
     * @param array $array
     *  The array to convert.
     * @return 
     *  The SimpleXML object.
     */
    public static function xml(SimpleXMLElement $xml, $array){
        foreach ($array as $key => $value) {
            if(is_array($value) || is_object($value)){
                Vci_ArrayObject::xml($xml->addChild($key), $value);
            }else{
                $xml->addChild($key, $value);
            }
        }
        return $xml;
    }

    /**
     * Runs an xpath style query on the array data, returning the result.
     *
     * Note: sub-elements, if null, become empty arrays. Be aware!
     *
     * <code>
     *  $result = $array->xpath('/here/is/my/path');
     * </code>
     *
     * @param string $path
     *  The xpath to use for querying.
     * @param array $array
     *  The array to run the xpath against.
     */
    public function xpath($path, $array = NULL){
        // handle any funky inputs
        if(empty($array)){
            $array = $this;
        }

        // set the root tag
        $rootTag = 'root';

        // convert to xml
        $xml = Vci_ArrayObject::xml(new SimpleXMLElement("<$rootTag/>"),$array);

        // run the xpath
        if(preg_match('/^\/{1}/', $path)){  // if it's an absolute path
            $result = $xml->xpath("/$rootTag".$path);
        }else{                              // if it's not an absolute path
            $result = $xml->xpath($path);
        }
        // convert into an array, including all sub-arrays
        $result = json_decode(json_encode($result), true);

        // convert back into an Vci_ArrayObject and return
        return new Vci_ArrayObject($result);
    }
}
