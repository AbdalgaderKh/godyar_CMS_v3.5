<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

function gdy_safe_exec(string $cmd, array $args = [], array $allow = []): array
{
    
    if (empty($allow)) {
        return ['ok' => false, 'code' => 403, 'out' => '', 'err' => 'Command execution disabled'];
    }

    
    $base = trim(strtok($cmd, " \t\r\n"));
    if (!in_array($base, $allow, true)) {
        return ['ok' => false, 'code' => 403, 'out' => '', 'err' => 'Command not allowed'];
    }

    
    $escaped = [];
    foreach ($args as $a) {
        $escaped[] = escapeshellarg((string)$a);
    }
    $full = $base . (count($escaped) ? (' ' . implode(' ', $escaped)) : '');

    $out = [];
    $ret = 0;
    @exec($full . ' 2>&1', $out, $ret);

    return [
        'ok'   => ($ret === 0),
        'code' => $ret,
        'out'  => implode("\n", $out),
        'err'  => ($ret === 0 ? '' : implode("\n", $out)),
    ];
}

function gdy_safe_ident(string $ident, array $allowed, string $fallback): string
{
    
    return in_array($ident, $allowed, true) ? $ident : $fallback;
}

function gdy_csrf_token(): string
{
    $token = (string)($_SESSION['_csrf'] ?? $_SESSION['csrf_token'] ?? '');
    if ($token === '') {
        $token = bin2hex(random_bytes(32));
    }
    $_SESSION['_csrf'] = $token;
    $_SESSION['csrf_token'] = $token;
    if (empty($_SESSION['csrf_time'])) {
        $_SESSION['csrf_time'] = time();
    }
    return $token;
}

function gdy_csrf_field(): string
{
    $t = htmlspecialchars(gdy_csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="_csrf" value="' . $t . '">';
}

function gdy_csrf_verify(?string $token): bool
{
    $sessionToken = (string)($_SESSION['_csrf'] ?? $_SESSION['csrf_token'] ?? '');
    if (!$token || $sessionToken === '') return false;
    $_SESSION['_csrf'] = $sessionToken;
    $_SESSION['csrf_token'] = $sessionToken;
    return hash_equals($sessionToken, $token);
}

if (!function_exists('gdy_csrf_inject_forms')) {
    function gdy_csrf_inject_forms(string $html): string
    {
        
        if ($html === '' || stripos($html, '<form') === false) return $html;

        $lower = strtolower($html);
        $out = '';
        $pos = 0;

        
        $pattern = '/<form\b[^>]*\bmethod\s*=\s*(["\']?)post\1[^>]*>/i';
        if (!preg_match_all($pattern, $html, $matches, PREG_OFFSET_CAPTURE)) {
            return $html;
        }

        foreach ($matches[0] as $m) {
            $tag = $m[0];
            $start = (int)$m[1];
            $end = $start + strlen($tag);

            
            $out .= substr($html, $pos, $start - $pos);

            
            $closePos = stripos($html, '</form', $end);
            if ($closePos === false) {
                
                $out .= $tag . "\n" . gdy_csrf_field();
                $pos = $end;
                continue;
            }

            $segment = substr($html, $end, $closePos - $end);
            if (stripos($segment, 'name="_csrf"') !== false || stripos($segment, "name='_csrf'") !== false) {
                $out .= $tag;
            } else {
                $out .= $tag . "\n" . gdy_csrf_field();
            }

            $pos = $end;
        }

        
        $out .= substr($html, $pos);
        return $out;
    }
}

if (!function_exists('gdy_csrf_inject_meta')) {
    function gdy_csrf_inject_meta(string $html): string
    {
        
        if ($html === '' || stripos($html, '<head') === false) return $html;

        $token = htmlspecialchars(gdy_csrf_token(), ENT_QUOTES, 'UTF-8');
        $inject = "\n" .
            '<meta name="csrf-token" content="' . $token . '">' . "\n" .
            '<script>' . "\n" .
            '(function(){' . "\n" .
            '  try{' . "\n" .
            '    var t = document.querySelector(\'meta[name="csrf-token"]\')?.getAttribute("content") || "";' . "\n" .
            '    if (!t) return;' . "\n" .
            '    window.GDY_CSRF_TOKEN = t;' . "\n" .
            '    if (window.fetch){' . "\n" .
            '      var _fetch = window.fetch;' . "\n" .
            '      window.fetch = function(input, init){' . "\n" .
            '        init = init || {};' . "\n" .
            '        init.headers = init.headers || {};' . "\n" .
            '        try{' . "\n" .
            '          if (init.method && String(init.method).toUpperCase() !== "GET"){' . "\n" .
            '            if (init.headers instanceof Headers){ init.headers.set("X-CSRF-Token", t); }' . "\n" .
            '            else if (Array.isArray(init.headers)) { init.headers.push(["X-CSRF-Token", t]); }' . "\n" .
            '            else { init.headers["X-CSRF-Token"] = t; }' . "\n" .
            '          }' . "\n" .
            '        }catch(e){}' . "\n" .
            '        return _fetch(input, init);' . "\n" .
            '      };' . "\n" .
            '    }' . "\n" .
            '    if (window.XMLHttpRequest){' . "\n" .
            '      var X = XMLHttpRequest.prototype;' . "\n" .
            '      var _open = X.open;' . "\n" .
            '      X.open = function(m, url){ this.__gdy_m = m; return _open.apply(this, arguments); };' . "\n" .
            '      var _send = X.send;' . "\n" .
            '      X.send = function(body){' . "\n" .
            '        try{ if (this.__gdy_m && String(this.__gdy_m).toUpperCase() !== "GET"){ this.setRequestHeader("X-CSRF-Token", t); } }catch(e){}' . "\n" .
            '        return _send.apply(this, arguments);' . "\n" .
            '      };' . "\n" .
            '    }' . "\n" .
            '  }catch(e){}' . "\n" .
            '})();' . "\n" .
            '</script>' . "\n";

        
        return preg_replace('/(<head\b[^>]*>)/i', '$1' . $inject, $html, 1) ?? $html;
    }
}

if (!function_exists('gdy_csrf_ob_callback')) {
    function gdy_csrf_ob_callback(string $html): string
    {
        
        if ($html === '' || (stripos($html, '<html') === false && stripos($html, '<!doctype') === false)) {
            return $html;
        }
        $html = gdy_csrf_inject_meta($html);
        $html = gdy_csrf_inject_forms($html);
        return $html;
    }
}
