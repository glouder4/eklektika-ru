<?php

namespace OnlineService\B24;

class Request
{
    protected function sendRequest($params, $debug = false)
    {
        return RestClient::postSiteRequestsHandler($params, $debug);
    }
}
