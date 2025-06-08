<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Carbon\Carbon;

class FaydaServiceTwo
{
    protected string $clientId = 'crXYIYg2cJiNTaw5t-peoPzCRo-3JATNfBd5A86U8t0';
    protected string $redirectUri = /* 'http://34.65.224.178:3000/' */ 'http://localhost:3000/callback'; // for this to work - I need to RUN the whole Laravel with port 3000, in localhost
    protected string $authorizationEndpoint = 'https://esignet.ida.fayda.et/authorize';
    protected string $tokenEndpoint = 'https://esignet.ida.fayda.et/v1/esignet/oauth/v2/token';
    protected string $userinfoEndpoint = 'https://esignet.ida.fayda.et/v1/esignet/oidc/userinfo';
    protected string $clientAssertionType = 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer';

    protected string $privateKey;
    protected string $keyId;

    public function __construct()
    {
        $keyJson = <<<EOD
        -----BEGIN PRIVATE KEY-----
        ewogICJrdHkiOiAiUlNBIiwKICAidXNlIjogInNpZyIsCiAgImtleV9vcHMiOiBbCiAgICAic2lnbiIKICBdLAogICJhbGciOiAiUlMyNTYiLAogICJraWQiOiAiMGIxOTRkZjQtNzE0OS00MTQ2LTk3YzUtNzhmZGYwZDRmYjFkIiwKICAiZCI6ICJhcFhneVlZSno3RUdZdGQ4bVF6dXgxMl90d2RfdHdUY1pwSzJnRzhUd29MYlVkX0dmc3J2VXlUYzYyUk1EdU43d0RIbHFyWWVQTHBtS25LWDJKYjE5U1hFa1Nvanp0c3dSOF9EdVYwTE9QZkpQb3JPVTd3MFdLMHJjX3pXM3VURTJ3Ti01Nm5DQnhLcE41ZnZSWDRyYkhsazlFSTZFNTRtS1VWUzl6T3pQdGRiTjc3OEY2U3haMl9kc2FTQUhKMzZqWlpRUFRUdmZRb0Z5UzlzSTJOVDlkbGpvVm1PMk1xVEZ2ZG5VY0RQWng4ajFIQzFmWWNFU2NsV2VURFNpRWFuX293RE9UVjdPLXFCb2h3N0pGd2tScTNVeC1oSjFKMHdlaEJsbGZVVnB3cjI1aElxNXBKUlF4cnZucVU3SHdqU2xuSmpfQjFLSFdCdk9WOUdyUUZfWFEiLAogICJuIjogIjRPd0s1SEk2QVFtS3ZkeGt5S29xX1d1UFcyVl9vcWtaNkVjSDZSQjd5bVJ3cDhteXFRVFR4LVlfejcwZ3Z6d1o1YUNyQ0JqVi10WThXckk1cDBENjg3aEFEbDEtQ2ttTHFHQjVCR016bXd3c0wzYS1lZlhOMVBXSFBhR3FYR0IxUDBueGl3TVJaMTZKcHhOSWtqWGdOajcyd0tob2VKVEFpVUpGbWE5RFdyQ2FxMlJ1ZWVDWWVKNnBqVmdYcEpWNUtOMzFFQUhoRHZMUDZEbk4zd3h6NnlOcTBBNjBQeFRLcmpHdzhUYzlqN18tT3pwSUV1Y3A2T3p0ZEg0c2o0QURSRWtBUWtYNktZV0c1SE9nWldyUWpaemF1MDlPOEVsa2hPZDFNdE9zVEZiRmdyTXBkamR1OFdoSGprVlp6Yk5hd0IwNFBra3Q2VkYtNU5EMktGWWx6dyIsCiAgImUiOiAiQVFBQiIsCiAgInAiOiAiLVB3R01xTzU1TjNwcmFGTjhrNmdmSEhEajBpRllQS3BvNVpBN1JqNEpZQmpybmlEcXJnN1ItaE53NlhFM2tXQ1hJNjZXLXI2aVFrb196SE9uU3IyZW0wTjU2Rm9zY3ZXMFhhSG5CbkRkQXVHUm9LUDQzcW16OG5zeDJTWjlKZmZNN0o1QjFvODk1RmhUTHA4ZXFLTkVlS3BpV3Y2bGR4M0txSFZnOGZqN0cwIiwKICAicSI6ICI1MEp6ZldBNlhueFJHQVN6Z21jaGI3V1JINTlmenhWSmt3ajNTZF9EMFlJOEVNLUFwanlIOG1tTU5fZVZ5cEhTcmJFSFo5cHJTTVVjUmZxRC1TUlNpT1NFd1BoVmJ3Zmc3X0VQeE5YeDVkOGxxZ0dOUUNyNVBEWGRCd2lYMEt3TXlpcXBNMEM2YWtYX2lfMmRmWlRFb2VkMmx0RlhIcFp1MDZKOGNBZC1mYXMiLAogICJkcCI6ICJ4SGNSYU9uNmFGYVc2bFFLem5VdWU2UEZIUTJyZVZsaGRGeS1kSmdzVG1NbHhPa0JkRGVWUjJOTjRXQ3ZuSGdhcW5CUkt2Q2Fxb0VZNFcxcXpHZTNQOWxIakl1M3NmdlhVVWNITUt5X3BwVGxha1BoeUN6aTdiazI1Z3RDMUZiMlg3T25mcDY4MXRqWGZ4VHoza3pmcGNwRjN0TGVVMXc0aC1KVk9Yd0VKRzAiLAogICJkcSI6ICJpX3lPbWt0QXFlZEkwMmd0SFhlLUpyZmF4RENlTjJWa1p3dmJYUzJGaEhINFdCaXpnRzFOd2JDZ2YxUndxUEdDZlQtWEF3ZVZQN1NKZTlhOFFuajVPUUpUVmRnOUp2dTI3cWVXYXdreTUzb2ZlM3g2LTJmSF9PbUNCUHJ2b3hJeW44SVpMX3d6bTVjSnJMejFzNG4xU1NncWdmcndhSVNaUzZTa19NLWNnd2MiLAogICJxaSI6ICJVbjZwWVZDMTZJZk5FLVlhaUtjNmZFRTZkZnZZWm4xOWdOb2JERmI2VFcxV1A0T0xac3IzdlVYREptajNfYm0yR19QOVNGbGdzNWZ6UUpLUDd5RTdDTjNlWmxmaHMzUE8tc3NWbDZrSGg1dTVtLWp2Q0R1OGo1dFl5c3FvRXFIV3RqMUI1VGNzSGxvOWkwTFl5dThkT3ViV1cxa0pmSWtteXFyb3pSa0NhZEkiCn0=
        -----END PRIVATE KEY-----
        EOD;

        $decoded = json_decode(base64_decode($keyJson), true);
        $this->privateKey = $decoded['d'];
        $this->keyId = $decoded['kid'];
    }



