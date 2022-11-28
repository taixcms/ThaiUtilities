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
        $bb = new $className($this::$Config->getDb());
        $bb->setRequest($_REQUEST)
            ->setsetmemcache_set(function ($memcache_obj, $memcachekey, $urows, $p1 = 0, $p2 = 60) {
                return false;
            })
            ->setmemcache_get(function ($memcache_obj, $memcachekey) {
                return false;
            })
            ->setLang($this::$Config->getUserLng())
            ->setNameDataBase($this::$Config->getDbName())
            ->setUserid($this::$Config->getUserid())
            ->setProjectID($this::$Config->getProjectID())
            ->setSelectCurr($this::$Config->getAccountCurrency())
            ->setIsLogged(false)
            ->setAuthHost($_SERVER['HTTP_HOST'])
            ->setPageSimpleURL($_SERVER['REQUEST_URI'])
            ->setLangConverter(function ($text) {
                return $text;
            })
            ->setExtraData('domain',$_SERVER['HTTP_HOST']);
        return $bb;
    }
}