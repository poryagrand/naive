<?php

namespace System\Security;

use System\Communicate\Session;
use System\Communicate\Debug\Console;

class Crypt{
    /**
     * @brief default key of decryption/encryption
     */
    static private $SEC_KEY = "6917c6108b8sa8r8w9r8yt314dc93e49f0d03bb83e2849";

    static private $HASH_KEY = "3GivknT.2',%G.z";

    static private $private_key = "";
    static private $public_key = "";

    const CIPHER_ALGO = "AES-128-CBC";

    // types of random output
    const RAND_NUM = 2;
    const RAND_STR = 4;
    const RAND_HASH = 8;
    const RAND_FLOAT = 16;

    public static function setSecKey($key){
        self::$SEC_KEY = $key;
    }

    public static function setHashKey($key){
        self::$HASH_KEY = $key;
    }


    public static function random($len=6,$type=1){
        if( $type > 16 || $type < 2 ){
            $type = 2;
        }
        if( $len < 1 ){
            $len = 1;
        }

        $alpha = "aopqzrntuvwxsbycdefghijklm1256340789";
        $acount = strlen($alpha);

        $signs = "qwe052809s,4rna/cbvi#$367}8%^&*o.49x!p765mz@tyu{1=2-fd1";
        $scount = strlen($signs);

        switch($type){
            case self::RAND_FLOAT:
                $float_part = mt_rand(0, mt_getrandmax())/mt_getrandmax();
                $integer_part = mt_rand((($len-1)==0?-1:0) + pow(10,$len-1),(int)pow(10,$len) - 1);
                return $integer_part + $float_part;
            case self::RAND_HASH:
                $str = "";
                for($i=0;$i<$len;$i++){
                    $str .= $signs[mt_rand(0,$scount-1)];
                }
                return $str;
            case self::RAND_STR:
                $str = "";
                for($i=0;$i<$len;$i++){
                    $str .= $alpha[mt_rand(0,$acount-11)];
                }
                return $str;
            case (self::RAND_STR|self::RAND_NUM):
                $str = "";
                for($i=0;$i<$len;$i++){
                    $str .= $alpha[mt_rand(0,$acount-1)];
                }
                return $str;
            default:
                $len = max($len,20);
                return @mt_rand((int)( (int)(($len-1)==0?-1:0) + pow(10,$len-1)),(int)(pow(10,$len) - 1));
        }
    }

    /**
     * serialize an object to save in db
     * @param mixed $val
     * @return string
     */
    public static function serialize($val){
        return self::base64Encode(serialize($val));
    }

     /**
     * unserialize an string to object
     * @param string $val
     * @return mixed
     */
    public static function unSerialize($val){
        $val = (string)$val;
        return unserialize(self::base64Decode($val));
    }

    /**
     * @brief hash every data
     * @param[in] any $data
     * @return string
     */
    public static function hash($data,$key=false,$raw=false,$type="sha1"){
        return \hash_hmac($type, $data , $key?$key:self::$HASH_KEY , !(!$raw) );
    }

    /**
     * hash password to save in db
     * @param string $data
     * @return string
     */
    public static function password( $data ){
        return \password_hash($data,PASSWORD_BCRYPT,array(
            "cost"=> 12
        ));
    }

    /**
     * validate password
     * @param string $raw
     * @param string $hashed
     * @return bool
     */
    public static function checkPass($raw,$hashed){
        return \password_verify($raw,$hashed);
    }


    public static function genPassword($len=8){
        $base32_alphabet='Q1!a2A@b3#Bcw4$eR5%Cf6^g7D&vh8*iu9E(jS0)Fk-Y_zGl=+mH[{Tn]}nIv\J\|o\'J"pX;K:qL/?rM.>NxsO,<Pt';
        $password_length=$len; // The length of the password to generate
        $bits_per_value=5;  // The 32 possible characters in the Base32 alphabet can be represented in exactly 5 bits
        $random_bytes_required=(integer)( ($password_length * $bits_per_value) / $len ) + 1;
        $crypto_strong=false;
        $random_bytes=openssl_random_pseudo_bytes($random_bytes_required, $crypto_strong); // Generate random bytes
        if (!$crypto_strong)
            die('openssl_random_pseudo_bytes() is not cryptographically strong');
        if (FALSE === $random_bytes)
            die('openssl_random_pseudo_bytes() failed');
        if (strlen($random_bytes) < $random_bytes_required)
            die('Logic error');
        // Transform each byte $random_bytes into $random_bits where each byte
        // is converted to its 8 character ASCII binary representation.
        // This allows us to work with the individual bits using the php string functions
        // Not very efficient, but easy to understand.
        $random_bits='';
        for ($i=0;$i<$random_bytes_required;++$i)
            $random_bits.=str_pad( decbin( ord($random_bytes[$i]) ), 8, '0', STR_PAD_LEFT);
        // Get 'bits' form $random_bits string in blocks of 5 bits, convert bits to value [0..32> and use
        // this as offset in $base32_alphabet to pick the character
        $password='';
        for ($i=0;$i<$password_length;++$i)
        {
            $random_value_bin=substr($random_bits, $i * $bits_per_value, $bits_per_value);
            if ( strlen($random_value_bin) < $bits_per_value )
                die('Logic error');
            $password.=$base32_alphabet[ bindec($random_value_bin) ];
        }
        return $password;
    }

