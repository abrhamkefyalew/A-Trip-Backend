<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class FaydaService
{
    private $clientId;
    private $redirectUri;
    private $authorizationEndpoint;
    private $tokenEndpoint;
    private $userinfoEndpoint;
    private $privateKey; // PEM format private key
    private $algorithm;
    private $clientAssertionType;

    private $codeVerifier;
    private $codeChallenge;

    public function __construct()
    {
        $this->clientId = env('CLIENT_ID');
        $this->redirectUri = env('REDIRECT_URI');
        $this->authorizationEndpoint = env('AUTHORIZATION_ENDPOINT');
        $this->tokenEndpoint = env('TOKEN_ENDPOINT');
        $this->userinfoEndpoint = env('USERINFO_ENDPOINT');
        $this->privateKey = env('PRIVATE_KEY');  // PEM private key string
        $this->algorithm = env('ALGORITHM', 'RS256');
        $this->clientAssertionType = env('CLIENT_ASSERTION_TYPE', 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer');
    }

    // Generate PKCE code verifier and code challenge
    private function generatePkce()
    {
        $this->codeVerifier = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $hash = hash('sha256', $this->codeVerifier, true);
        $this->codeChallenge = rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');
    }

    // Generate signed JWT for client assertion
    private function generateSignedJwt()
    {
        $now = time();
        $payload = [
            'iss' => $this->clientId,
            'sub' => $this->clientId,
            'aud' => $this->tokenEndpoint,
            'iat' => $now,
            'exp' => $now + (15 * 60),  // 15 minutes expiry
        ];

        $privateKey = $this->privateKey;

        // Sign the JWT with RS256
        $jwt = JWT::encode($payload, $privateKey, $this->algorithm);

        return $jwt;
    }

    // Step 1: Show home page with login link (authorization URL)
    public function home(Request $request)
    {
        $this->generatePkce();

        // Store code verifier in session for later use
        session(['code_verifier' => $this->codeVerifier]);

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
            'id_token' => new \stdClass(),
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

    // Step 2: Callback to exchange code for token and fetch userinfo
    public function callback(Request $request)
    {
        $code = $request->query('code');
        if (!$code) {
            return response()->json(['error' => 'Authorization code not provided'], 400);
        }

        $codeVerifier = session('code_verifier');
        if (!$codeVerifier) {
            return response()->json(['error' => 'Code verifier not found in session'], 400);
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
            $tokenResponse = Http::asForm()->post($this->tokenEndpoint, $payload);

            if ($tokenResponse->successful()) {
                $tokenData = $tokenResponse->json();
                $accessToken = $tokenData['access_token'] ?? null;

                if (!$accessToken) {
                    return response()->json(['error' => 'Access token not found in token response'], 500);
                }

                // Get user info
                $userinfoResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                ])->get($this->userinfoEndpoint);

                if ($userinfoResponse->successful()) {
                    $userInfoJwt = $userinfoResponse->body();

                    // Decode user info JWT without verification
                    $decodedUserInfo = JWT::decode($userInfoJwt, new Key('', $this->algorithm), [$this->algorithm]);

                    // Convert stdClass to array for easier access
                    $userInfoArray = json_decode(json_encode($decodedUserInfo), true);

                    return view('oidc.callback', [
                        'name' => $userInfoArray['name'] ?? 'N/A',
                        'email' => $userInfoArray['email'] ?? 'N/A',
                        'sub' => $userInfoArray['sub'] ?? 'N/A',
                        'picture' => $userInfoArray['picture'] ?? '',
                        'phone' => $userInfoArray['phone'] ?? '',
                        'birthdate' => $userInfoArray['birthdate'] ?? '',
                        'residence_status' => $userInfoArray['residenceStatus'] ?? '',
                        'gender' => $userInfoArray['gender'] ?? '',
                        'address' => $userInfoArray['address'] ?? '',
                    ]);
                } else {
                    Log::error('Userinfo endpoint error: ' . $userinfoResponse->body());
                    return response()->json(['error' => 'Failed to fetch userinfo'], $userinfoResponse->status());
                }
            } else {
                Log::error('Token endpoint error: ' . $tokenResponse->body());
                return response()->json(['error' => 'Failed to fetch tokens'], $tokenResponse->status());
            }
        } catch (\Exception $e) {
            Log::error('Exception: ' . $e->getMessage());
            return response()->json(['error' => 'Exception occurred: ' . $e->getMessage()], 500);
        }
    }
}
