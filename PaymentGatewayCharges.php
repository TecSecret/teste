<?php
/*
 * @ PHP 7.2
 * @ Decoder version : 1.0.0.3
 * @ Release on : 14/04/2021
 * @ Website    : http://EasyToYou.eu
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . "core" . DIRECTORY_SEPARATOR . "Bootstrap.php";
class payment_gateway_charges_license_4924PDOWrapper
{
    private static $pdoConnection = NULL;
    private static function getDbConnection()
    {
        if (class_exists("Illuminate\\Database\\Capsule\\Manager")) {
            return Illuminate\Database\Capsule\Manager::connection()->getPdo();
        }
        if (self::$pdoConnection === NULL) {
            self::$pdoConnection = $this::setNewConnection();
        }
        return self::$pdoConnection;
    }
    private static function setNewConnection()
    {
        try {
            $includePath = ROOTDIR . DIRECTORY_SEPARATOR . "configuration.php";
            if (file_exists($includePath)) {
                require $includePath;
                $connection = new PDO(sprintf("mysql:host=%s;dbname=%s;port=%s;charset=utf8", $db_host, $db_name, $db_port ? $db_port : 3360), $db_username, $db_password);
                $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                return $connection;
            }
            throw new Exception("No configuration file found");
        } catch (PDOException $exc) {
        }
    }
    public static function query($query, $params = [])
    {
        $statement = $this::getDbConnection()->prepare($query);
        $statement->execute($params);
        return $statement;
    }
    public static function real_escape_string($string)
    {
        return substr($this::getDbConnection()->quote($string), 1, -1);
    }
    public static function fetch_assoc($query)
    {
        return $query->fetch(PDO::FETCH_ASSOC);
    }
    public static function fetch_array($query)
    {
        return $query->fetch(PDO::FETCH_BOTH);
    }
    public static function fetch_object($query)
    {
        return $query->fetch(PDO::FETCH_OBJ);
    }
    public static function num_rows($query)
    {
        $query->fetch(PDO::FETCH_BOTH);
        return $query->rowCount();
    }
    public static function insert_id()
    {
        return $this::getDbConnection()->lastInsertId();
    }
    public static function errorInfo()
    {
        $tmpErr = $this::getDbConnection()->errorInfo();
        if ($tmpErr[0] && $tmpErr[0] !== "00000") {
            return $tmpErr;
        }
        return false;
    }
    public static function mysql_get_array($query, $params = [])
    {
        $qRes = $this::query($query, $params);
        $arr = [];
        while ($row = $this::fetch_assoc($qRes)) {
            $arr[] = $row;
        }
        return $arr;
    }
    public static function mysql_get_row($query, $params = [])
    {
        $qRes = $this::query($query, $params);
        return $this::fetch_assoc($qRes);
    }
}
class payment_gateway_charges_license_4924
{
    /**
     * @var array
     */
    protected $servers = ["https://www.modulesgarden.com/client-area/", "https://licensing.modulesgarden.com", "https://zeus.licensing.modulesgarden.com", "https://ares.licensing.modulesgarden.com", "https://hades.licensing.modulesgarden.com"];
    protected $db = "payment_gateway_charges_license_4924PDOWrapper";
    protected $verifyPath = "modules/servers/licensing/verify.php";
    protected $moduleName = NULL;
    protected $secret = "";
    protected $localKeyValidTime = 1;
    protected $allowCheckFailDays = 4;
    protected $dir = NULL;
    protected $checkToken = NULL;
    protected $licenseKey = "";
    const STATUS_ACTIVE = "active";
    const STATUS_INVALID = "invalid";
    const STATUS_INVALID_IP = "invalid_ip";
    const STATUS_INVALID_DOMAIN = "invalid_domain";
    const STATUS_INVALID_DIRECTORY = "invalid_directory";
    const STATUS_EXPIRED = "expired";
    const STATUS_NO_CONNECTION = "no_connection";
    const STATUS_WRONG_RESPONSE = "wrong_response";
    const ERRORS = ["active" => "Your module license is active.", "invalid" => "Your module license is invalid.", "invalid_ip" => "Your module license is invalid.", "invalid_domain" => "Your module license is invalid.", "invalid_directory" => "Your module license is invalid.", "expired" => "Your module license has expired.", "no_connection" => "Connection not possible. Please report your server IP to support@modulesgarden.com", "wrong_response" => "Connection not possible. Please report your server IP to support@modulesgarden.com"];
    protected function __construct($moduleName)
    {
        $this->moduleName = $moduleName;
        $this->dir = $this->getModuleDir();
        $this->secret = "a664vade6E75obdee6379ffda514xd53809f";
        if (!function_exists("curl_exec")) {
            throw new Exception("Please install curl library");
        }
    }
    protected function __clone()
    {
    }
    public static function validate()
    {
        $checker = new $this("payment_gateway_charges");
        $file = $checker->dir . "/license.php";
        $fileRename = $checker->dir . "/license_RENAME.php";
        if (!file_exists($file) && file_exists($fileRename)) {
            throw new Exception($checker->moduleName . ": Unable to find " . $file . " file. Please rename file license_RENAME.php to license.php");
        }
        return $checker->validateFile($file);
    }
    public static function getLicenseData()
    {
        $checker = new $this("payment_gateway_charges");
        return $checker->getLocalKey();
    }
    protected function validateFile($file = "")
    {
        if (!file_exists($file)) {
            throw new Exception("Unable to find " . $file . " file.");
        }
        $keyName = $this->moduleName . "_licensekey";
        $func = function ($file, $keyName) {
            require $file;
            return ${$keyName};
        };
        return $this->validateKey($func($file, $keyName));
    }
    protected function validateKey($licenseKey)
    {
        $this->licenseKey = $licenseKey;
        $this->checkToken = time() . md5(mt_rand(1000000000, 0) . $this->licenseKey);
        $localKey = [];
        try {
            $localKey = $this->getLocalKey();
            $this->validateKeyData($localKey);
            return true;
        } catch (Exception $ex) {
            try {
                $license = $this->obtainLicenseFromServer();
                if ($license) {
                    $this->validateServerLicense($license);
                    $this->storeLicense($license);
                    return true;
                }
                throw new Exception($this->getErrorMessage($ex->getMessage()));
            } catch (Exception $ex) {
                if ($this->checkLocalExpiry($localKey)) {
                    return true;
                }
                throw new Exception($this->getErrorMessage($ex->getMessage()));
            }
        }
    }
    protected function checkLocalExpiry($license)
    {
        $localExpiry = date("Ymd", mktime(0, 0, 0, date("m"), date("d") - ($this->localKeyValidTime + $this->allowCheckFailDays), date("Y")));
        return $localExpiry < $license["checkdate"] && $license["checkdate"] - $localExpiry <= 5;
    }
    protected function obtainLicenseFromServer()
    {
        $data = ["licensekey" => $this->licenseKey, "domain" => $this->getWhmcsDomain(), "ip" => $this->getIp(), "dir" => $this->dir, "whmcs" => $this->getWhmcsVersion(), "module" => $this->getModuleVersion(), "php" => phpversion()];
        $data = array_merge($this->getLicenseUsage(), $data);
        if ($this->checkToken) {
            $data["check_token"] = $this->checkToken;
        }
        $license = NULL;
        $lastException = NULL;
        foreach ($this->servers as $server) {
            try {
                $response = $this->callServer($server, $data);
                $license = $this->parseServeResponse($response);
                return $license;
            } catch (Exception $ex) {
                $lastException = $ex;
            }
        }
        throw $lastException;
    }
    protected function getLicenseUsage()
    {
        $file = pathinfo($this->dir)["filename"];
        $func = "\\" . $file . "_LicenseUsage";
        if (!function_exists($func)) {
            return [];
        }
        return $easytoyou_decoder_beta_not_finish;
    }
    protected function storeLicense($license)
    {
        $license["checkdate"] = date("Ymd");
        $license["checktoken"] = $this->checkToken;
        $encoded = serialize($license);
        $encoded = base64_encode($encoded);
        $encoded = md5($license["checkdate"] . $this->secret) . $encoded;
        $encoded = strrev($encoded);
        $encoded = $encoded . md5($encoded . $this->secret);
        $encoded = wordwrap($encoded, 80, "\n", true);
        $query_result = call_user_func($this->db . "::query", "SELECT value FROM tblconfiguration WHERE setting = '" . $this->moduleName . "_localkey'");
        $query_row = call_user_func($this->db . "::fetch_assoc", $query_result);
        if (isset($query_row["value"])) {
            call_user_func($this->db . "::query", "UPDATE tblconfiguration SET value = '" . call_user_func($this->db . "::real_escape_string", $encoded) . "' WHERE setting = '" . $this->moduleName . "_localkey'");
        } else {
            call_user_func($this->db . "::query", "INSERT INTO tblconfiguration (setting,value) VALUES ('" . $this->moduleName . "_localkey','" . call_user_func($this->db . "::real_escape_string", $encoded) . "')");
        }
        return true;
    }
    protected function callServer($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url . $this->verifyPath);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode !== 200) {
            throw new Exception("no_connection");
        }
        return $response;
    }
    protected function parseServeResponse($response)
    {
        preg_match_all("/<(.*?)>([^<]+)<\\/\\1>/i", $response, $matches);
        $results = [];
        foreach ($matches[1] as $k => $v) {
            $results[$v] = $matches[2][$k];
        }
        if (!is_array($results)) {
            throw new Exception("wrong_response");
        }
        if ($results["md5hash"] && $results["md5hash"] != md5($this->secret . $this->checkToken)) {
            throw new Exception("invalid");
        }
        return $results;
    }
    protected function validateServerLicense($data)
    {
        if (!empty($data["md5hash"]) && $data["md5hash"] != md5($this->secret . $this->checkToken)) {
            throw new Exception("invalid");
        }
        if ($data["status"] == "Active") {
            return true;
        }
        if (!empty($data["description"])) {
            throw new Exception($data["description"]);
        }
        switch ($data["status"]) {
            case "Invalid":
                throw new Exception("invalid");
                break;
            case "Expired":
                throw new Exception("expired");
                break;
            case "Suspended":
                throw new Exception("expired");
                break;
            default:
                throw new Exception("no_connection");
        }
    }
    protected function getLocalKey()
    {
        $key = WHMCS\Database\Capsule::table("tblconfiguration")->where("setting", $this->moduleName . "_localkey")->first();
        if (!$key) {
            return [];
        }
        $localkey = str_replace("\n", "", $key->value);
        $localdata = substr($localkey, 0, strlen($localkey) - 32);
        $md5hash = substr($localkey, strlen($localkey) - 32);
        if ($md5hash != md5($localdata . $this->secret)) {
            return [];
        }
        $localdata = strrev($localdata);
        $md5hash = substr($localdata, 0, 32);
        $localdata = substr($localdata, 32);
        $localdata = base64_decode($localdata);
        $localkeyresults = unserialize($localdata);
        if ($md5hash != md5($localkeyresults["checkdate"] . $this->secret)) {
            return [];
        }
        return $localkeyresults;
    }
    protected function validateKeyData($key, $checkDate = true)
    {
        if (empty($key)) {
            throw new Exception("invalid");
        }
        $localExpiry = date("Ymd", mktime(0, 0, 0, date("m"), date("d") - $this->localKeyValidTime, date("Y")));
        if ($checkDate && $key["checkdate"] < $localExpiry) {
            throw new Exception("expired");
        }
        $maxExpiryDate = date("Ymd", mktime(0, 0, 0, date("m"), date("d") + 3, date("Y")));
        if ($maxExpiryDate < $key["checkdate"]) {
            throw new Exception("invalid");
        }
        $validDomains = explode(",", $key["validdomain"]);
        if ($this->getWhmcsDomain() && !in_array($this->getWhmcsDomain(), $validDomains)) {
            throw new Exception("invalid_domain");
        }
        $validips = explode(",", $key["validip"]);
        $usersip = $this->getIp();
        if (!empty($usersip) && !in_array($usersip, $validips)) {
            throw new Exception("invalid_ip");
        }
        $validDirectory = explode(",", $key["validdirectory"]);
        if (!in_array($this->dir, $validDirectory)) {
            throw new Exception("invalid_directory");
        }
        return true;
    }
    protected function getWhmcsVersion()
    {
        global $CONFIG;
        return $CONFIG["Version"];
    }
    protected function getWhmcsDomain()
    {
        if (!empty($_SERVER["SERVER_NAME"])) {
            return $_SERVER["SERVER_NAME"];
        }
        global $CONFIG;
        return parse_url($CONFIG["SystemURL"], PHP_URL_HOST);
    }
    protected function getModuleVersion()
    {
        $moduleVersionFile = $this->dir . "/moduleVersion.php";
        $moduleVersion = "";
        if (file_exists($moduleVersionFile)) {
            $func = function ($moduleVersionFile) {
                require $moduleVersionFile;
                return $moduleVersion;
            };
            $moduleVersion = $func($moduleVersionFile);
        }
        return $moduleVersion ? $moduleVersion : NULL;
    }
    protected function getModuleDir()
    {
        return __DIR__;
    }
    protected function fileExists($file, $elements)
    {
        $path = is_array($elements) ? implode(DIRECTORY_SEPARATOR, $elements) : $elements;
        return file_exists($path . DIRECTORY_SEPARATOR . $file);
    }
    protected function getErrorMessage($message)
    {
        return !empty(["active" => "Your module license is active.", "invalid" => "Your module license is invalid.", "invalid_ip" => "Your module license is invalid.", "invalid_domain" => "Your module license is invalid.", "invalid_directory" => "Your module license is invalid.", "expired" => "Your module license has expired.", "no_connection" => "Connection not possible. Please report your server IP to support@modulesgarden.com", "wrong_response" => "Connection not possible. Please report your server IP to support@modulesgarden.com"][$message]) ? ["active" => "Your module license is active.", "invalid" => "Your module license is invalid.", "invalid_ip" => "Your module license is invalid.", "invalid_domain" => "Your module license is invalid.", "invalid_directory" => "Your module license is invalid.", "expired" => "Your module license has expired.", "no_connection" => "Connection not possible. Please report your server IP to support@modulesgarden.com", "wrong_response" => "Connection not possible. Please report your server IP to support@modulesgarden.com"][$message] : $message;
    }
    protected function getIp()
    {
        return isset($_SERVER["SERVER_ADDR"]) ? $_SERVER["SERVER_ADDR"] : $_SERVER["LOCAL_ADDR"];
    }
}
function PaymentGatewayCharges_config()
{
    return ModulesGarden\PaymentGatewayCharges\Core\ServiceLocator::call("configurationAddon", "config");
}
function PaymentGatewayCharges_activate()
{
    return ModulesGarden\PaymentGatewayCharges\Core\ServiceLocator::call("configurationAddon", "activate");
}
function PaymentGatewayCharges_deactivate()
{
    return ModulesGarden\PaymentGatewayCharges\Core\ServiceLocator::call("configurationAddon", "deactivate");
}
function PaymentGatewayCharges_upgrade($params)
{
    return ModulesGarden\PaymentGatewayCharges\Core\ServiceLocator::call("configurationAddon")->update(isset($params["version"]) ? $params["version"] : "");
}
function PaymentGatewayCharges_output($params)
{
    try {
        $license_check = payment_gateway_charges_license_4924::validate();
        ModulesGarden\PaymentGatewayCharges\Core\ServiceLocator::call("controller")->setParams($params)->execute();
    } catch (Exception $ex) {
        echo "<strong>" . $ex->getMessage() . "</strong>";
        return $ex->getMessage();
    }
}
function PaymentGatewayCharges_clientarea($params)
{
    return ModulesGarden\PaymentGatewayCharges\Core\ServiceLocator::call("clientController")->setParams($params)->execute();
}

?>