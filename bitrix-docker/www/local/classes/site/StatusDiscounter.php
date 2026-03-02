<?php
    namespace OnlineService\Site;
    class StatusDiscounter{
        private array $request;
        private OnlineService\Site\UserGroups $group;
        public function __construct($params,OnlineService\Site\UserGroups $group)
        {
            $this->request = $params;
            $this->group = $group;
        }
    }