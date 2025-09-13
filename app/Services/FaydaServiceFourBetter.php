<?php

namespace App\Services;

use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Illuminate\Support\Facades\Log;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Illuminate\Support\Facades\Http;
use Lcobucci\JWT\Signer\Key\InMemory;

class FaydaServiceFourBetter
{
    protected string $clientId = 'crXYIYg2cJiNTaw5t-peoPzCRo-3JATNfBd5A86U8t0';
    protected string $redirectUri = 'http://localhost:3000/callback';
    protected string $authorizationEndpoint = 'https://esignet.ida.fayda.et/authorize';
    protected string $tokenEndpoint = 'https://esignet.ida.fayda.et/v1/esignet/oauth/v2/token';
    protected string $userInfoEndpoint = 'https://esignet.ida.fayda.et/v1/esignet/oidc/userinfo';
    protected string $keyId = '0b194df4-7149-4146-97c5-78fdf0d4fb1d';

    // RSA Private Key (PEM format)
    protected string $privateKey = <<<EOD
-----BEGIN RSA PRIVATE KEY-----
MIIEpAIBAAKCAQEA4OwK5HI6AQmKvdxkyKoq/WuPW2V/oqkZ6EcH6RB7ymRwp8my
qQTTx+Y/z70gvzwZ5aCrCBjV+tY8WrI5p0D687hADl1+CkmLqGB5BGMzmwwsL3a+
efXN1PWHPaGqXGB1P0nxiwMRZ16JpxNIkjXgNj72wKhoeJTAiUJFma9DWrCaq2Ru
eeCYeJ6pjVgXpJV5KN31EAHhDvLP6DnN3wxz6yNq0A60PxTKrjGw8Tc9j7/+OzpI
Eucp6OztdH4sj4ADREkAQkX6KYWG5HOgZWrQjZzau09O8ElkhOd1MtOsTFbFgrMp
djdu8WhHjkVZzbNawB04Pkkt6VF+5ND2KFYlzwIDAQABAoIBAGqV4MmGCc+xBmLX
fJkM7sddv7cHf7cE3GaStoBvE8KC21Hfxn7K71Mk3OtkTA7je8Ax5aq2Hjy6Zipy
l9iW9fUlxJEqI87bMEfPw7ldCzj3yT6KzlO8NFitK3P81t7kxNsDfuepwgcSqTeX
70V+K2x5ZPRCOhOeJilFUvczsz7XWze+/BeksWdv3bGkgByd+o2WUD00730KBckv
bCNjU/XZY6FZjtjKkxb3Z1HAz2cfI9RwtX2HBEnJVnkw0ohGp/6MAzk1ezvqgaIc
OyRcJEat1MfoSdSdMHoQZZX1FacK9uYSKuaSUUMa756lOx8I0pZyY/wdSh1gbzlf
Rq0Bf10CgYEA+PwGMqO55N3praFN8k6gfHHDj0iFYPKpo5ZA7Rj4JYBjrniDqrg7
R+hNw6XE3kWCXI66W+r6iQko/zHOnSr2em0N56FoscvW0XaHnBnDdAuGRoKP43qm
z8nsx2SZ9JffM7J5B1o895FhTLp8eqKNEeKpiWv6ldx3KqHVg8fj7G0CgYEA50Jz
fWA6XnxRGASzgmchb7WRH59fzxVJkwj3Sd/D0YI8EM+ApjyH8mmMN/eVypHSrbEH
Z9prSMUcRfqD+SRSiOSEwPhVbwfg7/EPxNXx5d8lqgGNQCr5PDXdBwiX0KwMyiqp
M0C6akX/i/2dfZTEoed2ltFXHpZu06J8cAd+fasCgYEAxHcRaOn6aFaW6lQKznUu
e6PFHQ2reVlhdFy+dJgsTmMlxOkBdDeVR2NN4WCvnHgaqnBRKvCaqoEY4W1qzGe3
P9lHjIu3sfvXUUcHMKy/ppTlakPhyCzi7bk25gtC1Fb2X7Onfp681tjXfxTz3kzf
pcpF3tLeU1w4h+JVOXwEJG0CgYEAi/yOmktAqedI02gtHXe+JrfaxDCeN2VkZwvb
XS2FhHH4WBizgG1NwbCgf1RwqPGCfT+XAweVP7SJe9a8Qnj5OQJTVdg9Jvu27qeW
awky53ofe3x6+2fH/OmCBPrvoxIyn8IZL/wzm5cJrLz1s4n1SSgqgfrwaISZS6Sk
/M+cgwcCgYBSfqlhULXoh80T5hqIpzp8QTp1+9hmfX2A2hsMVvpNbVY/g4tmyve9
RcMmaPf9ubYb8/1IWWCzl/NAko/vITsI3d5mV+Gzc876yxWXqQeHm7mb6O8IO7yP
m1jKyqgSoda2PUHlNyweWj2LQtjK7x065tZbWQl8iSbKqujNGQJp0g==
-----END RSA PRIVATE KEY-----
EOD;

    // RSA Public Key (PEM format)
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
        $exp = $now->modify('+15 minutes');

        $signingKey = InMemory::plainText($this->privateKey);
        $verificationKey = InMemory::plainText($this->publicKey);

        $jwtConfig = Configuration::forAsymmetricSigner(
            new Sha256(),
            $signingKey,
            $verificationKey
        );

        $token = $jwtConfig->builder()
            ->issuedBy($this->clientId)
            ->permittedFor($this->tokenEndpoint)
            ->issuedAt($now)
            ->expiresAt($exp)
            ->identifiedBy(bin2hex(random_bytes(8)))
            ->relatedTo($this->clientId)
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
                'verify' => base_path('cacert.pem'), // Set to false only for development if needed
            ])
            ->post($this->tokenEndpoint, [
                'grant_type' => 'authorization_code',
                'code' => $authorizationCode,
                'redirect_uri' => $this->redirectUri,
                'client_id' => $this->clientId,
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
