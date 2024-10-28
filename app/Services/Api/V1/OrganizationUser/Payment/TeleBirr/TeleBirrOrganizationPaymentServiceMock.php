<?php

namespace App\Services\Api\V1\OrganizationUser\Payment\TeleBirr;

use Exception;
// use phpseclib\Crypt\RSA; OLD Package
use phpseclib3\Crypt\RSA;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class TeleBirrOrganizationPaymentServiceMock
{
    public static function buy(string $subject, float $amount, string $returnUrl, string $outTradeNo)
    {
        $appId = config('telebirr.test_mode') ? config('telebirr.test_appId') : config('telebirr.appId');
        //$notifyUrl = config('telebirr.test_mode') ? config('telebirr.test_notifyUrl') : config('telebirr.notifyUrl');
        $publicKey = config('telebirr.test_mode') ? config('telebirr.test_publicKey') : config('telebirr.publicKey');
        $appKey = config('telebirr.test_mode') ? config('telebirr.test_appKey') : config('telebirr.appKey');
        $url = config('telebirr.test_mode') ? config('telebirr.test_url') : config('telebirr.url');
        $shortCode = config('telebirr.test_mode') ? config('telebirr.test_shortCode') : config('telebirr.shortCode');
        $notifyUrl = config('telebirr.notifyUrl');

        $telebirrRequest = [
            'appId' => $appId,
            'returnUrl' => $returnUrl,
            'subject' => $subject,
            'outTradeNo' => $outTradeNo/*strval(bin2hex(random_bytes(8)))*/,
            'timeoutExpress' => strval(config('telebirr.timeoutExpress')),
            'totalAmount' => strval($amount),
            'shortCode' => $shortCode,
            'timestamp' => strval(now()->timestamp),
            'nonce' => strval(bin2hex(random_bytes(8))),
            'receiveName' => strval(config('telebirr.receiveName')),
            'notifyUrl' => $notifyUrl,
        ];

        try {
            ksort($telebirrRequest);

            $json = json_encode($telebirrRequest);

            $json_array = str_split($json, 245);

            $encrypted = '';

            foreach ($json_array as $j) {
                openssl_public_encrypt($j, $data, $publicKey);
                $encrypted = $encrypted.$data;
            }

            $telebirrRequest['appKey'] = $appKey;

            ksort($telebirrRequest);

            $string = '';

            foreach ($telebirrRequest as $key => $value) {
                if ($key == 'appId') {
                    $string = $string.$key.'='.$value;
                } else {
                    $string = $string.'&'.$key.'='.$value;
                }
            }

            $sign = hash('sha256', $string);

            // dump($amount);
            // dump($appId);
            // dump($sign);
            // dump(base64_encode($encrypted));

            $response = Http::withHeaders(['Content-Type' => 'application/json'])->post($url.'/toTradeWebPay', [
                'appid' => $appId,
                'sign' => $sign,
                'ussd' => base64_encode($encrypted),
            ]);
        } catch (Exception $e) {
            throw $e;
        }

        return response()->json(['teleResponse' => $response->json(), 'outTradeNo' => $telebirrRequest['outTradeNo']]);
    }

    public static function notify(string $notifyData)
    {
        $base64_data = base64_decode($notifyData);

        $decrypted = '';

        $splited_data = str_split($base64_data, 256);

        foreach ($splited_data as $data) {
            openssl_public_decrypt($data, $decrypted_data, config('telebirr.test_mode') ? config('telebirr.test_publicKey') : config('telebirr.publicKey'));
            $decrypted = $decrypted.$decrypted_data;
        }

        $dec = json_decode($decrypted, true);

        return $dec;
    }

    public static function getFabricToken()
    {
        try {
            $verify = ! config('telebirr-super-app.testing'); //Only for dev, true in production

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-APP-KEY' => config('telebirr-super-app.fabric_app_id'), ])
                ->withOptions([
                    'verify' => $verify, ])
                ->post(config('telebirr-super-app.base_url').config('telebirr-super-app.fabric-token-endpoint'), [
                    'appSecret' => config('telebirr-super-app.app_secret'),
                ]);

            if (isset($response->json()['token'])) {
                Log::info('Telebirr Super App: Got success requesting fabric token. Response data: '.$response->body());

                return $response->json()['token'];
            } else {
                Log::alert("Telebirr Super App: Couldn't find token when requesting fabric token! Status: ".$response->status().' Body: '.$response->body());
                abort(400);
            }

            // $data = ["appSecret"=> config('telebirr-super-app.app_secret')];
            // $payload = json_encode($data);

            // $ch = curl_init();
            // curl_setopt($ch, CURLOPT_URL, config('telebirr-super-app.base_url') . config('telebirr-super-app.fabric-token-endpoint'));

            // curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept' => 'application/json', 'Content-Type' => 'application/json', 'X-APP-KEY' => config('telebirr-super-app.fabric_app_id')]);
            // curl_setopt($ch, CURLOPT_POST, 1);
            // curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

            // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //TODO: this should be set to true in production
            // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            // $response = curl_exec($ch);
            // curl_close($ch);

            // if (curl_errno($ch)) {
            //     Log::info('Got error requesting fabric token');
            //     abort(400, curl_errno($ch));
            // } else {
            //     Log::info('Got success requesting fabric token. Response data: '.json_encode($response));
            // }
        } catch (Exception $e) {
            Log::alert('Telebirr Super App: Error happened when sending fabric token request to telebirr Message: '.$e->getMessage());
            abort(400);
        }
    }

    public static function createTimeStamp()
    {
        //   return (string)round(time());
        return (string) strtotime(date('Y-m-d H:i:s'));
    }

    public static function createNonceStr()
    {
        return uniqid();
    }

    public static function sign2($ussd)
    {
        $data = $ussd;
        ksort($data);
        $stringApplet = '';
        foreach ($data as $key => $values) {
            if ($key !== 'biz_content') {
                if ($stringApplet == '') {
                    $stringApplet = $key.'='.$values;
                } else {
                    $stringApplet = $stringApplet.'&'.$key.'='.$values;
                }
            } elseif ($key == 'biz_content') {
                foreach ($values as $value => $single_value) {
                    if ($stringApplet == '') {
                        $stringApplet = $value.'='.$single_value;
                    } else {
                        $stringApplet = $stringApplet.'&'.$value.'='.$single_value;
                    }
                }
            }
        }

        $sortedString = self::sortedString($stringApplet);

        return self::encrypt_RSA($sortedString);
    }

    public static function sign($request, $signature_type)
    {
        $exclude_fields = ['sign', 'sign_type', 'header', 'refund_info', 'openType', 'raw_request'];
        $data = $request;
        ksort($data);
        $stringApplet = '';
        foreach ($data as $key => $values) {
            if (in_array($key, $exclude_fields)) {
                continue;
            }

            if ($key == 'biz_content') {
                foreach ($values as $value => $single_value) {
                    if ($stringApplet == '') {
                        $stringApplet = $value.'='.$single_value;
                    } else {
                        $stringApplet = $stringApplet.'&'.$value.'='.$single_value;
                    }
                }
            } else {
                if ($stringApplet == '') {
                    $stringApplet = $key.'='.$values;
                } else {
                    $stringApplet = $stringApplet.'&'.$key.'='.$values;
                }
            }
        }

        $sortedString = self::sortedString($stringApplet);

        if ($signature_type === config('telebirr-super-app.sign_type')) {
            return self::SignWithRSA($sortedString);
        }

        if ($signature_type === config('telebirr-super-app.pay_order_sign_type')) {
            $sig = hash_hmac('sha256', 'wossen', config('telebirr-super-app.app_secret')); //TODO: Wossen // ask tele

            return $sig;
        }
    }

    public static function sortedString($stringApplet)
    {
        $stringExplode = '';
        $sortedArray = explode('&', $stringApplet);
        sort($sortedArray);
        foreach ($sortedArray as $x => $x_value) {
            if ($stringExplode == '') {
                $stringExplode = $x_value;
            } else {
                $stringExplode = $stringExplode.'&'.$x_value;
            }
        }

        return $stringExplode;
    }

    /*
    // commented because it is not working
    public static function SignWithRSAOLD($data)
    {
        // requires package installation 
        //        - composer require phpseclib/phpseclib            --- installs the latest 3.0   (import = use phpseclib3\Crypt\RSA)
        //        
        //
        $rsa = new RSA();

        $private_key_load = config('telebirr-super-app.private_key');
        $private_key = self::trimPrivateKey($private_key_load)[2];

        if ($rsa->loadPrivateKey($private_key) != true) {
            echo 'Error loading PrivateKey';

            return;
        }

        $rsa->setHash('sha256');

        $rsa->setMGFHash('sha256');

        $signtureByte = $rsa->sign($data);

        return base64_encode($signtureByte);
    }
    */

// FOR RSA signing
public static function signWithRSA($data)
{
    // Load the RSA private key from configuration
    $rsaPrivateKeyConfig = config('telebirr-super-app.rsa_private_key');

    // Trim the RSA private key using a custom method
    $rsaPrivateKey = self::trimPrivateKey($rsaPrivateKeyConfig);

    // Create a private key resource for RSA operation
    $rsaPrivateKeyResource = openssl_pkey_get_private($rsaPrivateKey);

    if ($rsaPrivateKeyResource === false) {
        echo 'Error loading PrivateKey';
        return;
    }

    // Sign the data
    if (!openssl_sign($data, $signature, $rsaPrivateKeyResource, OPENSSL_ALGO_SHA256)) {
        echo 'Error signing data';
        return;
    }


    // Free the private key resource (this is deprecated, so commented)
    // we do NOT need to free the resource because 
    //      the deprecation is due to PHP's improved resource management. 
    //      In modern PHP versions, especially 8 and above, you can rely on the automatic cleanup of resources.
    //
    // openssl_free_key($privateKeyResource);

    return base64_encode($signature);
}


// FOR DSA signing
public static function signWithDSA($data)
{
    // Load the DSA private key from configuration
    $dsaPrivateKeyConfig = config('telebirr-super-app.dsa_private_key');

    // Trim the DSA private key using a custom method
    $dsaPrivateKey = self::trimPrivateKey($dsaPrivateKeyConfig);

    // Create a private key resource for DSA operation
    $dsaPrivateKeyResource = openssl_pkey_get_private($dsaPrivateKey);

    if ($dsaPrivateKeyResource === false) {
        echo 'Error loading DSA Private Key';
        return;
    }

    // Sign the data with DSA
    if (!openssl_sign($data, $signature, $dsaPrivateKeyResource, OPENSSL_ALGO_DSS1)) {
        echo 'Error during DSA signing';
        return;
    }

    return base64_encode($signature);
}



    public static function encrypt_RSA($data)
    {
        // $private_key = config('telebirr-super-app.private_key');
        $private_key = config('telebirr-super-app.private_key');
        // $private_key = self::trimPrivateKey($private_key_load)[2];
        // $private_key = self::trimPrivateKey($private_key)[2];

        $binary_signature = '';

        $algo = 'sha256WithRSAEncryption';

        openssl_sign($data, $binary_signature, $private_key, $algo);

        $signature = base64_encode($binary_signature);

        return $signature;
    }

    public static function trimPrivateKey($stringData)
    {
        return explode('-----', (string) $stringData);
    }
}
