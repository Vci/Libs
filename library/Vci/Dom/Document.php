<?php
/**
 * @file
 * Part of the voyager search brain project.
 *
 * @author David Hazel
 *
 * © Copyright 2011 Voyager Components. All Rights Reserved.
 */


//require_once('my/path/to/file.php');

/**
 * This is a utility class for dealing with php DOM documents.
 *
 * Currently (2011-04-12), it combines DOMDocument and XPath together, and it
 *  adds some functionality similar to that of SimpleXMLElement.
 *
 * Objects of this class will accept methods of and return attributes from both
 *  DOMDocument and DOMXPath resource objects. Also, because DOMDocuments are
 *  actually *resource* type rather than true objects, this is conceptually a
 *  little cleaner than the standard php implementation.
 *
 * Ps. I *really* like this class. Standard PHP dom can get so incredibly
 *  clunky.
 **/
class Vci_Dom_Document
{
    //====== construction ============================================
    /**
     * Defines and instantiates the resources we are encapsulating.
     *
     * @param DOMDocument $dom
     *  The DOMDocument to wrap. If not provided, a new one is created.
     * @param text $encoding
     *  The document encoding to use.
     * @return this
     * @author David Hazel
     **/
    public function __construct($dom = NULL, $encoding = 'UTF-8'){
        // typecheck and instantiate the dom resource
        if( $dom === NULL ){
            $this->domDocument = new DOMDocument('1.0', $encoding);
        }else{
            if( !($dom instanceof DOMDocument) ){
                throw new Exception('The "dom" parameter must be an instance of "DOMDocument. "'.gettype($dom).'" of class "'.get_class($dom).'" given.');
            }else{
                $this->domDocument = $dom;
            }
        }

        // typecheck the encoding
        if( !is_string($encoding) ){
            throw new Exception('The "encoding" parameter must be a string. "'.gettype($encoding).'" given.');
        }else{
            $this->encoding = $encoding;
        }

        // instantiate the xpath linked resource
        $this->xpathObject = $this->generateXPathObject($this->domDocument);

        // instantiate the xslt resource
        $this->xsltObject = new XSLTProcessor();

        // define the encapsulation array
        $this->encapsulatedResources = array(
            &$this->domDocument,
            &$this->xpathObject,
            &$this->xsltObject,
        );
    }

    //====== variables ===============================================
    public $domDocument;     ///< the dom document
    public $xpathObject;   ///< the xpath object (referenced to the dom)
    public $xsltObject;   ///< the xslt processor object
    protected $encapsulatedResources; ///< the array of resources we encapsulate
    /// array populated upon schema, dtd, etc validation
    protected $validationErrors = array(); 

    /// array of all methods for which we wish to suppress errors
    protected $suppressErrors = array(
        'loadHTML',
    );

    /**
     * Insertion mode for inserting "before" a dom node.
     **/
    const INSERT_BEFORE = 'insertBefore';

    /**
     * Insertion mode for inserting "after" a dom node.
     **/
    const INSERT_AFTER = 'insertAfter';

    /**
     * Insertion mode for inserting "inside" a dom node.
     **/
    const INSERT_INSIDE = 'insertInside';

    protected static $INSERTION_MODES = array(
        self::INSERT_BEFORE,
        self::INSERT_AFTER,
        self::INSERT_INSIDE,
    );

    //====== methods =================================================
    /**
     * Passes method calling through to any/all encapsulated resources.
     *
     * @author David Hazel
     **/
    public function __call($method, $parameters){
        // check for a local method (required for the error handler to work)
        if ( method_exists($this, $method) ) {
            return  call_user_func_array(
                array($this, $method),
                $parameters
            );
        }

        // check the resources for the requested method
        foreach($this->encapsulatedResources as $resource) {
            if(method_exists($resource, $method)) {
                $callable = $resource;
            }
        }

        // if none found, throw an exception, like normal
        if(empty($callable)) {
            throw new Exception(sprintf(
                'No encapsulated resource corresponds with the requested method: %s',
                $method
            ));
        }

        // transparently call the requested method
        if ( preg_match('/[Vv]alidate/', $method) ) {
            // special handling to make validation errors accessible
            $originalErrorHandler = set_error_handler(
                array($this, "onValidateError")
            );
            $return = $this->_call(
                $callable,
                $method,
                $parameters
            );
            if ($originalErrorHandler) {
                set_error_handler($originalErrorHandler);
            }
        }else{
            // default functionality
            $return = $this->_call(
                $callable,
                $method,
                $parameters
            );
        }

        // determine whether we need to instantate the xpath 
        if((preg_match('/load/', $method)
         ||((!$this->domDocument->hasChildNodes()) && preg_match('/create|insert|append/', $method))
        )){
            // we could just do this after *every* method call, but this is a
            //  little less intensive
            $this->xpathObject = $this->generateXPathObject(
                $this->domDocument
            );
        }

        // return
        return $return;
    }

