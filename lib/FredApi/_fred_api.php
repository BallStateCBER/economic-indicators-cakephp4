<?php

/*

Copyright (c) 2009, 2015 Federal Reserve Bank of St. Louis

This source file is subject to the BSD license,
that is bundled with this package in the file LICENSE, and is
available through the world-wide-web at the following url:
https://research.stlouisfed.org/docs/api/licenses/BSD_LICENSE


*/

require_once 'fred_api_exception.php';

class fred_api
{
    public $api_key = null;

    function __construct($api_key = null)
    {
        if (empty($api_key)) {
            throw new fred_api_exception('Your API key has not been set.');
        }

        $this->api_key = $api_key;
    }

    function factory($type)
    {
        $class = 'fred_api_'.strtolower($type);
        $file = $class.'.php';
        if (!file_exists($file)) {
            throw new fred_api_exception('File does not exist: '.$file);
        }

        require_once($file);
        $object = new $class($this->api_key);

        return $object;
    }
}

?>
