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

    
}

?>