    /**
     * Transparently handles get references for any of the encapsulated 
     *  resources.
     *
     * @author David Hazel
     **/
    public function __get($attribute){
        // run through the resources, looking for matches
        foreach($this->encapsulatedResources as $resource) {
            if(isset($resource->$attribute)) {
                $value = $resource->$attribute;
                break; // we give priority to the first resource match
            }
        }

        // return
        return $value;
    }

    /**
     * Decorates the calling of a function
     *
     * @param object $callable
     *  The object who's method is being called
     * @param string $method
     * @param array $parameters
     * @return [mixed]
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    private function _call($callable, $method, $parameters = NULL){
        if ( in_array($method, $this->suppressErrors) ) {
            return @call_user_func_array(
                array($callable, $method),
                $parameters
            );
        } else {
            return call_user_func_array(
                array($callable, $method),
                $parameters
            );
        }
    }

    /**
     * Proxy for DomDocument::loadHTML() so errors can be supressed
     *
     * @return [bool] True on success, false on failure
     * @author "David Hazel" <dhazel@gmail.com>
     * @todo Eventually change this in favor of either setting libxml options
     *  (possible for loadHTML in php 5.4+), or using a better parsing library
     *  like HTML5_Parse.
     **/
    public function loadHTML($sourceString){
        return @$this->domDocument->loadHTML($sourceString);
    }

    /**
     * This method enables us to add nice features to our xpath
     *  implementation, like augmenting it with php functions so that we
     *  can approach some of the functionality of XPath2.
     *
     * @param DOMDocument $dom
     * @return A new XPath object generated for the given dom. The object
     *  will have all PHP functions registered to augment the native XPath1
     *  functions.
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function generateXPathObject(DOMDocument $dom){
        // create the object
        $xpath = new DOMXPath($this->domDocument);

        // Register the php: namespace (required for function augmenting)
        $xpath->registerNamespace("php", "http://php.net/xpath");

        // Register PHP functions (no restrictions)
        $xpath->registerPHPFunctions();

        // return the object
        return $xpath;
    }

    /**
     * This method is inspired by the SimpleXMLElement method of the same name,
     *  though the parameters are different.
     *
     * This method is meant to be extremely versatile, and do exactly what it
     *  says.
     *
     * @param string $child
     *  The name of the node we are adding.
     * @param [string|xml-string|DOMNode] $value
     *  The contents of the node we are adding. This may be a string, or
     *  alternatively a DOMNode itself. If the string is valid xml, then it
     *  will be converted to xml dom prior to insertion. Otherwise, the string
     *  will be escaped prior to its insertion.
     * @param [NULL|DOMNode|xpath-string] $parent
     *  The parent whom we are attaching the child to. This may be NULL
     *  (will be attached to the document root), a DOMNode within the 
     *  document, or an XPath query string or DOMNodeList that references a 
     *  single or multiple nodes.
     * @return Reference(s) to the DOMNode within the document once added.
     *  See DOMNode::appendChild() return documentation. If there are multiple
     *  parents (ie: xpath), then children will be returned in an array.
     * @author David Hazel
     **/
    public function addChild($child, $value = NULL, $parent = NULL){
        $child = $this->createNode($child, $value);
        return $this->addNode($child, $parent, self::INSERT_INSIDE);
    }

