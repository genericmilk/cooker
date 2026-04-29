<?php

namespace Genericmilk\Cooker\Blade;

use Genericmilk\Cooker\Cooker;

class Tag
{
    protected static bool $hmrEmitted = false;

    public static function render(string ...$names): string
    {
        $cooker = app(Cooker::class);
        $out = '';

        foreach ($names as $name) {
            $name = trim($name);
            $url  = $cooker->asset($name);

            if ($url === null) {
                $out .= '<!-- cooker: missing build for '.htmlspecialchars($name, ENT_QUOTES).' (run php artisan cooker:cook) -->';
                continue;
            }

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $out .= match ($ext) {
                'css'        => '<link rel="stylesheet" data-cooker="'.htmlspecialchars($name, ENT_QUOTES).'" href="'.htmlspecialchars($url, ENT_QUOTES).'">',
                'js', 'mjs'  => '<script type="module" data-cooker="'.htmlspecialchars($name, ENT_QUOTES).'" src="'.htmlspecialchars($url, ENT_QUOTES).'"></script>',
                default      => '<!-- cooker: unknown extension '.htmlspecialchars($ext, ENT_QUOTES).' -->',
            };
        }

        if ($cooker->devEnabled() && !self::$hmrEmitted) {
            $out .= self::hmrSnippet($cooker->devServerUrl());
            self::$hmrEmitted = true;
        }

        return $out;
    }

    /**
     * @internal — for tests
     */
    public static function resetForTesting(): void
    {
        self::$hmrEmitted = false;
    }

    protected static function hmrSnippet(string $url): string
    {
        $url = htmlspecialchars($url, ENT_QUOTES);

        return <<<HTML
<script data-cooker-hmr>(() => {
    const url = "{$url}";
    let es;
    const connect = () => {
        try { es = new EventSource(url); } catch (e) { return; }
        es.addEventListener("hello", () => console.log("%c👨‍🍳 Cooker HMR connected", "color:#C2410C;font-weight:600"));
        es.addEventListener("reload", () => location.reload());
        es.addEventListener("css-update", (ev) => {
            let payload;
            try { payload = JSON.parse(ev.data); } catch (_) { return location.reload(); }
            const changes = (payload && payload.changes) || [];
            const out = document.querySelector("base")?.href || location.origin;
            for (const c of changes) {
                if (c.type !== "css" || !c.hashed) continue;
                const link = document.querySelector('link[data-cooker="' + c.output + '"]');
                if (!link) { return location.reload(); }
                const next = link.cloneNode();
                next.href = link.href.replace(/\/[^\/]+\.css(\?.*)?$/, "/" + c.hashed + "?v=" + Date.now());
                next.addEventListener("load", () => link.remove(), { once: true });
                link.parentNode.insertBefore(next, link.nextSibling);
            }
        });
        es.addEventListener("error", () => {
            es.close();
            setTimeout(connect, 1000);
        });
    };
    connect();
})();</script>
HTML;
    }
}
