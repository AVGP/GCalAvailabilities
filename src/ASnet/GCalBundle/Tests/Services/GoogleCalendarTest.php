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
     *              'title': 'xyz',
     *              'link': array(
     *                  0 => { href: http://calendar.google.com/xyz}
     *              )
     *          },
     *          ...
     *      )
     */
    protected $calendarsTestSet;

    /**
     * List of events for mocked Zend_Gdata_Calendar will return when asked for a list of events.
     * @var Array Structure
     *      array(
     *          {
     *              'title': 'xyz',
     *              'when': array(
     *                  0 => DateTime,
     *                  ..
     *          },
     *          ...
     *      )
     */
    public static $eventsTestSet;

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

        GoogleCalendarTest::$eventsTestSet = array(
            array(
                    (object) array(
                        'title' => 'Test Event #1 in Calendar #1',
                        'when' => array(
                            0 => (object) array(
                                'startTime' => new \DateTime('tomorrow 8am'),
                                'endTime' => new \DateTime('tomorrow 9am')
                            )
                        )
                    ),
                    (object) array(
                        'title' => 'Test Event #2 in Calendar #1',
                        'when' => array(
                            0 => (object) array(
                                'startTime' => new \DateTime('tomorrow 10am'),
                                'endTime' => new \DateTime('tomorrow 11am')
                            )
                        )
                    ),
                    (object) array(
                        'title' => 'Test Event #3 (recurring) in Calendar #1',
                        'when' => array(
                            0 => (object) array(
                                'startTime' => new \DateTime('today 1pm'),
                                'endTime' => new \DateTime('today 2pm')
                            ),
                            1 => (object) array(
                                'startTime' => new \DateTime('tomorrow 1pm'),
                                'endTime' => new \DateTime('tomorrow 2pm')
                            ),
                            2 => (object) array(
                                'startTime' => new \DateTime('+2 days 1pm'),
                                'endTime' => new \DateTime('+2 days 2pm')
                            ),
                        )
                    ),
                ),
            array(
                    (object) array('title' => 'Test Event #1 in Calendar #2'),
                    (object) array('title' => 'Test Event #2 in Calendar #2')
                )
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

    public function testGetEventsFromCalendar() {

        $testSubject = new GoogleCalendar($this->getDataProviderMock());
        try {
            $testSubject->getEventsFromCalendar('unknownCalendar');
            $this->fail('Calling GoogleCalendar::getEventsFromCalendar() with unknown calendar name given should raise an exception');
        } catch(\Exception $e) {
            $this->assertEquals('Unknown calendar', $e->getMessage());
        }

        $this->assertEquals(GoogleCalendarTest::$eventsTestSet[0], $testSubject->getEventsFromCalendar('Cal #1'));
        $this->assertEquals($this->eventsTestSet[1], $testSubject->getEventsFromCalendar('Cal #2'));
    }

    public function testIsEventPossible() {
        $testSubject = new GoogleCalendar($this->getDataProviderMock());

        try {
            $testSubject->isEventPossible('unknownCalendar', new \DateTime, new \DateTime);
            $this->fail('Calling GoogleCalendar::getEventsForCalendar() with unknown calendar name given should raise an exception');
        } catch(\Exception $e) {
            $this->assertEquals('Unknown calendar', $e->getMessage());
        }

        //Case 1: New event starts before existing event and ends while existing event isn't finished
        $this->assertFalse($testSubject->isEventPossible('Cal #1', new \DateTime('tomorrow 7am'), new DateTime('tomorrow 8:30am')));
        //Case 2: New event starts when existing event starts and ends befor existing event ends
        $this->assertFalse($testSubject->isEventPossible('Cal #1', new \DateTime('tomorrow 8am'), new DateTime('tomorrow 8:30am')));
        //Case 3: New event starts after existing events started & before it ended and runs longer than existing event
        $this->assertFalse($testSubject->isEventPossible('Cal #1', new \DateTime('tomorrow 8:30am'), new DateTime('tomorrow 9:30am')));
        //Case 4: New event starts after existing events started & ends before existing event ends.
        $this->assertFalse($testSubject->isEventPossible('Cal #1', new \DateTime('tomorrow 8:10am'), new DateTime('tomorrow 8:50am')));
        // Case 5: New event ends exactly when existing event starts
        $this->assertTrue($testSubject->isEventPossible('Cal #1', new \DateTime('tomorrow 7:30am'), new DateTime('tomorrow 8:00am')));
        // Case 6: New event starts exactly when existing event ends
        $this->assertTrue($testSubject->isEventPossible('Cal #1', new \DateTime('tomorrow 9:00am'), new DateTime('tomorrow 9:30am')));

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

        $mock->expects($this->any())
                ->method('getCalendarEventFeed')
                ->will($this->returnCallback(function($calendar) {
                    if($calendar == 'http://calendar.google.com/cal1')
                        return GoogleCalendarTest::$eventsTestSet[0];
                    else if($calendar == 'http://calendar.google.com/cal1')
                        return GoogleCalendarTest::$eventsTestSet[1];
                    else
                        return array();
                }));

        return $mock;
    }
}

?>
