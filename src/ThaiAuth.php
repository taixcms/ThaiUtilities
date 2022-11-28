<?php
namespace ThaiUtilities;
use Exception;

class ThaiAuth
{
    private static $Db;
    private static  $userId;
    private static  $Config;
    private static  $codAuth;

    /**
     * @param $ConfigCMS
     * @throws Exception
     * @return $this
     */
    public function __construct( $ConfigCMS ) {
        self::$Config = $ConfigCMS;
        $this->auth();
        return $this;
    }

    /**
     * @return ThaiAuth
     */
    public function auth(): ThaiAuth
    {
        self::$userId = 0;
        if(!empty($_COOKIE["auth"])){
            self::$codAuth = htmlspecialchars($_COOKIE["auth"]);
            $sql = self::$Config->query("SELECT id FROM users WHERE auth = '".self::$codAuth."'");
            if($sql){
                foreach ($sql as $grow) {
                    if((int)$grow['id']>=1){
                        self::$userId = $grow['id'];
                    }
                }
            }
        }
        return  $this;
    }

    /**
     * @return int $userId
     */
    public function getUserId(): int
    {
        return self::$userId;
    }

    /**
     * @param $login
     * @param $password
     * @return ThaiAuth
     */
    public function login($login,$password): ThaiAuth
    {
        $sql = self::$Config->query("SELECT id,password FROM users WHERE users.email = '".htmlspecialchars($login)."'");
        if(!empty($sql))
        {
            foreach ($sql as $grow) {
                if($this->mc_decrypt($grow['password'],self::$Config->getKeyEncrypt()) === $password){
                    self::$codAuth = $this->mc_encrypt($grow['id'].self::$Config->getKeyEncrypt().round(99,99999999),self::$Config->getKeyEncrypt());
                    self::$Config->query("UPDATE users SET auth = '".self::$codAuth."' WHERE users.id = '".$grow['id']."';");
                    self::$userId = (int)$grow['id'];
                }
            }
        }
        return $this;
    }


    public function mc_decrypt($data, $key)
    {
        $res = $data;
        $data = $this->base64URLDecode($data);
        $ivLen = openssl_cipher_iv_length($cipher="aes-256-cbc");
        $iv = substr($data, 0, $ivLen);
        $hmac = substr($data, $ivLen, $sha2len=32);
        $ciphertext_raw = substr($data, $ivLen+$sha2len);
        $original_plaintext = openssl_decrypt($ciphertext_raw, $cipher, $key, OPENSSL_RAW_DATA, $iv);
        $calCmac = hash_hmac('sha256', $ciphertext_raw, $key, true);
        if (hash_equals($hmac, $calCmac))
        {
            $res = $original_plaintext;
        }
        return $res;
    }
    public function mc_encrypt($data, $key)
    {
        $ivLen = openssl_cipher_iv_length($cipher="aes-256-cbc");
        $iv = openssl_random_pseudo_bytes($ivLen);
        $ciphertext_raw = openssl_encrypt($data, $cipher, $key, OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $ciphertext_raw, $key, true);
        return $this->base64URLEncode( $iv.$hmac.$ciphertext_raw );
    }
    public function base64URLDecode($s)
    {
        $s = str_replace('-', '+', $s);
        $s = str_replace('_', '/', $s);
        $s = str_replace('@', '=', $s);
        return base64_decode($s);
    }
    public function base64URLEncode($s)
    {
        $s = base64_encode($s);
        $s = str_replace('+', '-', $s);
        $s = str_replace('/', '_', $s);
        return str_replace('=', '@', $s);
    }
}