    /**
     * This method creates a DOM node that is not yet inserted into the 
     *  document.
     *
     * This method is meant to be extremely versatile, and do exactly what it
     *  says.
     *
     * @param string $name
     *  The name of the node we are adding.
     * @param [string|xml-string|DOMNode] $value
     *  The contents of the node we are adding. This may be a string, or
     *  alternatively a DOMNode itself. If the string is valid xml, then it
     *  will be converted to xml dom prior to insertion. Otherwise, the string
     *  will be escaped prior to its insertion.
     * @return [DOMNode]
     * @author David Hazel
     **/
    public function createNode($name, $value = NULL){
        // handle value input scenarios with typechecking
        if( ! is_string($name) ) {
            throw new Exception(sprintf(
                'The "name" parameter must be a string. "%s" given.',
                gettype($name)
            ));
        }elseif(!is_string($value) && !is_null($value)){
            // create the child
            $node = $this->createElement($name);

            // append child contents
            $node->appendChild($value);
        }elseif(@simplexml_load_string($value)){ // the string is valid xml
            // create a hanging fragment (shortcut to creating a new DOMDoc)
            $frag = $this->createDocumentFragment();

            // append the raw xml string to the fragment
            $frag->appendXML($value);

            // create the child
            $node = $this->createElement($name);

            // append the fragment to the child
            $node->appendChild($frag);
        }else{
            // create the child, adding string escaping
            $node = $this->createElement($name);
            $node->appendChild($this->createTextNode($value));
        }

        return $node;
    }

    /**
     * This method is inspired by the SimpleXMLElement method of the same name,
     *  and it behaves exactly the same *for simple cases*, excluding 
     *  namespaces.
     *
     * @param [string|DOMNode] $node
     *  The name of the node we are adding, else an actual node to add.
     * @param [NULL|DOMNode|xpath-string] $reference
     *  The reference used to determine the node's location. This may be NULL
     *  (will be attached to the document root), a DOMNode within the 
     *  document, or an XPath query string or DOMNodeList that references a 
     *  single or multiple nodes.
     * @param string $insertionMode
     *  See Vci_Dom_Document::insertNode()
     * @return Reference(s) to the DOMNode within the document once added.
     *  See DOMNode::appendChild() return documentation. If there are multiple
     *  parents (ie: xpath), then children will be returned in an array.
     * @author David Hazel
     **/
    public function addNode($node, $reference = NULL, $insertionMode = self::INSERT_INSIDE){
        // handle value input scenarios with typechecking
        if( $node instanceof DOMNode ){
            $node = $this->importNode($node, true);
        }elseif( ! is_string($node) ) {
            throw new Exception(sprintf(
                'The "node" parameter must be either a string or an instance of DOMNode. "%s" given.',
                gettype($node)
            ));
        }else{
            // create the node, adding string escaping
            $node = $this->createElement($node);
        }

        // if parent is xpath, convert it to node list
        if(is_string($reference)){
            $reference = $this->query($reference);
        }

        // handle reference scenarios, add node(s) to the document
        if($reference === NULL){
            // append the node to doc root (insertion-mode is inconsequential)
            $node = $this->appendChild($node);
        }elseif($reference instanceof DOMNodeList){
            // loop-add nodes
            $nodes = array();
            foreach($reference as $element){
                $nodes[] = $this->insertNode(
                    $node->cloneNode(true),
                    $element,
                    $insertionMode
                );
            }

            // set our node variable for return
            if(count($nodes) == 1){
                $node = $nodes[0];
            }else{
                $node = $nodes;
            }
        }else{
            // append the node
            $node = $this->insertNode($node, $reference, $insertionMode);
        }

        // return 
        return $node;
    }

