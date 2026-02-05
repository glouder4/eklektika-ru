<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use OnlineService\CRM\PersonalStaff;

if( $_REQUEST['ACTION'] == "UPDATE_MANAGER" ){
    $staff = new PersonalStaff();
    echo $staff->update($_REQUEST);
}