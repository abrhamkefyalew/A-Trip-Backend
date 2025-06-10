<?php

namespace App\Services;

use DateTimeImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Jose\Component\Core\JWK;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\KeyManagement\JWKFactory;

class FaydaServiceSix
{
    protected string $clientId = 'crXYIYg2cJiNTaw5t-peoPzCRo-3JATNfBd5A86U8t0';
    protected string $redirectUri = 'http://localhost:3000/callback';
    protected string $authorizationEndpoint = 'https://esignet.ida.fayda.et/authorize';
    protected string $tokenEndpoint = 'https://esignet.ida.fayda.et/v1/esignet/oauth/v2/token';
    protected string $userInfoEndpoint = 'https://esignet.ida.fayda.et/v1/esignet/oidc/userinfo';
    protected string $keyId = '0b194df4-7149-4146-97c5-78fdf0d4fb1d';

    // Use the JWK JSON Base64-encoded value as-is
    protected string $base64Jwk = 'ewogICJrdHkiOiAiUlNBIiwKICAidXNlIjogInNpZyIsCiAgImtleV9vcHMiOiBbCiAgICAic2lnbiIKICBdLAogICJhbGciOiAiUlMyNTYiLAogICJraWQiOiAiMGIxOTRkZjQtNzE0OS00MTQ2LTk3YzUtNzhmZGYwZDRmYjFkIiwKICAiZCI6ICJhcFhneVlZSno3RUdZdGQ4bVF6dXgxMl90d2RfdHdUY1pwSzJnRzhUd29MYlVkX0dmc3J2VXlUYzYyUk1EdU43d0RIbHFyWWVQTHBtS25LWDJKYjE5U1hFa1Nvanp0c3dSOF9EdVYwTE9QZkpQb3JPVTd3MFdLMHJjX3pXM3VURTJ3Ti01Nm5DQnhLcE41ZnZSWDRyYkhsazlFSTZFNTRtS1VWUzl6T3pQdGRiTjc3OEY2U3haMl9kc2FTQUhKMzZqWlpRUFRUdmZRb0Z5UzlzSTJOVDlkbGpvVm1PMk1xVEZ2ZG5VY0RQWng4ajFIQzFmWWNFU2NsV2VURFNpRWFuX293RE9UVjdPLXFCb2h3N0pGd2tScTNVeC1oSjFKMHdlaEJsbGZVVnB3cjI1aElxNXBKUlF4cnZucVU3SHdqU2xuSmpfQjFLSFdCdk9WOUdyUUZfWFEiLAogICJuIjogIjRPd0s1SEk2QVFtS3ZkeGt5S29xX1d1UFcyVl9vcWtaNkVjSDZSQjd5bVJ3cDhteXFRVFR4LVlfejcwZ3Z6d1o1YUNyQ0JqVi10WThXckk1cDBENjg3aEFEbDEtQ2ttTHFHQjVCR016bXd3c0wzYS1lZlhOMVBXSFBhR3FYR0IxUDBueGl3TVJaMTZKcHhOSWtqWGdOajcyd0tob2VKVEFpVUpGbWE5RFdyQ2FxMlJ1ZWVDWWVKNnBqVmdYcEpWNUtOMzFFQUhoRHZMUDZEbk4zd3h6NnlOcTBBNjBQeFRLcmpHdzhUYzlqN18tT3pwSUV1Y3A2T3p0ZEg0c2o0QURSRWtBUWtYNktZV0c1SE9nWldyUWpaemF1MDlPOEVsa2hPZDFNdE9zVEZiRmdyTXBkamR1OFdoSGprVlp6Yk5hd0IwNFBra3Q2VkYtNU5EMktGWWx6dyIsCiAgImUiOiAiQVFBQiIsCiAgInAiOiAiLVB3R01xTzU1TjNwcmFGTjhrNmdmSEhEajBpRllQS3BvNVpBN1JqNEpZQmpybmlEcXJnN1ItaE53NlhFM2tXQ1hJNjZXLXI2aVFrb196SE9uU3IyZW0wTjU2Rm9zY3ZXMFhhSG5CbkRkQXVHUm9LUDQzcW16OG5zeDJTWjlKZmZNN0o1QjFvODk1RmhUTHA4ZXFLTkVlS3BpV3Y2bGR4M0txSFZnOGZqN0cwIiwKICAicSI6ICI1MEp6ZldBNlhueFJHQVN6Z21jaGI3V1JINTlmenhWSmt3ajNTZF9EMFlJOEVNLUFwanlIOG1tTU5fZVZ5cEhTcmJFSFo5cHJTTVVjUmZxRC1TUlNpT1NFd1BoVmJ3Zmc3X0VQeE5YeDVkOGxxZ0dOUUNyNVBEWGRCd2lYMEt3TXlpcXBNMEM2YWtYX2lfMmRmWlRFb2VkMmx0RlhIcFp1MDZKOGNBZC1mYXMiLAogICJkcCI6ICJ4SGNSYU9uNmFGYVc2bFFLem5VdWU2UEZIUTJyZVZsaGRGeS1kSmdzVG1NbHhPa0JkRGVWUjJOTjRXQ3ZuSGdhcW5CUkt2Q2Fxb0VZNFcxcXpHZTNQOWxIakl1M3NmdlhVVWNITUt5X3BwVGxha1BoeUN6aTdiazI1Z3RDMUZiMlg3T25mcDY4MXRqWGZ4VHoza3pmcGNwRjN0TGVVMXc0aC1KVk9Yd0VKRzAiLAogICJkcSI6ICJpX3lPbWt0QXFlZEkwMmd0SFhlLUpyZmF4RENlTjJWa1p3dmJYUzJGaEhINFdCaXpnRzFOd2JDZ2YxUndxUEdDZlQtWEF3ZVZQN1NKZTlhOFFuajVPUUpUVmRnOUp2dTI3cWVXYXdreTUzb2ZlM3g2LTJmSF9PbUNCUHJ2b3hJeW44SVpMX3d6bTVjSnJMejFzNG4xU1NncWdmcndhSVNaUzZTa19NLWNnd2MiLAogICJxaSI6ICJVbjZwWVZDMTZJZk5FLVlhaUtjNmZFRTZkZnZZWm4xOWdOb2JERmI2VFcxV1A0T0xac3IzdlVYREptajNfYm0yR19QOVNGbGdzNWZ6UUpLUDd5RTdDTjNlWmxmaHMzUE8tc3NWbDZrSGg1dTVtLWp2Q0R1OGo1dFl5c3FvRXFIV3RqMUI1VGNzSGxvOWkwTFl5dThkT3ViV1cxa0pmSWtteXFyb3pSa0NhZEkiCn0=';

