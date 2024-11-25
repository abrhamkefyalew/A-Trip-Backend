<?php

namespace App\Services\Api\V1\OrganizationUser\Payment\TeleBirr;

use App\Models\Invoice;
// use phpseclib3\Crypt\RSA;
use phpseclib\Crypt\RSA;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\View;

/**
 * handle different kinds of PAYMENTs for organization with different methods within this same class
 * i.e. PR Payment for organization  - or -  any other Payment for organization
 * 
 */
class TeleBirrOrganizationPaymentService
{    
    
      
    public function createOrder($title, $amount)
    {

        // // FOR TEST
        // $reqObject = $this->createRequestObject($title, $amount);
        // return $reqObject;

        
        $fabricTokenFunction = $this->applyFabricToken();
        $fabricToken = $fabricTokenFunction['token'];


        



        $requestCreateOrderResult = $this->requestCreateOrder($fabricToken, $title, $amount);
        // FOR TEST
        return $requestCreateOrderResult;


        $prepayId = $requestCreateOrderResult['biz_content']['prepay_id'];
        // return $prepayId;

        $rawRequest = $this->createRawRequest($prepayId);
        // return $rawRequest;

        $baseUrlPay = config('telebirr-super-app.testing') ? config('telebirr-super-app.baseUrlPay_testing') : config('telebirr-super-app.baseUrlPay');
        // //

        
        $completeUrl = $baseUrlPay . '?' . $rawRequest . '&version=1.0&trade_type=Checkout';
        // echo trim((string)$completeUrl);
        

        // return response()->json(['PayOrderUrl' => $baseUrlPay . '?' . $rawRequest . '&version=1.0&trade_type=Checkout'], 200);

        
        $renderedView = View::make('telebirr_pay_organization_using_url', ['completeUrl' => (string)$completeUrl])->render(); // passing payload directly

        return $renderedView;
    }

