<?php
declare(strict_types=1);

namespace charlymatloc\infra\jwt;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use charlymatloc\core\ports\api\dto\ProfileDTO;
use charlymatloc\core\ports\api\jwt\JwtManagerInterface;
use charlymatloc\core\ports\api\jwt\JwtManagerExpiredTokenException;
use charlymatloc\core\ports\api\jwt\JwtManagerInvalidTokenException;

class JwtManager implements JwtManagerInterface
{
    private string $secret;
    private string $issuer;
    private string $algorithm;
    private int $accessTokenExpiry;
    private int $refreshTokenExpiry;

    public function __construct(
        string $secret,
        string $issuer = 'charlymatloc.api',
        string $algorithm = 'HS512',
        int $accessTokenExpiry = 3600,
        int $refreshTokenExpiry = 2592000
    ) {
        $this->secret = $secret;
        $this->issuer = $issuer;
        $this->algorithm = $algorithm;
        $this->accessTokenExpiry = $accessTokenExpiry;
        $this->refreshTokenExpiry = $refreshTokenExpiry;
    }

    public function create(ProfileDTO $profile, int $type): string
    {
        $now = time();
        $expiry = $type === self::ACCESS_TOKEN 
            ? $now + $this->accessTokenExpiry 
            : $now + $this->refreshTokenExpiry;

        $payload = [
            'iss' => $this->issuer,
            'iat' => $now,
            'exp' => $expiry,
            'sub' => $profile->ID,
            'email' => $profile->email,
            'role' => $profile->role,
        ];

        return JWT::encode($payload, $this->secret, $this->algorithm);
    }

    public function validate(string $token): ProfileDTO
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));
            
            return new ProfileDTO(
                $decoded->sub,
                $decoded->email,
                $decoded->role
            );
        } catch (ExpiredException $e) {
            throw new JwtManagerExpiredTokenException('Token has expired', 0, $e);
        } catch (\Exception $e) {
            throw new JwtManagerInvalidTokenException('Invalid token: ' . $e->getMessage(), 0, $e);
        }
    }
}
