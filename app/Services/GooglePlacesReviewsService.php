<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GooglePlacesReviewsService
{
    private const CACHE_TTL_SECONDS = 86_400;

    /**
     * Tenta extrair Place ID de uma URL do Google Maps.
     */
    public static function extractPlaceIdFromMapsUrl(?string $mapsUrl): ?string
    {
        if ($mapsUrl === null || trim($mapsUrl) === '') {
            return null;
        }

        if (preg_match('/[?&]place_id=([^&]+)/i', $mapsUrl, $matches)) {
            return urldecode($matches[1]);
        }

        if (preg_match('/(ChI[a-zA-Z0-9_-]{20,})/', $mapsUrl, $matches)) {
            return $matches[1];
        }

        if (preg_match('/!1s(0x[a-fA-F0-9]+:0x[a-fA-F0-9]+)/', $mapsUrl, $matches)) {
            return $matches[1];
        }

        if (preg_match('/!16s(?:%2F|\/)g(?:%2F|\/)([A-Za-z0-9_-]+)/i', $mapsUrl, $matches)) {
            return 'g/'.$matches[1];
        }

        if (preg_match('/(?:^|[?&#!\/])g\/([A-Za-z0-9_-]{8,})/i', $mapsUrl, $matches)) {
            return 'g/'.$matches[1];
        }

        return null;
    }

    public static function normalizePlaceId(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $value = trim($value);

        if (
            str_starts_with($value, 'http')
            || str_contains($value, 'google.com/maps')
            || str_contains($value, 'maps.app.goo.gl')
        ) {
            return self::extractPlaceIdFromMapsUrl($value);
        }

        return $value;
    }

    public function resolvePlaceId(Organization $organization): ?string
    {
        $explicit = self::normalizePlaceId($organization->google_place_id);
        if ($explicit !== null && $explicit !== '') {
            return $explicit;
        }

        return self::extractPlaceIdFromMapsUrl($organization->maps_url);
    }

    public function writeReviewUrl(?string $placeId): ?string
    {
        if ($placeId === null || trim($placeId) === '') {
            return null;
        }

        $id = trim($placeId);

        if (preg_match('/^0x[a-fA-F0-9]+:0x[a-fA-F0-9]+$/', $id)) {
            return 'https://www.google.com/maps/place//data=!4m3!3m2!1s' . rawurlencode($id) . '!12e1';
        }

        return 'https://search.google.com/local/writereview?placeid=' . rawurlencode($id);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function forOrganization(Organization $organization): ?array
    {
        if (! $organization->google_reviews_enabled) {
            return null;
        }

        $placeId = $this->resolvePlaceId($organization);
        if ($placeId === null) {
            return null;
        }

        $cacheKey = 'google_reviews:org:' . $organization->id;

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($placeId) {
            return $this->fetchPlaceReviews($placeId);
        });
    }

    public function forgetOrganizationCache(int $organizationId): void
    {
        Cache::forget('google_reviews:org:' . $organizationId);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchPlaceReviews(string $placeId): ?array
    {
        $apiKey = config('services.google_places.api_key');
        if (! is_string($apiKey) || trim($apiKey) === '') {
            Log::warning('Google Places API key ausente — reviews não carregadas.');

            return null;
        }

        $response = Http::timeout(12)->get('https://maps.googleapis.com/maps/api/place/details/json', [
            'place_id' => $placeId,
            'fields' => 'rating,user_ratings_total,reviews,url',
            'language' => 'pt-BR',
            'reviews_sort' => 'newest',
            'key' => $apiKey,
        ]);

        if (! $response->successful()) {
            Log::warning('Google Places API HTTP error', ['status' => $response->status()]);

            return null;
        }

        $payload = $response->json();
        if (($payload['status'] ?? '') !== 'OK') {
            Log::warning('Google Places API status error', [
                'status' => $payload['status'] ?? null,
                'error_message' => $payload['error_message'] ?? null,
            ]);

            return null;
        }

        $result = $payload['result'] ?? [];
        $reviews = collect($result['reviews'] ?? [])
            ->take(5)
            ->map(function (array $review): array {
                return [
                    'author_name' => (string) ($review['author_name'] ?? 'Visitante'),
                    'rating' => (int) ($review['rating'] ?? 0),
                    'text' => (string) ($review['text'] ?? ''),
                    'relative_time' => (string) ($review['relative_time_description'] ?? ''),
                    'profile_photo_url' => isset($review['profile_photo_url'])
                        ? (string) $review['profile_photo_url']
                        : null,
                ];
            })
            ->filter(fn (array $review) => $review['text'] !== '' || $review['rating'] > 0)
            ->values()
            ->all();

        return [
            'place_id' => $placeId,
            'rating' => isset($result['rating']) ? round((float) $result['rating'], 1) : null,
            'user_ratings_total' => isset($result['user_ratings_total']) ? (int) $result['user_ratings_total'] : null,
            'write_review_url' => $this->writeReviewUrl($placeId),
            'maps_url' => isset($result['url']) ? (string) $result['url'] : null,
            'reviews' => $reviews,
        ];
    }
}
