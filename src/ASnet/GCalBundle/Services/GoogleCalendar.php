<?php

namespace ASnet\GCalBundle\Services;

use \Zend_Gdata_Calendar;
use \Zend_Gdata_Calendar_EventQuery;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a wrapper for the external Zend_Gdata_Calendar class
 * This service helps us to easily utilize Zend_Gdata and capsule it, so we can
 * easily test our code.
 */
class GoogleCalendar {
    protected $dataProvider;
    protected $openingHours;

    /**
     * Creates new instance of the service.
     * @param Array Array of the opening hours in the following structure:
     *              array(
     *                  'Mon' => array('open' => '08:00', 'close' => '18:00'),
     *                  'Tue' => array('open' => '08:00', 'close' => '18:00'),
     *                  ...
     *              )
     *              Every weekday that is not specified is considered to be "closed"
     * @param Object (Optional) Allows to set an object to carry out requests
     *        to Google Calendar API. This allows dependency injection.
     */
    public function __construct($openingHours, $dataProvider = null) {
        $this->openingHours = $openingHours;
        $this->dataProvider = $dataProvider;
    }

    /**
     * Initializes a default Zend_Gdata_Calendar instance using an AuthSub session token.
     * This method allows to use a session token from the current user session to be used
     * with the service instance, when no special provider needs to be injected.
     */
    public function initDefaultProviderFromToken($token) {
        if($token == null) throw new \Exception('Missing authentication token');

        $client = \Zend_Gdata_AuthSub::getHttpClient($token);
        $this->dataProvider = new Zend_Gdata_Calendar($client);
    }

    /**
     * For calls to the methods actually utilizing the data provider, this method
     * will be called to make sure the data provider is set up (i.e. an object for now)
     * In actual production code it would be better to ensure that it exposes the necessary
     * methods.
     */
    public function isInitialized() {
        return is_object($this->dataProvider);
    }

    /**
     * Returns a list of calendars for the current user.
     * This is a boundary method to wrap the external dependency
     * @return Array Array of Calendar-Objects (@see https://developers.google.com/google-apps/calendar/v3/reference/calendarList?hl=de#resource)
     */
    public function getCalendars() {
        return $this->dataProvider->getCalendarListFeed();
    }

    public function getEventsFromCalendar($calendarName) {
        $listFeed = $this->getCalendars();

        $feedUrl = null;
        foreach($listFeed as $feed) {
            if($feed->title == $calendarName) {
                $feedUrl = $feed->link[0]->href;
                break;
            }
        }

        if($feedUrl == null) throw new NotFoundHttpException('Unknown calendar');

        return $this->dataProvider->getCalendarEventFeed($feedUrl);
    }

    /**
     * Add an event with title $title from $start until $end to Calendar $calendarName
     * @param string $title The title of the event
     * @param DateTime $start Begin of the event
     * @param DateTime $end End of the event
     * @param string $calendarName Name of the calendar
     */
    public function addEvent($title, $start, $end, $calendarName) {
        $listFeed = $this->getCalendars();
        $feedUrl = null;
        foreach($listFeed as $feed) {
            if($feed->title == $calendarName) {
                $feedUrl = $feed->link[0]->href;
                break;
            }
        }

        if($feedUrl == null) throw new NotFoundHttpException('Unknown calendar');

        $event = $this->dataProvider->newEventEntry();
        $event->title = $this->dataProvider->newTitle('TEST');

        $when = $this->dataProvider->newWhen();
        $when->startTime = '2012-04-04T12:00:00.000+01:00';
        $when->endTime   = '2012-04-04T13:00:00.000+01:00';

        $event->when = array($when);

        $this->dataProvider->insertEvent($event, $feedUrl);

        $events = $this->getEventsFromCalendar($calendarName);
        foreach($events as $event) {
            if($event->title == $title && isset($event->when[0]) && new \DateTime($event->when[0]->startTime) == $start && new \DateTime($event->when[0]->endTime) == $end)
                return true;
        }
        return false;
    }

