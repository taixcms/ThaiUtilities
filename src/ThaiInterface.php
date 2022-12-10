<?php

namespace ThaiUtilities;

use \Closure;
use \Exception;

/**
 * Class ThaiInterface
 * */
abstract class ThaiInterface
{
    public $lastID;
    public $insert_id;
    public $ProjectID;
    public $userid;
    public $debug;
    public $sqlType = '-sqlType-';
    public $LangConverter;
    public $CallBackFunction;
    public $Lang;
    public $database;
    public $SelectCurr;
    public $Db;
    public $Connect;
    public $IDs;
    public $ExtraData;
    public $Data;
    public $valueWhere;
    public $memcache_time;
    public $fieldsWhere;
    public $PageSimpleURL;
    public $AuthHost;
    public $numberPage;
    public $sortOrder;
    public $sortOrderFieldName;
    public $TableNameWhere;
    public $sortOrderType;
    public $Request;
    public $memcache_get;
    public $memcache_set;
    public $TableName;
    public $memcache_obj;
    public $Skeleton;
    public $CountSelectResult;
    public $CountSelect;
    public $Limit;
    public $countPage;
    public $currentPage;
    public $sqlCount;
    public $Offset;
    public $checkSum;
    public $autoSearch;
    public $CrossAjaxData;
    public $Attachments;
    public $Permissions;
    public $DefaultValue;
    public $Condition;
    public $KeyExcludeTranslate = ['id', 'key'];
    public $DefaultFieldType;
    public $ClassAction;
    public $Filter;
    public $fieldsId;
    public $isLogged;
    public $isAdmin = false;
    private static $Config;

    /**
     * @return mixed
     */
    public static function getConfig()
    {
        return self::$Config;
    }

    /**
     * @return Doctrine\ORM\EntityManager
     * @throws ORMException
     */
    public function getEm():Doctrine\ORM\EntityManager
    {
        return self::$Config->getEm();
    }

    /**
     * @param $Config
     * @return $this
     */
    public function setConfig( $Config): ThaiInterface
    {
        self::$Config = $Config;
        return $this;
    }

    /**
     * @return string
     */
    public function getSqlType(): string
    {
        return $this->sqlType;
    }

    /**
     * @param string $sqlType
     * @return $this
     */
    public function setSqlType(string $sqlType): ThaiInterface
    {
        $this->sqlType = $sqlType;
        return $this;
    }
    
    /**
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }

    /**
     * @param bool $isAdmin
     * @return $this
     */
    public function setIsAdmin(bool $isAdmin): ThaiInterface
    {
        $this->isAdmin = $isAdmin;
        return $this;
    }

    /**
     * @param  $Db
     * @param  $className
     */
    public function __construct($Db = NULL, $className = NULL)
    {
        if (get_class($Db) != 'mysqli' && get_class($Db) != 'db') {

            if (!empty($Db->db_id)) {
                $this->Connect = $Db->db_id;
            } else {
                $this->Connect = $Db;
            }
            $this->Db = $Db;

            if ($className) {
                $this->TableName = $className;
            } else {
                $this->TableName = strtolower(get_class($this));
            }
            $this->mergeParam($Db)->setNameDataBase($Db->database);
        } else {

            if (!empty($Db->db_id)) {
                $this->Connect = $Db;
            } else {
                if (!empty($Db->Db)) {
                    $this->Connect = $Db->Db;
                } else {
                    $this->Connect = $Db;
                }
            }
            if (!empty($Db->Db)) {
                $this->Db = $Db->Db;
            } else {
                $this->Db = $Db;
            }

            if ($className) {
                $this->TableName = $className;
            } else {
                $this->TableName = strtolower(get_class($this));
            }
        }
        $this->setLimit(10)->getPermission()->setCurrentPage(1);

    }


    /**
     * @param bool $Filter
     * @return bool
     */
    public function isFilter(bool $Filter = null): ?bool
    {
        if ($Filter != null) {
            $this->Filter = $Filter;
        } else {
            if ($Filter === false || $Filter === true) {
                $this->Filter = $Filter;
            }
        }
        return $this->Filter;
    }

    /**
     * @param array|null $param
     * @param string|null $key
     * @return $this
     * @throws Exception
     */
    public function get(array $param = null, string $key = null): ThaiInterface
    {
        $Permission = $this->checkPermission($this->getUser($this->getUserId()), 'view');
        if (!$Permission) {
            $this->setData('error', array_merge($this->getData('error'), [[
                'method' => 'getListByUserComponents',
                'data' => [],
                'TableName' => $this->TableName,
                'key' => $key,
                'msg' => $this->translated('Access denied'),
            ]]));
            return $this;
        }
        if ($this->Connect) {
            $dopSqlText = '';
            if (!empty($param)) {
                foreach ($param as $keys => $value) {
                    if (gettype($keys) === 'integer') {
                        $dopSqlText .= " and " . $value . " ";
                    } else {
                        $dopSqlText .= " and " . $keys . "='" . $value . "' ";
                    }
                }
            }
            $getRequest = $this->getRequest();
            if (!empty($getRequest['Filters'])) {
                foreach ($getRequest['Filters'] as $Filters) {
                    $dopSqlText .= $this->getFieldTextSql($Filters);
                }
            }

            $sqlText = "SELECT " . $this->getSqlType() . " FROM " . $this->getTableNameWhere() . " " . $this->parseSqlPermission($this->getUser($this->getUserId())) . " " . $dopSqlText;
            //var_dump($sqlText);
            $this->setSqlCount(str_replace($this->getSqlType(), 'COUNT(*)', $sqlText));
            $arr = $this->ReformatRows(
                $this->query(
                    str_replace(
                        $this->getSqlType(),
                        $this->getTableNameWhere() . '.*',
                        $this->ReformatSql($sqlText)
                    )
                )
            );
            if (!empty($key)) {
                $this->setData($key, $arr);
            } else {
                $this->setData($this->TableName, $arr);
            }

        }
        return $this;
    }

    /**
     * @return $this
     */
    public function init(): ThaiInterface
    {
        return $this;
    }

    /**
     * @param array $item
     * @param string $type
     * @return string
     */
    public function parseSqlPermission(array $item, string $type = 'search'): string
    {
        $return_string = null;
        if (!empty($this->Permissions[$this->TableName][$type])) {
            $condition = str_replace('{thisUserID}', $this->getUser($this->getUserId())['id'], $this->Permissions[$this->TableName][$type]['condition']);
            $condition_var = ' where ' . str_replace('{table}', $this->getTableNameWhere(), $condition);
            foreach ($this->Permissions[$this->TableName][$type]['items'] as $value) {
                if ($value['query'] != '') {
                    foreach ($item as $k => $v) {
                        $value['query'] = str_replace('{' . $k . '}', $v, $value['query']);
                        $value['query'] = str_replace('{table}', $this->getTableNameWhere(), $value['query']);
                        $value['query'] = str_replace('{thisUserID}', $this->getUser($this->getUserId())['id'], $value['query']);
                        $condition_var = str_replace('{' . $k . '}', $v, $condition_var);
                    }
                    $return_string .= ' ' . $value['query'];
                }
            }
            $return_string .= $condition_var;
        }
        return $return_string ?: ' where 1=1';
    }

    /**
     * @param array $item
     * @param string $type
     * @return $this
     */
    public function getPermissionSetting(array $item, string $type): ThaiInterface
    {
        $this->checkPermission($item, $type);
        return $this;
    }

    /**
     * @param array $item
     * @param string $type
     * @return bool|null
     */
    public function checkPermission(array $item, string $type): ?bool
    {
        if (!empty($this->Permissions[$this->TableName][$type])) {
            $condition_var = $this->Permissions[$this->TableName][$type]['condition'];
            foreach ($this->Permissions[$this->TableName][$type]['items'] as $keyItems => $valueItems) {
                foreach ($item as $k => $v) {
                    if ($k != 'attachments' && gettype($v) != 'array') {
                        $valueItems['query'] = str_replace('{' . $k . '}', $v, $valueItems['query']);
                    }
                }
                $thisUserID = $this->getUser($this->getUserId())['id'];
                $valueItems['query'] = str_replace('{thisUserID}', $thisUserID, $valueItems['query']);
                $valueItems['query'] = str_replace('{table}', $this->getTableNameWhere(), $valueItems['query']);
                if ($valueItems['query'] != '') {
                    $permissions = $this->query($valueItems['query']);
                    if ($permissions) {
                        $condition_var = str_replace('{' . $this->Permissions[$this->TableName][$type]['items'][$keyItems]['id'] . '}', 'true', $condition_var);
                        $this->Permissions[$this->TableName][$type]['items'][$keyItems]['check'] = true;
                    } else {
                        $condition_var = str_replace('{' . $this->Permissions[$this->TableName][$type]['items'][$keyItems]['id'] . '}', 'false', $condition_var);
                        $this->Permissions[$this->TableName][$type]['items'][$keyItems]['check'] = false;
                    }
                }
            }
            $cond = eval('return ' . $condition_var . ';');
            if (count($this->Permissions[$this->TableName][$type]['items']) === 0) {
                $cond = true;
            }
            if (!empty($item['id']) && ($type != 'search')) {
                $this->setCondition($type, [
                    'id' => $item['id'],
                    'is' => $cond,
                    'caption' => $this->translated($this->Permissions[$this->TableName][$type]['caption']),
                    'message' => $this->translated($this->Permissions[$this->TableName][$type]['message']),
                    'caption_error' => $this->translated($this->Permissions[$this->TableName][$type]['caption_error']),
                    'message_error' => $this->translated($this->Permissions[$this->TableName][$type]['message_error'])
                ]);
            } else {
                $this->setCondition($type, [
                    'is' => $cond,
                    'caption' => $this->translated($this->Permissions[$this->TableName][$type]['caption']),
                    'message' => $this->translated($this->Permissions[$this->TableName][$type]['message']),
                    'caption_error' => $this->translated($this->Permissions[$this->TableName][$type]['caption_error']),
                    'message_error' => $this->translated($this->Permissions[$this->TableName][$type]['message_error'])
                ]);
            }
            return $cond;
        }
        if ($type === 'remove') {
            return null;
        }
        if ($type === 'update') {
            return null;
        }
        if ($type === 'edit') {
            return null;
        }
        return true;
    }

    /**
     * @param $fiendName
     * @param $value
     * @return $this
     */
    public function setDefaultValue($fiendName, $value): ThaiInterface
    {
        $this->DefaultValue[$fiendName] = $value;
        return $this;
    }

    /**
     * @param string $fiendName
     * @return mixed|null
     */
    public function getDefaultValue(string $fiendName)
    {
        if (!empty($this->DefaultValue[$fiendName])) {
            return $this->DefaultValue[$fiendName] ?: null;
        } else {
            return null;
        }
    }

    /**
     * @param string $ConditionType
     * @param array $value
     * @return $this
     */
    public function setCondition(string $ConditionType, array $value = []): ThaiInterface
    {
        if (empty($this->Condition[$this->TableName])) {
            $this->Condition[$this->TableName] = [];
            $this->Condition[$this->TableName][$ConditionType] = [];
        }
        if (!empty($value['id'])) {
            $this->Condition[$this->TableName][$ConditionType][$value['id']] = ['is' => $value['is'], 'caption' => $value['caption'], 'message' => $value['message'], 'caption_error' => $value['caption_error'], 'message_error' => $value['message_error']];
        } else {
            $this->Condition[$this->TableName][$ConditionType] = ['is' => $value['is'], 'caption' => $value['caption'], 'message' => $value['message'], 'caption_error' => $value['caption_error'], 'message_error' => $value['message_error']];
        }
        return $this;
    }

