<?php
    // \OnlineService\B24\User
    AddEventHandler("main", "OnBeforeUserDelete", "OnBeforeUserDeleteHandler");
    function OnBeforeUserDeleteHandler($userId){
        $user = new \OnlineService\B24\User();

        $user->OnBeforeUserDeleteHandler($userId);
    }

    // \OnlineService\B24\RegisterUserCompany
    AddEventHandler("main", "OnBeforeUserRegister", "OnBeforeUserRegisterHandler");

    function OnBeforeUserRegisterHandler(&$arFields){
        $registerUserCompany = new \OnlineService\B24\RegisterUserCompany();

        $result = $registerUserCompany->OnBeforeUserRegisterHandler($arFields);
        return $result;
    }
    function OnAfterUserRegisterHandler(&$arFields){
        $registerUserCompany = new \OnlineService\B24\RegisterUserCompany();

        $registerUserCompany->OnAfterUserRegisterHandler($arFields);
    }

    AddEventHandler("main", "OnAfterUserRegister", "OnAfterUserRegisterHandler");


    function OnAfterUserUpdateHandler(&$arFields){
        if($arFields["RESULT"]){
            $userObj = new \OnlineService\B24\User();

            $userObj->OnAfterUserUpdateHandler($arFields);
        }
    }

    AddEventHandler("main", "OnAfterUserUpdate", "OnAfterUserUpdateHandler");