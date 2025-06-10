<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use phpseclib3\Crypt\RSA;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class FaydaService
{
    private $clientId;
    private $redirectUri;
    private $authorizationEndpoint;
    private $tokenEndpoint;
    private $userinfoEndpoint;
    private $privateKeyJwkBase64; // base64-encoded JWK JSON string from env
    private $algorithm;
    private $clientAssertionType;

    private $codeVerifier;
    private $codeChallenge;

    protected $publicKey;  // You must set this with Fayda's public key, IF you have it


    public function __construct()
    {
        $this->clientId = env('CLIENT_ID');
        $this->redirectUri = env('REDIRECT_URI');
        $this->authorizationEndpoint = env('AUTHORIZATION_ENDPOINT');
        $this->tokenEndpoint = env('TOKEN_ENDPOINT');
        $this->userinfoEndpoint = env('USERINFO_ENDPOINT');
        $this->privateKeyJwkBase64 = env('FAYDA_PRIVATE_KEY_BASE64_JWK_JSON_STRING'); // base64 JWK JSON string
        $this->algorithm = env('ALGORITHM', 'RS256');
        $this->clientAssertionType = env('CLIENT_ASSERTION_TYPE', 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer');
        $this->publicKey = env('FAYDA_PUBLIC_KEY');

    }


    //---------------------------- Decode ---------------------------------------------------------------------------//
    // TEST i.e. this is how fayda decodes
    //
    // i.e. USED for separate testing
    //
    public function decodeUserInfo($userInfoJwt)
    {
        // Decode JWT using the public key and RS256 algorithm
        return JWT::decode($userInfoJwt, new Key($this->publicKey, $this->algorithm));
    }
    //---------------------------- end Decode ---------------------------------------------------------------------------//











    // Base64url decode helper
    private function base64urlDecode(string $input): string
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }

    // Convert base64-encoded JWK JSON string to PEM private key string
    private function convertJwkToPem(string $base64Jwk): string
    {
        $jwkJson = base64_decode($base64Jwk);
        $jwk = json_decode($jwkJson, true);

        if (!$jwk) {
            throw new \Exception("Failed to decode JWK JSON");
        }

        // Extract components and convert to phpseclib RSA private key
        $n = $this->base64urlDecode($jwk['n']);
        $e = $this->base64urlDecode($jwk['e']);
        $d = $this->base64urlDecode($jwk['d']);
        $p = $this->base64urlDecode($jwk['p']);
        $q = $this->base64urlDecode($jwk['q']);
        $dp = $this->base64urlDecode($jwk['dp']);
        $dq = $this->base64urlDecode($jwk['dq']);
        $qi = $this->base64urlDecode($jwk['qi']);

        // Use phpseclib3 to import RSA key components and generate PEM
        $rsa = RSA::loadPrivateKey([
            'n' => new \phpseclib3\Math\BigInteger($n, 256),
            'e' => new \phpseclib3\Math\BigInteger($e, 256),
            'd' => new \phpseclib3\Math\BigInteger($d, 256),
            'p' => new \phpseclib3\Math\BigInteger($p, 256),
            'q' => new \phpseclib3\Math\BigInteger($q, 256),
            'dp' => new \phpseclib3\Math\BigInteger($dp, 256),
            'dq' => new \phpseclib3\Math\BigInteger($dq, 256),
            'qi' => new \phpseclib3\Math\BigInteger($qi, 256),
        ]);

        // Export as PEM string
        return $rsa->toString('PKCS1');
    }







    // NEW: Method to extract public key from a private Base64 JWK string
    private function getPublicKeyFromPrivateJwk(string $base64Jwk): string
    {
        $jwkJson = base64_decode($base64Jwk);
        $jwk = json_decode($jwkJson, true);

        if (!$jwk || !isset($jwk['n']) || !isset($jwk['e'])) {
            throw new \Exception("Failed to decode JWK JSON or missing 'n' or 'e' for public key extraction.");
        }

        $n = $this->base64urlDecode($jwk['n']);
        $e = $this->base64urlDecode($jwk['e']);

        $rsa = RSA::loadPublicKey([
            'n' => new \phpseclib3\Math\BigInteger($n, 256),
            'e' => new \phpseclib3\Math\BigInteger($e, 256),
        ]);

        return $rsa->toString('PKCS1');
    }




    // Example usage:
    public function publicKeyValue()
    {
        try {
            $pemPublicKey = $this->getPublicKeyFromPrivateJwk($this->privateKeyJwkBase64);
                
            Log::info('PUBLIC KEY : - (Extracted PUBLIC KEY (PEM)  - from -  base64 JWK JSON string Private Key): ================== : =  ' . $pemPublicKey);

            // Now you can use $pemPublicKey for verification or other purposes.
        } catch (\Exception $e) {
            Log::error('Error extracting public key: ' . $e->getMessage());
            // Handle the exception appropriately.
        }
    }





    // private function generatePkce()
    // {
    //     $this->codeVerifier = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    //     $hash = hash('sha256', $this->codeVerifier, true);
    //     $this->codeChallenge = rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');

    //     // Save to session for later use
    //     session(['code_verifier' => $this->codeVerifier]);

    //     session()->save(); // Force the session to persist

    //     Log::info('Storing code_verifier in session: ' . $this->codeVerifier);


    // }
    //
    //
    private function generatePkce()
    {
        $this->codeVerifier = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $hash = hash('sha256', $this->codeVerifier, true);
        $this->codeChallenge = rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');

        // Save to session for later use
        session()->put('code_verifier', $this->codeVerifier);

        Log::info('Storing Session ID: ' . session()->getId());
        Log::info('Storing code_verifier in session: ' . $this->codeVerifier . ', - - - - - ORIGINAL Session(code_verifier) = ' . session('code_verifier')); // both are similar
    }







    private function generateSignedJwt(): string
    {
        $now = time();
        $payload = [
            'iss' => $this->clientId,
            'sub' => $this->clientId,
            'aud' => $this->tokenEndpoint,
            'iat' => $now,
            'exp' => $now + 900, // 15 minutes
        ];

        $pemPrivateKey = $this->convertJwkToPem($this->privateKeyJwkBase64);


        Log::info('PRIVATE KEY : - (Extracted PRIVATE KEY (PEM)  - from -  base64 JWK JSON string Private Key): ================== : =  ' . $pemPrivateKey);

        // Use Firebase JWT to sign with PEM private key
        $jwt = JWT::encode($payload, $pemPrivateKey, $this->algorithm);

        return $jwt;
    }

    public function home(Request $request)
    {
        $this->generatePkce();

        $claims = [
            'userinfo' => [
                'given_name' => ['essential' => true],
                'phone' => ['essential' => true],
                'email' => ['essential' => true],
                'picture' => ['essential' => true],
                'gender' => ['essential' => true],
                'birthdate' => ['essential' => true],
                'address' => ['essential' => true],
            ],
            'id_token' => (object)[],
        ];

        $encodedClaims = urlencode(json_encode($claims));

        $authUrl = $this->authorizationEndpoint
            . '?response_type=code'
            . '&client_id=' . urlencode($this->clientId)
            . '&redirect_uri=' . urlencode($this->redirectUri)
            . '&scope=' . urlencode('openid profile email')
            . '&acr_values=' . urlencode('mosip:idp:acr:password')
            . '&code_challenge=' . $this->codeChallenge
            . '&code_challenge_method=S256'
            . '&claims=' . $encodedClaims;

        return view('oidc.home', ['authUrl' => $authUrl]);
    }

    public function callback(Request $request)
    {
        $code = $request->query('code');
        if (!$code) {
            return response()->json(['error' => 'Authorization code not provided'], 400);
        }

        
        $codeVerifier = session('code_verifier');
        //
        Log::info('Retrieved code_verifier from session: ' . session('code_verifier'));


        if (!$codeVerifier) {
            return response()->json(['error' => 'Code verifier missing from session'], 400);
        }

        $clientAssertion = $this->generateSignedJwt();

        $payload = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
            'client_id' => $this->clientId,
            'client_assertion_type' => $this->clientAssertionType,
            'client_assertion' => $clientAssertion,
            'code_verifier' => $codeVerifier,
        ];

        try {
            $tokenResponse = Http::asForm()
                ->withOptions([
                    // 'verify' => file_exists(base_path('cacert.pem')) ? base_path('cacert.pem') : false, // or set to false for dev environment
                    // TIP = USE .env for this   // ------------ // RECOMMENDED
                    // 'verify' => base_path(env('CURL_CA_BUNDLE', 'cacert.pem')),
                    //
                    'verify' => false,
                ])
                ->post($this->tokenEndpoint, $payload);

            if (!$tokenResponse->successful()) {
                Log::error('Token endpoint error: ' . $tokenResponse->body());
                return response()->json(['error' => 'Failed to fetch tokens'], $tokenResponse->status());
            }

            $tokenData = $tokenResponse->json();
            $accessToken = $tokenData['access_token'] ?? null;
            if (!$accessToken) {
                return response()->json(['error' => 'Access token missing from token response'], 500);
            }

            $userinfoResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                ])
                ->withOptions([
                        // 'verify' => file_exists(base_path('cacert.pem')) ? base_path('cacert.pem') : false, // or set to false for dev environment
                        // TIP = USE .env for this   // ------------ // RECOMMENDED
                        // 'verify' => base_path(env('CURL_CA_BUNDLE', 'cacert.pem')),
                        //
                        'verify' => false,
                    ])
                ->get($this->userinfoEndpoint);

            if (!$userinfoResponse->successful()) {
                Log::error('Userinfo endpoint error: ' . $userinfoResponse->body());
                return response()->json(['error' => 'Failed to fetch userinfo'], $userinfoResponse->status());
            }
            //
            Log::info('Userinfo (User INFO) got SUCCESSFULLY = ' . $userinfoResponse->body());



            
            try {
                $pemPublicKey = $this->getPublicKeyFromPrivateJwk($this->privateKeyJwkBase64);
                
                Log::info('PUBLIC KEY : - (Extracted PUBLIC KEY (PEM)  - from -  base64 JWK JSON string Private Key): ================== : =  ' . $pemPublicKey);

                // Now you can use $pemPublicKey for verification or other purposes.
            } catch (\Exception $e) {
                Log::error('Error extracting public key: ' . $e->getMessage());
                // Handle the exception appropriately.
            }



            $userInfoJwt = $userinfoResponse->body();



            // Decode userinfo JWT without verifying signature (for demo)
            // FOR DEBUGGING ONLY: Temporarily decode without signature verification
            // NEVER USE THIS IN PRODUCTION!

            // --- START DEBUGGING CODE (TEMPORARY & INSECURE) ---
            $decodedUserInfo = null;
            try {
                // First, try the secure way with Fayda's public key (if available)
                $decodedUserInfo = $this->decodeUserInfo($userInfoJwt);
                Log::info('SECURELY Decoded Userinfo (User INFO) = ' . json_encode($decodedUserInfo));
            } catch (\Exception $e) {
                Log::warning('Failed secure decoding: ' . $e->getMessage() . '. Attempting insecure decode for debugging.');
                // If secure decoding fails, proceed with insecure parsing for debugging
                $parts = explode('.', $userInfoJwt);
                if (count($parts) >= 2) {
                    $encodedPayload = $parts[1];
                    try {
                        $jsonPayload = $this->base64urlDecode($encodedPayload);
                        $decodedUserInfo = json_decode($jsonPayload);
                        Log::info('INSECURELY Parsed Userinfo (User INFO) for debugging: ' . json_encode($decodedUserInfo));
                        Log::warning('WARNING: This decoding was done INSECURELY for debugging. DO NOT USE IN PRODUCTION!');
                    } catch (\Exception $decodeError) {
                        Log::error('Failed to parse JWT payload for debugging: ' . $decodeError->getMessage());
                        return response()->json(['error' => 'Failed to parse user info for debugging.'], 500);
                    }
                } else {
                    Log::error('Invalid JWT format for debugging: ' . $userInfoJwt);
                    return response()->json(['error' => 'Invalid user info JWT format.'], 500);
                }
            }
            // --- END DEBUGGING CODE ---

            if (!$decodedUserInfo) {
                return response()->json(['error' => 'User info could not be decoded.'], 500);
            }
            



            // // does NOT make sence, since to decode it i need fyada's public key, NOT this once
            // //      BECAUSE they are sending the encrypted request back to me by encrypting th emessage by THEIR OWN PUBLIC KEY
            // //
            // // Decode userinfo JWT with proper signature verification
            // $decodedUserInfo = JWT::decode($userInfoJwt, new Key($this->publicKey, $this->algorithm));
            // //
            // Log::info('Decoded Userinfo (User INFO) = ' . $decodedUserInfo);


            $userInfo = json_decode(json_encode($decodedUserInfo), true);
            //
            Log::info('Final Userinfo (User INFO) array = ' . json_encode($userInfo));

            

            return view('oidc.callback', [
                'name' => $userInfo['name'] ?? 'N/A',
                'email' => $userInfo['email'] ?? 'N/A',
                'sub' => $userInfo['sub'] ?? 'N/A',
                'picture' => $userInfo['picture'] ?? '',
                'phone' => $userInfo['phone'] ?? '',
                'birthdate' => $userInfo['birthdate'] ?? '',
                'residence_status' => $userInfo['residenceStatus'] ?? '',
                'gender' => $userInfo['gender'] ?? '',
                'address' => $userInfo['address'] ?? '',
            ]);
        } catch (\Exception $e) {
            Log::error('Exception: ' . $e->getMessage());
            return response()->json(['error' => 'Exception: ' . $e->getMessage()], 500);
        }
    }

    



}
