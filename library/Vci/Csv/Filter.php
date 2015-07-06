<?php
/**
 * @file
 * Part of the voyager brain project.
 *
 * @author "David Hazel" <dhazel@gmail.com>
 *
 * © Copyright 2012 Voyager Components Inc. All Rights Reserved.
 */

//require_once('my/path/to/file.php');


/**
 * Filter class for importing CSV files.
 **/
class Vci_Csv_Filter
extends SplFileObject
{
    //====== construction ============================================
    /**
     * See the documentation for SplFileObject
     *
     * @return this
     **/
    public function __construct($filename, $openMode, $useIncludePath, $context){
        // construct per the parent constructor
        if ( empty($context) ) {
            // stinking annoying PHP gotchas
            parent::__construct($filename, $openMode, $useIncludePath);
        } else {
            parent::__construct($filename, $openMode, $useIncludePath, $context);
        }
        
        // set default CSV options
        $this->setFlags(SplFileObject::READ_CSV);

        // return
        return $this;
    }

    //====== variables ===============================================
    /**
     * Array defining the row input schema and output transforms.
     *
     * <code>
     *  $inputDefinition = array(
     *     array(                             // order defines output
     *         'offset' => 0,                 // defines csv offset
     *         'name' => 'first column',      // defines the output row key
     *         'header' => '/^header regex$/',// all headers must match to verify a header row
     *         'cell' => '/^cell regex$/',    // will throw error if match fails
     *         'preg_replacements' => array(  // processed sequentially!
     *             '/regex/' => 'replacement string',
     *             '/first (string)/' => 'second \1',
     *         ),
     *         'transform' => function ($inputValue) { // defines a closure transform that can go beyond a regex replacement
     *             return (int) $inputValue;
     *         }
     *         'trim' => true,                // trim whitespace off the final value
     *         'default' => 'default value',  // value to use if empty cell
     *     ),
     *     array(
     *         'offset' => 3,
     *         'name' => 'second column',
     *         'header' => '/^header regex$/',
     *         'cell' => '/^cell regex$/',
     *         'preg_replacements' => array(  
     *             '/regex/' => 'replacement string',
     *             '/first (string)/' => 'second \1',
     *         ),
     *         'default' => 'default value'
     *     ),
     *     array(
     *         'offset' => 1,
     *         'name' => 'third column',
     *         'header' => '/^header regex$/',
     *         'cell' => '/^cell regex$/',
     *         'preg_replacements' => array(  
     *             '/regex/' => 'replacement string',
     *             '/first (string)/' => 'second \1',
     *         ),
     *         'default' => 'default value'
     *     ),
     *  );
     * </code>
     **/
    protected $inputDefinition;
    
    /**
     * Array defining the row output schema with the capacity for complex
     *  transforms using anonymous functions.
     *
     * If this attribute is not present, then it is not used as part of the 
     *  transformation chain of the csv row.
     *
     * Each array element must be an anonymous function that receives as its
     *  single parameter an array containing the full row as per output by the 
     *  $inputDefinition array. Each anonymous function will have its return 
     *  value taken and used as the final value of the corresponding index of 
     *  the output row array.
     *
     * <code>
     *  $outputDefinition = array(
     *      function ($row) {
     *      },
     *      function ($row) {
     *      },
     *  );
     * </code>
     **/
    protected $outputDefinition;

    protected $headerLess; ///< identifies whether this CSV has a header

    protected $skipEmpty = true; ///< flag for whether to skip empty rows

    protected $skipHeaders = true; ///< flag for whether to skip header rows

    protected $skipOnInvalid = false; ///< flag whether to skip or fail on invalid rows

    //====== methods =================================================

    /**
     * Sets the input and output row definitions. 
     *
     * @param array $definitions
     *  Array containing 'input' and/or 'output' keys which reference their
     *  respective definition. See setInputDefinition() and 
     *  setOutputDefinition() for more information on each definition format.
     * @return this
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function setRowDefinitions(Array $definitions){
        $this->setInputDefinition($definitions['input']);
        $this->setOutputDefinition($definitions['output']);
        return $this;
    }
    /**
     * @param array $inputDefinition
     *  Defines the transforms to apply to the CSV
     * @return this
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function setInputDefinition(Array $inputDefinition){
        // check for required fields
        $i = 0;
        $headerLess = NULL;
        foreach ( $inputDefinition as $key => $array ) {
            // check for name value (used as array key for the output)
            if ( ! isset($array['name']) ) {
                $inputDefinition[$key]['name'] = $key;
            }
            $i += $i;

            // check for complete header definition
            if ( isset($array['header']) ) {
                if ( $headerLess === NULL ) {
                    $headerLess = false;
                } else if ( $headerLess === true ) {
                    $incompleteHeaderDefinition = true;
                }
            } else {
                if ( $headerLess === NULL ) {
                    $headerLess = true;
                } else if ( $headerLess === false ) {
                    $incompleteHeaderDefinition = true;
                }
            }
            if ( isset($incompleteHeaderDefinition) ) {
                    throw new Exception(
                        'For a header to be completely defined, each cell definition in the row definition array must have a "header" defined. Please complete the header definition.'
                    );
            }
        }

        // store our header status
        $this->headerLess = $headerLess;

        // set the definition
        $this->inputDefinition = $inputDefinition;
        return $this;
    }

    /**
     * @param array $outputDefinition
     *  Defines the transforms to apply to the pre-output array
     * @return this
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function setOutputDefinition(Array $outputDefinition){
        // set the definition
        $this->outputDefinition = $outputDefinition;
        return $this;
    }

    /**
     * @param array $row
     *  A row of values from the CSV file (before transformation)
     * @return [bool] Indicating whether or not the row is a header row
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    protected function isHeader(Array $row){
        if ( $this->headerLess === true ) return false;
        foreach ( $this->inputDefinition as $definition ) {
            if ( ! isset($definition['offset']) ) {
                continue; // if this is a virtual row, it will have no header
            } else if ( ! isset($definition['header']) ) {
                trigger_error(
                    sprintf(
                        'Row definition is missing a header value for offset %s. Header can not be detected. Continuing gracefully.',
                        $definition['offset']
                    ),
                    E_USER_WARNING
                );
                return false;
            }
            $header = $definition['header'];
            $offset = $definition['offset'];
            if ( ! preg_match($header, $row[$offset]) ) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param array $row
     *  A row of values from the CSV file (after transformation)
     * @return [bool] Indicating whether or not the row is empty
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    protected function isEmpty(Array $row){
        foreach ( $this->inputDefinition as $definition ) {
            if ( ! preg_match('/^[\p{Z}\s]*$/u', $row[$definition['name']]) ) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param array $row
     *  A row of values from the CSV file (after transformation)
     * @return [bool] Indicating whether or not the row is valid
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    protected function isValid(Array $row){
        foreach ( $this->inputDefinition as $definition ) {
            if ( isset($definition['cell'])
              && ! preg_match($definition['cell'], $row[$definition['name']])
            ) {
                $errorMessage = sprintf(
                    'The cell in row %s at offset %s and column name "%s" with value "%s" did not match its regex definition of %s',
                    $this->key(),
                    $definition['offset'],
                    $definition['name'],
                    $row[$definition['name']],
                    $definition['cell']
                );
                if ( $this->skipOnInvalid ) {
                    trigger_error( $errorMessage, E_USER_WARNING);
                    return false;
                } else {
                    throw new Exception($errorMessage);
                }
            }
        }
        return true;
    }

    /**
     * @param array $row
     * @return [array] The row as transformed by the definition in the 
     *  $inputDefinition array.
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    protected function transformInput(Array $row){
        // check for the row definition
        if ( empty($this->inputDefinition) ) {
            throw new Exception('The row definition has not been set yet! Aborting.');
        }

        // build the transformed array
        foreach ( $this->inputDefinition as $definition ) {
            // build our return array
            if ( isset($definition['offset']) ) {
                $newRow[$definition['name']] = $row[$definition['offset']];
            } else {
                $newRow[$definition['name']] = '';
            }

            // do preg replacements
            foreach ( $definition['preg_replacements'] as $preg => $value) {
                $newRow[$definition['name']] = preg_replace(
                    $preg,
                    $value,
                    $newRow[$definition['name']]
                );
            }

            // do trims
            if ( ! empty($definition['trim']) ) {
                $newRow[$definition['name']] = trim(
                    $newRow[$definition['name']]
                );
            }

            // do transforms
            if ( isset($definition['transform'])
              && $definition['transform'] instanceof \Closure 
            ) {
                $newRow[$definition['name']] = $definition['transform'](
                    $row[$definition['offset']]
                );
            }
        }

        // set default values
        foreach ( $this->inputDefinition as $definition ) {
            if ( $newRow[$definition['name']] == '' ){
                if ( isset($definition['default']) ){
                    $newRow[$definition['name']] = $definition['default'];
                } else {
                    $newRow[$definition['name']] = '';
                }
            }
        }

        // return
        return $newRow;
    }

    /**
     * @param array $row
     * @return [array] The row as transformed by the definition in the 
     *  $outputDefinition array.
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    protected function transformOutput(Array $row){
        // check for the out-row definition
        if ( empty($this->outputDefinition) ) {
            return $row;
        }

        // build the transformed array
        foreach ( $this->outputDefinition as $key => $definition ) {
            // build our return array
            $newRow[$key] = $definition($row);
        }

        // return
        return $newRow;
    }

    /**
     * Overrides SplFileObject method of same name
     *
     * @return [array] The current row
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function current(){
        // check for skip scenarios
        if ( parent::current() === false ) return false;
        $current = $this->transformInput(parent::current());
        while ( ($this->skipHeaders && $this->isHeader(parent::current()))
          || ($this->skipEmpty && $this->isEmpty($current))
          || (! $this->isValid($current))
        ) { 
            trigger_error( 
                sprintf('Skipping row: %s', print_r(parent::current(), true)), 
                E_USER_WARNING
            );
            parent::next();
            if ( parent::current() === false ) return false;
            $current = $this->transformInput(parent::current());
        }

        // perform the output transforms on the row
        $current = $this->transformOutput($current);

        // return the transformed row
        return $current;
    }

    /**
     * Overrides the SplFileObject method of the same name.
     *
     * @return void
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function rewind(){
        parent::rewind();
    }

    /**
     * Overrides the SplFileObject method of the same name
     *
     * @return [bool]
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function valid(){
        $current = $this->current();
        return ! empty($current);
    }

    /**
     * @param bool $bool
     *  Whether or not to remove headers from the output. Default is true.
     * @return this
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function setSkipHeaders($bool){
        $this->skipHeaders = ! empty($bool);
        return $this;
    }

    /**
     * @param bool $bool
     *  Whether or not to remove empty rows from the output. Default is true.
     * @return this
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function setSkipEmpty($bool){
        $this->skipEmpty = ! empty($bool);
        return $this;
    }

    /**
     * @param bool $bool
     *  Whether or not to remove invalid rows from the output. Default is false.
     *  Note that unless this is set, an exception is thrown for any invalid
     *  row.
     * @return this
     * @author "David Hazel" <dhazel@gmail.com>
     **/
    public function setSkipOnInvalid($bool = true){
        $this->skipOnInvalid = ! empty($bool);
        return $this;
    }
}
?>