    /**
     * @brief encrypt data with a key
     * @param[in] string $input_string the string want to be encrypted
     * @param[in] string $key the key of encryption
     * @return string
     */
    public static function encrypt($input_string, $key = false)
    {
        $key = $key ? $key : self::$SEC_KEY;

        $ivlen = openssl_cipher_iv_length(self::CIPHER_ALGO);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt($input_string, self::CIPHER_ALGO, $key, $options=OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary=true);
        return self::base64Encode( $iv.$hmac.$ciphertext_raw );
    }

    /**
     * @brief decrypt data with a key
     * @param[in] string $encrypted_input_string the string want to be decrypted
     * @param[in] string $key the key of decryption
     * @return string
     */
    public static function decrypt($encrypted_input_string, $key = false)
    {
        $key = $key ? $key : self::$SEC_KEY;

        $c = self::base64Decode($encrypted_input_string);
        $ivlen = openssl_cipher_iv_length(self::CIPHER_ALGO);
        $iv = substr($c, 0, $ivlen);
        $hmac = substr($c, $ivlen, $sha2len=32);
        $ciphertext_raw = substr($c, $ivlen+$sha2len);
        $original_plaintext = openssl_decrypt($ciphertext_raw, self::CIPHER_ALGO, $key, $options=OPENSSL_RAW_DATA, $iv);
        $calcmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary=true);
        if (hash_equals($hmac, $calcmac))
        {
            return $original_plaintext;
        }
        return null;
    }

    /**
     * @param string $data       The data to encode
     * @param bool   $usePadding If true, the "=" padding at end of the encoded value are kept, else it is removed
     *
     * @return string The data encoded
     */
    public static function base64Encode($data, $usePadding = false)
    {
        $encoded = \strtr(\base64_encode($data), '+/', '-_');
        return true === $usePadding ? $encoded : \rtrim($encoded, '=');
    }

    /**
     * @param string $data The data to decode
     *
     * @throws \InvalidArgumentException
     *
     * @return string The data decoded
     */
    public static function base64Decode($data)
    {
        $decoded = \base64_decode(\strtr($data, '-_', '+/'), true);
        if ($decoded === false) {
            throw new \InvalidArgumentException('Invalid data provided');
        }
        return $decoded;
    }


    /**
     * @brief simple hashing function to transform string to other one with  a key
     * @param string $data
     * @param int $key
     * @return string|null
     */
    public static function transform($data,$key){
        if( gettype($data) != "string" || !is_numeric($key) ){
            return null;
        }

        $key = (int)$key;
        $DataLen = strlen($data);
        $Sum = 0;
        $retStr = "";

        for($i=0;$i<$DataLen;$i++){
            $Sum += ord($data[$i]);
            $newCode = ( (ord($data[$i])+$Sum ) * $key)%255;
            
            $scaled = ((95 * ($newCode))/(255)) + 32;
            
            $retStr = $retStr . chr($scaled);
        }

        return $retStr;
    }



    /**
     * @brief generate random key to use in decryption/encryption function
     * @param[in] int $round the length of temp length before hashing
     * @return string
     */
    public static function generateKey($round=8)
    {

        $output = "";
        $KeyArray = str_split("qwertyuio12345pasdfghjklzxcvbnm][}{;:/.?.,<!@#$%^&*()_+QWERTYUIOPL67890KJHGFDSAZXCVBNM");//length > 43
        $KeyCount = count($KeyArray);
        if (!is_numeric($round)) {
            $round = mt_rand(1,$KeyCount );
        }
        if ($round < 1) {
            $round = 1;
        }
        if($round > $KeyCount ){
            $round = mt_rand(1, $KeyCount );
        }
        for ($i = 0; $i < $round; $i++) {
            $output .=  (ord(array_shift($KeyArray)) << mt_rand(1,32));
        }
        return self::Hash($output);
    }

    /**
     * get the current unix time 
     * @param int $Offset
     * @param bool $Format
     * @return int|string
     */
    public static function currentTime( $Offset = 0 , $Formate = false){
        if( !is_numeric($Offset) ){
            $Offset = 0;
        }
        return (($Formate === false)? (time()+$Offset) : gmdate( $Formate , (time()+$Offset) )) ;
    }

    /**
     * generate new csrf token ans store it in secure session
     * @param string $name
     * @return void
     */
    public static function generateCSRFToken($name){
        if( !is_string($name) ){
            throw new \Exception("Token Storage name must be string");
        }

        Session::openSecure($name);
            Session::get()->value = array(
                "key"=>self::generateKey(),
                "timestamp"=>self::currentTime()
            );
        Session::closeSecure();
    }

    public static function getCSRF($name){
        if( Session::has($name) ){
            $token = "";
            Session::openSecure($name);
                $details = Session::get()->value;
                $token = self::hash($details["key"] . $details["timestamp"]);
            Session::closeSecure();
            return $token;
        }
        return false;
    }

    public static function getOrGenerateIfNotExist($name){
        $csrf = self::getCSRF($name);
        if( !$csrf ){
            $csrf = self::generateCSRFToken($name);
        }
        return self::getCSRF($name);
    }

    public static function checkCSRF($name,$csrf){
        $check = false;
        if( Session::has($name) ){
            Session::openSecure($name);
                $details = Session::get()->value;
                $token = self::hash($details["key"] . $details["timestamp"]);
                if( $token == $csrf ){
                    $check = true;
                }
            Session::closeSecure();
        }
        return $check;
    }
    /*
    public static function eval($phpCode) {
        $handle = tmpfile();
        $tmpfname = stream_get_meta_data($handle)["uri"];
        fwrite($handle, "<?php\n try{" . $phpCode . ";}catch(\Exception \$ee){}");
        
        $val = null;
        if( !preg_match("!No syntax errors detected!",shell_exec(sprintf("php -l %s 2>&1",escapeshellarg($tmpfname)))) ){
            fclose($handle);
            return null;
        }
        ob_start();
        try{
            $val = ( include $tmpfname );
        }
        catch(\Exception $ee){$val = null;}
        ob_end_clean();
        fclose($handle);
        $err = Console::getLastError();
        if( $err !== null && $err["file"] == $tmpfname ){
            error_clear_last();
            return null;
        }
        return $val;
    }
    */
    public static function eval($phpCode){
        try{
            try{
                ob_start();
                $ret = @eval($phpCode);
                ob_end_clean();
                return $ret;
            }
            catch(\ParseError $tt){}
        }
        catch(\Throwable $t){}
            return null;
    }


     // public-private key encryption
    //===========================\/

    public static function generateAsymKeys(){
        // Set the key parameters
        $config = array(
            "digest_alg" => "sha512",
            "private_key_bits" => 4096,
            "private_key_type" => OPENSSL_KEYTYPE_RSA
        );

        // Create the private and public key
        $res = openssl_pkey_new($config);

        // Extract the private key from $res to $privKey
        openssl_pkey_export($res, $privKey);
        // Extract the public key from $res to $pubKey
        $pubKey = openssl_pkey_get_details($res);


        self::$public_key = $pubKey["key"];
        self::$private_key = $privKey;
    }

    public static function publicKey($key=null){
        if( is_string($key) ){
            self::$public_key = $key;
        }
        return self::$public_key;
    }

    public static function privateKey($key=null){
        if( is_string($key) ){
            self::$private_key = $key;
        }
        return self::$private_key;
    }

    public static function asymEncrypt($data){
        // Encrypt the data using the public key
        openssl_public_encrypt($data, $encryptedData, self::$public_key);
        // Return encrypted data
        return $encryptedData;
    }

    public static function asymDecrypt($enc){
        // Encrypt the data using the public key
        openssl_private_decrypt($enc, $decryptedData, self::$private_key);

        // Return encrypted data
        return $decryptedData;
    }

}


