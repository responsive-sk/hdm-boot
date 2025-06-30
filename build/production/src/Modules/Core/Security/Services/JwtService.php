<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Security\Services;

use DateTimeImmutable;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use HdmBoot\Modules\Core\Security\Domain\ValueObjects\JwtToken;
use HdmBoot\Modules\Core\User\Domain\Entities\User;
use InvalidArgumentException;
use RuntimeException;

/**
 * JWT Service.
 *
 * Handles JWT token generation, validation, and parsing.
 */
final class JwtService
{
    private const ALGORITHM = 'HS256';

    public function __construct(
        private readonly string $secret,
        private readonly int $expirySeconds = 3600 // 1 hour default
    ) {
        if (empty($secret)) {
            throw new InvalidArgumentException('JWT secret cannot be empty');
        }

        if (strlen($secret) < 32) {
            throw new InvalidArgumentException('JWT secret must be at least 32 characters long');
        }
    }

    /**
     * Generate JWT token for user.
     */
    public function generateToken(User $user): JwtToken
    {
        $now = new DateTimeImmutable();
        $expiresAt = $now->modify("+{$this->expirySeconds} seconds");

        $payload = [
            'iss'            => 'mva-bootstrap', // Issuer
            'aud'            => 'mva-bootstrap', // Audience
            'iat'            => $now->getTimestamp(), // Issued at
            'exp'            => $expiresAt->getTimestamp(), // Expiration
            'user_id'        => $user->getId()->toString(),
            'email'          => $user->getEmail(),
            'name'           => $user->getName(),
            'role'           => $user->getRole(),
            'status'         => $user->getStatus(),
            'email_verified' => $user->isEmailVerified(),
        ];

        try {
            $token = JWT::encode($payload, $this->secret, self::ALGORITHM);

            return new JwtToken($token, $payload, $expiresAt);
        } catch (\Exception $e) {
            throw new RuntimeException('Failed to generate JWT token: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Validate and parse JWT token.
     */
    public function validateToken(string $token): JwtToken
    {
        if (empty($token)) {
            throw new InvalidArgumentException('Token cannot be empty');
        }

        try {
            $decoded = JWT::decode($token, new Key($this->secret, self::ALGORITHM));
            /** @var array<string, mixed> $payload */
            $payload = (array) $decoded;

            // Validate required fields
            $this->validatePayload($payload);

            $expTimestamp = is_int($payload['exp'] ?? null) ? $payload['exp'] : time();
            $expiresAt = new DateTimeImmutable('@' . $expTimestamp);

            return new JwtToken($token, $payload, $expiresAt);
        } catch (\Firebase\JWT\ExpiredException $e) {
            throw new InvalidArgumentException('Token has expired', 0, $e);
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            throw new InvalidArgumentException('Token signature is invalid', 0, $e);
        } catch (\Firebase\JWT\BeforeValidException $e) {
            throw new InvalidArgumentException('Token is not yet valid', 0, $e);
        } catch (\Exception $e) {
            throw new InvalidArgumentException('Invalid token: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Extract token from Authorization header.
     */
    public function extractTokenFromHeader(string $authorizationHeader): string
    {
        if (empty($authorizationHeader)) {
            throw new InvalidArgumentException('Authorization header is empty');
        }

        if (!str_starts_with($authorizationHeader, 'Bearer ')) {
            throw new InvalidArgumentException('Authorization header must start with "Bearer "');
        }

        $token = substr($authorizationHeader, 7); // Remove "Bearer " prefix

        if (empty($token)) {
            throw new InvalidArgumentException('Token is empty in Authorization header');
        }

        return $token;
    }

    /**
     * Refresh token (generate new token with same user data).
     */
    public function refreshToken(JwtToken $token): JwtToken
    {
        if ($token->isExpired()) {
            throw new InvalidArgumentException('Cannot refresh expired token');
        }

        $payload = $token->getPayload();
        $now = new DateTimeImmutable();
        $expiresAt = $now->modify("+{$this->expirySeconds} seconds");

        // Update timestamps
        $payload['iat'] = $now->getTimestamp();
        $payload['exp'] = $expiresAt->getTimestamp();

        try {
            $newToken = JWT::encode($payload, $this->secret, self::ALGORITHM);

            return new JwtToken($newToken, $payload, $expiresAt);
        } catch (\Exception $e) {
            throw new RuntimeException('Failed to refresh JWT token: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get token expiry time in seconds.
     */
    public function getExpirySeconds(): int
    {
        return $this->expirySeconds;
    }

    /**
     * Validate JWT payload structure.
     *
     * @param array<string, mixed> $payload
     */
    private function validatePayload(array $payload): void
    {
        $requiredFields = ['iss', 'aud', 'iat', 'exp', 'user_id', 'email', 'role'];

        foreach ($requiredFields as $field) {
            if (!isset($payload[$field])) {
                throw new InvalidArgumentException("Missing required field: {$field}");
            }
        }

        // Validate issuer and audience
        if ($payload['iss'] !== 'mva-bootstrap') {
            throw new InvalidArgumentException('Invalid token issuer');
        }

        if ($payload['aud'] !== 'mva-bootstrap') {
            throw new InvalidArgumentException('Invalid token audience');
        }

        // Validate timestamps
        if (!is_numeric($payload['iat']) || !is_numeric($payload['exp'])) {
            throw new InvalidArgumentException('Invalid timestamp format');
        }

        if ($payload['exp'] <= $payload['iat']) {
            throw new InvalidArgumentException('Token expiration must be after issued time');
        }
    }
}
