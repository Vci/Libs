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
//require_once('Vci/Dom/Table/Row.php');

/**
 * A utility class for parsing and extracting table data from a dom document
 **/
class Vci_Dom_Table
implements ArrayAccess, Iterator, Countable
{
    //====== construction ============================================
    /**
     * Defines the table DOM Node that this object will refer to
     *
     * @param DOMNode $tableNode
     *  Node with the <table> tag as its root
     * @param array $headers
     *  Specially formatted array describing the expected table headers.
     *  See self::headers
     * @return this
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function __construct(DOMNode $tableNode, $headers = NULL){
        // typecheck that this is actually a table node
        if( $tableNode->nodeName != 'table' ){
            throw new Exception('The provided node must have the name "table". "'.$tableNode->nodeName.'" given.');
        }

        // create a Vci_Dom_Document from the parent DOMDocument
        $this->dom = new Vci_Dom_Document($tableNode->ownerDocument);

        // store our table reference
        $this->reference = $tableNode;

        // store the list of row nodes
        $this->rowNodeList = $this->getRowNodes();

        // determine row offsets and set the row offset mode
        $this->setIterateAllRows(false);

        // if headers, set/validate them
        if( $headers !== NULL ){
            $this->setHeaders($headers);
        }

        // return
        return;
    }

    //====== variables ===============================================
    protected $reference; ///< the DOM reference to our table node
    protected $dom; ///< the parent Vci_Dom_Document object

    /**
     * The column reference and verificaton data
     * 
     * Format:
     *  array (
     *    0 => 
     *    array (
     *      'alias' => 'one',
     *      'offset' => 0,
     *      'value' => 'First Column',
     *      'regex' => '/^First Column$/',
     *    ),
     *    1 => 
     *    array (
     *      'alias' => 'two',
     *      'offset' => 1,
     *      'value' => 'Second Column',
     *      'regex' => '/^Second Column$/',
     *    ),
     *  )
     *
     * Note that the "regex" and "value" fields are used for verification, and 
     *  they must match the text-node-value of the column header in order to 
     *  pass verification. The "regex" field is a regular expression with 
     *  which to match the header, and if it is present, then the "value" 
     *  field is ignored.
     *
     * The "alias" field is optional. If it is present, then it can be used to
     *  refer to the column in an associative manner.
     *
     * @see Vci_Dom_Table_Row::headers
     **/
    protected $headers = array();

    protected $rowNodeList; ///< the DOMNodeList of all table row nodes

    protected $bodyOffset; ///< the offset of the first table body row
    
    /**
     * The offset of the last table header row of the first table header.
     **/
    protected $headerOffset; 

    protected $iteratorPosition = 0; ///< @see PHP Iterator interface

    /// @see Vci_Dom_Table_Row::setReturnType()
    protected $returnType = Vci_Dom_Table_Row::RETURN_DOM;

    /// the flag indicating our iteration setting (@see setIterateAllRows())
    protected $iterateAllRows = false;


    //====== methods =================================================
    /**
     * Shortcut function for creating a DOM Table object
     *
     * @param Vci_Dom_Document $dom
     *  The dom document that we are operating on
     * @param string $xpath
     *  An xpath to the table we want to access
     * @param array $headers
     *  Any headers describing the table structure (see __construct())
     * @param DOMNode $reference
     *  A reference node to run the xpath relative to
     * @return A new DOM Table object, else false
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function factory(Vci_Dom_Document $dom, $xpath, $headers = null, DOMNode $reference = null){
        // resolve the xpath to a single table node
        $table = $dom->query(
            $xpath
            ,$reference
        );

        // check for extraction failure
        $table = $table->item(0);
        if ( empty($table) ) {
            throw new Exception('The xpath, "'.$xpath.'", did not resolve to any valid DOM Nodes.');
        }

        // instantiate a table object for the table
        return new self($table, $headers);
    }
    /**
     * Sets the column references
     *
     * @param array $headersArray
     *  This is a specially formatted array that describes the expected column
     *  headers and that defines column aliases.
     * @param bool $validate
     *  Sets whether or not to validate the headers (see validateHeaders())
     * @return void
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function setHeaders(array $headersArray, $validate = true){
        // typecheck 
        foreach( $headersArray as $key => $column ){
            // typecheck the datastructure for required elements
            if( !isset($column['offset']) ){
                throw new Exception('Row column "offset" is missing from the headers array at key "'.$key.'".');
            }else if( empty($column['value']) && empty($column['regex']) ){
                throw new Exception('Row column at key "'.$key.'" must have either a "value" or a "regex" field, both are missing.');
            }
        }

        // set the headers array
        $this->headers = $headersArray;

        // validate
        if( !empty($validate) ){
            $this->validateHeaders(); // (expects the headers to be loaded)
        }

        // return
        return;
    }

    /**
     * Validates the column headers.
     *
     * If the headers are invalid, then an exception is thrown, otherwise 
     *  the function completes and returns void. This is a little un-orthodox,
     *  and I don't really like it for that reason; but for the scraper's 
     *  purposes at least, it simplifies the API considerably.
     *
     * @return Void, else an exception is thrown
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function validateHeaders(){
        // typecheck
        if( empty($this->headers) ){
            throw new Exception('No headers have been loaded yet');
        }

        // get the table's header row
        $headerRow = $this->getHeader();

        // validate the headers against the header row
        $headerRow->setReturnType(Vci_Dom_Table_Row::RETURN_TEXT);
        foreach( $this->headers as $column ){
            if( !empty($column['regex']) ){
                if( !preg_match($column['regex'], $headerRow[$column['offset']]) ){
                    throw new Exception('Header regex "'.$column['regex'].'" does not match "'.$headerRow[$column['offset']].'".');
                }
            }else if( !empty($column['value']) ){
                if( $headerRow[$column['offset']] != $column['value'] ){
                    throw new Exception('Header value "'.$column['value'].'" does not match "'.$headerRow[$column['offset']].'".');
                }
            }
        }

        // return
        return;
    }

    /**
     * Determines row head and body offsets and sets the iteration mode.
     *
     * Iterating over all rows means we include the header, else we just
     *  iterate over the body.
     *
     * The default behavior is to iterate over the body rows only, leaving out 
     *  the initial header rows at the top of the table.
     *
     * @param bool $value
     *  True to iterate over all rows, False to leave out the top header
     * @return The previous value
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function setIterateAllRows($value = true){
        // store the original value
        $originalValue = $this->iterateAllRows;

        // set the flag
        $this->iterateAllRows = !empty($value);

        // configure the class
        if( $this->iterateAllRows ){
            // zero body and header offsets
            $this->bodyOffset = 0;
            $this->headerOffset = 0;
        }else{
            // set the standard body and header offsets
            $this->generateBodyAndHeaderOffsets();
        }

        // return the original value
        return $originalValue;
    }

    /**
     * Gets all the top-level row DOMNodes in the table (including those nested
     *  in <thead> and <tbody> tags.
     *
     * @return A DOMNodeList of all top-level DOMNodes in the table (including
     *  those nested in <thead> and <tbody> tags.
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    protected function getRowNodes(){
        return $this->dom->query('tr | thead/tr | tbody/tr', $this->reference);
    }

    /**
     * Sets the offsets for the first row of the table body and the last row
     *  of the table header.
     *
     * @return this
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    protected function generateBodyAndHeaderOffsets(){
        // iterate through the row nodes
        $i = 0;
        foreach( $this->rowNodeList as $node ){
            // the body starts after the last official header row
            if( $this->isHeader($node) ){
                $this->headerOffset = $i;
            }
            if( isset($this->headerOffset) ){
                if( ! $this->isHeader($node) ){
                    $this->bodyOffset = $i;
                    break; // stop at the first instance
                }
            }
            $i = $i + 1;
        }
        
        // check for no header found
        if ( ! isset($this->headerOffset) ) {
            $this->bodyOffset = 0;
            $this->headerOffset = 0;
        }

        // return
        return $this;
    }

    /**
     * Gets the header row of the table
     *
     * @return Vci_Dom_Table_Row object corresponding with the table header row
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    protected function getHeader(){
        return new Vci_Dom_Table_Row(
            $this->rowNodeList->item($this->headerOffset)
        );
    }

    /**
     * Checks to see if the given DOM row node is part of a table header
     *
     * Note that this has potential to get _very_ confusing. For simplicity sake
     *  this function considers only *one* row to be the header row, even if the
     *  <thead> section of a table contains multiple rows. 
     *
     * Currently (2011-10-07) this function uses the following rules to
     *  determine "headiness":
     *  1) If the row is in a <thead>, it is prefered; in which case, the 
     *  bottom row of the thead is considered to be the header row. Note that 
     *  occur within a <thead> tag.
     *  rows in the middle of a table can be considered header rows if they 
     *  2) If the table nas no <thead>, the first row of the table is 
     *  considered a header row, whether or not it is within a <tbody> section.
     *
     * @return bool
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    protected function isHeader(DOMNode $row){
        // typecheck
        if( $row->nodeName != 'tr' ){
            throw new Exception('Header nodes must all be "tr" nodes. "'.$row->nodeName.'" submitted for checking.');
        }

        // get previous sibling "tr"
        $previousSibling = $this->dom->query('preceding-sibling::tr', $row);
        if( empty($previousSibling->length) ){
            $previousSibling = false;
        }else{
            $previousSibling = true;
        }

        // get next sibling "tr"
        $nextSibling = $this->dom->query('following-sibling::tr', $row);
        if( empty($nextSibling->length) ){
            $nextSibling = false;
        }else{
            $nextSibling = true;
        }

        // check the node for "headerishness"
        if( ($row->parentNode->nodeName == 'table') 
         && (! $previousSibling) ){
            return true;
        }else if( ($row->parentNode->nodeName == 'thead')
         && (! $nextSibling) ){
            return true;
        }else if( ($row->parentNode->nodeName == 'tbody') ){
            // check for presence of a preceding "uncle" "thead/tr", if so false
            $tmp = $this->dom->query(
                'preceding-sibling::thead/tr'
                ,$row->parentNode
            );
            if( !empty($tmp->length) ){
                return false;
            }

            // check for the presence of a preceding "uncle" "tr", if so, false
            $tmp = $this->dom->query(
                'preceding-sibling::tr'
                ,$row->parentNode
            );
            if( !empty($tmp->length) ){
                return false;
            }

            // check for nonexistant previousSibling, if so, true
            if( ! $previousSibling ){
                return true;
            }

            // else false
            return false;
        }else{
            return false;
        }
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
     * @see Vci_Dom_Table_Row::setReturnType()
     **/
    public function setReturnType($type){
        // typecheck
        if( !is_int($type) ){
            throw new Exception('The "type" argument must be an integer. "'.gettype($type).'" given.');
        }else if( ($type !== Vci_Dom_Table_Row::RETURN_DOM)
         && ($type !== Vci_Dom_Table_Row::RETURN_TEXT) ){
            throw new Exception('The "type" argument must match either Vci_Dom_Table_Row::RETURN_TEXT or Vci_Dom_Table_Row::RETURN_DOM. "'.print_r($type, true).'" given.');
        }

        // store the original return type
        $returnType = $this->returnType;

        // set the return type
        $this->returnType = $type;

        // return the original return type
        return $returnType;
    }

    /**
     * Gets the internal offset value (accounting for the body offset).
     *  Unlike the table_row class, this function leaves the offset in the PHP
     *  standard 0-indexed scheme.
     *
     * @param mixed $offset
     *  The offset we wish to get 
     * @return The actual offset
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    protected function getInternalOffset($offset){
        // determine the internal offset (if associative)
        $offset = $this->bodyOffset + $offset;

        // return the offset
        return $offset;
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
     * @return bool
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function offsetExists($offset){
        // determine the internal offset 
        $offset = $this->getInternalOffset($offset);

        // check and return the result
        if( $offset < $this->rowNodeList->length ){
            return true;
        }else{
            return false;
        }
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

        // generate the result
        $row = new Vci_Dom_Table_Row(
            $this->rowNodeList->item($offset)
            ,$this->headers
        );

        // set the result type
        if( $this->returnType === Vci_Dom_Table_Row::RETURN_DOM ){
            $row->setReturnType(Vci_Dom_Table_Row::RETURN_DOM);
        }else if( $this->returnType === Vci_Dom_Table_Row::RETURN_TEXT ){
            $row->setReturnType(Vci_Dom_Table_Row::RETURN_TEXT);
        }else{
            throw new Exception('Invalid return type! The value could not be returned.');
        }

        // return the result
        return $row;
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
        return $this->offsetGet($this->iteratorPosition);
    }

    /**
     * Implementation of Iterator from the PHP SPL.
     *
     * @return The key (alias) of the current iterator position
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function key(){
        return $this->iteratorPosition;
    }

    /**
     * Implementation of Iterator from the PHP SPL.
     *
     * @return void
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function next(){
        $this->iteratorPosition += 1;
        return;
    }

    /**
     * Addition to Iterator from the PHP SPL.
     *
     * This function is provided to increase flexibility, since html tables
     *  can be fickle things depending on who is designing them.
     *
     * @return void
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function previous(){
        $this->iteratorPosition -= 1;
        return;
    }

    /**
     * Implementation of Iterator from the PHP SPL.
     *
     * @return bool
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function valid(){
        return $this->offsetExists($this->iteratorPosition);
    }

    /**
     * Implementation of Countable from the PHP SPL.
     *
     * @return (int) The number of elements available in the row.
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function count(){
        // check for empty body offset
        if ( $bodyOffset === NULL ) return 0;

        // return the number of body rows found
        // (note that this does not check for embedded header rows)
        return ($this->rowNodeList->length - $this->bodyOffset);
    }

    /**
     * Passes method calling through to the table DOMElement
     *
     * @author David Hazel
     **/
    public function __call($method, $parameters){
        // check the resources for the requested method
        if(method_exists($this->reference, $method)) {
            $callable = $this->reference;
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
        if(isset($this->reference->$attribute)) {
            $value = $this->reference->$attribute;
        }

        // return
        return $value;
    }
}
?>