    public function createClientAssertion(): string
    {
        $now = new DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $exp = $now->modify('+3 minutes');

        $jwkJson = base64_decode($this->base64Jwk);
        $jwk = new JWK(json_decode($jwkJson, true));

        $algorithmManager = new AlgorithmManager([new RS256()]);
        $jwsBuilder = new JWSBuilder($algorithmManager);
        $serializer = new CompactSerializer();

        $payload = json_encode([
            'iss' => $this->clientId,
            'sub' => $this->clientId,
            'aud' => 'https://esignet.ida.fayda.et/v1/esignet/oauth/v2/token',
            'iat' => $now->getTimestamp(),
            'exp' => $exp->getTimestamp(),
            'jti' => bin2hex(random_bytes(8)),
        ], JSON_UNESCAPED_SLASHES);

        Log::info('JWT payload: ' . $payload);
        Log::info('Decoded JWK kid: ' . $jwk->get('kid'));

        $jws = $jwsBuilder->create()
            ->withPayload($payload)
            ->addSignature($jwk, [
                'alg' => 'RS256',
                'kid' => $this->keyId,
                // 'typ' => 'JWT' // Removed
            ])
            ->build();

        return $serializer->serialize($jws, 0);
    }

    public function getToken(string $authorizationCode): array
    {
        $clientAssertion = $this->createClientAssertion();

        Log::info('Client assertion JWT: - got by using the base64 exactly ' . $clientAssertion);

        $response = Http::asForm()
            ->withOptions([
                'verify' => false,
            ])
            ->post($this->tokenEndpoint, [
                'grant_type' => 'authorization_code',
                'code' => $authorizationCode,
                'redirect_uri' => $this->redirectUri,
                'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
                'client_assertion' => $clientAssertion,
            ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to get token: ' . $response->body());
        }

        return $response->json();
    }

    public function getUserInfo(string $accessToken): array
    {
        $response = Http::withToken($accessToken)->get($this->userInfoEndpoint);

        if (!$response->successful()) {
            throw new \Exception('Failed to get user info: ' . $response->body());
        }

        return $response->json();
    }

    public function getAuthorizationUrl(?string $state = null, ?string $nonce = null): string
    {
        $state = $state ?? bin2hex(random_bytes(8));
        $nonce = $nonce ?? bin2hex(random_bytes(8));

        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => 'openid profile',
            'state' => $state,
            'nonce' => $nonce,
        ]);

        return "{$this->authorizationEndpoint}?{$query}";
    }
}
