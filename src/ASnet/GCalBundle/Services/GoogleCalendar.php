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

    /**
     * Creates new instance of the service.
     * @param Object (Optional) Allows to set an object to carry out requests
     *        to Google Calendar API. This allows dependency injection.
     */
    public function __construct($dataProvider = null) {
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


                if(($dtStart < $start && $dtEnd > $start) || ($dtStart >= $start && $dtStart < $end))
                    return false;
            }
        }
        return true;
    }

}

?>
