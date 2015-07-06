<?php
/**
 * @file
 * Part of the voyager brain project.
 *
 * @author "David Hazel" <dhazel@gmail.com>
 *
 * © Copyright 2012 Voyager Components. All Rights Reserved.
 */


/**
 * Decorator object to ease the use of DOMNodeList
 **/
class Vci_Dom_NodeList
implements ArrayAccess, Iterator, Countable
{
    //====== construction ============================================
    /**
     * @param DOMDocument $domDocument
     *  The dom-document from which this list is generated
     * @param string $query
     *  See the parameters of DOMXpath::query()
     * @param DOMNode $contextNode
     *  See the parameters of DOMXpath::query()
     * @param bool $registerNodeNS
     *  See the parameters of DOMXpath::query()
     * @return this
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function __construct(
        //DOMDocument $domDocument,
        Vci_Dom_Document $domDocument,
        $query,
        DOMNode $contextNode = NULL,
        $registerNodeNS = true
    ){
        $this->domDocument = $domDocument;
        $this->query = $query;
        $this->contextNode = $contextNode;
        $this->registerNodeNS = $registerNodeNS;

        $this->generate();
    }

    //====== variables ===============================================
    protected $domDocument; ///< The dom object used to generate this list
    protected $nodeList;  ///< The internal DOMNodeList
    protected $iteratorPosition; ///< The internal iterator

    protected $query; ///< see DOMXpath::query() parameters
    protected $contextNode; ///< see DOMXpath::query() parameters
    protected $registerNodeNS; ///< see DOMXpath::query() parameters

    //====== methods =================================================
    /**
     * Generates the internal node-list.
     *
     * @return this
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    protected function generate(){
        $xpathObject = new DOMXPath($this->domDocument->domDocument);
        $this->nodeList = $xpathObject->query(
            $this->query,
            $this->contextNode,
            $this->registerNodeNS
        );
        if ( empty($this->nodeList) ) {
            throw new Exception(sprintf(
                'NodeList generation failed with the following xpath query: %s',
                $this->query
            ));
        }
    }

    /**
     * Removes all nodes in the list from the dom document
     *
     * Similar to the jquery function of the same name.
     *
     * @param [string|NULL] $query
     *  An optional additional query to delete only specified sub-elements of
     *  each node in the list.
     * @return this
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function remove($query = NULL){
        // loop through all elements
        foreach ( $this as $node ) {
            // check for query type
            if ( empty($query) ) {
                // remove the node
                $node->parentNode->removeChild($node);
            }else{
                // remove all indicated sub-nodes
                $this->domDocument->xpath($query, $node)->remove();
            }
        }

        // re-generate the internal node-list
        $this->generate();

        // return
        return $this;
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
        // check and return the result
        if( $this->nodeList->item($offset) === NULL ){
            return false;
        }else{
            return true;
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
        return $this->nodeList->item($offset);
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
        return $this->offsetExists($this->iteratorPosition);
    }

    /**
     * Implementation of Countable from the PHP SPL.
     *
     * @return (int) The number of elements available in the row.
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function count(){
        return $this->nodeList->length;
    }
}
?>
