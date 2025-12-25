<?php

/**
 * Book Cover Template Helper.
 *
 * Provides easy cover image rendering in Symfony templates.
 */

use AtomFramework\Services\BookCoverService;

/**
 * Render book cover image with fallback.
 *
 * @param string $isbn       ISBN-10 or ISBN-13
 * @param string $size       'S', 'M', or 'L'
 * @param array  $attributes Additional HTML attributes
 */
function book_cover(string $isbn, string $size = 'M', array $attributes = []): string
{
    return BookCoverService::imgTag($isbn, $size, $attributes);
}

/**
 * Get Open Library cover URL.
 *
 * @param string $isbn ISBN-10 or ISBN-13
 * @param string $size 'S', 'M', or 'L'
 */
function book_cover_url(string $isbn, string $size = 'M'): string
{
    return BookCoverService::getOpenLibraryUrl($isbn, $size);
}

/**
 * Get all cover size URLs.
 *
 * @return array{small: string, medium: string, large: string}
 */
function book_cover_urls(string $isbn): array
{
    return BookCoverService::getAllSizes($isbn);
}

/**
 * Render cover with verified fallback.
 *
 * Slower but ensures a valid image is returned.
 *
 * @param string     $isbn     ISBN-10 or ISBN-13
 * @param array|null $metadata Optional metadata with cover_url
 */
function book_cover_verified(string $isbn, ?array $metadata = null): array
{
    return BookCoverService::getCover($isbn, BookCoverService::SIZE_MEDIUM, $metadata, true);
}