    /**
     * Inserts a DOMNode into a DOMDocument
     *
     * @param DOMNode $newNode
     *  The node to insert
     * @param DOMNode $referenceNode
     *  The node referenced as the insertion point
     * @param string $insertionMode
     *  One of several insertion modes (ie: before, after, inside).
     *  These are pre-defined in the Vci_Dom_Document::$INSERTION_MODES array.
     * @return [DOMNode] The inserted node
     **/
    protected function insertNode($newNode, $referenceNode, $insertionMode = self::INSERT_INSIDE) {
        // typecheck
        if ( ! in_array($insertionMode, self::$INSERTION_MODES) ) {
            throw new Exception(sprintf(
                'Insertion mode, "%s", is not a valid mode. Please use a mode from the Vci_Dom_Document::$INSERTION_MODES array.',
                $insertionMode
            ));
        }

        // work the insertion
        if ( ! $insertionMode || $insertionMode === self::INSERT_INSIDE ) {

            $node = $referenceNode->appendChild($newNode);

        } else if ( $insertionMode === self::INSERT_BEFORE ) {

            $node = $referenceNode->parentNode->insertBefore(
                $newNode,
                $referenceNode
            );

        } else if ( $insertionMode === self::INSERT_AFTER ) {

            if ( $referenceNode->nextSibling ) {
                $node = $referenceNode->parentNode->insertBefore(
                    $newNode,
                    $referenceNode->nextSibling
                );
            } else {
                $node = $referenceNode->parentNode->appendChild($newNode);
            }      
        }

        return $node;
    }

    /**
     * Complimentary method to the DOMNode::insertBefore() function
     *
     * @param DOMNode $newNode
     *  The node to insert
     * @param DOMNode $referenceNode
     *  The node who will be getting the new sibling
     * @return [DOMNode] The inserted node
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function insertAfter(DOMNode $newNode, DOMNode $referenceNode){
        return $this->insertNode($newNode, $referenceNode, self::INSERT_AFTER);
    }

    /**
     * Complimentary method to the DOMNode::insertBefore() function
     *
     * @param DOMNode $newNode
     *  The node to insert
     * @param DOMNode $referenceNode
     *  The node who will be getting the new sibling
     * @return [DOMNode] The inserted node
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function insertBefore(DOMNode $newNode, DOMNode $referenceNode){
        return $this->insertNode($newNode, $referenceNode, self::INSERT_BEFORE);
    }

    /**
     * Complimentary method to the DOMNode::insertBefore() function
     *
     * @param DOMNode $newNode
     *  The node to insert
     * @param DOMNode $referenceNode
     *  The node who will be getting the new sibling
     * @return [DOMNode] The inserted node
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function insertInside(DOMNode $newNode, DOMNode $referenceNode){
        return $this->insertNode($newNode, $referenceNode, self::INSERT_INSIDE);
    }

    /**
     * Cousin of the javascript attribute of the same name.
     *
     * Several methods of implementation are available, though the best is 
     *  likely not written yet, and would include node cloning and document
     *  fragments.
     *
     * @param DOMElement $element
     *  The element whos innerds we want
     * @param bool $deep
     *  Sets whether or not we want to do a "deep copy" and recurse into child
     *  elements (which is the de-facto for the standard javascript innerHTML
     *  attribute). Set this to false in order to get a "shallow" innerHTML.
     *  If $deep is set to false, then $method is ignored, and method 1 is 
     *  used since it is the only one that supports this option.
     * @param int $method
     *  An integer selector for the method we wish to use (0, 1, or 2). There 
     *  are many ways of implementing this functionality, some better than 
     *  others.  
     *  Method 0 takes less memory and should be slightly faster than method 1.
     *  Method 1 is the most versatile.
     *  Method 2 only works with php v5.3.6 and above, and ignores the deep
     *  setting, but is the most efficient method.
     * @param boo $preserve
     *  If method 1 or 2 are used, then $preserve may be set to true in order to
     *  preserve all characters from the original document; otherwise, they
     *  will strip out all carriage-returns ("\n") in order to be a functional 
     *  equivalent to the default method 0.
     * @return String of the html within the submitted element
     * @author David Hazel
     **/
    public static function innerHtml(
        DOMNode $node
        ,$deep = true
        ,$method = 0
        ,$preserve = false
    ){
        // do type checking
        if( ! is_bool($deep) ){
            throw new Exception(sprintf(
                'The second parameter (for the "deep" setting) must be boolean. %s given.',
                gettype($deep)
            ));
        }elseif( ! is_int($method) ){
            throw new Exception(sprintf(
                'The third parameter (for the "method" setting) must be an integer. %s given.',
                gettype($method)
            ));
        }elseif( ! is_bool($preserve) ){
            throw new Exception(sprintf(
                'The fourth parameter (for the "preserve" setting) must be boolean. %s given.',
                gettype($preserve)
            ));
        }

        // if deep is false, use the one method that can do shallow
        if( $deep === false ) $method = 1;

        // execute the method
        if($method == 0){
            // taken from the Raxan PDI framework
            $d = new DOMDocument('1.0');
            $b = $d->importNode($node->cloneNode(true), true);
            $d->appendChild($b); $h = $d->saveHTML();
            // remove outer tags (comment out this line if tags are desired)
            $h = substr($h,strpos($h,'>')+1,-(strlen($node->nodeName)+4));
            return $h;
        }else if($method == 1){
            // my first method
            $innerHTML = '';
            $children = $node->childNodes;
            foreach ($children as $child) {
                $tmp_dom = new DOMDocument();
                $tmp_dom->appendChild($tmp_dom->importNode($child, $deep));
                //$innerHTML .= trim($tmp_dom->saveHTML());
                if( $preserve !== true ){
                    $innerHTML .= preg_replace('/\n/', '', $tmp_dom->saveHTML());
                }else{
                    $innerHTML .= $tmp_dom->saveHTML();
                }
            }
            return $innerHTML;
        }else if($method == 2){
            // my own creation, prior to the Raxan method
            // (only works with php v5.3.6 and above)
            $innerHTML= '';
            $children = $node->childNodes;
            foreach ($children as $child) {
                $tmpString = $child->ownerDocument->saveHTML($child);
                if( $preserve !== true ){
                    $innerHTML .= preg_replace('/\n/', '', $tmpString);
                }else{
                    $innerHTML .= $tmpString;
                }
            }
            return $innerHTML; 
        }else{
            throw new Exception(sprintf(
                'The requested innerHtml method ("%s") does not exist.',
                $method
            ));
        }
    }

