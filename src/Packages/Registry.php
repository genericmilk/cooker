<?php

namespace Genericmilk\Cooker\Packages;

use GuzzleHttp\Client;
use RuntimeException;

class Registry
{
    public function __construct(
        protected string $baseUrl = 'https://registry.npmjs.org',
        protected ?Client $http = null,
    ) {
        $this->http ??= new Client(['timeout' => 60]);
    }

    /**
     * Resolve a package + range to a concrete version + tarball URL.
     * Returns ['version' => '1.2.3', 'tarball' => '...', 'dependencies' => [...]].
     */
    public function resolve(string $name, string $range = 'latest'): array
    {
        $url  = $this->baseUrl.'/'.$this->encodeName($name);
        $body = json_decode((string) $this->http->get($url)->getBody(), true);

        if (!is_array($body) || empty($body['versions'])) {
            throw new RuntimeException("Package not found: $name");
        }

        $version = $this->pickVersion($body, $range);
        $meta    = $body['versions'][$version];

        return [
            'name'         => $name,
            'version'      => $version,
            'tarball'      => $meta['dist']['tarball'] ?? null,
            'dependencies' => $meta['dependencies'] ?? [],
        ];
    }

    protected function pickVersion(array $body, string $range): string
    {
        $tags = $body['dist-tags'] ?? [];

        if (isset($tags[$range])) {
            return $tags[$range];
        }

        if (isset($body['versions'][$range])) {
            return $range;
        }

        $clean    = ltrim($range, '^~=v ');
        $versions = array_keys($body['versions']);
        usort($versions, 'version_compare');

        if (str_starts_with($range, '^')) {
            [$major] = explode('.', $clean);
            $match = null;
            foreach ($versions as $v) {
                if (str_starts_with($v, $major.'.') && version_compare($v, $clean, '>=')) {
                    $match = $v;
                }
            }
            if ($match) {
                return $match;
            }
        }

        if (str_starts_with($range, '~')) {
            $parts = explode('.', $clean);
            $prefix = $parts[0].'.'.($parts[1] ?? '0').'.';
            $match = null;
            foreach ($versions as $v) {
                if (str_starts_with($v, $prefix) && version_compare($v, $clean, '>=')) {
                    $match = $v;
                }
            }
            if ($match) {
                return $match;
            }
        }

        if (isset($body['versions'][$clean])) {
            return $clean;
        }

        return $tags['latest'] ?? end($versions);
    }

    protected function encodeName(string $name): string
    {
        return str_starts_with($name, '@')
            ? '@'.rawurlencode(substr($name, 1))
            : rawurlencode($name);
    }
}
