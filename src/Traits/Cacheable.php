<?php

namespace HosnyAdeeb\ModelActions\Traits;

use Illuminate\Support\Facades\Cache;

trait Cacheable
{
    /**
     * Cache TTL in minutes. Null uses config default.
     */
    protected ?int $cacheTtl = null;

    /**
     * Custom cache key. Null auto-generates.
     */
    protected ?string $cacheKey = null;

    /**
     * Whether caching is enabled for this action.
     */
    protected bool $cacheEnabled = true;

    /**
     * Cache tags for grouped invalidation.
     */
    protected array $cacheTags = [];

    /**
     * Execute with caching.
     *
     * @param callable $callback
     * @return mixed
     */
    protected function remember(callable $callback): mixed
    {
        if (!$this->shouldCache()) {
            return $callback();
        }

        $key = $this->getCacheKey();
        $ttl = $this->getCacheTtl();

        if ($this->hasCacheTags()) {
            return Cache::tags($this->getCacheTags())->remember($key, $ttl * 60, $callback);
        }

        return Cache::remember($key, $ttl * 60, $callback);
    }

    /**
     * Clear this action's cache.
     *
     * @return bool
     */
    protected function clearCache(): bool
    {
        if ($this->hasCacheTags()) {
            return Cache::tags($this->getCacheTags())->flush();
        }

        return Cache::forget($this->getCacheKey());
    }

    /**
     * Clear cache by specific key.
     *
     * @param string $key
     * @return bool
     */
    protected function clearCacheByKey(string $key): bool
    {
        return Cache::forget($key);
    }

    /**
     * Clear cache by tags.
     *
     * @param array $tags
     * @return bool
     */
    protected function clearCacheByTags(array $tags): bool
    {
        return Cache::tags($tags)->flush();
    }

    /**
     * Determine if caching should be used.
     *
     * @return bool
     */
    protected function shouldCache(): bool
    {
        return $this->cacheEnabled && config('model-actions.cache.enabled', false);
    }

    /**
     * Generate or get the cache key.
     *
     * @return string
     */
    protected function getCacheKey(): string
    {
        if ($this->cacheKey) {
            return $this->cacheKey;
        }

        $prefix = config('model-actions.cache.prefix', 'model_actions');
        $class = class_basename(static::class);
        $hash = md5(serialize($this->getCacheKeyData()));

        return "{$prefix}:{$class}:{$hash}";
    }

    /**
     * Get data to include in cache key generation.
     * Override to customize cache key generation.
     *
     * @return array
     */
    protected function getCacheKeyData(): array
    {
        return get_object_vars($this);
    }

    /**
     * Get the cache TTL in minutes.
     *
     * @return int
     */
    protected function getCacheTtl(): int
    {
        return $this->cacheTtl ?? (int) config('model-actions.cache.ttl', 60);
    }

    /**
     * Get cache tags.
     *
     * @return array
     */
    protected function getCacheTags(): array
    {
        return $this->cacheTags;
    }

    /**
     * Check if cache tags are available.
     *
     * @return bool
     */
    protected function hasCacheTags(): bool
    {
        return !empty($this->cacheTags) && $this->cacheDriverSupportsTags();
    }

    /**
     * Check if current cache driver supports tags.
     *
     * @return bool
     */
    protected function cacheDriverSupportsTags(): bool
    {
        $driver = config('cache.default');
        $taggableDrivers = ['redis', 'memcached', 'array', 'dynamodb'];

        return in_array($driver, $taggableDrivers);
    }

    /**
     * Set custom cache key.
     *
     * @param string $key
     * @return static
     */
    public function setCacheKey(string $key): static
    {
        $this->cacheKey = $key;
        return $this;
    }

    /**
     * Set cache TTL.
     *
     * @param int $minutes
     * @return static
     */
    public function setCacheTtl(int $minutes): static
    {
        $this->cacheTtl = $minutes;
        return $this;
    }

    /**
     * Set cache tags.
     *
     * @param array $tags
     * @return static
     */
    public function setCacheTags(array $tags): static
    {
        $this->cacheTags = $tags;
        return $this;
    }

    /**
     * Disable caching for this action.
     *
     * @return static
     */
    public function withoutCache(): static
    {
        $this->cacheEnabled = false;
        return $this;
    }

    /**
     * Enable caching for this action.
     *
     * @return static
     */
    public function withCache(): static
    {
        $this->cacheEnabled = true;
        return $this;
    }
}
