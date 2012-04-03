<?php

namespace ASnet\GCalBundle\Tests\Service;

use ASnet\GCalBundle\Services\GoogleCalendar;

/**
 * Testing the GoogleCalendar service
 */
class GoogleCalendarTest extends \PHPUnit_Framework_TestCase {

    /**
     * The shop opening hours to use for testing
     * @var Array The shop opening hours for each day of the week
     */
    protected $openingHours = array(
        'Mon' => array('open' => '06:00', 'close' => '18:00'),
        'Tue' => array('open' => '06:00', 'close' => '18:00'),
        'Wed' => array('open' => '06:00', 'close' => '18:00'),
        'Thu' => array('open' => '06:00', 'close' => '18:00'),
        'Fri' => array('open' => '06:00', 'close' => '18:00')
    );

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
                        'id' => 'http://calendar.google.com/feeds/default/cal1@calendar.google.com/private',
                        'title' => 'Cal #1',
                        'link' => array( (object) array('href' => 'http://calendar.google.com/cal1') )
                    ),
                (object) array(
                        'id' => 'http://calendar.google.com/feeds/default/cal2@calendar.google.com/full',
                        'title' => 'Cal #2',
                        'link' => array( (object) array('href' => 'http://calendar.google.com/cal2') )
                    ),
                (object) array(
                        'id' => 'http://calendar.google.com/feeds/default/cal3@calendar.google.com/private/full',
                        'title' => 'Cal #3',
                        'link' => array( (object) array('href' => 'http://calendar.google.com/cal3') )
                    ),
            );

        $eventTimes = array(
            array(
                new \DateTime('2012-02-03 08:00'),
                new \DateTime('2012-02-03 09:00')
            ),
            array(
                new \DateTime('2012-02-03 10:00'),
                new \DateTime('2012-02-03 11:00')
            ),
            array(
                new \DateTime('2012-02-02 13:00'),
                new \DateTime('2012-02-02 14:00')
            ),
            array(
                new \DateTime('2012-02-03 13:00'),
                new \DateTime('2012-02-03 14:00')
            ),
            array(
                new \DateTime('2012-02-04 13:00'),
                new \DateTime('2012-02-04 14:00')
            ),
            array(
                new \DateTime('2012-02-03 10:00'),
                new \DateTime('2012-02-03 11:00')
            ),
           array(
                new \DateTime('2012-02-03 10:00'),
                new \DateTime('2012-02-03 11:00')
            ),
            array(
                new \DateTime('2012-02-03 10:00'),
                new \DateTime('2012-02-03 11:00')
            ),
        ); //Its about time to be abled to rely on PHP 5.4

        GoogleCalendarTest::$eventsTestSet = array(
            array(
                    (object) array(
                        'title' => 'Test Event #1 in Calendar #1',
                        'when' => array(
                            0 => (object) array(
                                'startTime' => $eventTimes[0][0]->format(DATE_ISO8601),
                                'endTime' => $eventTimes[0][1]->format(DATE_ISO8601)
                            )
                        )
                    ),
                    (object) array(
                        'title' => 'Test Event #2 in Calendar #1',
                        'when' => array(
                            0 => (object) array(
                                'startTime' => $eventTimes[1][0]->format(DATE_ISO8601),
                                'endTime' => $eventTimes[1][1]->format(DATE_ISO8601)
                            )
                        )
                    ),
                    (object) array(
                        'title' => 'Test Event #3 (recurring) in Calendar #1',
                        'when' => array(
                            0 => (object) array(
                                'startTime' => $eventTimes[2][0]->format(DATE_ISO8601),
                                'endTime' => $eventTimes[2][1]->format(DATE_ISO8601)
                            ),
                            1 => (object) array(
                                'startTime' => $eventTimes[3][0]->format(DATE_ISO8601),
                                'endTime' => $eventTimes[3][1]->format(DATE_ISO8601)
                            ),
                            2 => (object) array(
                                'startTime' => $eventTimes[4][0]->format(DATE_ISO8601),
                                'endTime' => $eventTimes[4][1]->format(DATE_ISO8601)
                            ),
                        )
                    ),
                    (object) array(
                        'title' => 'Test Event #4 (parallel with #2) in Calendar #1',
                        'when' => array(
                            0 => (object) array(
                                'startTime' => $eventTimes[5][0]->format(DATE_ISO8601),
                                'endTime' => $eventTimes[5][1]->format(DATE_ISO8601)
                            )
                        )
                    )
                ),
            array(
                    (object) array(
                        'title' => 'Test Event #1 in Calendar #2',
                        'when' => array(
                            0 => (object) array(
                                    'startTime' => $eventTimes[6][0]->format(DATE_ISO8601),
                                    'endTime' => $eventTimes[6][1]->format(DATE_ISO8601)
                                )
                            )
                        ),
                    (object) array(
                        'title' => 'Test Event #2 in Calendar #2',
                        'when' => array(
                            0 => (object) array(
                                    'startTime' => $eventTimes[7][0]->format(DATE_ISO8601),
                                    'endTime' => $eventTimes[7][1]->format(DATE_ISO8601)
                                )
                            )
                        )
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
        $this->assertAttributeEquals(null, 'dataProvider', new GoogleCalendar(array()));
        $this->assertAttributeEquals($this, 'dataProvider', new GoogleCalendar(array(),$this)); //Passing $this as the data provider does
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
      $testSubject = new GoogleCalendar(array());
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

        $testSubject = new GoogleCalendar(array(), $this);   // Semantically it does not make sense to pass $this,
                                                     // see comment in testConstructor.
        $this->assertTrue($testSubject->isInitialized());
        
        $testSubject = new GoogleCalendar(array());
        $this->assertFalse($testSubject->isInitialized());

        $testSubject->initDefaultProviderFromToken('sometoken');
        $this->assertTrue($testSubject->isInitialized());
    }

    public function testGetCalendars() {
        $testSubject = new GoogleCalendar($this->openingHours, $this->getDataProviderMock());

        $this->assertEquals($this->calendarsTestSet, $testSubject->getCalendars(), 'Calendar list was not properly returned by getCalendars()');
    }

    public function testGetEventsFromCalendar() {

        $testSubject = new GoogleCalendar($this->openingHours, $this->getDataProviderMock());
        try {
            $testSubject->getEventsFromCalendar('unknownCalendar');
            $this->fail('Calling GoogleCalendar::getEventsFromCalendar() with unknown calendar name given should raise an exception');
        } catch(\Exception $e) {
            $this->assertEquals('Unknown calendar', $e->getMessage());
        }

        $this->assertEquals(GoogleCalendarTest::$eventsTestSet[0], $testSubject->getEventsFromCalendar('Cal #1'), 'Get Events for Calendar #1');
        $this->assertEquals(GoogleCalendarTest::$eventsTestSet[1], $testSubject->getEventsFromCalendar('Cal #2'), 'Get Events for Calendar #2');
    }

    public function testIsEventPossible() {
        $testSubject = new GoogleCalendar($this->openingHours, $this->getDataProviderMock());

        try {
            $testSubject->isEventPossible('unknownCalendar', new \DateTime, new \DateTime);
            $this->fail('Calling GoogleCalendar::getEventsForCalendar() with unknown calendar name given should raise an exception');
        } catch(\Exception $e) {
            $this->assertEquals('Unknown calendar', $e->getMessage());
        }

        // Case 1: New event starts before existing event and ends while existing event isn't finished
        $this->assertFalse($testSubject->isEventPossible('Cal #1', new \DateTime('2012-02-03 07:00'), new \DateTime('2012-02-03 08:30')), 'Case #1');
        // Case 2: New event starts when existing event starts and ends befor existing event ends
        $this->assertFalse($testSubject->isEventPossible('Cal #1', new \DateTime('2012-02-03 08:00'), new \DateTime('2012-02-03 08:30')), 'Case #2');
        // Case 3: New event starts after existing events started & before it ended and runs longer than existing event
        $this->assertFalse($testSubject->isEventPossible('Cal #1', new \DateTime('2012-02-03 08:30'), new \DateTime('2012-02-03 09:30')), 'Case #3');
        // Case 4: New event starts after existing events started & ends before existing event ends.
        $this->assertFalse($testSubject->isEventPossible('Cal #1', new \DateTime('2012-02-03 08:10'), new \DateTime('2012-02-03 08:50')), 'Case #4');
        // Case 5: New event ends exactly when existing event starts
        $this->assertTrue($testSubject->isEventPossible('Cal #1', new \DateTime('2012-02-03 07:30'), new \DateTime('2012-02-03 08:00')), 'Case #5');
        // Case 6: New event starts exactly when existing event ends
        $this->assertTrue($testSubject->isEventPossible('Cal #1', new \DateTime('2012-02-03 09:00'), new \DateTime('2012-02-03 09:30')), 'Case #6');
        // Case 7: New event collides with a recurring event
        $this->assertFalse($testSubject->isEventPossible('Cal #1', new \DateTime('2012-02-03 13:30'), new \DateTime('2012-02-03 14:30')), 'Case #7');
        // Case 8: New event would be out of shop opening hours (too early):
        $this->assertFalse($testSubject->isEventPossible('Cal #1', new \DateTime('2012-02-03 05:00 +0100'), new \DateTime('2012-02-03 05:30 +0100')));
        // Case 9: New event would be out of shop opening hours (too late):
        $this->assertFalse($testSubject->isEventPossible('Cal #1', new \DateTime('2012-02-03 22:00 +0100'), new \DateTime('2012-02-03 22:30 +0100')));
        // Case 10: New event would be out of shop opening hours (Weekend):
        $this->assertFalse($testSubject->isEventPossible('Cal #1', new \DateTime('2012-02-04 12:00 +0100'), new \DateTime('2012-02-04 12:30 +0100')));

    }

    public function testGetPossibleEventPlacements() {
        $testSubject = new GoogleCalendar($this->openingHours, $this->getDataProviderMock());
        
        $this->assertEquals(array(), $testSubject->getPossibleEventPlacements(
                    array(),
                    10
                ),
                'Test with empty array for calendars');
        try {
            $testSubject->getEventsFromCalendar('unknownCalendar');
            $this->fail('Calling GoogleCalendar::getPossibleEventPlacementsInCalendar() with unknown calendar name given should raise an exception');
        } catch(\Exception $e) {
            $this->assertEquals('Unknown calendar', $e->getMessage());
        }
        
        $this->assertEquals(array(), $testSubject->getPossibleEventPlacements(
                    array('Cal #1'),
                    60,
                    new \DateTime('2012-02-03 08:00'),
                    2,
                    new \DateTime('2012-02-03 09:00')
                ),
                'Test with no possibilities');

        $this->assertEquals(array(
                    array(
                        'calendar'  => 'Cal #2',
                        'start'     => new \DateTime('2012-02-03 08:00 +0100'),
                        'end'       => new \DateTime('2012-02-03 09:00 +0100')
                    ),
                    array(
                        'calendar'  => 'Cal #2',
                        'start'     => new \DateTime('2012-02-03 08:30 +0100'),
                        'end'       => new \DateTime('2012-02-03 09:30 +0100')
                    )
                ),
                $testSubject->getPossibleEventPlacements(
                    array('Cal #1', 'Cal #2'),
                    60,
                    new \DateTime('2012-02-03 08:00 +0100'),
                    50,
                    new \DateTime('2012-02-03 09:30 +0100')
                ),
                'Test with 2 possibilities in "Cal #2"');
        
        $this->assertEquals(array(
                    array(
                        'calendar'  => 'Cal #1',
                        'start'     => new \DateTime('2012-02-03 07:00 +0100'),
                        'end'       => new \DateTime('2012-02-03 08:00 +0100')
                    ),
                    array(
                        'calendar'  => 'Cal #2',
                        'start'     => new \DateTime('2012-02-03 07:00 +0100'),
                        'end'       => new \DateTime('2012-02-03 08:00 +0100')
                    ),
                    array(
                        'calendar'  => 'Cal #2',
                        'start'     => new \DateTime('2012-02-03 07:30 +0100'),
                        'end'       => new \DateTime('2012-02-03 08:30 +0100')
                    )
                ),
                $testSubject->getPossibleEventPlacements(
                    array('Cal #1', 'Cal #2'),
                    60,
                    new \DateTime('2012-02-03 07:00 +0100'),
                    5,
                    new \DateTime('2012-02-03 08:30 +0100')
                ),
                'Test with 3 possibilities, one in "Cal #1", two in "Cal #2"');

        $this->assertEquals(array(
                    array(
                        'calendar'  => 'Cal #1',
                        'start'     => new \DateTime('2012-02-03 07:00 +0100'),
                        'end'       => new \DateTime('2012-02-03 08:00 +0100')
                    )
                ),
                $testSubject->getPossibleEventPlacements(
                    array('Cal #1', 'Cal #2'),
                    60,
                    new \DateTime('2012-02-03 07:00 +0100'),
                    1,
                    new \DateTime('2012-02-03 08:30 +0100')
                ),
                'Test with more possibilities than requested');

        //Now we're testing the optional stepping
        $this->assertEquals(
                array(
                    array(
                        'calendar'  => 'Cal #1',
                        'start'     => new \DateTime('2012-02-03 06:30 +0100'),
                        'end'       => new \DateTime('2012-02-03 07:30 +0100')
                    )
                ),
                $testSubject->getPossibleEventPlacements(
                    array('Cal #1'),
                    60,
                    new \DateTime('2012-02-03 06:30 +0100'),
                    10,
                    new \DateTime('2012-02-03 08:00 +0100'),
                    60
                ),
                'Test with $stepping = 60 minutes');
    }

    public function testAddEvent() {
        $testSubject = new GoogleCalendar($this->openingHours, $this->getDataProviderMock());
        
        $this->assertTrue($testSubject->addEvent(
                'Test',
                new \DateTime('2012-03-05 12:00 +0100'),
                new \DateTime('2012-03-05 13:00 +0100'),
                'Cal #1'
            ));

        try {
            $testSubject->addEvent(
                'Test',
                new \DateTime('2012-03-05 12:00 +0100'),
                new \DateTime('2012-03-05 13:00 +0100'),
                'Invalid Cal'
            );
            $this->fail('Calling GoogleCalendar::addEvent() with unknown calendar name given should raise an exception');
        } catch(\Exception $e) {
            $this->assertEquals('Unknown calendar', $e->getMessage());
        }
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

                    if(is_object($calendar)) $calendar = $calendar->getUser();

                    if($calendar == 'http://calendar.google.com/cal1' || $calendar == 'cal1@calendar.google.com' || is_object($calendar)) {
                        return GoogleCalendarTest::$eventsTestSet[0];
                    }
                    else if($calendar == 'http://calendar.google.com/cal2' || $calendar == 'cal2@calendar.google.com') {
                        return GoogleCalendarTest::$eventsTestSet[1];
                    }
                    else {
                        return array();
                    }
                }));

        return $mock;
    }
}

?>
