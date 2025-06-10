<?php

namespace App\Services;

use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Illuminate\Support\Facades\Log;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Illuminate\Support\Facades\Http;
use Lcobucci\JWT\Signer\Key\InMemory;

class FaydaServiceFive
{
    protected string $clientId = 'crXYIYg2cJiNTaw5t-peoPzCRo-3JATNfBd5A86U8t0';
    protected string $redirectUri = 'http://localhost:3000/callback';
    protected string $authorizationEndpoint = 'https://esignet.ida.fayda.et/authorize';

    // Confirm this is exactly the token endpoint expected for the 'aud' claim
    protected string $tokenEndpoint = 'https://esignet.ida.fayda.et/v1/esignet/oauth/v2/token';

    protected string $userInfoEndpoint = 'https://esignet.ida.fayda.et/v1/esignet/oidc/userinfo';
    protected string $keyId = '0b194df4-7149-4146-97c5-78fdf0d4fb1d';

    // RSA Private Key (PEM format)
    protected string $privateKey = <<<EOD
-----BEGIN PRIVATE KEY-----
MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQDg7ArkcjoBCYq9
3GTIqir9a49bZX+iqRnoRwfpEHvKZHCnybKpBNPH5j/PvSC/PBnloKsIGNX61jxa
sjmnQPrzuEAOXX4KSYuoYHkEYzObDCwvdr559c3U9Yc9oapcYHU/SfGLAxFnXomn
E0iSNeA2PvbAqGh4lMCJQkWZr0NasJqrZG554Jh4nqmNWBeklXko3fUQAeEO8s/o
Oc3fDHPrI2rQDrQ/FMquMbDxNz2Pv/47OkgS5yno7O10fiyPgANESQBCRfophYbk
c6BlatCNnNq7T07wSWSE53Uy06xMVsWCsyl2N27xaEeORVnNs1rAHTg+SS3pUX7k
0PYoViXPAgMBAAECggEAapXgyYYJz7EGYtd8mQzux12/twd/twTcZpK2gG8TwoLb
Ud/GfsrvUyTc62RMDuN7wDHlqrYePLpmKnKX2Jb19SXEkSojztswR8/DuV0LOPfJ
PorOU7w0WK0rc/zW3uTE2wN+56nCBxKpN5fvRX4rbHlk9EI6E54mKUVS9zOzPtdb
N778F6SxZ2/dsaSAHJ36jZZQPTTvfQoFyS9sI2NT9dljoVmO2MqTFvdnUcDPZx8j
1HC1fYcESclWeTDSiEan/owDOTV7O+qBohw7JFwkRq3Ux+hJ1J0wehBllfUVpwr2
5hIq5pJRQxrvnqU7HwjSlnJj/B1KHWBvOV9GrQF/XQKBgQD4/AYyo7nk3emtoU3y
TqB8ccOPSIVg8qmjlkDtGPglgGOueIOquDtH6E3DpcTeRYJcjrpb6vqJCSj/Mc6d
KvZ6bQ3noWixy9bRdoecGcN0C4ZGgo/jeqbPyezHZJn0l98zsnkHWjz3kWFMunx6
oo0R4qmJa/qV3HcqodWDx+PsbQKBgQDnQnN9YDpefFEYBLOCZyFvtZEfn1/PFUmT
CPdJ38PRgjwQz4CmPIfyaYw395XKkdKtsQdn2mtIxRxF+oP5JFKI5ITA+FVvB+Dv
8Q/E1fHl3yWqAY1AKvk8Nd0HCJfQrAzKKqkzQLpqRf+L/Z19lMSh53aW0Vcelm7T
onxwB359qwKBgQDEdxFo6fpoVpbqVArOdS57o8UdDat5WWF0XL50mCxOYyXE6QF0
N5VHY03hYK+ceBqqcFEq8JqqgRjhbWrMZ7c/2UeMi7ex+9dRRwcwrL+mlOVqQ+HI
LOLtuTbmC0LUVvZfs6d+nrzW2Nd/FPPeTN+lykXe0t5TXDiH4lU5fAQkbQKBgQCL
/I6aS0Cp50jTaC0dd74mt9rEMJ43ZWRnC9tdLYWEcfhYGLOAbU3BsKB/VHCo8YJ9
P5cDB5U/tIl71rxCePk5AlNV2D0m+7bup5ZrCTLneh97fHr7Z8f86YIE+u+jEjKf
whkv/DOblwmsvPWzifVJKCqB+vBohJlLpKT8z5yDBwKBgFJ+qWFQteiHzRPmGoin
OnxBOnX72GZ9fYDaGwxW+k1tVj+Di2bK971FwyZo9/25thvz/UhZYLOX80CSj+8h
Owjd3mZX4bNzzvrLFZepB4ebuZvo7wg7vI+bWMrKqBKh1rY9QeU3LB5aPYtC2Mrv
HTrm1ltZCXyJJsqq6M0ZAmnS
-----END PRIVATE KEY-----
EOD;