    // NOT WORKING
    //
    // public function createClientAssertion(): string
    // {
    //     $config = Configuration::forAsymmetricSigner(
    //         new Sha256(),
    //         InMemory::plainText($this->privateKey),
    //         InMemory::empty()
    //     );

    //     $now = Carbon::now();
    //     $token = $config->builder()
    //         ->issuedBy($this->clientId)
    //         ->permittedFor($this->tokenEndpoint)
    //         ->identifiedBy(bin2hex(random_bytes(16)))
    //         ->issuedAt($now)
    //         ->expiresAt($now->copy()->addMinutes(15))
    //         ->withHeader('kid', $this->keyId)
    //         ->getToken($config->signer(), $config->signingKey());

    //     return $token->toString();
    // }
    //
    //
    //
    // WORKING
    //
    public function createClientAssertion(): string
    {
        $config = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::plainText($this->privateKey),
            InMemory::empty()
        );

        $now = Carbon::now()->toDateTimeImmutable(); // Fix: convert to DateTimeImmutable

        $token = $config->builder()
            ->issuedBy($this->clientId)
            ->permittedFor($this->tokenEndpoint)
            ->identifiedBy(bin2hex(random_bytes(16)))
            ->issuedAt($now)
            ->expiresAt($now->modify('+15 minutes')) // DateTimeImmutable method
            ->withHeader('kid', $this->keyId)
            ->getToken($config->signer(), $config->signingKey());

        return $token->toString();
    }









    public function getToken(string $code): array
    {
        $assertion = $this->createClientAssertion();

        $response = Http::asForm()->post($this->tokenEndpoint, [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
            'client_id' => $this->clientId,
            'client_assertion_type' => $this->clientAssertionType,
            'client_assertion' => $assertion,
        ]);

        return $response->json();
    }

    public function getUserInfo(string $accessToken): array
    {
        return Http::withToken($accessToken)
            ->get($this->userinfoEndpoint)
            ->json();
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
