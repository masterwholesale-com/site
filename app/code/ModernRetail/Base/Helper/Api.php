<?php

namespace ModernRetail\Base\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Api extends AbstractHelper
{

    const XML_CONFIG_API_LOGIN = "modernretail_base/api_credentials/login";
    const XML_CONFIG_API_PASSWORD = "modernretail_base/api_credentials/password";
    const XML_CONFIG_API_IS_LIVE = "modernretail_base/api_credentials/is_live";

    const API_BASE_URl_DEV = "http://api.oleg.modernretail.com/";
    const API_BASE_URl_LIVE = "https://api.modernretail.com/";


    protected $_stocksCache = [];

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \ModernRetail\Base\Helper\ApiLogger $logger,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor
    )
    {
        $this->apiLogger = $logger;
        $this->context = $context;
        $this->encryptor = $encryptor;
    }


    private function _getLogin($store_id = null)
    {
        return $this->context->getScopeConfig()->getValue(self::XML_CONFIG_API_LOGIN, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store_id);
    }

    private function _getPassword($store_id = null)
    {
        $password = $this->context->getScopeConfig()->getValue(self::XML_CONFIG_API_PASSWORD, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store_id);
        $password = $this->encryptor->decrypt($password);
        return $password;
    }


    public function call($method = "GET", $endpoint, $jsonData = null, $store_id = null)
    {


        if (strpos($endpoint,'monitor')!=0) {
            if (!$this->_getLogin($store_id) && !$this->_getPassword($store_id)) {
                $this->apiLogger->info('Authorization missed. Please, specify your API authorization');
                return false;
            }
        }

        if ($endpoint["0"] == "/") {
            $endpoint = substr($endpoint, 1);
        }

        if ($this->context->getScopeConfig()->getValue(self::XML_CONFIG_API_IS_LIVE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store_id) > 0) {
            $fullUrl = self::API_BASE_URl_LIVE . $endpoint;
        } else {
            $fullUrl = self::API_BASE_URl_DEV . $endpoint;
        }


        $ch = curl_init();

        $timeout = 15;
        $user_agent = 'Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';
        curl_setopt($ch, CURLOPT_URL, $fullUrl);
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($method == "POST") {
            // curl_setopt($ch,  CURLOPT_POST, true);
        }

        if ($jsonData) {

            $json = json_encode($jsonData);


            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($json))
            );
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        }

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);


        /**
         * adding auth
         */
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $this->_getLogin($store_id) . ":" . $this->_getPassword($store_id));


        $rawdata = $data = curl_exec($ch);
        curl_close($ch);

        if (!$jsonData) {
            $jsonData = [];
        }
        $data = json_decode($data, true);
        if (!$data) {
            $this->apiLogger->info('MR API SAID:' . $rawdata, $jsonData);
            throw new \Exception('MR API SAID:' . $rawdata);
        }

        if (array_key_exists('status', $data) && ($data['status'] == 'Error' || $data['status'] == 'ERROR')) {
            $this->apiLogger->info($data['message'], $jsonData);
            //return false;
            throw new \Exception($data['message']);
        }
        return $data;


    }

    public function apiGET($endpoint, $store_id = null)
    {
        return $this->call("GET", $endpoint, null, $store_id);
    }

    public function apiPOST($endpoint, $data = null, $store_id = null)
    {
        return $this->call("POST", $endpoint, $data, $store_id);
    }

    public function apiPUT($endpoint, $data = null, $store_id = null)
    {
        return $this->call("PUT", $endpoint, $data, $store_id);
    }


    public function getStock($sku){


        if (array_key_exists($sku,$this->_stocksCache))
            return $this->_stocksCache[$sku];




        try {
            $response = $this->apiPOST('client/getstock/',[$sku]);
        }catch (\Exception $ex){

            die($ex->getMessage());
        }

        $this->_stocksCache[$sku]['stocks'] = $response['stocks'];


        $maxQty = 0;

        foreach($response['stocks'] as $stock){
            $maxQty+=$stock['qty'];
        }
        $this->_stocksCache[$sku]['max_qty'] = $maxQty;
        return  $this->_stocksCache[$sku];

    }
}