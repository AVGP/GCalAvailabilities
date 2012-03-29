<?php

namespace ASnet\GCalBundle\Tests\Service;

use ASnet\GCalBundle\Services\GoogleCalendar;

/**
 * Testing the GoogleCalendar service
 */
class GoogleCalendarTest extends \PHPUnit_Framework_TestCase {

    /**
     * List of calendars our mocked Zend_Gdata_Calendar will return when asked
     * for a list of calendars
     * @var Array Structure
     *      array(
     *          {
     *              title: 'xyz',
     *              'link' => array(
     *                  0 => { href: http://calendar.google.com/xyz}
     *              )
     *          },
     *          ...
     *      )
     */
    protected $calendarsTestSet;

    /**
     * Initializing test data for the mock.
     * This is placed in the constructor and not in setUp, because we need this only once and not
     * before each testcase. 
     */
    public function __construct() {
        $this->calendarsTestSet = array(
                (object) array(
                        'title' => 'Cal #1',
                        'link' => array( (object) array('href' => 'http://calendar.google.com/cal1') )
                    ),
                (object) array(
                        'title' => 'Cal #2',
                        'link' => array( (object) array('href' => 'http://calendar.google.com/cal2') )
                    ),
                (object) array(
                        'title' => 'Cal #3',
                        'link' => array( (object) array('href' => 'http://calendar.google.com/cal3') )
                    ),
            );

        parent::__construct();
    }

   /**
    * Test the constructor:
    * Case 1: Call without a data provider
    * Case 2: Call with some object as a data provider
    */
   public function testConstructor() {
        $this->assertAttributeEquals(null, 'dataProvider', new GoogleCalendar());
        $this->assertAttributeEquals($this, 'dataProvider', new GoogleCalendar($this)); //Passing $this as the data provider does
                                                                                        // not make sense semantically but the constructor
                                                                                        // just sets the internal property, so it's okay here.
    }

    /**
     * Test the initialization of the default Zend_Gdata_Calendar provider using
     * a session AuthSub token.
     * Case 1: null passed as a token - should raise an exception
     * Case 2: some string passed as a token - should set the internal $dataProvider
     *         to an instance of Zend_Gdata_Calendar
     */
    public function testInitDefaultProviderFromToken() {
      $testSubject = new GoogleCalendar();
        try {
            $testSubject->initDefaultProviderFromToken(null);
            $this->fail('GoogleCalendar::initWithToken with null as parameter should throw an exception.');
        } catch(\Exception $e) {
            $this->assertEquals('Missing authentication token', $e->getMessage());
        }

        $testSubject->initDefaultProviderFromToken('sometoken');
        $this->assertAttributeInstanceOf('Zend_Gdata_Calendar', 'dataProvider', $testSubject);
    }

    /**
     * Test if we can reliably determine if we're ready to get some data from the data provider
     */
    public function testIsInitialized() {

        $testSubject = new GoogleCalendar($this);   // Semantically it does not make sense to pass $this,
                                                     // see comment in testConstructor.
        $this->assertTrue($testSubject->isInitialized());
        
        $testSubject = new GoogleCalendar();
        $this->assertFalse($testSubject->isInitialized());

        $testSubject->initDefaultProviderFromToken('sometoken');
        $this->assertTrue($testSubject->isInitialized());
    }

    public function testGetCalendars() {
        $testSubject = new GoogleCalendar($this->getDataProviderMock());

        $this->assertEquals($this->calendarsTestSet, $testSubject->getCalendars(), 'Calendar list was not properly returned by getCalendars()');
    }

    /**
     * Returns a mock object for the Zend_Gdata service implementation
     */
    protected function getDataProviderMock() {

        $mock = $this->getMock('Zend_Gdata_Calendar',
                array('getCalendarListFeed', 'getCalendarEventFeed'),
                array(),
                '',
                false
            );

        $mock->expects($this->any())
                ->method('getCalendarListFeed')
                ->will($this->returnValue($this->calendarsTestSet));

        return $mock;
    }
}

?>
