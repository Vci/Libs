<?php
/**
 * @file
 * Part of the voyager data mining project.
 *
 * @author "David Hazel" <dhazel@gmail.com>
 *
 * © Copyright 2011 Voyager Components. All Rights Reserved.
 */


//require_once('Vci/Dom/Document.php');

/**
 * A utility class for parsing and extracting table row data from a dom document
 **/
class Vci_Dom_Table_Row
implements ArrayAccess, Iterator, Countable
{
    //====== construction ============================================
    /**
     * Defines the table row DOM Node that this object will refer to
     *
     * @return this
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function __construct(DOMNode $rowNode, $headers = NULL){
        // typecheck that this is actually a table node
        if( $rowNode->nodeName != 'tr' ){
            throw new Exception('The provided node must have the name "tr". "'.$rowNode->nodeName.'" given.');
        }

        // create a Vci_Dom_Document from the parent DOMDocument
        $this->dom = new Vci_Dom_Document($rowNode->ownerDocument);

        // store our row node
        $this->node = $rowNode;

        // if headers, set them
        if( $headers !== NULL ){
            $this->setHeaders($headers);
        }

        // return
        return;
    }

    //====== variables ===============================================
    public $node; ///< the DOM reference to our row node
    public $dom; ///< the parent Vci_Dom_Document object

    /**
     * The column reference data
     *
     * Format:
     *  array (
     *    0 => 
     *    array (
     *      'alias' => 'one',
     *      'offset' => 0,
     *    ),
     *    1 => 
     *    array (
     *      'alias' => 'two',
     *      'offset' => 1,
     *    ),
     *  )
     *
     * Note that the "alias" field is optional. If it is present, then it can
     *  be used to refer to the column in an associative manner.
     * 
     * @see Vci_Dom_Table::headers
     **/
    protected $headers; 

    protected $iteratorPosition = 0; ///< @see PHP Iterator interface

    /// @see Vci_Dom_Table_Row::setReturnType()
    protected $returnType = Vci_Dom_Table_Row::RETURN_DOM;

    /// flags whether to iterate (foreach) on only elements defined in the 
    ///  headers array
    protected $iterateViaHeaders = true;

    /**
     * Indicates that the class should return DOMNode objects when accessing
     *  row fields.
     **/
    const RETURN_DOM = 1;

    /**
     * Indicates that the calss should return the text from within the DOMNode
     *  when accessing row fields.
     **/
    const RETURN_TEXT = 2;


    //====== methods =================================================
    /**
     * Sets the column references
     *
     * @param array $headersArray
     *  This is a specially formatted array that describes the expected column
     *  headers and that defines column aliases.
     * @return void
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function setHeaders(array $headersArray){
        // typecheck
        foreach( $headersArray as $key => $column ){
            // make sure that there is an offset value
            if( !isset($column['offset']) ){
                throw new Exception('Row column "offset" is missing from the headers array at key "'.$key.'".');
            }
        }

        // make the headers contiguous (just in case)
        $headersArray = array_values($headersArray);

        // set the headers array
        $this->headers = $headersArray;
    }

    /**
     * Sets the return-type for when elements are retrieved.
     *
     * @param int $type
     *  The flag indicating which return type to use. Currently (2011-10-05) the
     *  following are supported:
     *
     *  * Vci_Dom_Table_Row::RETURN_DOM   (return the DOM Node)
     *  * Vci_Dom_Table_Row::RETURN_TEXT  (return the text within the DOM Node)
     * @return The original return-type value
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function setReturnType($type){
        // typecheck
        if( !is_int($type) ){
            throw new Exception('The "type" argument must be an integer. "'.gettype($type).'" given.');
        }else if( ($type !== Vci_Dom_Table_Row::RETURN_DOM)
         && ($type !== Vci_Dom_Table_Row::RETURN_TEXT) ){
            throw new Exception('The "type" argument must match either Vci_Dom_Table_Row::RETURN_TEXT or Vci_Dom_Table_Row::RETURN_DOM. "'.print_r($type, true).'" given.');
        }

        // save the original value
        $returnType = $this->returnType;

        // set the return type
        $this->returnType = $type;

        // return the original return type
        return $returnType;
    }

    /**
     * Sets the iterateViaHeaders flag, which indicates to the class whether
     *  to iterate only on the defined headers (if true) or to iterate on all
     *  available columns (if false).
     *
     * If the headers have not been set, then this setting is automatically 
     *  false.
     *
     * @param bool $value
     *  The value to set the flag to
     * @return The previous value
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function setIterateViaHeaders($value = true){
        // store the original value
        $originalValue = $this->iterateViaHeaders;

        // set the flag
        $this->iterateViaHeaders = !empty($value);

        // return the original value
        return $originalValue;
    }

    /**
     * Checks whether this row matches the entries in the header
     *
     * @return bool
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function isValidLength(){
        // loop through all the headers
        foreach( $this->headers as $column ){
            // check for a valid offset
            if( !$this->offsetExists($column['offset']) ){
                return false;
            }
        }

        // a false offset was not found
        return true;
    }

    /**
     * Gets the internal offset value (accounting for associate offsets),
     *  and modifies it from 0-indexed to 1-indexed (so it can be used directly
     *  in xpath expressions).
     *
     * @param mixed $offset
     *  The offset we wish to get (left unchanged if not associate)
     * @return The actual offset, else NULL if not present.
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    protected function getInternalOffset($offset){
        // determine the internal offset (if associative)
        if( !preg_match('/^\d+$/', $offset) ){
            foreach( $this->headers as $column ){
                if( (!empty($column['alias']))
                 && ($column['alias'] === $offset) ){
                    $offset = $column['offset'];
                    $found = true;
                    break;
                }
            }   
            if( empty($found) ){
                return NULL;
            }
        }

        // modify the offset from php spec to xpath spec
        $offset = $offset + 1;

        // return the offset
        return $offset;
    }

    /**
     * Gets the alias for the given offset (if present).
     *
     * @param mixed $offset
     *  The offset we wish to get the alias for. This may be a string or an int.
     *
     *  Note that this must be a regular php 0-indexed offset value, not the 
     *  modified (xpath ready) value provided by getInternalOffset().
     * @return The alias to the given offset
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    protected function getOffsetAlias($offset){
        // search for an alias in the headers array
        foreach( $this->headers as $column ){
            if( $column['offset'] == $offset ){
                if( !empty($column['alias']) ){
                    $alias = $column['alias'];
                }
                break;
            }
        }   

        // if no alias, return the regular offset
        if( empty($alias) ){
            $alias = $offset;
        }

        // return the offset
        return $alias;
    }

    /**
     * Implementation of ArrayAccess from the PHP SPL.
     *
     * @return void
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function offsetSet($offset, $value){
        // throw exception (not implemented / read-only)
        throw new Exception('Setting is not possible. This object is read-only.');
    }

    /**
     * Implementation of ArrayAccess from the PHP SPL.
     *
     * Note that this checks for existance of both <td> and <th> tags.
     *
     * @return bool
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function offsetExists($offset){
        // determine the internal offset 
        $associative = preg_match('/[\D]/', $offset) ? true : false;
        $offset = $this->getInternalOffset($offset);
        if( $offset === NULL ){
            return false;
        }

        // search in the row for the offset
        $results = $this->dom->query(
            'td['.$offset.'] | th['.$offset.']'
            ,$this->node
        );

        // check for malformed table
        if ( empty($results->length) && $associative ) {
            trigger_error(sprintf(
                'Malformed table row detected! Table cells were not found. They may be missing or may not be immediate children of the row: %s',
                $this->dom->innerHtml($this->node)),
                E_WARNING
            );
            return false;
        }

        // return the result
        return !empty($results->length);
    }

    /**
     * Implementation of ArrayAccess from the PHP SPL.
     *
     * @return void
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function offsetUnset($offset){
        // throw exception (not implemented / read-only)
        throw new Exception('Unsetting is not possible. This object is read-only.');
    }

    /**
     * Implementation of ArrayAccess from the PHP SPL.
     *
     * Note that this gets <td> or <th> elements if available.
     *
     * @param mixed $offset
     *  The offset that we wish to get. This may be either an alias or an
     *  integer offset, but priority is always given to aliases if they exist.
     * @return (mixed) Whatever is at the offset. The return type is determined
     *  by the Vci_Dom_Table_Row::returnType attribute, and can be either a 
     *  DOMNode, or the text content of the DOMNode at the offset.
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function offsetGet($offset){
        // check the offset
        if( ! $this->offsetExists($offset) ){
            return NULL;
        }

        // determine the internal offset
        $offset = $this->getInternalOffset($offset);

        // query for the offset
        $results = $this->dom->query(
            'td['.$offset.'] | th['.$offset.']'
            ,$this->node
        );

        // return the result
        if( $this->returnType === Vci_Dom_Table_Row::RETURN_DOM ){
            return $results->item(0);
        }else if( $this->returnType === Vci_Dom_Table_Row::RETURN_TEXT ){
            return $results->item(0)->nodeValue;
        }else{
            throw new Exception('Invalid return type! The value could not be returned.');
        }
    }

    /**
     * Implementation of Iterator from the PHP SPL.
     *
     * @return void
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function rewind(){
        $this->iteratorPosition = 0;
    }

    /**
     * Implementation of Iterator from the PHP SPL.
     *
     * @return The element in the current iteration
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function current(){
        // check the iteration type
        if( $this->iterateViaHeaders && !empty($this->headers) ){
            // set the position based on the headers
            $position = $this->key();
        }else{
            // use the generic position
            $position = $this->iteratorPosition;
        }

        // return the value
        return $this->offsetGet($position);
    }

    /**
     * Implementation of Iterator from the PHP SPL.
     *
     * @return The key (alias) of the current iterator position
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function key(){
        // check the iteration type
        if( $this->iterateViaHeaders && !empty($this->headers) ){
            // set the position based on the headers
            if( isset($this->headers[$this->iteratorPosition]) ){
                // header iterator position exists
                $position = $this->headers[$this->iteratorPosition]['offset'];
            }else{
                // find the highest header offset
                $highestOffset = 0;
                foreach( $this->headers as $entry ){
                    if( $entry['offset'] > $highestOffset ){
                        $highestOffset = $entry['offset'];
                    }
                }

                // make a fictitious offset just beyond the end
                $position = $highestOffset + 1;
            }
        }else{
            // use the generic position
            $position = $this->iteratorPosition;
        }

        // return the current key
        return $this->getOffsetAlias($position);
    }

    /**
     * Implementation of Iterator from the PHP SPL.
     *
     * @return void
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function next(){
        $this->iteratorPosition = $this->iteratorPosition + 1;
        return;
    }

    /**
     * Implementation of Iterator from the PHP SPL.
     *
     * @return bool
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function valid(){
        // check the iteration type
        if( $this->iterateViaHeaders && !empty($this->headers) ){
            // set the position based on the headers
            $position = $this->key();
        }else{
            // use the generic position
            $position = $this->iteratorPosition;
        }

        // return the validity of the position
        return $this->offsetExists($position);
    }

    /**
     * Implementation of Countable from the PHP SPL.
     *
     * @return (int) The number of elements available in the row.
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function count(){
        // query for "td"s
        $results = $this->dom->query('td | th', $this->node);

        // return the number found
        return $results->length;
    }

    /**
     * Passes method calling through to the table DOMElement
     *
     * @author David Hazel
     **/
    public function __call($method, $parameters){
        // check the resources for the requested method
        if(method_exists($this->node, $method)) {
            $callable = $this->node;
        }

        // if none found, throw an exception, like normal
        if(empty($callable)) {
            throw new Exception('The requested method does not exist.');
        }

        // transparently call the requested method
        $return = call_user_func_array(array($callable, $method), $parameters);

        // return
        return $return;
    }

    /**
     * Transparently handles get references for the row DOMElement
     *
     * @author David Hazel
     **/
    public function __get($attribute){
        // run through the resources, looking for matches
        if(isset($this->node->$attribute)) {
            $value = $this->node->$attribute;
        }

        // return
        return $value;
    }
}
?>
