<?php
namespace ThaiUtilities;

use \Exception;

/**
 * Class ThaiGettingClass
 * */
class ThaiGettingClass
{
    private static $Config;

    /**
     * @param $ConfigCMS
     * @return $this
     * @throws Exception
     */
    public function __construct($ConfigCMS)
    {
        $this::$Config = $ConfigCMS;
        return $this->setSPL($ConfigCMS->getClassDir());
    }

    /**
     * @param array $dirList
     * @return $this
     * @throws Exception
     */
    public function setSPL(array $dirList): ThaiGettingClass
    {
        spl_autoload_register(function ($class_name) use ($dirList) {
            foreach ($dirList as $value) {
                if (file_exists( $value . DIRECTORY_SEPARATOR . $class_name . '.php')) {
                    require( $value . DIRECTORY_SEPARATOR . $class_name . '.php');
                    return true;
                }
            }
            throw new \Exception($class_name . ' not found');
        });
        return $this;
    }

    public function getClass($className)
    {
        $config = $this::$Config;
        $ActionClass = new $className($this::$Config->getDb());

        if(method_exists($ActionClass,'setConfig')){
            $ActionClass->setConfig($config);
        }



        $ActionClass->setNameDataBase($this::$Config->getDbName());
        $ActionClass->setRequest($_REQUEST)
            ->setsetmemcache_set(function ($memcache_obj, $memcachekey, $urows, $p1 = 0, $p2 = 60) {
                return false;
            })
            ->setmemcache_get(function ($memcache_obj, $memcachekey) {
                return false;
            })
            ->setLang($this::$Config->getUserLng())
            ->setNameDataBase($this::$Config->getDbName())
            ->setUserid($this::$Config->getUserid())
            ->setIsAdmin($this::$Config->isIsadmin())
            ->setProjectID($this::$Config->getProjectID())
            ->setSelectCurr($this::$Config->getAccountCurrency())
            ->setIsLogged(false)
            ->setAuthHost($_SERVER['HTTP_HOST'])
            ->setPageSimpleURL($_SERVER['REQUEST_URI'])
            ->setLangConverter(function ($text, $from_lng, $to_lng, $Reformat) use($config) {
                return $config->translate($text, $from_lng, $to_lng, $Reformat);
            })
            ->setExtraData('domain',$_SERVER['HTTP_HOST']);
        return $ActionClass;
    }
}