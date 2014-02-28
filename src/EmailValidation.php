<?php
/**
 * Email Validation class
 *
 * @license    http://www.opensource.org/licenses/mit-license.html MIT License
 */
 class EmailValidation
 {
   private $validateOptions = array( 'RFC',
                                     'DomainSpelling',
                                     'MX',
                                     'AccountOnMailServer',
                                     'DisposableEmail'); 

   /**
    * dataDirPath - path to the data directory eg: holding typographical errors
    *
    * @var string
    * @access private
   **/
   private $dataDirPath = null;

   /**
    * useAPC - enable apc cache for deafilter.com
    *
    * @var string
    * @access private
   **/
   private $useAPC = false;
 
   /**
    * keyAPC - key used for storing deafilter.com web service result
    *
    * @var string
    * @access private
   **/
   private $keyAPC = "dealfilter_%domain%";

   /**
    * socketTimeout - socket timeout - checking if the account on the mail server exists
    *
    * @see function validateAccountOnMailServer
    * @see function setMailServerTimeout 
    * @var int
    * @access private
   **/
   private $socketTimeout = null;

   /**
    * deaFilterKey - key for accessing http://www.deafilter.com/ web service
    * 
    * @var string
    * @access private
   **/
   private $deaFilterKey = null;

   /**
    * cache - holds typographical errors
   **/
   private static $cache = array();

   /**
    * setValidateOptions - choose different options for validate method
    * 
    * @param mixed $validateOptions 
    * @access public
    * @return void
   **/
   public function setValidateOptions($validateOptions)
   {
     $this->validateOptions = (array)$validateOptions;
   }

   /**
    * setDeaFilterKey - set key for http://www.deafilter.com/ web service
    * 
    * @param string $deaFilterKey - md5 encoded key
    * @access public
    * @return void
   **/
   public function setDeaFilterKey($deaFilterKey)
   {
     $this->deaFilterKey = $deaFilterKey;
   }

   /**
    * setDataDirPath - set the path to the data directory
    * 
    * @param string $path 
    * @access public
    * @return void
   **/
   public function setDataDirPath($dataDirPath)
   {
     $this->dataDirPath = $dataDirPath;
   }

   /**
    * setMailServerTimeout - set the timeout for validateAccountOnMailServer method 
    * 
    * @param int $socketTimeout (seconds) 
    * @access public
    * @return void
   **/
   public function setMailServerTimeout($socketTimeout)
   {
     $this->socketTimeout = (int)$socketTimeout;
   }

   /**
    * __construct 
    * 
    * @param array $validateOptions - options for email validation
    * @param string $dataDirPath - path to the data dir
    * @param int $socketTimeout - mail server account check timeout
    * @param string $deaFilterKey - key for accessing www.deafilter.com web service
    *
    * @access public
    * @return EmailValidation
   **/
   public function __construct($validateOptions = null, $dataDirPath = null, $socketTimeout = 5, $deaFilterKey = "d584509dd6079a7fed6367db25e5e91c")
   {
     if (is_array($validateOptions))
       $this->setValidateOptions($validateOptions);
 
     if ($dataDirPath === null)
       $dataDirPath = __DIR__.DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR;

     $this->setDataDirPath($dataDirPath);
     $this->setMailServerTimeout($socketTimeout);
     $this->setDeaFilterKey($deaFilterKey);

     if (function_exists("apc_store"))
       $this->useAPC = true;
   }

   /**
    * validateRFC - check if email matches RFC standards 
    * 
    * @param string $email - email address to check
    * @access public
    * @return bool
   **/
   public function validateRFC($email)
   {
     return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
   }

   /**
    * validateMX -  validate MX records
    * 
    * @param string $email 
    * @access public
    * @return bool
   **/
   public function validateMX($email)
   {
     $_hostname = explode("@", $email);
     $_hostname = $_hostname[1];

     if (!getmxrr($_hostname, $mxHosts))
       return false;

     foreach ($mxHosts as $hostname)
       if (  (checkdnsrr($hostname, "A") || checkdnsrr($hostname, "AAAA") || checkdnsrr($hostname, "A6")))
         return true;

     return false;
   }

   /**
    * validateDomainSpelling - get email with fixed domain spelling
    *
    * @see src/data/typographical.php
    * 
    * @param string $email 
    * @access public
    * @return string - email with fixed domain
   **/
   public function validateDomainSpelling($email)
   {
     $dataPath = $this->dataDirPath.DIRECTORY_SEPARATOR."typographical.php";
     if (!isset(self::$cache[ $dataPath ]))
     {
       if (file_exists($dataPath))
       {
         include_once ($dataPath);
         self::$cache[ $dataPath ] = &$_;
       }
     }

     $emEx = explode("@", $email);

     if (isset(self::$cache[ $dataPath ][ $emEx[1] ]))
       return $emEx[0].'@'.self::$cache[ $dataPath ][$emEx [ 1 ]];

     return $email;
   }

   /**
    * validateNonDisposableEmail - check if disposable email
    * 
    * @param string $email 
    * @access public
    * @return bool
   **/
   public function validateNonDisposableEmail($email)
   {
     $_hostname = explode("@", $email);
     $_hostname = $_hostname[1];

     $keyAPC = str_replace("%domain%", $_hostname, $this->keyAPC);

     $dataDispPath = $this->dataDirPath.DIRECTORY_SEPARATOR."disposable_email.php";
     $dataIgnorePath = $this->dataDirPath.DIRECTORY_SEPARATOR."disposable_email_ignore.php";

     if (!isset(self::$cache[ $dataIgnorePath ]) && file_exists($dataIgnorePath))
     {
         $_ = array();
         include_once ($dataIgnorePath);
         self::$cache[ $dataIgnorePath ] = $_;
     }

     foreach ((array)self::$cache[ $dataIgnorePath ] as $k => $ignoreRegExp)
       if (preg_match($ignoreRegExp, $_hostname))
         return true;

     if (!isset(self::$cache[ $dataDispPath ]) && file_exists($dataDispPath))
     {
         $_ = array();
         include_once ($dataDispPath);
         self::$cache[ $dataDispPath ] = $_;
     }
    
     if (array_search ( $_hostname, (array)self::$cache[ $dataDispPath ]))
       return false;

     if ($this->useAPC)
     {
   	$apcOut = apc_fetch($keyAPC, $exists);

	if ($exists)
  	  return $apcOut;
     }

     $url = "http://www.deafilter.com/classes/DeaFilter.php?mail=".urlencode($email)."&key=".$this->deaFilterKey;
     $curl = curl_init();
     curl_setopt_array($curl, array(
         CURLOPT_RETURNTRANSFER => 1,
         CURLOPT_URL => $url
     ));

     $result = curl_exec($curl);
     $data = json_decode($result);
     
     $val = @$data->result;

     if ($this->useAPC && ($val == 'ok' || $val == 'ko'))
	apc_store($keyAPC, $val == 'ok'?1:0);

     if ($val == 'ko')
       return false;

     return true;
   }

   private function socketSend(&$fp, $query)
   {
       stream_socket_sendto($fp, $query . "\r\n");

       do
       {
           $reply = stream_get_line($fp, 1024, "\r\n");
           $status = stream_get_meta_data($fp);
           
           if ($status['eof'] == 1)
             break;
       }
       while (($reply[3] != ' ') && ($status['timed_out'] === FALSE));

       preg_match('/^(?<code>[0-9]{3}) (.*)$/ims', $reply, $matches);
       $code = isset($matches['code']) ? $matches['code'] : false;
       return $code;
   }

   /**
    * validateAccountOnMailServer - check if the mail server replyes with a valid response code
    *
    ***************************************************************************************
    * @IMPORTANT                                                                          *
    * It's common for large ISPs to block outbound connections on port 25. Try running:   * 
    * telnet gmail-smtp-in.l.google.com 25 to test your connection.                       *
    * If you are using firewall check if apache user ("www-data") can access port 25.     *
    ***************************************************************************************
    *
    * @param string $email 
    * @access public
    * @return bool
   **/
   public function validateAccountOnMailServer($email)
   {
     if (PHP_VERSION_ID == "50310")
       return true;

     $_hostname = explode("@", $email);
     $_hostname = $_hostname[1];

     if (!getmxrr($_hostname, $mxHosts)) 
       return false;

     $mxHosts[] = $_hostname;

     $startStamp = time();
     foreach ($mxHosts as $host)
     {
       $timeLeft = time()-$startStamp;

       if ($timeLeft >= $this->socketTimeout)
         return false;

       if ($fp = stream_socket_client("tcp://{$host}:25", $errNo, $errStr, $this->socketTimeout-$timeLeft))
       {
         stream_set_timeout($fp, $this->socketTimeout-$timeLeft);
         stream_set_blocking($fp, 1);
         do {
             $reply = stream_get_line($fp, 1024, "\r\n");
             $status = stream_get_meta_data($fp);

             if ($status['eof'] == 1)
               break;
         } while (($reply[3] != ' ') && ($status['timed_out'] === FALSE));

         preg_match('/^(?<code>[0-9]{3}) (.*)$/ims', $reply, $matches);
         $code = isset($matches['code']) ? $matches['code'] : false;

         if ($code == '220') {
             break;
         } else {
             fclose($fp);
             $fp = false;
         }
       }
     }

     if ($fp)
     {
         $this->socketSend($fp, "HELO localhost");
         $this->socketSend($fp, "MAIL FROM: <noreply@localhost>");
         $code = $this->socketSend($fp, "RCPT TO: <" . $email . ">");
         $this->socketSend($fp, "RSET");
         $this->socketSend($fp, "QUIT");
         fclose($fp);

         if ($code == '250' || $code == '450' || $code == '451' || $code == '452')
            return true;
     }

     return false;
   }

   /**
    * validate - one method to validate all optins defined in $this->validateOptions
    * 
    * @param string $email - email address to check
    * @access public
    *
    * @return info
    * A) Error code
    *   1) No error
    *   2) Invalid Email Address
    *   3) Typographical error
    *   4) MX Records could not be resolved
    *   5) Invalid Account Address
    *   6) DEA Detected
    *
    * B) Validated email address
    * Validated email address can be empty if the error code is one of these II, IV, V, VI
    *
    * @return array
    * eg: [ 'errorCode' => 3, 'validatedEmail' => 'lukasz.tlalka@netblink.net']
   **/
   public function validate($email)
   {
     if (in_array("RFC", $this->validateOptions) && !$this->validateRFC($email))
       return array('errorCode' => 2);
 
     if (in_array("DomainSpelling", $this->validateOptions))
     {
       $validDomainEmail = $this->validateDomainSpelling($email);

       if ($validDomainEmail != $email)
       {
         $validateOptionsHelp = $this->validateOptions;

         // protect from recursion
         $key = array_search("DomainSpelling", $this->validateOptions);
         unset($this->validateOptions[$key]);

         $newValidation = $this->validate($validDomainEmail);
         $this->validateOptions = $validateOptionsHelp;

         if ($newValidation['errorCode'] != 1)
           return $newValidation;

         return array('errorCode' => 3, 'validatedEmail' => $validDomainEmail);
       }
     }

     if (in_array("MX", $this->validateOptions) && !$this->validateMX($email))
       return array('errorCode' => 4);
     
     if (in_array("AccountOnMailServer", $this->validateOptions) && !$this->validateAccountOnMailServer($email))
       return array('errorCode' => 5);

     if (in_array("DisposableEmail", $this->validateOptions) && !$this->validateNonDisposableEmail($email))
       return array('errorCode' => 6);

     return array('errorCode' => 1);
   }
 }