    public function applyFabricToken()
    {

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-APP-Key' => config('telebirr-super-app.testing') ? config('telebirr-super-app.fabricAppId_testing') : config('telebirr-super-app.fabricAppId'),
        ])
        ->timeout(60)
        ->withOptions([
            'verify' => (!config('telebirr-super-app.testing')), // To bypass SSL verification
        ])
        ->post((config('telebirr-super-app.testing') ? config('telebirr-super-app.baseUrl_testing') : config('telebirr-super-app.baseUrl')) . '/payment/v1/token', [
            'appSecret' => config('telebirr-super-app.testing') ? config('telebirr-super-app.appSecret_testing') : config('telebirr-super-app.appSecret'),
        ]);


        // if (!$response->successful()) {
        //     // return response()->json(['message' => 'Authentication failed (precondition failed)'], 412);
        //     // return response()->json(['message' => 'Authentication failed (expectation failed)'], 417);
        //     // return response()->json(['message' => 'Authentication failed (gateway timeout)'], 504);
        //     return response()->json(['message' => 'Authentication failed (request timeout)'], 408);
        // }

        return $response;

    }


    private function requestCreateOrder($fabricToken, $title, $amount)
    {
        $reqObject = $this->createRequestObject($title, $amount);

        // return $reqObject;

        $header = [
            'Content-Type' => 'application/json',
            'APP-Key' => "ea8b06fd-7d1e-40f7-9624-c06d5a5280ad",
            'Authorization' => $fabricToken,
        ];

        $response = Http::withHeaders([
            $header,
        ])
        ->withOptions([
            'verify' => (!config('telebirr-super-app.testing')), // To bypass SSL verification
        ])
        ->post((config('telebirr-super-app.testing') ? config('telebirr-super-app.baseUrl_testing') : config('telebirr-super-app.baseUrl')) . '/payment/v1/merchant/preOrder', 
            $reqObject,
        );

        // if (!$response->successful()) {
        //     // return response()->json(['message' => 'Authentication failed (precondition failed)'], 412);
        //     // return response()->json(['message' => 'Authentication failed (expectation failed)'], 417);
        //     // return response()->json(['message' => 'Authentication failed (gateway timeout)'], 504);
        //     return response()->json(['message' => 'Authentication failed (request timeout)'], 408);
        // }

        return $response;
    }


    private function createRequestObject($title, $amount)
    {
        // $timestamp = strval(now()->timestamp);
        // $dateTime = date('Y-m-d H:i:s', $timestamp);

        // $req = [
        //     'method' => 'payment.preorder',
        //     'nonce_str' => $this->createNonceStr(),
        //     'timestamp' => $this->createTimeStamp(),
        //     'version' => '1.0',
        // ];


        // $biz = [
        //     'appid' => config('telebirr-super-app.testing') ? config('telebirr-super-app.merchantAppId_testing') : config('telebirr-super-app.merchantAppId'),
        //     'business_type' => 'BuyGoods',
        //     'callback_info' => 'From web',
        //     'merch_code' => config('telebirr-super-app.testing') ? config('telebirr-super-app.merchantCode_testing') : config('telebirr-super-app.merchantCode'),
        //     'merch_order_id' => $this->createMerchantOrderId(),
        //     'notify_url' => 'http://51.21.65.237:9050/api/v1/call_backs/tele_birr/pay_invoices_call_back',
        //     'redirect_url' => 'https://www.adiamat.com/', // this URL is : - it is the page that the Payer will Redirected after finishing the payment
        //     'timeout_express' => '120m',
        //     'title' => $title,
        //     'total_amount' => $amount,
        //     'trade_type' => 'Checkout',
        //     'trans_currency' => 'ETB',
        // ];


        $req = [
            'timestamp' => $this->createTimeStamp(),
            'nonce_str' => $this->createNonceStr(),
            'method' => 'payment.preorder',
            'version' => '1.0',
        ];

        // at last 
        // add prefix = "OPR-" : - prefix on the invoice code variable so that during call back later we could know that it is for ORGANIZATION PR payment
        $invoiceCodeValWithPrefixPr = config('constants.payment.customer_to_business.organization_pr') . $this->createMerchantOrderId(); // add the OPR- prefix to indicate the invoice code is for organization payment // we will use it later when the callback comes from the banks
        // $invoiceCodeValWithPrefixPr = $this->createMerchantOrderId();

        $biz = [
            'notify_url' => 'http://51.21.65.237:9050/api/v1/call_backs/tele_birr/pay_invoices_call_back',
            'appid' => '1194619669504001',
            'merch_code' => '513361',
            'merch_order_id' => $invoiceCodeValWithPrefixPr,
            'trade_type' => 'Checkout',
            'title' => $title,
            'total_amount' => $amount,
            'trans_currency' => 'ETB',
            'timeout_express' => '120m',
            'business_type' => 'BuyGoods',
            'redirect_url' => 'https://www.adiamat.com/', // this URL is : - it is the page that the Payer will Redirected after finishing the payment
            'callback_info' => 'From web',
        ];




        // $req = array_merge($req, ['biz_content' => $biz]);
        // $req['biz_content'] = array_merge([], $biz); 
        $req['biz_content'] = $biz;
        
        $req['sign'] = $this->sign($req);
        $req['sign_type'] = 'SHA256WithRSA';
        
        

        // dd($req);
        // dd(json_encode($req));
        return $req;
    }






    private function createRawRequest($prepayId)
    {
        $map = [
            'appid' => config('telebirr-super-app.testing') ? config('telebirr-super-app.merchantAppId_testing') : config('telebirr-super-app.merchantAppId'),
            'merch_code' => config('telebirr-super-app.testing') ? config('telebirr-super-app.merchantCode_testing') : config('telebirr-super-app.merchantCode'),
            'nonce_str' => $this->createNonceStr(),
            'prepay_id' => $prepayId,
            'timestamp' => $this->createTimeStamp(),
        ];

        $sign = $this->sign($map);


        $rawRequestArray = [
            "appid" => $map['appid'],
            "merch_code" => $map['merch_code'],
            "nonce_str" => $map['nonce_str'],
            "prepay_id" => $map['prepay_id'],
            "sign" => $sign,
            "sign_type" => "SHA256WithRSA",
            "timestamp" => $map['timestamp'],  
        ];

        // $rawRequestArrayComplete['rawRequest'] = $rawRequestArray;
        // dd($rawRequestArrayComplete);

        // order by ascii in array
        $rawRequest = http_build_query($rawRequestArray);

        return $rawRequest;
    }














    /**
     * @use phpseclibCryptRSA version - 1.0 // this is what they say in TELEBirr Documentation // but it is NOT the correct version, the correct version is "~2.0"
     * 
     * So 1.0 is the WRONG version
     * 
     * instead USE = "~2.0"
     * 
     * PUT this in COMPOSER    then do = composer update and composer install
     * phpseclib/phpseclib": "~2.0",
     * // then
     * import = use phpseclib3\Crypt\RSA
     * 
     * 
     */
    private function sign($request)
    {

        $exclude_fields = array("sign", "sign_type", "header", "refund_info", "openType", "raw_request");
        $data = $request;
        ksort($data);
        $stringApplet = '';
        foreach ($data as $key => $values) {

            if (in_array($key, $exclude_fields)) {
                continue;
            }

            if ($key == "biz_content") {
                foreach ($values as $value => $single_value) {
                    if ($stringApplet == '') {
                        $stringApplet = $value . '=' . $single_value;
                    } else {
                        $stringApplet = $stringApplet . '&' . $value . '=' . $single_value;
                    }
                }
            } else {
                if ($stringApplet == '') {
                    $stringApplet = $key . '=' . $values;
                } else {
                    $stringApplet = $stringApplet . '&' . $key . '=' . $values;
                }
            }
        }

        $sortedString = $this->sortedString($stringApplet);

        return $this->signWithRSA($sortedString);
    }

    /**
     * @Purpose: sorting string
     *
     * @Param: stringApplet|string
     * @Return: 
     */

    private function sortedString($stringApplet)
    {
        $stringExplode = '';
        $sortedArray = explode("&", $stringApplet);
        sort($sortedArray);
        foreach ($sortedArray as $x => $x_value) {
            if ($stringExplode == '') {
                $stringExplode = $x_value;
            } else {
                $stringExplode = $stringExplode . '&' . $x_value;
            }
        }

        return $stringExplode;
    }


    public function SignWithRSA($data)
    {
        // requires package installation 
        //          ~ v2.0   (import = use phpseclib3\Crypt\RSA)
        //          // 
        //          PUT this in COMPOSER    then do = composer update and composer install
        //              phpseclib/phpseclib": "~2.0",
        //
        //
        $rsa = new RSA();

        $private_key_load = config('telebirr-super-app.testing') ? config('telebirr-super-app.privateKey_testing') : config('telebirr-super-app.privateKey');
        $private_key = $this->trimPrivateKey($private_key_load)[2];

        if ($rsa->loadKey($private_key) != true) {
            echo 'Error loading PrivateKey';

            return;
        }

        $rsa->setHash('sha256');

        $rsa->setMGFHash('sha256');

        $signtureByte = $rsa->sign($data);

        return base64_encode($signtureByte);
    }


    // public function signWithRSA($data)
    // {
    //     // requires package installation 
    //     //        - composer require phpseclib/phpseclib            --- installs the latest 3.0   (import = use phpseclib3\Crypt\RSA)
    //     //               or
    //     //        PUT this in COMPOSER    then do = composer update
    //     //               "phpseclib/phpseclib": "^3.0",
    //     //

    //     // Create a new RSA key pair
    //     $rsa = RSA::createKey();

    //     $privateKeyFromConfig = config('telebirr-super-app.testing') ? config('telebirr-super-app.privateKey_testing') : config('telebirr-super-app.privateKey');

    //     $private_key = $this->trimPrivateKey($privateKeyFromConfig)[2];

    //     // Load the private key
    //     RSA::load($private_key);

    //     $rsa->withHash('sha256');
    //     $rsa->withMGFHash('sha256');

    //     $signature = $rsa->sign($data);

    //     return base64_encode($signature);

    // }


    // private function signWithRSA($data)
    // {
    //     // Load the RSA private key from configuration
    //     $rsaPrivateKeyConfig = config('telebirr-super-app.testing') ? config('telebirr-super-app.privateKey_testing') : config('telebirr-super-app.privateKey');

    //     // Create a private key resource for RSA operation
    //     $rsaPrivateKeyResource = openssl_pkey_get_private($rsaPrivateKeyConfig);

    //     if ($rsaPrivateKeyResource === false) {
    //         return  'Error loading PrivateKey';
    //     }

    //     // Sign the data
    //     if (!openssl_sign($data, $signature, $rsaPrivateKeyResource, OPENSSL_ALGO_SHA256)) {
    //         return 'Error signing data';
    //     }


    //     // Free the private key resource (this is deprecated, so commented)
    //     // we do NOT need to free the resource because 
    //     //      the deprecation is due to PHP's improved resource management. 
    //     //      In modern PHP versions, especially 8 and above, you can rely on the automatic cleanup of resources.
    //     //
    //     // openssl_free_key($privateKeyResource);

    //     return base64_encode($signature);
    // }


    /**
     * @Purpose: Generate RSA signature of data
     *
     * @Param: $data - the sign message in array format
     * @Return: base64 encoded sign signed with sha256
     */
    // private function SignWithRSA($data)
    // {
    //      // requires package installation 
    //      //      - v1.0   (import = use phpseclib3\Crypt\RSA)
    //      //      //
    //      //      PUT this in COMPOSER    then do = composer update
    //      //              "phpseclib/phpseclib": "1.0.*",
    //      //
    //      //
    //     $rsa = new Crypt_RSA();

    //     $private_key_load = file_get_contents('./config/private_key.pem');

    //     $private_key = $this->trimPrivateKey($private_key_load)[2];

    //     if ($rsa->loadKey($private_key) != TRUE) {
    //         echo "Error loading PrivateKey";
    //         return;
    //     };

    //     $rsa->setHash("sha256");

    //     $rsa->setMGFHash("sha256");

    //     // $rsa->signatureMode(Crypt_RSA::$signatureMode);
    //     $signtureByte = $rsa->sign($data);

    //     return base64_encode($signtureByte);
    // }



    /**
     * @Purpose: To trim the private key 
     *
     * @Param: $stringData -> the private key to be trimmed
     * @Return: array of the return of explode function
     */
    public function trimPrivateKey($stringData)
    {
        return explode('-----', (string) $stringData);
    }

    /**
     * NOT USED 
     * 
     * @Purpose: Generate unique merchant order id
     *
     * @Param: no-Parameter is required.
     * @Return: String format of the time function.
     */
    // NOT USED
    private function createMerchantOrderId()
    {
        return (string)time();
    }

    /**
     * @Purpose: Generate timestamp
     *
     * @Param: no-Parameter is required.
     * @Return: String format of the time function.
     */
    private function createTimeStamp()
    {
        return (string)time();
    }

    /**
     * @Purpose: Generate a 32 length of random string
     *
     * @Param: no-Parameter is required.
     * @Return: A random string with length of 32..
     */
    private function createNonceStr()
    {
        $chars = [
            "0",
            "1",
            "2",
            "3",
            "4",
            "5",
            "6",
            "7",
            "8",
            "9",
            "A",
            "B",
            "C",
            "D",
            "E",
            "F",
            "G",
            "H",
            "I",
            "J",
            "K",
            "L",
            "M",
            "N",
            "O",
            "P",
            "Q",
            "R",
            "S",
            "T",
            "U",
            "V",
            "W",
            "X",
            "Y",
            "Z",
        ];
        $str = "";
        for ($i = 0; $i < 32; $i++) {
            $index = intval(rand() * 35);
            $str .= $chars[$i];
        }
        return uniqid();
        // return "fcab0d2949e64a69a212aa83eab6ee1d";
    }


}
