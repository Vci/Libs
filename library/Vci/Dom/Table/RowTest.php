<?php

//require_once '/var/www-libs/vci/Dom/Table/Row.php';

/**
 * Test class for Vci_Dom_Table_Row.
 * Generated by PHPUnit on 2011-10-13 at 11:00:46.
 */
class Vci_Dom_Table_RowTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Vci_Dom_Table_Row
     */
    protected $object;

    /**
     * @var Vci_Dom_Table_Row
     */
    protected $object2;

    /**
     * Basic HTML4 table
     **/
    protected $table = <<<TABLE
        <table>
          <tr>
            <td>d1</td>
            <td>d2</td>
            <td>d3</td>
          </tr>
          <tr>
            <td>d1-1</td>
            <td>d1-2</td>
            <td>d1-3</td>
          </tr>
          <tr>
            <td>d2-1</td>
            <td>d2-2</td>
          </tr>
        </table>
TABLE;

    /**
     * Correct headers for the table
     **/
    protected $headers = array(
        array(
            'offset' => 0
            ,'alias' => 'head1'
            ,'value'  => 'd1'
        )
        ,array(
            'offset' => 1
            ,'value'  => 'd2'
        )
        ,array(
            'offset' => 2
            ,'alias' => 'head3'
            ,'value'  => 'd3'
        )
    );

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        // set up our object
        $dom = new Vci_Dom_Document();
        $dom->loadHTML($this->table);
        $table = $dom->query('//table');
        $table = $table->item(0);
        $table = new Vci_Dom_Table($table);
        $this->object = $table[0];
        $this->object->setHeaders($this->headers);
        $this->object1 = $table[1];
        $this->object1->setHeaders($this->headers);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    /**
     * Complete
     */
    public function testSetHeaders()
    {
        // check for the correct exception when missing offset
        $headers = $this->headers;
        unset($headers[0]['offset']);
        $caught = false;
        try{
            $this->object->setHeaders($headers);
        }catch( Exception $expected ){
            $caught = true;
        }
        if( !$caught ){
            $this->fail('Missing "offset" was not caught');
        }
    }

    /**
     * Complete
     */
    public function testSetReturnType()
    {
        // set the object
        $object = $this->object;

        // test the typechecking
        $caught = false;
        try{
            $object->setReturnType(array());
        }catch( Exception $expected ){
            $caught = true;
        }
        if( !$caught ){
            $this->fail('The incorrect input type was not caught');
        }

        // test the const-checking
        $caught = false;
        try{
            $object->setReturnType(1000);
        }catch( Exception $expected ){
            $caught = true;
        }
        if( !$caught ){
            $this->fail('The incorrect CONST input was not caught');
        }

        // set return-type text and test
        $object->setReturnType(Vci_Dom_Table_Row::RETURN_TEXT);
        $this->assertInternalType('string', $object[0]);

        // set return-type dom and test
        $previousType = $object->setReturnType(Vci_Dom_Table_Row::RETURN_DOM);
        $this->assertInstanceOf('DOMNode', $object[0]);

        // check that the previous type was returned
        $this->assertEquals(Vci_Dom_Table_Row::RETURN_TEXT, $previousType);
    }

    /**
     * Complete
     */
    public function testSetIterateViaHeaders()
    {
        // set the object
        $object = $this->object;

        // add in a modified headers
        $headers = $this->headers;
        unset($headers[1]);
        $object->setHeaders($headers);

        // set return-type text and test
        $object->next();
        $object->setIterateViaHeaders(true);
        $this->assertEquals('head3', $object->key());

        // set return-type dom and test
        $previous = $object->setIterateViaHeaders(false);
        $this->assertEquals('1', $object->key());

        // check that the previous type was returned
        $this->assertEquals(true, $previous);
    }

    /**
     * Complete
     */
    public function testIsValidLength()
    {
        // check for offset
        $this->assertTrue($this->object->isValidLength());

        // check for false offset
        $this->assertFalse($this->object1->isValidLength());
    }

    /**
     * @todo Implement testOffsetSet().
     *  (currently these objects are read-only, so this is unneccesary)
     */
    public function testOffsetSet()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * Complete
     */
    public function testOffsetExists()
    {
        // get our object
        $object = $this->object;

        // check for offset
        $this->assertTrue($object->offsetExists(0));

        // check for false offset
        $this->assertFalse($object->offsetExists(3));

        // check for offset alias
        $this->assertTrue($object->offsetExists('head1'));

        // check for false offset alias
        $this->assertFalse($object->offsetExists('head5'));
    }

    /**
     * @todo Implement testOffsetUnset().
     *  (currently these objects are read-only, so this is unneccesary)
     */
    public function testOffsetUnset()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * Complete
     */
    public function testOffsetGet()
    {
        // set the object
        $object = $this->object;

        // check the return of an illegal offset
        $this->assertNull($object->offsetGet(3));

        // check the return for a legal offset
        $object->setReturnType(Vci_Dom_Table_Row::RETURN_TEXT);
        $this->assertEquals('d1-1',$object->offsetGet(0));

        // check the return of an illegal offset alias
        $this->assertNull($object->offsetGet('head5'));

        // check the return for a legal offset alias
        $object->setReturnType(Vci_Dom_Table_Row::RETURN_TEXT);
        $this->assertEquals('d1-1', $object->offsetGet('head1'));
    }

    /**
     * Complete
     */
    public function testRewind()
    {
        // set our object
        $object = $this->object;

        // next() and rewind() and check our output
        $object->next();
        $object->rewind();
        $this->assertEquals('head1',$object->key());

        // check iteration not on headers
        $object->next();
        $object->rewind();
        $object->setIterateViaHeaders(false);
        $this->assertEquals('head1', $object->key());
    }

    /**
     * Complete
     */
    public function testCurrent()
    {
        // set our object
        $object = $this->object;
        $object->setReturnType(Vci_Dom_Table_Row::RETURN_TEXT);

        // rewind() and check our current output
        $object->rewind();
        $this->assertEquals('d1-1',$object->current());

        // next() and check our current output
        $object->next();
        $this->assertEquals('d1-2',$object->current());

        // check iteration not on headers
        $object->rewind();
        $object->setIterateViaHeaders(false);
        $this->assertEquals('d1-1', $object->current());
    }

    /**
     * Complete
     */
    public function testKey()
    {
        // set our object
        $object = $this->object;

        // check our initial key
        $this->assertEquals('head1', $object->key());

        // next() and check our current output
        $object->next();
        $this->assertEquals(1, $object->key());

        // check iteration not on headers
        $object->rewind();
        $object->setIterateViaHeaders(false);
        $this->assertEquals('head1', $object->key());
    }

    /**
     * Complete
     */
    public function testNext()
    {
        // set our object
        $object = $this->object;

        // check our initial key
        $this->assertEquals('head1', $object->key());

        // check our next key
        $object->next();
        $this->assertEquals(1, $object->key());

        // check our next key
        $object->next();
        $this->assertEquals('head3', $object->key());

        // check iteration not on headers
        $object->rewind();
        $object->setIterateViaHeaders(false);
        $this->assertEquals('head1', $object->key());
    }

    /**
     * Complete
     */
    public function testValid()
    {
        // set our object
        $object = $this->object;

        // check our next current position
        $object->next(); // 1
        $this->assertTrue($object->valid());

        // check our next next current position!
        $object->next(); // 2
        $object->next(); // 3
        $this->assertFalse($object->valid());

        // check iteration not on headers
        $object->rewind();
        $object->setIterateViaHeaders(false);
        $this->assertTrue($object->valid());
    }

    /**
     * Complete
     */
    public function testCount()
    {
        // set our object
        $object = $this->object;

        // check our count
        $this->assertEquals(3, $object->count());
    }
}
?>