    /**
     * @param string $ConditionType
     * @return array|null
     */
    public function getCondition(string $ConditionType): ?array
    {
        if (!empty($this->Condition[$this->TableName])) {
            if (!empty($this->Condition[$ConditionType])) {
                return $this->Condition[$ConditionType] ?: null;
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    /**
     * @return $this
     */
    public function getPermission(): ThaiInterface
    {
        $this->Permissions[$this->TableName] = [];
        $permissions = $this->query("SELECT * FROM permissions where components= '" . $this->TableName . "'");
        if ($permissions) {
            $this->Permissions[$this->TableName] = [];
            if (!empty($permissions)) {
                foreach ($permissions as $v) {
                    $p_g = $this->query("SELECT * FROM permissions_groups where id= '" . $v['idgroup'] . "' and enabled = '1'");
                    if (!empty($p_g)) {
                        foreach ($p_g as $v_g) {
                            $p_s = $this->query("SELECT * FROM permissions_setting where idgroup= '" . $v['idgroup'] . "'");
                            $ArrSettings = [];
                            if (!empty($p_s)) {
                                foreach ($p_s as $v_s) {
                                    $ArrSettings[] = [
                                        'query' => base64_decode($this->dbStringOld($v_s['database_query'])),
                                        'id' => $v_s['id']
                                    ];
                                }
                            }

                            $this->Permissions[$this->TableName][$v_g['permission_type']] = [
                                'condition' => $this->dbStringOld(base64_decode($v_g['condition_text'])),
                                'items' => $ArrSettings,
                                'message' => $this->dbStringOld($v_g['message']),
                                'caption' => $this->dbStringOld($v_g['caption']),
                                'message_error' => $this->dbStringOld($v_g['message_error']),
                                'caption_error' => $this->dbStringOld($v_g['caption_error'])
                            ];
                        }
                    }
                }
            }
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getCrossAjaxData(): array
    {
        return $this->CrossAjaxData;
    }

    /**
     * @return array
     */
    public function getPermissionType(): array
    {
        return [
            ['id' => 'edit', 'value' => $this->translated('Editing rights')],
            ['id' => 'view', 'value' => $this->translated('Viewing rights')],
            ['id' => 'viewItem', 'value' => $this->translated('Viewing item right')],
            ['id' => 'creat', 'value' => $this->translated('Creation right')],
            ['id' => 'update', 'value' => $this->translated('Update right')],
            ['id' => 'remove', 'value' => $this->translated('Remove right')],
            ['id' => 'search', 'value' => $this->translated('Search')],
            ['id' => 'add', 'value' => $this->translated('Add')],
            ['id' => 'addTo', 'value' => $this->translated('Add to')],
            ['id' => 'root', 'value' => $this->translated('Root')]
        ];
    }

    /**
     * @param string|null $key
     * @return string
     */
    public function _GET(string $key = null): ?string
    {
        $params = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
        parse_str($params, $ArrParams);
        if ($key) {
            if (!empty($ArrParams[$key])) {
                return $ArrParams[$key];
            } else {
                return null;
            }
        } else {
            return $ArrParams;
        }
    }


    /**
     * @return int
     */
    public function getRouteParam(): ?int
    {
        $ID = null;
        if (!empty($_REQUEST['routestring'])) {
            $routeString = explode('/', $_REQUEST['routestring']);
            if (count($routeString) >= 3) {
                $ID = (int)explode('/', $_REQUEST['routestring'])[2];
            }
        }
        if (!empty($_REQUEST['route'])) {
            $routeString = explode('/', $_REQUEST['route']);
            if (count($routeString) >= 2) {
                $ID = (int)explode('/', $_REQUEST['route'])[1];
            }
        }
        return $ID;
    }

    /**
     * @return int
     */
    public function getUserIdByRoute(): ?int
    {
        $ID = null;
        if (!empty($_REQUEST['routestring'])) {
            $routeString = explode('/', $_REQUEST['routestring']);
            if (count($routeString) >= 3) {
                $ID = (int)explode('/', $_REQUEST['routestring'])[2];
            }
        }
        if (!empty($_REQUEST['route'])) {
            $routeString = explode('/', $_REQUEST['route']);
            if (count($routeString) >= 2) {
                $ID = (int)explode('/', $_REQUEST['route'])[1];
            }
        }
        if ((int)$ID <= 0) {
            $ID = $this->getUserId();
        }
        return $ID;
    }

    /**
     * @param string $Key
     * @param array|string $CrossAjaxData
     * @return $this
     */
    public function setCrossAjaxData(string $Key, $CrossAjaxData): ThaiInterface
    {
        $this->CrossAjaxData[$this->TableName][$Key] = $CrossAjaxData;
        return $this;
    }

    /**
     * @param string $str
     * @return string
     */
    public function translated(string $str): string
    {
        $funcCalBack = $this->getLangConverter();
        return (new \users($this->Connect))->translated($funcCalBack, $str, $this->Lang, $this->TableName);
    }

    /**
     * @param int $id
     * @return array
     */
    public function getUser(int $id): array
    {
        return (new \users($this->Connect))->getUserDTO($id);
    }

    /**
     * @param string $Key
     * @param array|string $CrossAjaxData
     * @return $this
     */
    public function setCrossAjaxDataDTO(string $Key, $CrossAjaxData): ThaiInterface
    {
        $this->CrossAjaxData[$Key] = $CrossAjaxData;
        return $this;
    }

    /**
     * @return $this
     */
    public function setAttachmentsStatus(): ThaiInterface
    {
        $this->Attachments[$this->TableName] = true;
        $this->setCrossAjaxData('Attachments', true);
        return $this;
    }

    /**
     * @return boolean
     */
    public function getAttachmentsStatus(): bool
    {
        if (!empty($this->Attachments)) {
            if (!empty($this->Attachments[$this->TableName])) {
                return $this->Attachments[$this->TableName];
            }
        }
        return false;
    }

    /**
     * @param String $Key
     * @param int $autoSearch
     * @return $this
     */
    public function setAutoSearchDTO(string $Key, int $autoSearch): ThaiInterface
    {
        $this->autoSearch[$Key] = $autoSearch;
        return $this;
    }

    /**
     * @param String $Key
     * @param String $ClassAction
     * @return $this
     */
    public function setClassActionDTO(string $Key, string $ClassAction): ThaiInterface
    {
        $this->ClassAction[$Key] = $ClassAction;
        return $this;
    }

    /**
     * @param int $autoSearch
     * @return $this
     */
    public function setAutoSearch(int $autoSearch): ThaiInterface
    {
        $this->autoSearch[$this->TableName] = $autoSearch;
        return $this;
    }


    /**
     * @param string $ClassAction
     * @return $this
     */
    public function setClassAction(string $ClassAction): ThaiInterface
    {
        $this->ClassAction[$this->TableName] = $ClassAction;
        return $this;
    }


    /**
     * @param int $id
     * @return array
     */
    public function getHistory(int $id): array
    {
        $arr = [];
        $query = $this->query("SELECT * FROM information_schema.tables WHERE TABLE_SCHEMA='" . $this->database . "' and TABLE_NAME='" . $this->getTableNameWhere() . "_history'");
        if ($query) {
            $arrRow = $this->query("SELECT * FROM " . $this->getTableNameWhere() . "_history where id ='" . $id . "'  ORDER BY index_id ASC");
            foreach ($arrRow as $uRow) {
                if (!empty($uRow['userid'])) {
                    $uRow['userid'] = (int)$uRow['userid'];
                    $uRow['username'] = $this->getUser($uRow['userid'])['name'] . ' ' . $this->getUser($uRow['userid'])['surname'];
                }
                if (!empty($uRow['id'])) {
                    $uRow['id'] = (int)$uRow['id'];
                }
                if (!empty($uRow['use_as_default'])) {
                    $uRow['use_as_default'] = filter_var($uRow['use_as_default'], FILTER_VALIDATE_BOOLEAN);
                }
                if ($this->getAttachmentsStatus()) {
                    if (!empty($uRow['attachments'])) {
                        $uRow['attachments'] = $this->getAttachments($uRow['attachments']);
                    } else {
                        $uRow['attachments'] = [];
                    }
                }
                $arr[] = $uRow;
            }
        }
        return $arr;
    }

    /**
     * @return $this
     */
    public function setHistory(): ThaiInterface
    {

        return $this;
    }

    /**
     * @return array
     */
    public function getAutoSearch(): array
    {
        return $this->autoSearch ?: [];
    }

    /**
     * @return array
     */
    public function getClassAction(): array
    {
        return $this->ClassAction ?: [];
    }

    /**
     * @param String $str
     * @return String
     */
    public function prepareStr(string $str): string
    {
        $str = encode_emoji($str);
        $str = replace_specchars_encode($str);
        return str_replace(
            array('\\', "\0", "\n", "\r", "'", "\x1a", "&#160;"),
            array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\Z', ' '),
            $str);
    }

    /**
     * @param String $str
     * @return String
     */
    public function prepareStrOld(string $str): string
    {
        $str = encode_emoji($str);
        $str = replace_specchars_encode($str);
        return str_replace(
            array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\Z', ' '),
            array('\\', "\0", "\n", "\r", "'", "\x1a", "&#160;"),
            $str);
    }

    /**
     * @param String $string
     * @return String
     */
    public function dbString(string $string): string
    {
        return htmlentities($string);
    }

    /**
     * @param String|null $string
     * @return String
     */
    public function dbStringOld(string $string = null): ?string
    {
        return html_entity_decode($string);
//        return $string;
    }

    /**
     * @param String $Key
     * @return $this
     * @throws Exception
     */
    public function setCheckSum(string $Key): ThaiInterface
    {
        if ($this->Connect && method_exists($this->Connect, 'get_checksum')) {
            $this->checkSum[$Key] = $this->Connect->get_checksum($Key);
        } else {
            $this->checkSum[$Key] = random_int(0, 99999999);
        }
        return $this;
    }

    /**
     * @param String $Key
     * @param String $checkSum
     * @return $this
     */
    public function setCheckSumDTO(string $Key, string $checkSum): ThaiInterface
    {
        $this->checkSum[$Key] = $checkSum;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getCheckSum(): ?array
    {
        return $this->checkSum;
    }

    /**
     * @param array|null $data
     * @return array
     */
    public function saveCallback(array $data = null): ?array
    {
        return $data;
    }

    /**
     * @param array $data
     * @return array
     */
    public function callbackBeforeSave(array $data): ?array
    {
        return $data;
    }

    /**
     * @param array $data
     * @return array
     */
    public function callbackAfterSave(array $data): ?array
    {
        return $data;
    }

    /**
     * @param array $data
     * @return array
     */
    public function callbackAfterUpdate(array $data): ?array
    {
        return $data;
    }

    /**
     * @param array $data
     * @return array
     */
    public function callbackAfterRemove(array $data): ?array
    {
        return $data;
    }

    /**
     * @param array $data
     * @return array
     */
    public function callbackBeforeRemove(array $data): ?array
    {
        return $data;
    }


    /**
     * @param array $data
     * @return array
     */
    public function callbackPostProcessing(array $data): ?array
    {
        return $data;
    }

    /**
     * @return $this
     * @throws Exception
     */
    public function getSkeleton(): ThaiInterface
    {
        if ($this->Connect && empty($this->Skeleton[$this->TableName])) {
            $this->init();
            $this->setCheckSum($this->getTableNameWhere());
            $arr = [];
            if (!empty($this->database) && $this->database != '') {
                $uRows = $this->query("SELECT DISTINCT COLUMN_NAME as fieldName,COLUMN_TYPE as fieldType,COLUMN_COMMENT as required FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='" . $this->database . "' AND TABLE_NAME='" . $this->getTableNameWhere() . "'");
            } else {
                $uRows = $this->query("SELECT DISTINCT COLUMN_NAME as fieldName,COLUMN_TYPE as fieldType,COLUMN_COMMENT as required FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='" . $this->getTableNameWhere() . "'");
            }
            if ($uRows) {
                foreach ($uRows as $uRow) {
                    if (strpos($uRow['fieldType'], 'enum') !== false) {
                        preg_match('/enum\((.*)\)$/', $uRow['fieldType'], $matches);
                        foreach (explode(',', $matches[1]) as $value) {
                            if (!empty($matches[1])) {
                                $uRow['fieldData'][] = [
                                    'key' => str_replace("'", '', $value),
                                    'value' => $this->translated(str_replace("'", '', $value))
                                ];
                            }
                            $matches[1] = str_replace('\'', '', $matches[1]);
                            $uRow['fieldOption'] = explode(',', $matches[1]);
                            $uRow['fieldType'] = 'enum';
                        }
                    }
                    if (strpos($uRow['fieldType'], 'varchar') !== false) {
                        $uRow['fieldType'] = 'varchar';
                    }
                    if (strpos($uRow['fieldType'], 'tinyint') !== false) {
                        $uRow['fieldType'] = 'bool7777';
                    }

                    if (strpos($uRow['fieldType'], 'int') !== false) {
                        $uRow['fieldType'] = 'int';
                    }
                    if (strpos($uRow['fieldType'], 'bool7777') !== false) {
                        $uRow['fieldType'] = 'tinyint';
                    }
                    if (strpos($uRow['fieldType'], 'decimal') !== false) {
                        $uRow['fieldType'] = 'decimal';
                    }
                    if ($uRow['fieldName'] === 'sortOrder') {
                        $this->setsortOrder('sortOrder');
                    }
                    if ($uRow['fieldName'] === 'id') {
                        $this->setsortOrderFieldName('id');
                    }
                    if ($uRow['required'] === 'required') {
                        $uRow['required'] = true;
                    } else {
                        $uRow['required'] = false;
                    }
                    if ($this->getDefaultFieldType($uRow['fieldName'])) {
                        $uRow['fieldType'] = $this->getDefaultFieldType($uRow['fieldName']);
                    }
                    $uRow['default'] = $this->getDefaultValue($uRow['fieldName']);
                    $arr[] = $uRow;
                }
            } else {
                $this->setData('error', array_merge($this->getData('error'), [[
                    'method' => 'getSkeleton',
                    'data' => [],
                    'TableName' => $this->TableName,
                    'key' => '>> ' . $this->getTableNameWhere() . ' << ' . $this->translated('dont table'),
                    'msg' => $this->translated('this and other methods are called only for a class that has tables in the database, for intermediate classes you do not need to specify methods, this creates an extra load on the server'),
                ]]));
            }
            $this->setSkeleton($this->TableName, $arr);
        }
        return $this;
    }

    /**
     * @param string $Key
     * @param array $Skeleton
     * @return $this
     */
    public function setSkeleton(string $Key, array $Skeleton): ThaiInterface
    {
        $this->Skeleton[$Key] = $Skeleton;
        return $this;
    }


    /**
     * @param array $Value
     * @return $this
     */
    public function setSkeletonField(array $Value): ThaiInterface
    {
        $this->Skeleton[$this->TableName][] = $Value;
        return $this;
    }


    /**
     * @param array $fieldInfo
     * @return $this
     */
    public function setDefaultFieldType(array $fieldInfo): ThaiInterface
    {
        $this->setCrossAjaxData('DefaultFieldType', $fieldInfo);
        if (empty($this->DefaultFieldType[$this->TableName])) {
            $this->DefaultFieldType = [];
            $this->DefaultFieldType[$this->TableName] = [];
        }
        $this->DefaultFieldType[$this->TableName][$fieldInfo[0]] = $fieldInfo[1];
        return $this;
    }

    /**
     * @return bool
     */
    public function isSelect(): ?bool
    {
        return (count($this->getDataObject()['Data'][$this->TableName]) >= 1);
    }

    /**
     * @param string $fieldName
     * @return string
     */
    public function getDefaultFieldType(string $fieldName): ?string
    {
        if (!empty($this->DefaultFieldType[$this->TableName])) {
            if (!empty($this->DefaultFieldType[$this->TableName][$fieldName])) {
                return $this->DefaultFieldType[$this->TableName][$fieldName];
            } else {
                return null;
            }
        } else {
            return null;
        }
    }


    /**
     * @param object $memcache_obj
     * @return $this
     */
    public function setMemcache_obj(object $memcache_obj): ThaiInterface
    {
        $this->memcache_obj = $memcache_obj;
        return $this;
    }

    /**
     * @param object $thisClass
     * @return $this
     */
    public function mergeParam(object $thisClass): ThaiInterface
    {
        $this->ProjectID = $thisClass->ProjectID;
        $this->SelectCurr = $thisClass->SelectCurr;
        $this->memcache_obj = $thisClass->memcache_obj;
        $this->LangConverter = $thisClass->getLangConverter();
        $this->memcache_set = $thisClass->memcache_set;
        $this->memcache_get = $thisClass->memcache_get;
        $this->PageSimpleURL = $thisClass->PageSimpleURL;
        $this->AuthHost = $thisClass->AuthHost;
        $this->isLogged = $thisClass->isLogged;
        $this->userid = $thisClass->userid;
        $this->Lang = $thisClass->Lang;
        return $this;
    }

    /**
     * @return object
     */
    public function getMemcache_obj(): object
    {
        return $this->memcache_obj;
    }

    /**
     * @param Closure $langFunction
     * @return $this
     */
    public function setLangConverter(Closure $langFunction): ThaiInterface
    {
        $this->LangConverter = $langFunction;
        return $this;
    }

    /**
     * @param String $key
     * @param Closure $Func
     * @return array|null
     */
    public function getCache(string $key, Closure $Func): ?array
    {
        if ($this->memcache_time) {
            $funcCalBackMemcache_get = $this->memcache_get;
            $funcCalBackMemcache_set = $this->memcache_set;
            if ($funcCalBackMemcache_get) {
                $data = $funcCalBackMemcache_get($this->memcache_obj, $key);
                if ($data) {
                    return $data;
                } else {
                    $data = $Func();
                    $funcCalBackMemcache_set($this->memcache_obj, $key, $data, 0, $this->memcache_time);
                }
            } else {
                $data = $Func();
            }
        } else {
            $data = $Func();
        }

        if (!empty($data)) {
            if (gettype($data) === 'object') {
                $uRowArr = [];
                foreach ($data as $uRow) {
                    $uRowArr[] = $uRow;
                }
            } else {
                return $data;
            }
            return $uRowArr;
        } else {
            return null;
        }
    }

    /**
     * @return Closure
     */
    public function getLangConverter(): Closure
    {
        return $this->LangConverter;
    }

    /**
     * @return Closure
     */
    public function getCallBack(): Closure
    {
        if (!$this->CallBackFunction) {
            $this->CallBackFunction = function ($a) {
                return $a;
            };
        }
        return $this->CallBackFunction;
    }

    /**
     * @param Closure $CallBackFunction
     * @return $this
     */
    public function setCallBack(Closure $CallBackFunction): ThaiInterface
    {
        $this->CallBackFunction = $CallBackFunction;
        return $this;
    }

    /**
     * @param Closure $CallBackFunction
     * @return $this
     */
    public function setCallBackToInit(Closure $CallBackFunction): ThaiInterface
    {
        if (empty($this->CallBackFunction)) {
            $this->setCallBack($CallBackFunction);
        }
        return $this;
    }

    /**
     * @param string $sql
     * @return string
     */
    public function ReformatSql(string $sql): ?string
    {
        if (!empty($this->getsortOrderFieldName()[$this->TableName])) {
            $sql = $sql . " ORDER BY " . $this->getTableNameWhere() . "." . $this->getsortOrderFieldName()[$this->TableName] . " " . $this->getsortOrderType()[$this->TableName];
        }
        if (!empty($this->getLimit()[$this->TableName])) {
            $sql = $sql . ' LIMIT ' . $this->getLimit()[$this->TableName];
        }
        if (!empty($this->getOffset()[$this->TableName])) {
            $sql = $sql . ' OFFSET ' . $this->getOffset()[$this->TableName];
        }
        return $sql;
    }

    /**
     * @param array|null $rows
     * @return array
     * @throws Exception
     */
    public function ReformatRows(array $rows = null): ?array
    {
        $this->getSkeleton();
        $arr = [];
        if ($rows) {
            foreach ($rows as $row) {
                foreach ($this->Skeleton[$this->TableName] as $value) {
                    if ($value['fieldType'] === 'int' && isset($row[$value['fieldName']])) {
                        $row[$value['fieldName']] = (int)$row[$value['fieldName']];
                    }
                    if ($value['fieldType'] === 'double' && isset($row[$value['fieldName']])) {
                        $row[$value['fieldName']] = (double)$row[$value['fieldName']];
                    }
                    if ($value['fieldType'] === 'decimal' && isset($row[$value['fieldName']])) {
                        $row[$value['fieldName']] = (double)$row[$value['fieldName']];
                    }
                    if ($value['fieldType'] === 'tinyint' && isset($row[$value['fieldName']])) {
                        $row[$value['fieldName']] = (int)$row[$value['fieldName']];
                    }
                    if ($value['fieldType'] === 'array' && isset($row[$value['fieldName']])) {
                        $row[$value['fieldName']] = explode('|', $row[$value['fieldName']]);
                    }
                    if ($value['fieldType'] === 'varchar' && isset($row[$value['fieldName']])) {
                        $row[$value['fieldName']] = $this->dbStringOld($row[$value['fieldName']]);
                    }
                    if ($value['fieldType'] === 'text' && isset($row[$value['fieldName']])) {
                        $row[$value['fieldName']] = $this->dbStringOld($row[$value['fieldName']]);
                    }
                    if ($value['fieldType'] === 'mediumtext' && isset($row[$value['fieldName']])) {
                        $row[$value['fieldName']] = $this->dbStringOld($row[$value['fieldName']]);
                    }
                    if ($value['fieldName'] === 'attachments') {
                        $this->setAttachmentsStatus();
                    }
                }
                if (!empty($row['attachments'])) {
                    $row['attachments'] = $this->getAttachments($row['attachments']);
                } else {
                    $row['attachments'] = [];
                }
                $cFunc = $this->getCallBack();
                $itemValue = $cFunc($row);

                if ($itemValue) {
                    if (!empty($itemValue['id'])) {
                        $this->setIDs($itemValue['id']);
                    }
                    $Permission = $this->checkPermission($itemValue, 'viewItem');
                    if ($Permission) {
                        $this->setConditionToDataObject($itemValue);
                        $arr[] = $itemValue;
                    }
                }

            }
        }
        return $arr;
    }

    /**
     * @param array $item
     * @return $this
     */
    public function setConditionToDataObject(array $item = []): ThaiInterface
    {
        $sqlText = "SELECT * FROM permissions WHERE components = '" . $this->TableName . "'";
        $condition = $this->query($sqlText);
        if (!empty($condition)) {
            foreach ($condition as $rowCondition) {
                $sqlText2 = "SELECT * FROM permissions_groups WHERE id = '" . $rowCondition['idgroup'] . "'";
                $permissions_groups = $this->query($sqlText2);
                if (!empty($permissions_groups)) {
                    foreach ($permissions_groups as $rowGroups) {
                        if ((int)$rowGroups['enabled'] >= 1 && $rowGroups['permission_type'] != 'search') {
                            $this->getPermissionSetting($item, $rowGroups['permission_type']);
                        }
                    }
                }
            }
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getLang(): string
    {
        return $this->Lang;
    }

    /**
     * @return string
     */
    public function getSelectCurr(): ?string
    {
        return $this->SelectCurr;
    }

    /**
     * @param Closure $memcache_set
     * @return $this
     */
    public function setSetMemCache_set(Closure $memcache_set): ThaiInterface
    {
        $this->memcache_set = $memcache_set;
        return $this;
    }

    /**
     * @return Closure
     */
    public function getSetMemCache_set(): Closure
    {
        return $this->memcache_set;
    }

    /**
     * @param Closure $memcache_get
     * @return $this
     */
    public function setMemCache_get(Closure $memcache_get): ThaiInterface
    {
        $this->memcache_get = $memcache_get;
        return $this;
    }

    /**
     * @return Closure
     */
    public function getMemCache_get(): Closure
    {
        return $this->memcache_get;
    }

    /**
     * @param String $lang
     * @return $this
     */
    public function setLang(string $lang): ThaiInterface
    {
        $this->Lang = $lang;
        return $this;
    }

    /**
     * @param String $database
     * @return $this
     */
    public function setNameDataBase(string $database): ThaiInterface
    {
        $this->database = $database;
        return $this;
    }

    /**
     * @param String $SelectCurr
     * @return $this
     */
    public function setSelectCurr(string $SelectCurr): ThaiInterface
    {
        $this->SelectCurr = $SelectCurr;
        return $this;
    }

    /**
     * @param String $AuthHost
     * @return $this
     */
    public function setAuthHost(string $AuthHost): ThaiInterface
    {
        $this->AuthHost = $AuthHost;
        return $this;
    }


    /**
     * @return String
     */
    public function getAuthHost(): string
    {
        return $this->AuthHost;
    }

    /**
     * @param bool $isLogged
     * @return $this
     */
    public function setIsLogged(bool $isLogged): ThaiInterface
    {
        $this->isLogged = $isLogged;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsLogged(): bool
    {
        return $this->isLogged;
    }

    /**
     * @param String $PageSimpleURL
     * @return $this
     */
    public function setPageSimpleURL(string $PageSimpleURL): ThaiInterface
    {
        $this->PageSimpleURL = $PageSimpleURL;
        return $this;
    }

    /**
     * @return String
     */
    public function getPageSimpleURL(): string
    {
        return $this->PageSimpleURL;
    }

    /**
     * @param String|null $text
     * @param String|null $lngTo
     * @return mixed
     */
    public function getPhrase(string $text = NULL, string $lngTo = NULL)
    {
        $funcCalBack = $this->getLangConverter();
        return $funcCalBack($text, $lngTo);
    }

    /**
     * @param array $tmpArray
     * @return array
     */
    public function translateOneItems(array $tmpArray): array
    {
        foreach (array_keys($tmpArray) as $value) {
            if (!in_array($value, $this->KeyExcludeTranslate, true)) {
                $tmpArray[$value] = $this->translated($tmpArray[$value]);
            }
        }
        return $tmpArray;
    }

    /**
     * @param String $ExcludeKey
     * @return $this
     */
    public function AddExcludeKey(string $ExcludeKey): ThaiInterface
    {
        $this->KeyExcludeTranslate[] = $ExcludeKey;
        return $this;
    }

    /**
     * @param String $ExcludeKey
     * @return $this
     */
    public function unsetExcludeKey(string $ExcludeKey): ThaiInterface
    {
        unset($this->KeyExcludeTranslate[$ExcludeKey]);
        return $this;
    }


    /**
     * @param string $sqlCount
     * @return $this
     */
    public function setSqlCount(string $sqlCount): ThaiInterface
    {
        $this->sqlCount[$this->TableName] = $sqlCount;
        return $this;
    }

    /**
     * @return array
     */
    public function getSqlCount(): array
    {
        return $this->sqlCount ?: [];
    }


    /**
     * @param string $sortOrder
     * @return $this
     */
    public function setSortOrder(string $sortOrder): ThaiInterface
    {
        $this->sortOrder[$this->TableName] = $sortOrder;
        return $this;
    }


    /**
     * @param string $sortOrderType
     * @return $this
     */
    public function setSortOrderType(string $sortOrderType): ThaiInterface
    {
        $this->setCrossAjaxData('sortOrderType', $sortOrderType);
        $this->sortOrderType[$this->TableName] = $this->dbString($sortOrderType);
        return $this;
    }


    /**
     * @param string $Key
     * @param string $sortOrder
     * @return $this
     */
    public function setSortOrderDTO(string $Key, string $sortOrder): ThaiInterface
    {
        $this->sortOrder[$Key] = $this->dbString($sortOrder);
        return $this;
    }

    /**
     * @param string $Key
     * @param array $DefaultFieldType
     * @return $this
     */
    public function setDefaultFieldTypeDTO(string $Key, array $DefaultFieldType): ThaiInterface
    {
        $this->DefaultFieldType[$Key] = $DefaultFieldType;
        return $this;
    }


    /**
     * @param string $sortOrderFieldName
     * @return $this
     */
    public function setSortOrderFieldName(string $sortOrderFieldName): ThaiInterface
    {
        $this->setCrossAjaxData('sortOrderFieldName', $sortOrderFieldName);
        $this->sortOrderFieldName[$this->TableName] = $this->dbString($sortOrderFieldName);
        return $this;
    }

    /**
     * @return array
     */
    public function getSortOrderFieldName(): array
    {
        return $this->sortOrderFieldName ?: [];
    }

    /**
     * @return array
     */
    public function getSortOrderType(): array
    {
        return $this->sortOrderType ?: [$this->TableName => 'ASC'];
    }

    /**
     * @return array
     */
    public function getSortOrder(): array
    {
        return $this->sortOrder ?: [];
    }

    /**
     * @param array $Filters
     * @return string
     */
    public function getFieldTextSql(array $Filters): string
    {
        $dopSqlText = '';
        if (!empty($Filters['fieldSort'])) {
            if ($Filters['fieldSort'] == 'like') {
                if (!empty($Filters['fieldValueArray'])) {
                    if (gettype($Filters['fieldValueArray']) === 'string') {
                        $dopSqlText = " AND " . $Filters['fieldName'] . " LIKE '%" . $Filters['fieldValueArray'] . "%'";
                    }
                }
                if (!empty($Filters['fieldValue'])) {
                    if (gettype($Filters['fieldValue']) === 'string') {
                        $dopSqlText = " AND " . $Filters['fieldName'] . " LIKE '%" . $Filters['fieldValue'] . "%'";
                    } else {
                        foreach ($Filters['fieldValue'] as $v) {
                            if (count($Filters['fieldValue']) <= 1) {

                                $dopSqlText = " AND " . $Filters['fieldName'] . " LIKE '%" . $v . "%'";
                            } else {
                                if ($dopSqlText === '') {
                                    $dopSqlText .= " AND (" . $Filters['fieldName'] . " LIKE '%" . $v . "%'";
                                } else {
                                    $dopSqlText .= " OR " . $Filters['fieldName'] . " LIKE '%" . $v . "%'";
                                }
                            }
                        }

                        if (count($Filters['fieldValue']) >= 2) {
                            $dopSqlText .= ')';
                        }
                    }
                }

            } elseif ($Filters['fieldSort'] == 'range') {
                if (!empty($Filters['fieldValue'])) {
                    if (gettype($Filters['fieldValue']) !== 'string') {
                        $dopSqlText = " AND " . $Filters['fieldName'] . " BETWEEN " . $Filters['fieldValue'][0] . " AND " . $Filters['fieldValue'][1];
                    }
                }
            } elseif ($Filters['fieldSort'] == 'rangeDate') {
                if (!empty($Filters['fieldValue'])) {
                    if (gettype($Filters['fieldValue']) !== 'string') {
                        $dopSqlText = " AND " . $Filters['fieldName'] . " BETWEEN '" . date("Y-m-d h:m:s", strtotime($Filters['fieldValue'][0])) . "' AND '" . date("Y-m-d h:m:s", strtotime($Filters['fieldValue'][1])) . "'";
                    }
                }
            } elseif ($Filters['fieldSort'] == 'equals') {
                if (!empty($Filters['fieldValue'])) {
                    if (gettype($Filters['fieldValue']) === 'string') {
                        $dopSqlText = " AND " . $Filters['fieldName'] . " = '" . $Filters['fieldValue'] . "'";
                    } else {
                        foreach ($Filters['fieldValue'] as $v) {
                            if (count($Filters['fieldValue']) <= 1) {
                                $dopSqlText = " AND " . $Filters['fieldName'] . "   '" . $v . "'";
                            } else {
                                if ($dopSqlText === '') {
                                    $dopSqlText .= " AND (" . $Filters['fieldName'] . " = '" . $v . "'";
                                } else {
                                    $dopSqlText .= " OR " . $Filters['fieldName'] . " = '" . $v . "'";
                                }
                            }
                        }
                        if (count($Filters['fieldValue']) >= 2) {
                            $dopSqlText .= ')';
                        }
                    }
                }
            }
        }
        return $dopSqlText;
    }

    /**
     * @param string $AttachmentString
     * @return array
     */
    public function getAttachments(string $AttachmentString): array
    {
        $Attachments = [];

        $AttachmentStringArrayID = explode(",", $AttachmentString);
        if (count($AttachmentStringArrayID) >= 1) {
            foreach ($AttachmentStringArrayID as $AttachmentID) {
                $inSql = "'attachments_" . $AttachmentID . "'";
                $sql = "SELECT * FROM filestorage_users where object_id in(" . $inSql . ")";
                $uRows = $this->query($sql);

                $sql3 = "SELECT * FROM attachments where id = " . $AttachmentID;
                $uRows222 = $this->query($sql3);

                if (!empty($uRows)) {
                    foreach ($uRows as $uRow) {
                        $AttachmentsID = (int)explode("_", $uRow['object_id'])[1];
                        $Attachments[] = [
                            'FileID' => $AttachmentsID,
                            'FileName' => $uRow['description'],
                            'Description' => $uRow['description'],
                            'UploadProgress' => null,
                            'NewName' => $uRow['file_name'],
                            'date_upload' => $uRows222[0]['date_upload'],
                            'file_size' => $uRows222[0]['file_size'],
                            'ErrorMessage' => null,
                            'preview' => $uRows222[0]['preview'],
                            'Selected' => true,
                            'Type' => true,
                            'commentscount' => $uRows222[0]['commentscount']
                        ];
                    }
                }
            }
        }
        return $Attachments;
    }

    /**
     * @param string|null $key
     * @return $this
     * @throws Exception
     */
    public function getListByUser(string $key = null): ThaiInterface
    {
        if (empty($this->getLimit()[$this->TableName])) {
            $this->setLimit(10);
        }
        $getRequest = $this->getRequest();
        $dopSqlText = '';
        if (!empty($getRequest['Filters'])) {
            foreach ($getRequest['Filters'] as $key => $Filters) {
                $dopSqlText .= $this->getFieldTextSql($Filters);
            }
        }

        if ($this->Connect) {
            $sqlText = "SELECT " . $this->getSqlType() . " FROM " . $this->getTableNameWhere() . " where " . $this->getFieldsWhere() . " = '" . $this->getValueWhere() . "' AND userid = '" . $this->getUserId() . "' " . $dopSqlText;
            $this->setSqlCount(str_replace($this->getSqlType(), 'COUNT(*)', $sqlText));
            $arr = $this->ReformatRows(
                $this->query(
                    str_replace(
                        $this->getSqlType(),
                        $this->getTableNameWhere() . '.*',
                        $this->ReformatSql($sqlText)
                    )
                )
            );
            $this->setData($key ?: $this->TableName, $arr);
        }
        return $this;
    }


    /**
     * @return array
     */
    public function getIDs(): array
    {
        if ($this->IDs[$this->TableName]) {
            return $this->IDs[$this->TableName];
        } else {
            return [];
        }
    }

    /**
     * @param string $key
     * @return array
     */
    public function getFieldsIDs(string $key = ''): ?array
    {
        if ($key === '') {
            return $this->IDs[$this->TableName];
        } else {
            return $this->IDs[$key];
        }
    }

    /**
     * @param string $key
     * @return array
     */
    public function getFields(string $key): array
    {
        $output_arr = [];

        foreach ($this->getData($this->TableName) as $k => $v) {


            if (gettype($v) == 'array') {

                foreach ($v as $k2 => $v2) {

                    if ($k2 === $key) {
                        $output_arr[] = $v2;
                    }

                }

            } else {
                if ($k === $key) {
                    $output_arr[] = $v;
                }
            }


            if (gettype($v) == 'string' && $k === $key) {
                $output_arr[] = $v;
            }
        }

        return $output_arr;
    }

    /**
     * @param int $id
     * @return ThaiInterface
     */
    public function setIDs(int $id): ThaiInterface
    {
        $this->IDs[$this->TableName][] = $id;
        return $this;
    }

    /**
     * @param string|null $key
     * @return $this
     * @throws Exception
     */
    public function getListToPermission(string $key = null): ThaiInterface
    {
        $Permission = $this->checkPermission($this->getUser($this->getUserId()), 'view');
        if (!$Permission) {
            $this->setData('error', array_merge($this->getData('error'), [[
                'method' => 'getListToPermission',
                'data' => [],
                'TableName' => $this->TableName,
                'key' => $key,
                'msg' => $this->translated('Access denied'),
            ]]));
            return $this;
        }
        if (empty($this->getLimit()[$this->TableName])) {
            $this->setLimit(10);
        }

        $getRequest = $this->getRequest();
        $dopSqlText = '';
        if (!empty($getRequest['Filters'])) {
            foreach ($getRequest['Filters'] as $Filters) {
                $dopSqlText .= $this->getFieldTextSql($Filters);
            }
        }

        if ($this->Connect) {
            $sqlText = "SELECT " . $this->getSqlType() . " FROM " . $this->getTableNameWhere() . " " . $this->parseSqlPermission($this->getUser($this->getUserId())) . " and " . $this->getTableNameWhere() . "." . $this->getFieldsWhere() . " = '" . $this->getValueWhere() . "'" . $dopSqlText;
            $this->setSqlCount(str_replace($this->getSqlType(), 'COUNT(*)', $sqlText));
            $arr = $this->ReformatRows(
                $this->query(
                    str_replace(
                        $this->getSqlType(),
                        $this->getTableNameWhere() . '.*',
                        $this->ReformatSql($sqlText)
                    )
                )
            );
            $this->setData($key ?: $this->TableName, $arr);
        }
        return $this;
    }


    /**
     * @param string|null $key
     * @return $this
     * @throws Exception
     */
    public function getList(string $key = null): ThaiInterface
    {
        $Permission = $this->checkPermission($this->getUser($this->getUserId()), 'view');
        if (!$Permission) {
            $this->setData('error', array_merge($this->getData('error'), [[
                'method' => 'getList',
                'data' => [],
                'TableName' => $this->TableName,
                'key' => $key,
                'msg' => $this->translated('Access denied'),
            ]]));
            return $this;
        }
        if (empty($this->getLimit()[$this->TableName])) {
            $this->setLimit(10);
        }
        $getRequest = $this->getRequest();
        $dopSqlText = '';
        if (!empty($getRequest['Filters'])) {
            foreach ($getRequest['Filters'] as $Filters) {
                $dopSqlText .= $this->getFieldTextSql($Filters);
            }
        }
        if ($this->Connect) {
            $sqlText = "SELECT " . $this->getSqlType() . " FROM " . $this->getTableNameWhere() . " where " . $this->getFieldsWhere() . " = '" . $this->getValueWhere() . "'" . $dopSqlText;
            $this->setSqlCount(str_replace($this->getSqlType(), 'COUNT(*)', $sqlText));
            $arr = $this->ReformatRows(
                $this->query(
                    str_replace(
                        $this->getSqlType(),
                        $this->getTableNameWhere() . '.*',
                        $this->ReformatSql($sqlText)
                    )
                )
            );
            $this->setData($key ?: $this->TableName, $arr);
        }
        return $this;
    }


    /**
     * @param string $FieldName
     * @param string|int $FieldValue
     * @param string|null $key
     * @return $this
     * @throws Exception
     */
    public function getItemsByField(string $FieldName, string $FieldValue, string $key = NULL): ThaiInterface
    {
        if ($this->Connect) {
            $sqlText = "SELECT * FROM " . $this->getTableNameWhere() . " where " . $FieldName . " = '" . $FieldValue . "'";
            $this->setSqlCount(str_replace($this->getSqlType(), 'COUNT(*)', $sqlText));
            $arr = $this->ReformatRows(
                $this->query(
                    str_replace(
                        $this->getSqlType(),
                        $this->getTableNameWhere() . '.*',
                        $this->ReformatSql($sqlText)
                    )
                )
            );
            $this->setData($key ?: $this->TableName, $arr);
        }
        return $this;
    }

    /**
     * @param string $FieldName
     * @param string|int $FieldValue
     * @param string|null $key
     * @return $this
     * @throws Exception
     */
    public function getItemByField(string $FieldName, string $FieldValue, string $key = NULL): ThaiInterface
    {
        if ($this->Connect) {
            $sqlText = "SELECT * FROM " . $this->getTableNameWhere() . " where " . $FieldName . " = '" . $FieldValue . "'";
            $this->setSqlCount(str_replace($this->getSqlType(), 'COUNT(*)', $sqlText));
            $arr = $this->ReformatRows(
                $this->query(
                    str_replace(
                        $this->getSqlType(),
                        $this->getTableNameWhere() . '.*',
                        $this->ReformatSql($sqlText)
                    )
                )
            );
            if (count($arr) >= 1) {
                if (!empty($key)) {
                    $this->setData($key, $arr[0]);
                } else {
                    $this->setData($this->TableName, $arr[0]);
                }
            } else {
                if (!empty($key)) {
                    $this->setData($key, []);
                } else {
                    $this->setData($this->TableName, []);
                }
            }

        }
        return $this;
    }


    /**
     * @param array $idList
     * @param string|null $key
     * @return $this
     * @throws Exception
     */
    public function getItemsByIDs(array $idList, string $key = NULL): ThaiInterface
    {
        if ($this->Connect) {
            $sqlText = "SELECT * FROM " . $this->getTableNameWhere() . " where " . $this->getFieldsWhere() . " in(" . implode(',', $idList) . ")";
            $this->setSqlCount(str_replace($this->getSqlType(), 'COUNT(*)', $sqlText));
            $arr = $this->ReformatRows(
                $this->query(
                    str_replace(
                        $this->getSqlType(),
                        $this->getTableNameWhere() . '.*',
                        $this->ReformatSql($sqlText)
                    )
                )
            );
            $this->setData($key ?: $this->TableName, $arr);
        }
        return $this;
    }


    /**
     * @param int|string $id
     * @param string|null $key
     * @return $this
     * @throws Exception
     */
    public function getItemByID($id, string $key = NULL): ThaiInterface
    {
        if ((int)$id <= 0) {
            $this->setData('error', array_merge($this->getData('error'), [[
                'method' => 'getItemByID',
                'data' => [],
                'TableName' => $this->TableName,
                'key' => $key,
                'msg' => '($id = 0) -  You are trying to get records from the table by ID equal to zero',
            ]]));
        }
        if ($this->Connect) {
            $sqlText = "SELECT * FROM " . $this->getTableNameWhere() . " where id ='" . $id . "'";
            $this->setSqlCount(str_replace($this->getSqlType(), 'COUNT(*)', $sqlText));
            $arr = $this->ReformatRows(
                $this->query(
                    str_replace(
                        $this->getSqlType(),
                        $this->getTableNameWhere() . '.*',
                        $this->ReformatSql($sqlText)
                    )
                )
            );
            if (count($arr) == 0) {
                $this->setData('error', array_merge($this->getData('error'), [[
                    'method' => 'getItemByID',
                    'data' => $arr,
                    'TableName' => $this->TableName,
                    'key' => $key,
                    'msg' => 'The record was not found in the database, the method should not return an empty response',
                ]]));
            }
            if (count($arr) >= 2) {
                $this->setData('error', array_merge($this->getData('error'), [[
                    'method' => 'getItemByID',
                    'data' => $arr,
                    'TableName' => $this->TableName,
                    'key' => $key,
                    'msg' => 'More than 1 record found, the method should not return an array, then use the method - getItemsByID',
                ]]));
            }
            $this->setData($key ?: $this->TableName, (!empty($arr[0]) ? $arr[0] : []));
        }
        return $this;
    }

    /**
     * @param string $sqlText
     * @return bool
     */
    public function is_select(string $sqlText = ''): bool
    {
        $searchArr = ['select', 'show'];
        $sqlArr = explode(' ', trim($sqlText));
        $key = in_array(mb_strtolower($sqlArr[0]), $searchArr);
        if ($key === false) {
            return false;
        }
        return true;
    }

    /**
     * @param string $sqlText
     * @param bool $cache
     * @return array|bool|null
     */
    public function query(string $sqlText = '', bool $cache = true)
    {
        if (!empty($this->Connect->db_id)) {
            if ($this->is_select($sqlText)) {
                return $this->Connect->super_query($sqlText, $cache);
            } else {
                $Results = $this->Connect->query($sqlText, $cache);
                $this->insert_id = $this->Connect->insert_id;
                return $Results;
            }
        }
        if ($sqlText === '') {
            return null;
        }
        try {
            $result = $this->Connect->query($sqlText);
            $this->insert_id = $this->Connect->insert_id;
        } catch (\Exception $e) {
            return null;
        }
        $rows = null;
        if (gettype($result) == 'array') {
            return $result;
        }
        if ($result === true) {
            return true;
        }
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            if (method_exists($this->Connect, 'store_result')) {
                do {
                    if ($result = $this->Connect->store_result()) {
                        while ($row = $result->fetch_row()) {
                            printf("'%s'\n", $row[0]);
                        }
                        $result->free();
                        $result->close();
                    }
                    if ($this->Connect->more_results()) {
                        printf("-----------------\n");
                    }

                } while ($this->Connect->more_results() && $this->Connect->next_result());
            }
        }
        return $rows;
    }

    /**
     * @param int $UserID
     * @param string|null $key
     * @return $this
     * @throws Exception
     */
    public function getItemByUserID(int $UserID, string $key = NULL): ThaiInterface
    {
        if ($UserID <= 0) {
            $this->setData('error', array_merge($this->getData('error'), [[
                'method' => 'getItemByUserID',
                'data' => [],
                'TableName' => $this->TableName,
                'key' => $key,
                'msg' => '($UserID = 0) -  You are trying to get records from the table by user ID equal to zero',
            ]]));
        }
        if ($this->Connect) {
            $sqlText = "SELECT * FROM " . $this->getTableNameWhere() . " where " . $this->getFieldsWhere() . " = '" . $UserID . "'";
            $this->setSqlCount(str_replace($this->getSqlType(), 'COUNT(*)', $sqlText));
            $arr = $this->ReformatRows(
                $this->query(
                    str_replace(
                        $this->getSqlType(),
                        $this->getTableNameWhere() . '.*',
                        $this->ReformatSql($sqlText)
                    )
                )
            );
            if (count($arr) == 0) {
                $this->setData('error', array_merge($this->getData('error'), [[
                    'method' => 'getItemByUserID',
                    'data' => $arr,
                    'TableName' => $this->TableName,
                    'key' => $key,
                    'msg' => 'The record was not found in the database, the method should not return an empty response',
                ]]));
            }
            if (count($arr) >= 2) {
                $this->setData('error', array_merge($this->getData('error'), [[
                    'method' => 'getItemByUserID',
                    'data' => $arr,
                    'TableName' => $this->TableName,
                    'key' => $key,
                    'msg' => 'More than 1 record found, the method should not return an array, if you need to select several records by ID, then use the method - getItemsByUserID',
                ]]));
            }
            $this->setData($key ?: $this->TableName, (!empty($arr[0]) ? $arr[0] : null));
        }
        return $this;
    }

    /**
     * @param int $UserID
     * @param string|null $key
     * @return $this
     * @throws Exception
     */
    public function getItemsByUserID(int $UserID, string $key = NULL): ThaiInterface
    {
        if ($this->Connect) {
            $sqlText = "SELECT " . $this->getSqlType() . " FROM " . $this->getTableNameWhere() . " where " . $this->getFieldsWhere() . " = '" . $UserID . "'";
            $this->setSqlCount(str_replace($this->getSqlType(), 'COUNT(*)', $sqlText));
            $arr = $this->ReformatRows(
                $this->query(
                    str_replace(
                        $this->getSqlType(),
                        $this->getTableNameWhere() . '.*',
                        $this->ReformatSql($sqlText)
                    )
                )
            );
            $this->setData($key ?: $this->TableName, $arr);
        }
        return $this;
    }

    /**
     * @param array $idList
     * @param string|null $key
     * @return $this
     * @throws Exception
     */
    public function getItemsByUserIDs(array $idList, string $key = NULL): ThaiInterface
    {
        if ($this->Connect) {
            $sqlText = "SELECT " . $this->getSqlType() . " FROM " . $this->getTableNameWhere() . " where '" . $this->getFieldsWhere() . "' in(" . implode(',', $idList) . ")";
            $this->setSqlCount(str_replace($this->getSqlType(), 'COUNT(*)', $sqlText));
            $arr = $this->ReformatRows(
                $this->query(
                    str_replace(
                        $this->getSqlType(),
                        $this->getTableNameWhere() . '.*',
                        $this->ReformatSql($sqlText)
                    )
                )
            );
            $this->setData($key ?: $this->TableName, $arr);
        }
        return $this;
    }

    /**
     * @param string|null $key
     * @return $this
     * @throws Exception
     */
    public function getListAllNotMy(string $key = null): ThaiInterface
    {
        return $this->extractedGetRow($this->getRequest(), ' where ' . $this->getFieldsWhere() . ' != ' . $this->getUserId(), $key);
    }

    /**
     * @param string|null $key
     * @return $this
     * @throws Exception
     */
    public function getListAll(string $key = null): ThaiInterface
    {
        //checkBlackList($this->getUserId());
        return $this->extractedGetRow($this->getRequest(), $this->parseSqlPermission($this->getUser($this->getUserId())), $key);
    }

    /**
     * @return $this
     */
    public function IsLogged(): ThaiInterface
    {
        if (!$this->isLogged && (int)$this->userid <= 0) {
//            if (function_exists('memcache_close')) {
//                memcache_close();
//            }
//            if ($this->Connect) {
//                $this->Connect->close();
//            }
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http";
            header('Location: ' . $protocol . '://' . $this->AuthHost . '/auth?returl=' . $this->PageSimpleURL);
            exit;
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function IsLoggedAjax(): ThaiInterface
    {
        if (!$this->isLogged && (int)$this->userid <= 0) {
            $this->addData('error', [
                'method' => 'IsLoggedAjax',
                'data' => [],
                'TableName' => $this->TableName,
                'key' => '',
                'msg' => $this->translated('Access to the method is allowed only to authorized users'),
            ]);
        }
        return $this;
    }

    /**
     * @param string $key
     * @param array|null $Data
     * @return $this
     */
    public function addData(string $key, ?array $Data): ThaiInterface
    {
        if (empty($key)) {
            $this->setData('error', array_merge($this->getData('error'), [$Data]));
        } else {
            $this->setData($key, array_merge($this->getData($key), [$Data]));
        }
        return $this;
    }

    /**
     * @param string $Key
     * @param array|string $Request
     * @return $this
     */
    public function setDataRequest(string $Key, $Request): ThaiInterface
    {
        $this->Request[$Key] = $Request;
        return $this;
    }

    /**
     * @param int $Limit
     * @return $this
     */
    public function setLimit(int $Limit): ThaiInterface
    {
        if (!empty($this->getRequest()['Limit'])) {
            $this->Limit[$this->TableName] = (int)$this->getRequest()['Limit'];
        } else {
            $this->Limit[$this->TableName] = $Limit;
        }
        return $this;
    }

    /**
     * @param int $memcache_time
     * @return $this
     */
    public function cachingTime(int $memcache_time): ThaiInterface
    {
        $this->memcache_time = $memcache_time;
        return $this;
    }

    /**
     * @param string $Key
     * @param int $Limit
     * @return $this
     */
    public function setLimitDTO(string $Key, int $Limit): ThaiInterface
    {
        $this->Limit[$Key] = $Limit;
        return $this;
    }

    /**
     * @param string $Key
     * @param int $Offset
     * @return $this
     */
    public function setOffsetDTO(string $Key, int $Offset): ThaiInterface
    {
        $this->Offset[$Key] = $Offset;
        return $this;
    }

    /**
     * @param string $Key
     * @param array|null $array
     * @return $this
     */
    public function setConditionDTO(string $Key, array $array = null): ThaiInterface
    {
        $this->Condition[$Key] = $array;
        return $this;
    }

    /**
     * @param string $Key
     * @param int $CountSelect
     * @return $this
     */
    public function setCountSelect(string $Key, int $CountSelect): ThaiInterface
    {
        $this->CountSelect[$Key] = $CountSelect;
        return $this;
    }


    /**
     * @return array
     */
    public function getOffset(): array
    {
        return $this->Offset ?: [];
    }

    /**
     * @param int $CountSelectResult
     * @return $this
     */
    public function setCountSelectResult(int $CountSelectResult): ThaiInterface
    {
        $this->CountSelectResult[$this->TableName] = $CountSelectResult;
        return $this;
    }

    /**
     * @param string $key
     * @param int $CountSelectResult
     * @return $this
     */
    public function setCountSelectResultDTO(string $key, int $CountSelectResult): ThaiInterface
    {
        $this->CountSelectResult[$key] = $CountSelectResult;
        return $this;
    }

    /**
     * @return $this
     */
    public function getCount(): ThaiInterface
    {
        if (!empty($this->getSqlCount()[$this->TableName])) {
            $uRow = $this->getCache('resume_count_' . md5($this->getSqlCount()[$this->TableName]), function () {
                return $this->query($this->getSqlCount()[$this->TableName]);
            });
            if (!empty($uRow[0]['COUNT(*)'])) {
                $this->setCountSelectResult((int)$uRow[0]['COUNT(*)']);
            }

        } else {
            $this->setData('error', array_merge($this->getData('error'), [[
                'method' => 'getCount',
                'data' => [],
                'TableName' => $this->TableName,
                'key' => '',
                'msg' => 'To call the getCount method, you must first call the getList method, or getListAll',
            ]]));
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getCountSelectResult(): array
    {
        $test = true;
        if (!empty($this->CountSelectResult[$this->TableName])) {
            if ($this->CountSelectResult[$this->TableName] === 0) {
                $this->setCountPage(0);
            }
            if (!empty($this->CountSelectResult[$this->TableName])) {
                if (!empty($this->getLimit()[$this->TableName])) {
                    $countPage = ceil($this->CountSelectResult[$this->TableName] / $this->getLimit()[$this->TableName]);
                    if ((int)$countPage === 1) {
                        if ($this->CountSelectResult[$this->TableName] < $this->getLimit()[$this->TableName]) {
                            $countPage = 0;
                            $this->numberPage[$this->TableName] = [];
                        } else {
                            $countPage = 2;
                        }
                    }
                    $this->setCountPage($countPage);
                    for ($i = 1; $i <= $countPage; $i++) {
                        $test = false;
                        $this->setNumberPage($i);
                    }
                }
            }
            if ($test) {
                $this->numberPage[$this->TableName] = [];
            }
        } else {
            $this->numberPage[$this->TableName] = [];
            $this->setCountPage(0);
        }

        return $this->CountSelectResult ?: [];
    }

    /**
     * @param int $Offset
     * @return $this
     */
    public function setOffset(int $Offset): ThaiInterface
    {
        if (!empty($this->getRequest()['Offset'])) {
            $this->Offset[$this->TableName] = (int)$this->getRequest()['Offset'];
        } else {
            $this->Offset[$this->TableName] = $Offset;
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getLimit(): array
    {
        return $this->Limit ?: [];
    }

    /**
     * @return array
     */
    public function getCountPage(): array
    {
        return $this->countPage ?: [];
    }

    /**
     * @param int $countPage
     * @return $this
     */
    public function setCountPage(int $countPage): ThaiInterface
    {
        $this->countPage[$this->TableName] = $countPage;
        return $this;
    }

    /**
     * @param string $Key
     * @param int|null $countPage
     * @return $this
     */
    public function setCountPageDTO(string $Key, int $countPage = null): ThaiInterface
    {
        $this->countPage[$Key] = $countPage;
        return $this;
    }

    /**
     * @return array
     */
    public function getNumberPage(): array
    {
        return $this->numberPage ?: [];
    }

    /**
     * @param int $numberPage
     * @return $this
     */
    public function setNumberPage(int $numberPage): ThaiInterface
    {
        $this->numberPage[$this->TableName][] = $numberPage;
        return $this;
    }

    /**
     * @param string $Key
     * @param array $numberPageArr
     * @return $this
     */
    public function setNumberPageDTO(string $Key, array $numberPageArr): ThaiInterface
    {
        $this->numberPage[$Key] = $numberPageArr;
        return $this;
    }

    /**
     * @param int $currentPage
     * @return $this
     */
    public function setCurrentPage(int $currentPage): ThaiInterface
    {
        if (!empty($this->getRequest()['CurrentPage'])) {
            $this->currentPage[$this->TableName] = (int)$this->getRequest()['CurrentPage'];
        } else {
            $this->currentPage[$this->TableName] = $currentPage;
        }
        if (!empty($this->getLimit()[$this->TableName])) {
            $this->setOffset((int)(($this->currentPage[$this->TableName] * $this->getLimit()[$this->TableName]) - $this->getLimit()[$this->TableName]));
        }
        return $this;
    }

    /**
     * @param string $Key
     * @param int $currentPage
     * @return $this
     */
    public function setCurrentPageDTO(string $Key, int $currentPage): ThaiInterface
    {
        $this->currentPage[$Key] = $currentPage;
        return $this;
    }

    /**
     * @return array
     */
    public function getCurrentPage(): array
    {
        return $this->currentPage ?: [];
    }

    /**
     * @return array
     */
    public function getRequest(): array
    {
        if (!empty($this->Request[$this->TableName])) {
            if (gettype($this->Request[$this->TableName]) === 'string') {
                $this->Request[$this->TableName] = (array)json_decode($this->Request[$this->TableName]);
                if (gettype($this->Request[$this->TableName]) === 'array') {
                    foreach ($this->Request[$this->TableName] as $key => $value) {
                        if (gettype($value) === 'object') {
                            $this->Request[$this->TableName][$key] = (array)$value;
                        }
                    }
                }
            } else {
                return $this->Request[$this->TableName];
            }
        }
        return [];
    }

    /**
     * @param array $Request
     * @return $this
     */
    public function setRequest(array $Request): ThaiInterface
    {
        if (!empty($Request['crossAjaxData'])) {
            if (count($Request['crossAjaxData']) >= 1) {
                foreach ($Request['crossAjaxData'] as $key => $value) {
                    $this->setCrossAjaxData($key, $value);
                    switch ($key) {
                        case 'TableNameWhere':
                            $this->setTableNameWhere($value);
                            break;
                        case 'DefaultFieldType':
                            $this->setDefaultFieldType($value);
                            break;
                        case 'ClassAction':
                            $this->setClassAction($value);
                            break;
                        case 'valueWhere':
                            $this->setValueWhere($value);
                            break;
                        case 'sortOrderFieldName':
                            $this->setsortOrderFieldName($value);
                            break;
                        case 'sortOrderType':
                            $this->setsortOrderType($value);
                            break;
                        case 'fieldsWhere':
                            $this->setFieldsWhere($value);
                            break;
                        case 'Attachments':
                            $this->setAttachmentsStatus();
                            break;
                        case 'fieldsId':
                            $this->setFieldsId($value);
                            break;
                    }
                }
            }
        }
        if (!empty($Request['route'])) {
            $Request['routestring'] = $Request['route'];
        }
        if (!empty($Request['data'])) {
            $this->setDataRequest($this->TableName, $Request['data']);
        } else {
            $this->setDataRequest($this->TableName, $Request);
        }
        return $this;
    }

    /**
     * @param String $Id
     * @return array
     * @throws Exception
     */
    public function Remove(string $Id): array
    {
        if ($this->Connect) {
            $sqlResult = $this->query("SELECT * FROM " . $this->getTableNameWhere() . " WHERE " . $this->getTableNameWhere() . "." . $this->getFieldsWhere() . " = '" . $this->getUserId() . "' AND " . $this->getTableNameWhere() . "." . $this->getFieldsId() . " = '" . $Id . "'");
            $sqlResultPerm = $this->query("SELECT * FROM " . $this->getTableNameWhere() . " WHERE  " . $this->getTableNameWhere() . "." . $this->getFieldsId() . " = '" . $Id . "'");
            if (!empty($sqlResultPerm)) {
                $Permission = $this->checkPermission($sqlResultPerm[0], 'remove');
                if ($Permission === true) {
                    if ($this->getAttachmentsStatus()) {
                        $this->AttachmentsRemove((int)$Id);
                    }
                    $this->query("DELETE FROM " . $this->getTableNameWhere() . " WHERE " . $this->getTableNameWhere() . ".id = '" . $Id . "'");
                    return [
                        'status' => 'warning',
                        'error' => 0,
                        'id' => (int)$Id,
                        'condition' => $this->Condition,
                        'msg' => $this->translated('Burn deleted'),
                    ];
                }
                if ($Permission === false) {
                    return [
                        'status' => 'error',
                        'error' => 0,
                        'id' => (int)$Id,
                        'condition' => $this->Condition,
                        'msg' => $this->translated('Access denied'),
                    ];
                }
            }
            if (!empty($sqlResult)) {
                if ($this->getAttachmentsStatus()) {
                    $this->AttachmentsRemove((int)$Id);
                }

                $r1 = $this->callbackBeforeRemove($sqlResult[0]);
                $this->query("DELETE FROM " . $this->getTableNameWhere() . " WHERE " . $this->getTableNameWhere() . "." . $this->getFieldsWhere() . " = '" . $this->getUserId() . "' AND " . $this->getTableNameWhere() . ".id = '" . $Id . "'");
                $r2 = $this->callbackAfterRemove($sqlResult[0]);
            } else {
                return [
                    'status' => 'error',
                    'error' => 0,
                    'id' => (int)$Id,
                    'condition' => $this->Condition,
                    'msg' => $this->translated('Access denied'),
                ];
            }
        }
        return [
            'status' => 'warning',
            'error' => 0,
            'id' => (int)$Id,
            'condition' => $this->Condition,
            'msg' => $this->translated('Burn deleted'),
        ];
    }


    /**
     * @return array
     * @throws Exception
     */
    public function RemoveAll(): array
    {
        $this->setLimit(10000)->getList();
        $arr = [];
        if (count($this->getDataObjectMin()['Data']['notifications']) >= 1) {
            foreach ($this->getDataObjectMin()['Data']['notifications'] as $value) {
                $arr[] = $this->Remove($value['id']);
            }
        }
        return $arr;
    }


    /**
     * @param array $sortable
     * @return array
     */
    public function Sortable(array $sortable): array
    {
        foreach ($sortable as $value) {
            $sqlResult = $this->query("SELECT * FROM " . $this->getTableNameWhere() . " WHERE " . $this->getTableNameWhere() . ".userid = '" . $this->getUserId() . "' AND " . $this->getTableNameWhere() . "." . $this->getFieldsId() . " = '" . $value['id'] . "'");

            if (!empty($sqlResult)) {
                $this->Connect->query("UPDATE " . $this->getTableNameWhere() . " SET sortOrder='" . $value['sortOrder'] . "' WHERE  " . $this->getTableNameWhere() . ".userid = '" . $this->getUserId() . "' AND  " . $this->getTableNameWhere() . ".id = '" . $value['id'] . "'");
            }
        }
        return [
            'status' => 'success',
            'error' => 0,
            'id' => $this->lastID,
            'msg' => $this->translated('Saved successfully'),
        ];
    }

    /**
     * @return $this
     */
    public function Action(): ThaiInterface
    {
        return $this;
    }

    /**
     * @param string $sql
     * @return array|null
     */
    public function get_row(string $sql): ?array
    {
        $res = $this->query($sql);

        if ($res && count($res) >= 1) {
            return $res[0];
        } else {
            return null;
        }

    }

    /**
     * @param int $id
     * @return $this
     */
    public function AttachmentsRemove(int $id): ThaiInterface
    {
        $AttachmentString = $this->get_row("SELECT * FROM " . $this->getTableNameWhere() . " WHERE " . $this->getTableNameWhere() . ".userid = '" . $this->getUserId() . "' AND " . $this->getTableNameWhere() . ".id = '" . $id . "'")["attachments"];
        $AttachmentStringArrayID = explode(",", $AttachmentString);
        if (count($AttachmentStringArrayID) >= 1) {
            $AttachmentStringArrayID[] = '0';
            $inSql = "'attachments_" . implode("','attachments_", $AttachmentStringArrayID) . "'";
            $inSql = str_replace(",'attachments_0'", "", $inSql);
            $sql = "SELECT * FROM filestorage_users where object_id in(" . $inSql . ")";
            $uRows = $this->query($sql);
            if (!empty($uRows)) {
                foreach ($uRows as $uRow) {
                    $AttachmentsID = (int)explode("_", $uRow['object_id'])[1];
                    $this->Connect->query("DELETE FROM attachments WHERE id='" . $AttachmentsID . "'");
                    $test_double = $this->query("SELECT COUNT(*) FROM filestorage_users where file_name='" . $uRow['file_name'] . "'");
                    if ((int)$test_double[0]['COUNT(*)'] <= 1) {
                        $path = $_SERVER['DOCUMENT_ROOT'] . "/uploads/files/" . substr($uRow['file_name'], 0, 1) . "/" . substr($uRow['file_name'], 1, 1) . "/" . substr($uRow['file_name'], 2, 1) . "/" . substr($uRow['file_name'], 3, 1) . "/" . substr($uRow['file_name'], 4, 1);
                        if (file_exists($path . "/" . $uRow['file_name'])) {
                            $sqlResult = "SELECT * FROM filestorage_users where file_name ='" . $uRow['file_name'] . "'";
                            $test_double2 = $this->query($sqlResult);
                            if (count($test_double2) <= 1) {
                                $this->Connect->query("delete from filestorage_users where file_name='" . $uRow['file_name'] . "' and object_id='attachments_" . $AttachmentsID . "'");
                                $this->Connect->query("delete from filestorage where file_name='" . $uRow['file_name'] . "'");
                                unlink($path . "/" . $uRow['file_name']);
                            }
                        }
                    }
                    $this->Connect->query("delete from filestorage_users where file_name='" . $uRow['file_name'] . "' and object_id='attachments_" . $AttachmentsID . "'");
                }
            }
        }
        return $this;
    }

    /**
     *
     * @return array
     * @throws Exception
     */
    public function saveArray(): array
    {
        $this->getSkeleton();
        $dataSaveInfo = [];

        foreach ($this->getRequest() as $valueData) {
            $data = $this->callbackBeforeSave((array)$valueData);
            if (isset($data['id'])) {
                $id = (int)$data['id'];
                if ($this->Connect) {
                    if ($id <= 0) {
                        $r = $this->insertItem($data);
                        if ($r[0] >= 1) {
                            $m = ['success', 'Entry added'];
                        } else {
                            $m = ['error', 'Recording not possible'];
                        }
                    } else {
                        $r = $this->updateItem($data);
                        if ($r[0] >= 1) {
                            $m = ['info', 'Saved successfully'];
                        } else {
                            $m = ['error', 'Access denied'];
                        }
                    }
                    $dataSaveInfo[] = [$m, $r];
                } else {
                    $m = ['error', 'Database connection error'];
                    $dataSaveInfo[] = [$m, 0];
                }
            } else {
                $m = ['error', 'No entries were found to save to the database, or the id field is missing'];
                $dataSaveInfo[] = [$m, 0];
            }

        }
        return [
            'status' => 'success',
            'error' => 0,
            'data' => $dataSaveInfo,
            'condition' => $this->Condition,
            'msg' => $this->translated('Saved successfully'),
        ];
    }

    /**
     * @param $param
     * @param $fieldName
     * @return array|false
     */
    public function getItemValue($param, $fieldName)
    {
        if ($fieldName === 'userid' || $fieldName === 'user_id') {
            if ((int)$param <= 0) {
                $param = $this->getUserId();
            }
        }
        if (gettype($param) != 'array') {
            if ($param === true || $param === 'true' || $param === '1' || $param === 1) {
                return [1, $fieldName];
            }
            if ($param === false || $param === 'false' || $param === '0' || $param === 0) {
                return [0, $fieldName];
            }
            if ($param != '') {
                return [$this->dbString($param), $fieldName];
            } else {
                return false;
            }
        } else {
            return [$this->dbString(implode('|', $param)), $fieldName];
        }
    }

    /**
     * @param array $data
     * @return int
     */
    public function insertItemHistory(array $data): ?int
    {
        if ($this->query("SELECT * FROM information_schema.tables WHERE TABLE_SCHEMA='" . $this->database . "' and TABLE_NAME='" . $this->getTableNameWhere() . "_history'")) {
            $arrValueList = [];
            $arrFieldList = [];
            foreach ($this->Skeleton[$this->TableName] as $value) {
                if ($value['fieldName'] != 'attachments') {
                    if ($value['fieldName'] != $this->getFieldsId()) {
                        $res = $this->getItemValue($data[$value['fieldName']], $value['fieldName']);
                        if ($res !== false) {
                            $arrValueList[] = $res[0];
                            $arrFieldList[] = $res[1];
                        } else {
                            $arrValueList[] = '';
                            $arrFieldList[] = $value['fieldName'];
                        }
                    }
                } else {
                    if (!empty($data[$value['fieldName']])) {
                        $arrFieldList[] = $value['fieldName'];
                        $arrValueList[] = $this->attachmentsArrayToString($data[$value['fieldName']]);
                    }
                }
            }
            $this->query("INSERT INTO " . $this->getTableNameWhere() . "_history (" . implode(",", $arrFieldList) . ") VALUES ('" . implode("','", $arrValueList) . "')");
            return $this->insert_id;
        }
        return NULL;
    }

    /**
     * @param array $data
     * @return array
     */
    public function insertItem(array $data): ?array
    {
        $arrValueList = [];
        $arrFieldList = [];

        foreach ($this->Skeleton[$this->TableName] as $value) {
            if ($value['fieldName'] != 'attachments') {
                if ($value['fieldName'] != $this->getFieldsId()) {
                    if (!empty($data[$value['fieldName']])) {
                        $res = $this->getItemValue($data[$value['fieldName']], $value['fieldName']);
                        if ($res !== false) {
                            $arrValueList[] = $res[0];
                            $arrFieldList[] = $res[1];
                        }
                    } else {
                        $arrValueList[] = '';
                        $arrFieldList[] = $value['fieldName'];
                    }
                }
            } else {
                if (!empty($data[$value['fieldName']])) {
                    $arrFieldList[] = $value['fieldName'];
                    $arrValueList[] = $this->attachmentsArrayToString($data[$value['fieldName']]);
                }
            }
        }
        $Permission = $this->checkPermission($data, 'creat');
        if ($Permission === false) {
            $this->setData('error', array_merge($this->getData('error'), [[
                'method' => 'insertItem',
                'data' => [],
                'TableName' => $this->TableName,
                'key' => '',
                'msg' => $this->translated('Access denied'),
            ]]));
            return [0, 0];
        }
        $this->query("INSERT INTO " . $this->getTableNameWhere() . "(" . implode(",", $arrFieldList) . ") VALUES ('" . implode("','", $arrValueList) . "')");
        return [
            $this->insert_id,
            $this->insertItemHistory($data)
        ];
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function updateItem(array $data): ?array
    {
        $tableSetArr = [];
        $id = (int)$data['id'];
        foreach ($this->Skeleton[$this->TableName] as $value) {
            if ($value['fieldName'] != 'attachments') {
                if ($value['fieldName'] != $this->getFieldsId()) {
                    if (!empty($data[$value['fieldName']])) {
                        $res = $this->getItemValue($data[$value['fieldName']], $value['fieldName']);
                        if ($res !== false) {
                            $tableSetArr[] = "" . $res[1] . "='" . $res[0] . "'";
                        }
                    } else {
                        $tableSetArr[] = "" . $value['fieldName'] . "=''";
                    }
                }
            } else {
                if ($this->getAttachmentsStatus() && empty($data['attachments'])) {
                    $this->AttachmentsRemove($id);
                    $tableSetArr[] = "attachments=''";
                } else {
                    if (!empty($data[$value['fieldName']])) {
                        $tableSetArr[] = "" . $value['fieldName'] . "='" . $this->attachmentsArrayToString($data[$value['fieldName']]) . "'";
                    }
                }
            }
        }

        $Permission = $this->checkPermission($data, 'update');
        if ($Permission === false) {
            $this->setData('error', array_merge($this->getData('error'), [[
                'method' => 'insertItem',
                'data' => [],
                'TableName' => $this->TableName,
                'key' => '',
                'msg' => $this->translated('Access denied'),
            ]]));
            return [0, 0];
        } else {
            if ($Permission === true) {
                $this->query("UPDATE " . $this->getTableNameWhere() . " SET " . implode(",", $tableSetArr) . " WHERE " . $this->getTableNameWhere() . "." . $this->getFieldsId() . " = '" . $id . "'");
                return [
                    $id,
                    $this->insertItemHistory($data)
                ];
            }
        }

        $this->lastID = $id;
        if (!empty($this->query("SELECT * FROM " . $this->getTableNameWhere() . " WHERE " . $this->getTableNameWhere() . "." . $this->getFieldsWhere() . " = '" . $this->getUserId() . "' AND " . $this->getTableNameWhere() . "." . $this->getFieldsId() . " = '" . $id . "'"))) {
            $this->query("UPDATE " . $this->getTableNameWhere() . " SET " . implode(",", $tableSetArr) . " WHERE " . $this->getTableNameWhere() . "." . $this->getFieldsWhere() . " = '" . $this->getUserId() . "' AND " . $this->getTableNameWhere() . "." . $this->getFieldsId() . " = '" . $id . "'");
            return [
                $id,
                $this->insertItemHistory($data)
            ];
        }
        return NULL;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function save(): array
    {
        $data = $this->getSkeleton()
            ->getRequest();
        $data = $this->callbackBeforeSave($data);
        $id = (int)$data['id'];
        $r = [];
        if ($this->Connect) {
            if ($id <= 0) {
                $r = $this->insertItem($data);
                if ($r[0] >= 1) {
                    $data['id'] = $r[0];
                    $data = $this->callbackAfterSave($data);
                    $m = ['success', 'Entry added'];
                } else {
                    $m = ['error', 'Recording not possible'];
                }
            } else {
                $r = $this->updateItem($data);
                if ($r[0] >= 1) {
                    $data = $this->callbackAfterUpdate($data);
                    $m = ['info', 'Saved successfully'];
                } else {
                    $m = ['error', 'Access denied'];
                }
            }
        } else {
            $m = ['error', 'Database connection error'];
        }

        return [
            'status' => $m[0],
            'error' => 0,
            'userid' => $this->getUserId(),
            'fieldsId' => $this->getFieldsId(),
            'result' => $r,
            'id' => $r[0],
            'condition' => $this->Condition,
            'msg' => $this->translated($m[1]),
        ];
    }

    /**
     * @return array
     */
    public function deny(): array
    {
        $StatusSuccessRequest = 'success';
        $id = $this->getRequest()['id'];
        if ($this->Connect) {
            if ((int)$id <= 0) {
                $StatusSuccessRequest = 'error';
                $msgSuccessRequest = ' $id <= 0';
            } else {
                $StatusSuccessRequest = 'info';
                $sqlText = "UPDATE " . $this->getTableNameWhere() . " SET status='" . $this->getRequest()['status'] . "',comment='" . $this->getRequest()['comment'] . "' WHERE  " . $this->getTableNameWhere() . ".id = '" . $id . "'";

                $this->Connect->query($sqlText);
                $this->lastID = (int)$id;
                $msgSuccessRequest = 'Saved successfully';
            }
        } else {
            $msgSuccessRequest = 'Database connection error';
        }

        return [
            'status' => $StatusSuccessRequest,
            'error' => 0,
            'userid' => $this->getUserId(),
            'fieldsId' => $this->getFieldsId(),
            'id' => $this->lastID,
            'msg' => $this->translated($msgSuccessRequest),
        ];
    }

    /**
     * @return array
     * @throws Exception
     */
    public function dislike(): array
    {
        $StatusSuccessRequest = 'success';
        $this->getSkeleton();
        $id = 0;
        $tableSet = '';

        foreach ($this->Skeleton[$this->TableName] as $value) {
            if ($value['fieldName'] != 'attachments') {
                if ($value['fieldName'] === $this->getFieldsId()) {
                    $id = $this->getRequest()[$value['fieldName']];
                }
            }
        }

        if ($this->Connect) {

            $sqlResult = $this->query("SELECT likedata FROM " . $this->getTableNameWhere() . " WHERE " . $this->getTableNameWhere() . "." . $this->getFieldsId() . " = '" . $id . "'");
            $uRow = $sqlResult[0];
            $StatusSuccessRequest = 'info';
            $msgSuccessRequest = '';
            $uRow['likedata'] = explode(',', $uRow['likedata']);
            $key = array_search('-' . $this->getUserId(), $uRow['likedata']);

            if ($key === false) {
                $uRow['likedata'][] = '-' . $this->getUserId();
            } else {
                unset($uRow['likedata'][$key]);
            }


            $key2 = array_search($this->getUserId() . '', $uRow['likedata']);
            if ($key2 !== false) {
                unset($uRow['likedata'][$key2]);
            }


            if (in_array('', $uRow['likedata'])) {
                unset($uRow['likedata'][array_search('', $uRow['likedata'])]);
            }
            $uRow['likedata'] = array_unique($uRow['likedata']);
            $uRow['likedata'] = array_values($uRow['likedata']);
            if (count($uRow['likedata']) === 1) {
                $tableSet = $uRow['likedata'][0];
            }
            if (count($uRow['likedata']) >= 2) {
                $tableSet = implode(',', $uRow['likedata']);
            }

            $tableSet = "likedata='" . $tableSet . "'";
            $sqlText = "UPDATE " . $this->getTableNameWhere() . " SET " . $tableSet . " WHERE " . $this->getTableNameWhere() . "." . $this->getFieldsId() . " = '" . $id . "'";
            $this->Connect->query($sqlText);
            $this->lastID = (int)$id;
        } else {
            $msgSuccessRequest = 'Database connection error';
        }

        return [
            'status' => $StatusSuccessRequest,
            'error' => 0,
            'userid' => $this->getUserId(),
            'fieldsId' => $this->getFieldsId(),
            'id' => $this->lastID,
            'msg' => $this->translated($msgSuccessRequest),
        ];
    }

    /**
     * @return array
     * @throws Exception
     */
    public function like(): array
    {
        $StatusSuccessRequest = 'success';
        $this->getSkeleton();
        $id = 0;
        $tableSet = '';

        foreach ($this->Skeleton[$this->TableName] as $value) {
            if ($value['fieldName'] != 'attachments') {
                if ($value['fieldName'] === $this->getFieldsId()) {
                    $id = $this->getRequest()[$value['fieldName']];
                }
            }
        }

        if ($this->Connect) {

            $sqlResult = $this->query("SELECT likedata FROM " . $this->getTableNameWhere() . " WHERE " . $this->getTableNameWhere() . "." . $this->getFieldsId() . " = '" . $id . "'");
            $uRow = $sqlResult[0];
            $StatusSuccessRequest = 'info';
            $msgSuccessRequest = '';
            $uRow['likedata'] = explode(',', $uRow['likedata']);
            $key = array_search($this->getUserId() . '', $uRow['likedata']);

            if ($key === false) {
                $uRow['likedata'][] = $this->getUserId();
            } else {
                unset($uRow['likedata'][$key]);
            }

            $key2 = array_search('-' . $this->getUserId(), $uRow['likedata']);
            if ($key2 !== false) {
                unset($uRow['likedata'][$key2]);
            }
            if (in_array('', $uRow['likedata'])) {
                unset($uRow['likedata'][array_search('', $uRow['likedata'])]);
            }
            $uRow['likedata'] = array_unique($uRow['likedata']);
            $uRow['likedata'] = array_values($uRow['likedata']);
            if (count($uRow['likedata']) === 1) {
                $tableSet = $uRow['likedata'][0];
            }
            if (count($uRow['likedata']) >= 2) {
                $tableSet = implode(',', $uRow['likedata']);
            }

            $tableSet = "likedata='" . $tableSet . "'";
            $sqlText = "UPDATE " . $this->getTableNameWhere() . " SET " . $tableSet . " WHERE " . $this->getTableNameWhere() . "." . $this->getFieldsId() . " = '" . $id . "'";
            $this->Connect->query($sqlText);
            $this->lastID = (int)$id;
        } else {
            $msgSuccessRequest = 'Database connection error';
        }

        return [
            'status' => $StatusSuccessRequest,
            'error' => 0,
            'userid' => $this->getUserId(),
            'fieldsId' => $this->getFieldsId(),
            'id' => $this->lastID,
            'msg' => $this->translated($msgSuccessRequest),
        ];
    }

    /**
     * @param array|null $attachments
     * @return string
     */
    public function attachmentsArrayToString(array $attachments): ?string
    {
        $attachments = (array)$attachments;
        $ArrIDAttachments = [];
        if (!empty($attachments)) {
            foreach ($attachments as $value) {
                if (gettype($value) === 'object') {
                    $ArrIDAttachments[] = $value->FileID;
                    if ($value->preview !== '' && $value->preview !== false && substr($value->preview, 0, 4) === 'data') {
                        $file = $this->base64_to_jpeg($value['preview']);
                        $sqlText = "UPDATE attachments SET preview='" . $file . "' WHERE attachments.id = '" . $value->Description . "' ";
                        $this->Connect->query($sqlText);
                    }
                    $sqlText = "UPDATE filestorage_users SET description='" . $value->Description . "' WHERE filestorage_users.object_id = 'attachments_" . $value->Description . "' ";
                    $this->Connect->query($sqlText);
                } else {
                    $sqlText = "UPDATE filestorage_users SET description='" . $value['Description'] . "' WHERE filestorage_users.object_id = 'attachments_" . $value['FileID'] . "' ";
                    $this->Connect->query($sqlText);
                    if ($value['preview'] !== '' && $value['preview'] !== false && substr($value['preview'], 0, 4) === 'data') {
                        $file = $this->base64_to_jpeg($value['preview']);
                        $sqlText = "UPDATE attachments SET preview='" . $file . "' WHERE attachments.id = '" . $value['FileID'] . "' ";
                        $this->Connect->query($sqlText);
                    }
                    $ArrIDAttachments[] = $value['FileID'];
                }
            }
        }
        return implode(',', $ArrIDAttachments);
    }

    /**
     * @param $base64_string
     * @return string
     */
    function base64_to_jpeg($base64_string): string
    {

        $md5_file = md5($base64_string);
        $path = "./uploads/files/" . substr($md5_file, 0, 1) . "/" . substr($md5_file, 1, 1) . "/" . substr($md5_file, 2, 1) . "/" . substr($md5_file, 3, 1) . "/" . substr($md5_file, 4, 1);
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        $avatar = $md5_file . ".jpg";
        $imageFile = $path . "/" . $md5_file . ".jpg";
        // split the string on commas
        // $data[ 0 ] == "data:image/png;base64"
        // $data[ 1 ] == <actual base64 string>
        $data = explode(',', $base64_string);
        file_put_contents($imageFile, base64_decode($data[1]));


        return $avatar;
    }

    /**
     * @param string|null $Key
     * @return array
     */
    public function getExtraData(string $Key = null): array
    {
        if ($Key) {
            if (!empty($this->ExtraData[$Key])) {
                return $this->ExtraData[$Key];
            } else {
                return [];
            }
        } else {
            return $this->ExtraData ?: [];
        }
    }

    /**
     * @param string|null $Key
     * @return bool
     */
    public function isExtraData(string $Key = null): bool
    {
        if (!empty($this->ExtraData[$Key])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param string|null $Key1
     * @param string|null $Key2
     * @return bool
     */
    public function isExtraDataArray(string $Key1 = null, string $Key2 = null): bool
    {
        if (!empty($this->ExtraData[$Key1])) {
            if (!empty($this->ExtraData[$Key1][$Key2])) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * @param string $Key
     * @param array|string $ExtraData
     * @return $this
     */
    public function setExtraData(string $Key, $ExtraData): ThaiInterface
    {
        if (empty($this->ExtraData[$Key])) {
            $this->ExtraData[$Key] = $ExtraData;
        }

        return $this;
    }

    /**
     * @param string $Key
     * @param string $Key2
     * @param array|string $ExtraData
     * @return $this
     */
    public function setExtraDataArray(string $Key, string $Key2, $ExtraData): ThaiInterface
    {
        if (empty($this->ExtraData[$Key])) {
            $this->ExtraData[$Key] = [];
        }
        $this->ExtraData[$Key][$Key2] = $ExtraData;

        return $this;
    }
    /**
     * @return array
     */
    public function getDTO(): array
    {
        $this->setExtraData('Lang', $this->Lang);
         return [
            'dataTmp' => $this->Data,
            'Lang' => $this->Lang,
            'user_langTmp' => $this->Lang,
            'DefaultFieldType' => $this->DefaultFieldType,
            'Condition' => $this->Condition,
            'extraDataTmp' => $this->ExtraData,
            'crossAjaxData' => $this->CrossAjaxData,
            'AuthHost' => $this->AuthHost,
            'PageSimpleURL' => $this->PageSimpleURL,
            'SkeletonTmp' => $this->Skeleton,
            'sortOrder' => $this->sortOrder,
            'UserId' => $this->userid,
            'testUrl'=>'',
            'RequestTmp' => $this->Request,
            'LimitTmp' => $this->getLimit(),
            'projectID' => $this->getProjectId(),
            'OffsetTmp' => $this->getOffset(),
            'CountSelectTmp' => $this->getCountSelectResult(),
            'CountPageTmp' => $this->getCountPage(),
            'NumberPageTmp' => $this->getNumberPage(),
            'CurrentPageTmp' => $this->getCurrentPage(),
            'UserIdTmp' => $this->getUserId(),
            'UserId' => $this->getUserId(),
            'Debug' => $this->getDebug(),
            'ClassAction' => $this->ClassAction,
            'routeParam' => $this->getRouteParam(),
            'AccessDenied'=>$this->translated('Access denied'),
            'requiredMesg'=>$this->translated('This field is required'),
            'AutoSearchTmp' => (count($this->getAutoSearch()) === 0) ? [$this->TableName => 0] : $this->getAutoSearch(),
            'checkSum' => ($this->getCheckSum()) ? [$this->TableName => 'CheckSum-Not-Info'] : $this->getCheckSum()
        ];
    }

    /**
     * @return array
     */
    public function getDataObject(): array
    {
        $this->setExtraData('Lang', $this->Lang);
        return [
            'Data' => $this->Data,
            'Lang' => $this->Lang,
            'DefaultFieldType' => $this->DefaultFieldType,
            'Condition' => $this->Condition,
            'ExtraData' => $this->ExtraData,
            'CrossAjaxData' => $this->CrossAjaxData,
            'AuthHost' => $this->AuthHost,
            'PageSimpleURL' => $this->PageSimpleURL,
            'Skeleton' => $this->Skeleton,
            'sortOrder' => $this->sortOrder,
            'UserId' => $this->userid,
            'Request' => $this->Request,
            'Limit' => $this->getLimit(),
            'projectID' => $this->getProjectId(),
            'Offset' => $this->getOffset(),
            'CountSelect' => $this->getCountSelectResult(),
            'CountPage' => $this->getCountPage(),
            'NumberPage' => $this->getNumberPage(),
            'CurrentPage' => $this->getCurrentPage(),
            'UserId' => $this->getUserId(),
            'Debug' => $this->getDebug(),
            'ClassAction' => $this->ClassAction,
            'routeParam' => $this->getRouteParam(),
            'AutoSearch' => (count($this->getAutoSearch()) === 0) ? [$this->TableName => 0] : $this->getAutoSearch(),
            'checkSum' => ($this->getCheckSum()) ? [$this->TableName => 'CheckSum-Not-Info'] : $this->getCheckSum()
        ];
    }

    /**
     * @return array
     */
    public function getDataObjectMin(): array
    {
        $this->setExtraData('Lang', $this->Lang);
        return [
            'Data' => $this->Data,
            'CrossAjaxData' => $this->CrossAjaxData,
            'Limit' => $this->getLimit(),
            'Offset' => $this->getOffset(),
            'CountSelect' => $this->getCountSelectResult(),
            'CountPage' => $this->getCountPage(),
            'projectID' => $this->getProjectId(),
            'sortOrder' => $this->getsortOrder(),
            'NumberPage' => $this->getNumberPage(),
            'CurrentPage' => $this->getCurrentPage(),
            'checkSum' => ($this->getCheckSum()) ? [$this->TableName => 'CheckSum-Not-Info'] : $this->getCheckSum()
        ];
    }

    /**
     * @param ThaiInterface $class
     * @return $this
     */
    public function setDTO(ThaiInterface $class): ThaiInterface
    {
        $this->setDataObject($class->TableName, $class->getDataObject());
        return $this;
    }

    /**
     * @param string $Key
     * @param array $DataSet
     * @return $this
     */
    public function setDataObject(string $Key, array $DataSet): ThaiInterface
    {
        $this->setDataRequest($Key, $this->getRequest());
        $this->setCountSelectResultDTO($Key, (!empty($DataSet['CountSelect'][$Key])) ? $DataSet['CountSelect'][$Key] : 0);
        if (!empty($DataSet['CountPage'])) {
            if ($Key != 'data_objects') {
                $this->setCountPageDTO($Key, $DataSet['CountPage'][$Key]);
            }
        }

        if (!empty($DataSet['Data'])) {
            foreach ($DataSet['Data'] as $keyData => $valueData) {
                if ($keyData === 'error') {
                    $this->setData('error', array_merge($this->getData($keyData), (!empty($DataSet['Data'][$keyData])) ? $DataSet['Data'][$keyData] : null));
                } else {
                    if (gettype($DataSet['Data'][$keyData]) === 'array') {
                        $this->setData($keyData, (!empty($DataSet['Data'][$keyData])) ? $DataSet['Data'][$keyData] : []);
                    } else {
                        $this->setData($keyData, (!empty($DataSet['Data'][$keyData])) ? $DataSet['Data'][$keyData] : null);
                    }

                }
            }
        }

        if (!empty($DataSet['ExtraData'])) {
            foreach ($DataSet['ExtraData'] as $keyExtraData => $valueExtraData) {
                $this->setExtraData($keyExtraData, $valueExtraData);
            }
        }
        if (!empty($DataSet['CrossAjaxData'])) {
            foreach ($DataSet['CrossAjaxData'] as $keyCrossAjaxData => $valueCrossAjaxData) {
                $this->setCrossAjaxDataDTO($keyCrossAjaxData, $valueCrossAjaxData);
            }
        }
        if (!empty($DataSet['checkSum'][$Key])) {
            $this->setCheckSumDTO($Key, $DataSet['checkSum'][$Key]);
        }
        if (!empty($DataSet['Skeleton'][$Key])) {
            if (count($DataSet['Skeleton']) >= 2) {
                foreach ($DataSet['Skeleton'] as $keyData => $valueData) {
                    $this->setSkeleton($keyData, $DataSet['Skeleton'][$keyData]);
                }
            } else {
                if (count($DataSet['Skeleton']) >= 1) {
                    $this->setSkeleton($Key, $DataSet['Skeleton'][$Key]);
                }
            }
        }
        if (!empty($DataSet['Limit'][$Key])) {
            $this->setLimitDTO($Key, $DataSet['Limit'][$Key]);
        }

        if (!empty($DataSet['Offset'][$Key])) {
            $this->setOffsetDTO($Key, $DataSet['Offset'][$Key]);
        } else {
            $this->setOffsetDTO($Key, 0);
        }

        if (!empty($DataSet['Condition'][$Key])) {
            $this->setConditionDTO($Key, $DataSet['Condition'][$Key]);
        }

        if (!empty($DataSet['sortOrder'][$Key])) {
            $this->setsortOrderDTO($Key, $DataSet['sortOrder'][$Key]);
        }
        if (!empty($DataSet['DefaultFieldType'][$Key])) {
            $this->setDefaultFieldTypeDTO($Key, $DataSet['DefaultFieldType'][$Key]);
        }
        if (isset($DataSet['NumberPage'][$Key])) {
            $this->setNumberPageDTO($Key, $DataSet['NumberPage'][$Key]);
        }
        if (!empty($DataSet['CurrentPage'][$Key])) {
            $this->setCurrentPageDTO($Key, $DataSet['CurrentPage'][$Key]);
        }

        if (isset($DataSet['AutoSearch'][$Key])) {
            $this->setAutoSearchDTO($Key, $DataSet['AutoSearch'][$Key]);
        } else {
            $this->setAutoSearchDTO($Key, 0);
        }

        if (isset($DataSet['ClassAction'][$Key])) {
            $this->setClassActionDTO($Key, $DataSet['ClassAction'][$Key]);
        }

        if (isset($DataSet['PageSimpleURL'][$Key])) {
            $this->setPageSimpleURL($DataSet['PageSimpleURL'][$Key]);
        } else {
            if (isset($DataSet['PageSimpleURL'])) {
                $this->setPageSimpleURL($DataSet['PageSimpleURL']);
            } else {
                $this->setPageSimpleURL('');
            }
        }
        if (isset($DataSet['AuthHost'][$Key])) {
            $this->setPageSimpleURL($DataSet['AuthHost'][$Key]);
        } else {
            if (isset($DataSet['AuthHost'])) {
                $this->setAuthHost($DataSet['AuthHost']);
            } else {
                $this->setAuthHost('');
            }
        }
        return $this;
    }

    /**
     * @param string $Key
     * @param array|null $Data
     * @return $this
     */
    public function setData(string $Key, ?array $Data): ThaiInterface
    {
        $this->Data[$Key] = $Data;
        return $this;
    }

    /**
     * @param string $Key
     * @return array
     */
    public function getData(string $Key): array
    {
        return (!empty($this->Data[$Key])) ? $this->Data[$Key] : [];
    }

    /**
     * @return int|null
     */
    public function getUserId(): ?int
    {
        return $this->userid;
    }

    /**
     * @return int|null
     */
    public function getDebug(): ?int
    {
        return $this->debug;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getFieldsWhere(): string
    {
        $fieldsWhere = $this->fieldsWhere;
        if ($fieldsWhere) {
            if ($fieldsWhere != 'id') {
                return $fieldsWhere;
            } else {
                throw new \Exception('getFieldsWhere method does not support id field');
            }
        } else {
            return 'userid';
        }
    }

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return $this->TableName;
    }

    /**
     * @return string
     */
    public function getTableNameWhere(): string
    {
        return $this->TableNameWhere ?: $this->TableName;
    }

    /**
     * @return string|int
     */
    public function getValueWhere(): ?string
    {
        return $this->valueWhere ?: $this->getUserId();
    }

    /**
     * @param string|null $valueWhere
     * @return $this
     */
    public function setValueWhere(?string $valueWhere): ThaiInterface
    {
        $this->setCrossAjaxData('valueWhere', $valueWhere);
        $this->valueWhere = $valueWhere;
        return $this;
    }


    /**
     * @param string $fieldsWhere
     * @return $this
     */
    public function setFieldsWhere(string $fieldsWhere): ThaiInterface
    {
        $this->setCrossAjaxData('fieldsWhere', $fieldsWhere);
        $this->fieldsWhere = $fieldsWhere;
        return $this;
    }


    /**
     * @param string $TableNameWhere
     * @return $this
     */
    public function setTableNameWhere(string $TableNameWhere): ThaiInterface
    {
        $this->setCrossAjaxData('TableNameWhere', $TableNameWhere);
        $this->TableNameWhere = $TableNameWhere;
        return $this;
    }

    /**
     * @return string
     */
    public function getFieldsId(): string
    {
        return !empty($this->fieldsId) ?: 'id';
    }

    /**
     * @param string $fieldsId
     * @return $this
     */
    public function setFieldsId(string $fieldsId): ThaiInterface
    {
        $this->setCrossAjaxData('fieldsId', $fieldsId);
        $this->fieldsId = $fieldsId;
        return $this;
    }

    /**
     * @param Int $userid
     * @return $this
     */
    public function setUserid(int $userid): ThaiInterface
    {
        $this->userid = $userid;
        return $this;
    }

    /**
     * @param Int $debug
     * @return $this
     */
    public function Debug(int $debug = 2): ThaiInterface
    {
        $this->debug = $debug;
        return $this;
    }

    /**
     * @return int
     */
    public function getProjectId(): int
    {
        return $this->ProjectID;
    }

    /**
     * @param Int $ProjectID
     * @return $this
     */
    public function setProjectID(int $ProjectID): ThaiInterface
    {
        $this->ProjectID = $ProjectID;
        return $this;
    }

    /**
     * @param array $getRequest
     * @param string $dopSqlText
     * @param string|null $key
     * @return $this
     * @throws Exception
     */
    public function extractedGetRow(array $getRequest, string $dopSqlText, ?string $key): ThaiInterface
    {
        if (!empty($getRequest['Filters']) && ($this->isFilter() === null || $this->isFilter() === true)) {
            foreach ($getRequest['Filters'] as $Filters) {
                $dopSqlText .= $this->getFieldTextSql($Filters);
            }
        }
        if ($this->Connect) {
            $sqlText = "SELECT " . $this->getSqlType() . " FROM " . $this->getTableNameWhere() . " " . $dopSqlText;
            $this->setSqlCount(str_replace($this->getSqlType(), 'COUNT(*)', $sqlText));
            $arr = $this->ReformatRows(
                $this->query(
                    str_replace(
                        $this->getSqlType(),
                        $this->getTableNameWhere() . '.*',
                        $this->ReformatSql($sqlText)
                    )
                )
            );
            $this->setData($key ?: $this->TableName, $arr);
        }
        return $this;
    }

}

