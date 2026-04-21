<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

class AuthRedirect
{
    public static function pathFor(User $user, array $query = []): string
    {
        $path = route(self::routeNameFor($user), absolute: false);

        if ($query === []) {
            return $path;
        }

        $separator = str_contains($path, '?') ? '&' : '?';

        return $path.$separator.http_build_query($query);
    }

    public static function routeNameFor(User $user): string
    {
        return match (true) {
            $user->isAdmin() => 'admin.reports.index',
            $user->isDoctor() => 'doctor.dashboard',
            default => 'home',
        };
    }

    public static function pathFromRequestOrDefault(Request $request, User $user, array $query = []): string
    {
        return self::sanitizePath($request->string('redirect')->toString()) ?? self::pathFor($user, $query);
    }

    public static function sanitizePath(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (str_starts_with($path, '/')) {
            return $path;
        }

        $parsed = parse_url($path);
        $application = parse_url(url('/'));

        if (! is_array($parsed) || ! is_array($application)) {
            return null;
        }

        if (($parsed['host'] ?? null) !== ($application['host'] ?? null)) {
            return null;
        }

        if (($parsed['scheme'] ?? 'http') !== ($application['scheme'] ?? 'http')) {
            return null;
        }

        if (self::normalizePort($parsed['scheme'] ?? 'http', $parsed['port'] ?? null) !== self::normalizePort($application['scheme'] ?? 'http', $application['port'] ?? null)) {
            return null;
        }

        $path = $parsed['path'] ?? '/';
        $query = isset($parsed['query']) ? '?'.$parsed['query'] : '';
        $fragment = isset($parsed['fragment']) ? '#'.$parsed['fragment'] : '';

        return $path.$query.$fragment;
    }

    private static function normalizePort(string $scheme, int|string|null $port): int
    {
        if ($port !== null) {
            return (int) $port;
        }

        return $scheme === 'https' ? 443 : 80;
    }
}
