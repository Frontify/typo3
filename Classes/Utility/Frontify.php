<?php
/* (c) Copyright Frontify Ltd., all rights reserved. Created 2020-01-27 */

namespace Frontify\Typo3\Utility;

final class Frontify {

    private static function extractTokenOfUrl(string $url): ?string {
        $path = parse_url($url)['path'] ?? '';
        $pathParts = explode('/', $path);
        $token = end($pathParts);
        return self::isValidToken($token) ? $token : null;
    }

    public static function identifierByIdAndUrl(int $id, string $genericUrl): string {
        return "{$id}@{$genericUrl}";
    }

    public static function extractIdAndToken(string $identifier): ?array {
        $parts = explode('@', ltrim($identifier, '/'), 2);

        if (count($parts) !== 2) {
            return null;
        }

        return [
            (int) $parts[0],
            $parts[1]
        ];
    }

    public static function extractUrl(string $identifier): ?string {
        try {
            list($id, $url) = self::extractIdAndToken($identifier);
            return $url;
        } catch (\Exception $exception) {
            return null;
        }
    }

    public static function isValidUrl(string $url): bool {
        return self::extractTokenOfUrl($url) !== null;
    }

    private static function isValidToken(string $token): bool {
        return preg_match('/^[a-zA-Z0-9_-]+:[a-z0-9-]+:[a-zA-Z0-9_-]{43}$/', $token);
    }

}