    // RSA Public Key (PEM format)
//     protected string $publicKey = <<<EOD
// -----BEGIN PUBLIC KEY-----
// MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA4OwK5HI6AQmKvdxkyKoq
// /WuPW2V/oqkZ6EcH6RB7ymRwp8myqQTTx+Y/z70gvzwZ5aCrCBjV+tY8WrI5p0D6
// 87hADl1+CkmLqGB5BGMzmwwsL3a+efXN1PWHPaGqXGB1P0nxiwMRZ16JpxNIkjXg
// Nj72wKhoeJTAiUJFma9DWrCaq2RueeCYeJ6pjVgXpJV5KN31EAHhDvLP6DnN3wxz
// 6yNq0A60PxTKrjGw8Tc9j7/+OzpIEucp6OztdH4sj4ADREkAQkX6KYWG5HOgZWrQ
// jZzau09O8ElkhOd1MtOsTFbFgrMpdjdu8WhHjkVZzbNawB04Pkkt6VF+5ND2KFYl
// zwIDAQAB
// -----END PUBLIC KEY-----
// EOD;


    protected string $publicKey = <<<EOD
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA4OwK5HI6AQmKvdxkyKoq
/WuPW2V/oqkZ6EcH6RB7ymRwp8myqQTTx+Y/z70gvzwZ5aCrCBjV+tY8WrI5p0D6
87hADl1+CkmLqGB5BGMzmwwsL3a+efXN1PWHPaGqXGB1P0nxiwMRZ16JpxNIkjXg
Nj72wKhoeJTAiUJFma9DWrCaq2RueeCYeJ6pjVgXpJV5KN31EAHhDvLP6DnN3wxz
6yNq0A60PxTKrjGw8Tc9j7/+OzpIEucp6OztdH4sj4ADREkAQkX6KYWG5HOgZWrQ
jZzau09O8ElkhOd1MtOsTFbFgrMpdjdu8WhHjkVZzbNawB04Pkkt6VF+5ND2KFYl
zwIDAQAB
-----END PUBLIC KEY-----
EOD;


    public function createClientAssertion(): string
    {
        $now = new DateTimeImmutable();
        $exp = $now->modify('+5 minutes');

        $signingKey = InMemory::plainText($this->privateKey);
        $verificationKey = InMemory::plainText($this->publicKey);

        $jwtConfig = Configuration::forAsymmetricSigner(
            new Sha256(),
            $signingKey,
            $verificationKey
        );

        $token = $jwtConfig->builder()
            ->issuedBy($this->clientId)
            ->relatedTo($this->clientId)
            ->permittedFor($this->tokenEndpoint)
            ->issuedAt($now)
            ->expiresAt($exp)
            ->identifiedBy(bin2hex(random_bytes(8)), true) // <-- note the second argument here!
            ->withHeader('kid', $this->keyId)
            ->getToken($jwtConfig->signer(), $jwtConfig->signingKey());

        return $token->toString();
    }

    public function getToken(string $authorizationCode): array
    {
        $clientAssertion = $this->createClientAssertion();

        Log::info('Client assertion JWT: ' . $clientAssertion);

        $response = Http::asForm()
            ->withOptions([
                'verify' => file_exists(base_path('cacert.pem')) ? base_path('cacert.pem') : false, // or set to false for dev environment
                // TIP = USE .env for this   // ------------ // RECOMMENDED
                // 'verify' => base_path(env('CURL_CA_BUNDLE', 'cacert.pem')),
                //
                // 'verify' => false,
            ])
            ->post($this->tokenEndpoint, [
                'grant_type' => 'authorization_code',
                'code' => $authorizationCode,
                'redirect_uri' => $this->redirectUri,
                // Remove client_id here, since client_assertion is used
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