<?php

namespace App\Models\Concerns;

use Spatie\Translatable\HasTranslations;

/**
 * Simplified translatable trait that reads fr > es > en
 * and always writes to the 'fr' locale.
 *
 * Keeps compatibility with the existing JSON column structure
 * from Spatie's HasTranslations without requiring per-locale management.
 */
trait HasFallbackTranslations
{
    use HasTranslations;

    protected static array $localePriority = ['fr', 'es', 'en'];

    public function getTranslation(string $key, string $locale, bool $useFallbackLocale = true): mixed
    {
        $translations = $this->getTranslations($key);

        foreach (static::$localePriority as $fallbackLocale) {
            if (isset($translations[$fallbackLocale]) && $translations[$fallbackLocale] !== '') {
                return $translations[$fallbackLocale];
            }
        }

        // Last resort: return any non-empty value
        foreach ($translations as $value) {
            if ($value !== '' && $value !== null) {
                return $value;
            }
        }

        return '';
    }

    public function setTranslation(string $key, string $locale, mixed $value): self
    {
        return parent::setTranslation($key, 'fr', $value);
    }
}