    /**
     * Decorator for DOMXPath::query() with an improved API.
     * 
     * @return [Vci_Dom_NodeList]
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function xpath($query, DOMNode $contextNode = NULL, $registerNodeNS = true){
        // execute query
        return new Vci_Dom_NodeList(
            $this,
            $query,
            $contextNode,
            $registerNodeNS
        );
    }

    /**
     * Decorator for DOMXPath::query() with an improved API, implementing
     *  functionality similar to DOMXPath::evaluate().
     *
     * @return [array]
     *  The return *never* contains DOMNode objects, and unless
     *  false (nothing matched) *always* returns an array of strings, each 
     *  string being the "nodeValue" attribute of each matched node.
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function expath($query, DOMNode $contextNode = NULL, $registerNodeNS = true){
        // execute query
        $results = $this->xpath($query, $contextNode, $registerNodeNS);

        // generate the final array of node values
        $return = array();
        foreach ( $results as $result ) {
            $return[] = $result->nodeValue;
        }

        // return the node values
        return $return;
    }

    /**
     * For those times when you just want a single result extracted. This is
     *  another decorator for DOMXPath::query().
     *
     * @return [mixed] The string nodeValue of the first matched DOMNode, NULL 
     *  if no matches.
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function sxpath($query, DOMNode $contextNode = NULL, $registerNodeNS = true){
        // execute query
        $results = $this->xpath($query, $contextNode, $registerNodeNS);
        
        // check for and return the result
        if ( count($results) > 0 ) {
            return $results[0]->nodeValue;
        } else {
            return NULL;
        }
    }

    /**
     * For those times when you just want a single result extracted. This is
     *  another decorator for DOMXPath::query().
     *
     * @return [mixed] The the first matched DOMNode, NULL if no matches.
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function snxpath($query, DOMNode $contextNode = NULL, $registerNodeNS = true){
        // execute query
        $results = $this->xpath($query, $contextNode, $registerNodeNS);
        
        // check for and return the result
        if ( count($results) > 0 ) {
            return $results[0];
        } else {
            return NULL;
        }
    }

    /**
     * Error handler for validation errors
     *
     * @return void
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    protected function onValidateError (
        $errNumber,
        $errString,
        $errFile = null,
        $errLine = null,
        $errContext = null
    ) {
        $this->validationErrors[] = preg_replace("/^.+\): */", "", $errString);
    }

    /**
     * undocumented function
     *
     * @return [array] The errors generated by a schema, dtd, etc validation
     *  operation.
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function getValidationErrors(){
        return $this->validationErrors;
    }
}
?>
