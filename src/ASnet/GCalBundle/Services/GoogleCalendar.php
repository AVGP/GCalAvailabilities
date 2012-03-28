<?php

namespace ASnet\GCalBundle\Services;

use \Zend_Gdata_Calendar;

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

    
}

?>