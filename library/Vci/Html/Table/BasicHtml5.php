<?php
/**
 * @file
 * General voyager library.
 *
 * @author David Hazel
 *
 * © Copyright 2011 Voyager Components. All Rights Reserved.
 */


//require_once('Vci/Html/Table.php');

/**
 * Generates an HTML5 table from an array.
 **/
class Vci_Html_Table_BasicHtml5
implements Vci_Html_Table
{
    //====== construction ============================================
    /**
     * An array is required to convert to a table.
     *
     * The array must be in the format that is output by the Zend_Db adapter.
     *  That is, a numerically indexed array of "rows", which are themselves
     *  arrays of key => value pairs representing column-heading => field-value
     *
     * @return this
     * @author David Hazel
     **/
    public function __construct($array){
        // save the array
        $this->array = $array;

        // generate a column list
        $keys = array_keys($array);
        $this->columns = array_keys($array[$keys[0]]);
    }

    //====== variables ===============================================
    protected $array; ///< the array that we are going to convert
    protected $columns; ///< the array columns that we are going to convert
    protected $className; ///< the class name for the table element
    protected $labelName; ///< the label name for the table element

    //====== methods =================================================
    /**
     * Sets the class name for the table element.
     *
     * @return void
     * @author David Hazel
     **/
    public function setClassName($name){
        $this->className = $name;
    }

    /**
     * Sets the label for the table element.
     *
     * @return void
     * @author David Hazel
     **/
    public function setLabelName($name){
        $this->labelName = $name;
    }

    /**
     * Removes the specified column (by header name) from the table.
     *
     * @return void, else false if no column was found to match $name
     * @author David Hazel
     **/
    public function removeColumn($name){
        $column = array_search($name, $this->columns);
        if(!empty($column)){
            unset($this->columns[$column]);
        }else{
            return false;
        }
        return;
    }

    /**
     * Generates the HTML5 source and prints it to the buffer.
     *
     * @return Table source printed to the buffer.
     * @author David Hazel
     **/
    public function display(){
        // check for an empty table, return if true
        if(empty($this->columns)){
            return;
        }
        ?>
        <table 
         class="<?php echo $this->className;?>" 
         id="<?php echo $this->labelName;?>"
        >
            <thead>
                <tr>
                <?php
                foreach($this->columns as $column){
                    ?>
                    <th 
                     class="<?php echo(preg_replace('/ +/','_',$column));?>"
                    >
                        <?php echo $column; ?>
                    </th>
                    <?php
                }
                ?>
                </tr>
            </thead>
            <tbody>
            <?php
            foreach($this->array as $row){
                ?>
                <tr>
                <?php
                foreach($this->columns as $column){
                    ?>
                    <td>
                        <?php
                        // This is slightly hackish, but we shouldn't have
                        //  arrays too deep, and this fixes array conversion
                        //  issues caused by Vci_ArrayObject->xpath()
                        // No adverse effects should be seen in any usage
                        //  scenarios.
                        if(!isset($row[$column][0][0])){
                            echo $row[$column]; 
                        }
                        ?>
                    </td>
                    <?php
                }
                ?>
                </tr>
                <?php
            }
            ?>
            </tbody>
        </table>
        <?php
    }
}

?>
