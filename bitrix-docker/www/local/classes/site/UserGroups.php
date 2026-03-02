<?php
    namespace OnlineService\Site;

    class UserGroups{
        private array $request;
        private ?int $group_id = null;
        public function __construct($request)
        {
            $this->request = $request;

            if( isset($this->request['ACTION']) )
                $this->GroupAction();
        }

        private function GroupAction(){
            if( $this->request['ACTION'] == "UPDATE_GROUP" ){
                $this->group_id = $this->searchGroup()['ID'];

                if( !$this->group_id ){
                    $this->group_id = $this->createGroup();
                }
                else
                    $this->updateGroup();
            }
        }

        public function getGroupId(){
            return $this->group_id;
        }

        public function searchGroup($id = null){

            $rsGroups = \CGroup::GetList($by = "c_sort", $order = "asc", array(
                'STRING_ID' => !is_null($id) ? "GROUP_".$id : "GROUP_".$this->request['ID']
            )); // выбираем группы

            return $rsGroups->Fetch() ?? false;
        }
        // Создание статуса
        private function createGroup(){
            $group = new \CGroup;
            $arFields = Array(
                "ACTIVE"       => $this->request['ACTIVE'],
                "C_SORT"       => $this->request['C_SORT'],
                "NAME"         => $this->request['NAME'],
                "DESCRIPTION"  => "",
                "USER_ID"      => array(),
                "STRING_ID"      => "GROUP_".$this->request['ID']
            );
            $NEW_GROUP_ID = $group->Add($arFields);
            if (strlen($group->LAST_ERROR)>0) ShowError($group->LAST_ERROR);

            return $NEW_GROUP_ID;
        }
        // Обновление статуса
        private function updateGroup(){
            $group = new \CGroup;
            $arFields = Array(
                "ACTIVE"       => $this->request['ACTIVE'],
                "C_SORT"       => $this->request['C_SORT'],
                "NAME"         => $this->request['NAME'],
            );
            $group->Update($this->group_id, $arFields);
            if (strlen($group->LAST_ERROR)>0) ShowError($group->LAST_ERROR);
        }

        public function updatePriceTypeID($id = null){
            if ($id === null) {
                return;
            }
            $group = new \CGroup;
            $arFields = Array(
                "PRICE_TYPE_ID" => $id
            );
            $group->Update($this->group_id, $arFields);
            if (strlen($group->LAST_ERROR)>0) ShowError($group->LAST_ERROR);
        }
    }