    /**
     * Returns $numResults possibilities to store an event of $duration minutes
     * in Calendars with a name from $calendarNames (or less, if there are not enough
     * possibilities before $maxDateTime) starting from $startFrom.
     * @param Array $calendarNames List of calendar names to search in
     * @param integer $eventDuration The length of the event in minutes
     * @param \DateTime $startFrom DateTime to start with
     * @param integer $numResults [Optional] (maximum) number of results to return, default is 50.
     * @param \DateTime $maxDateTime [Optional] Specifies when to stop searching, when there are not enough
     *                          possibilities.
     * @param integer $stepping [Optional] Number of minutes to add after each check.
     *                          E.g. $stepping = 30, $startFrom = "today 08:00" will try to put the event
     *                          on 08:00, 08:30, 9:00,... while $stepping = 60 would try 08:00, 09:00, ...
     *                          Default is 30 minutes per step.
     *
     * @return Array Array of structure:
     *         array(
     *              0 => array( 'calendar' => 'Name of the calendar', 'start' => DateTime, 'end' => DateTime),
     *              1 => array( 'calendar' => 'Name of the calendar', 'start' => DateTime, 'end' => DateTime),
     *              ...
     *          )
     */
    public function getPossibleEventPlacements($calendarNames, $eventDuration, $startFrom = null, $numResults = 50, $maxDateTime = null, $stepping = 30) {
        $possibilitiesFound = array();

        if($startFrom === null) $startFrom = new \DateTime();
        if($maxDateTime === null) {
            $maxDateTime = $startFrom;
            $maxDateTime->modify('+100 days');
        }


        $startDateTime = new \DateTime($startFrom->format('c'));    // I really hate PHP for this.
                                                                    // If you don't do it this way, its
                                                                    // using references and screws up when you use ->modify(). Brilliant!
        $endDateTime = new \DateTime($startDateTime->format('c'));
        $endDateTime->modify('+' . $eventDuration . ' minutes' );

        while(count($possibilitiesFound) < $numResults && $endDateTime <= $maxDateTime) {
            foreach($calendarNames as $calendar) {
                if(count($possibilitiesFound) === $numResults) break;
                try {
                    if($this->isEventPossible($calendar, $startDateTime, $endDateTime)) {
                        $possibilitiesFound[] = array(
                            'calendar'  => $calendar,
                            'start'     => new \DateTime($startDateTime->format('c')), // For those of you, who are still wondering:
                                                                                       // PHP references here if not explicitely overriden!
                            'end'       => new \DateTime($endDateTime->format('c'))
                        );
                    }
                } catch(\Exception $e) {
                    throw $e;
                }
            }

            $startDateTime->modify('+' . $stepping . ' minutes');
            $endDateTime->modify('+' . $stepping . ' minutes');
        }

        return $possibilitiesFound;
    }

    /**
     * Tests if an event (defined by its start and end DateTime) can be stored in the specified calendar.
     * @param string $calendarName Name of the calendar to use
     * @param DateTime $start A DateTime instance specifying the start of the event
     * @param DateTime $end A DateTime instance specifying the start of the event
     * @return boolean Returns true if event is possible, returns false if not
     * @throws NotFoundHttpException In case of an unknown calendar (i.e. no calendar with specified name),
     *         a NotFoundHttpException is thrown
     */
    public function isEventPossible($calendarName, $start, $end) {
        $listFeed = $this->getCalendars();

        $calendarId = null;
        foreach($listFeed as $feed) {
            if($feed->title == $calendarName) {
                $calendarId = $feed->id;
                break;
            }
        }

        if($calendarId == null) throw new NotFoundHttpException('Unknown calendar');

        if((!isset($this->openingHours[$start->format('D')]) || !isset($this->openingHours[$end->format('D')])) ||
            ($start < new \DateTime($start->format('Y-m-d').' ' . $this->openingHours[$start->format('D')]['open'])) ||
            ($end > new \DateTime($end->format('Y-m-d').' ' . $this->openingHours[$end->format('D')]['close'])))
                return false;

        //The Calendar-ID is not suitable to be used in EventQuery, it has to be extracted from a longer URL
        $urlPart = array('','');
        preg_match('#(?<=/)([^/]+)(/private)?(/full)?$#isU', $calendarId, $urlPart);

        $query = new Zend_Gdata_Calendar_EventQuery;
        $tmp = new \DateTime($start->format('c')); //This is necessary, because modify() changes the object DIRECTLY
        $query->setStartMin($tmp->modify('-1 day')->format('Y-m-d'));
        $tmp = new \DateTime($end->format('c'));
        $query->setStartMax($tmp->modify('+1 day')->format('Y-m-d')); //Though the docs said "setStartMax is INCLUSIVE" it turns out: It isn't.
        $query->setOrderBy('starttime');
        $query->setProjection('full');
        $query->setUser($urlPart[1]);
        $query->setVisibility('private');

        foreach($this->dataProvider->getCalendarEventFeed($query) as $event) {
            foreach($event->when as $when) {
                $dtStart = new \DateTime($when->startTime);
                $dtEnd = new \DateTime($when->endTime);


                if(($dtStart < $start && $dtEnd > $start) || ($dtStart >= $start && $dtStart < $end)) {
                    return false;
                }
            }
        }
        return true;
    }
}

?>
