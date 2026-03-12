<?php

declare(strict_types=1);

namespace GodyarV4\Services;

use GodyarV4\Bootstrap\Request;
use GodyarV4\Bootstrap\Response;

final class LegacyRedirectService
{
    public function match(Request $request): ?Response
    {
        $path = $request->path;
        $query = $request->get;
        $rules = require godyar_v4_project_root() . '/app/Config/legacy_redirects.php';

        foreach ($rules as $rule) {
            $method = strtoupper((string)($rule['method'] ?? 'GET'));
            if ($method !== '*' && $method !== strtoupper($request->method)) {
                continue;
            }

            $pattern = (string)($rule['pattern'] ?? '');
            if ($pattern === '' || !preg_match($pattern, $path, $matches)) {
                continue;
            }

            $target = (string)($rule['target'] ?? '/');
            foreach ($matches as $index => $value) {
                if (is_int($index)) {
                    $target = str_replace('{' . $index . '}', (string)$value, $target);
                }
            }

            if (!empty($rule['query_map']) && is_array($rule['query_map'])) {
                foreach ($rule['query_map'] as $queryKey => $placeholder) {
                    $target = str_replace($placeholder, (string)($query[$queryKey] ?? ''), $target);
                }
            }

            $target = preg_replace('#//+#', '/', $target) ?: $target;
            $target = rtrim($target, '/') ?: '/';
            $status = (int)($rule['status'] ?? 301);

            return new Response('', $status, ['Location' => $target]);
        }

        return null;
    }
}
