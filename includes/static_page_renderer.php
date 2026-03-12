<?php

declare(strict_types=1);

if (!function_exists('gdy_static_page_h')) {
    function gdy_static_page_h($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('gdy_static_page_base_url')) {
    function gdy_static_page_base_url(): string
    {
        if (function_exists('base_url')) {
            return rtrim((string) base_url(), '/');
        }
        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $scheme = $https ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
        return $host !== '' ? $scheme . '://' . $host : '';
    }
}

if (!function_exists('gdy_static_page_site_name')) {
    function gdy_static_page_site_name(): string
    {
        if (function_exists('settings_get')) {
            $name = trim((string) settings_get('site_name', ''));
            if ($name !== '') {
                return $name;
            }
        }
        if (class_exists('HomeController') && method_exists('HomeController', 'getSiteSettings')) {
            try {
                $settings = HomeController::getSiteSettings();
                if (is_array($settings) && !empty($settings['site_name'])) {
                    return (string) $settings['site_name'];
                }
            } catch (Throwable $e) {
            }
        }
        return 'Godyar News Platform';
    }
}

if (!function_exists('gdy_static_page_url')) {
    function gdy_static_page_url(string $path = ''): string
    {
        $base = gdy_static_page_base_url();
        $path = '/' . ltrim($path, '/');
        return $base . $path;
    }
}

if (!function_exists('gdy_static_page_flash')) {
    function gdy_static_page_flash(): ?array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            if (function_exists('gdy_session_start')) {
                gdy_session_start();
            } else {
                @session_start();
            }
        }
        $flash = $_SESSION['contact_flash'] ?? null;
        unset($_SESSION['contact_flash']);
        return is_array($flash) ? $flash : null;
    }
}

if (!function_exists('gdy_static_page_defs')) {
    function gdy_static_page_defs(): array
    {
        return [
            'about' => [
                'title' => 'من نحن',
                'description' => 'تعرف على منصة Godyar ورسالتها ورؤيتها وخدماتها.',
                'eyebrow' => 'هوية الموقع',
                'intro' => 'منصة إخبارية مرنة تهدف إلى تقديم محتوى منظم وهوية بصرية موحدة وتجربة استخدام حديثة.',
                'sections' => [
                    ['title' => 'رؤيتنا', 'body' => 'نطوّر منصة تحرير ونشر تجمع بين السرعة والوضوح والمرونة، بحيث تبقى تجربة القارئ متسقة في جميع الصفحات.'],
                    ['title' => 'ما الذي يميزنا', 'body' => 'نعتمد على بنية قابلة للتوسع، وصفحات قانونية وتواصل بهوية موحدة، ومسارات واضحة، وتحسينات مستمرة في الأداء والمظهر.'],
                    ['title' => 'تجربة المستخدم', 'body' => 'نركز على تنظيم المحتوى، سهولة الوصول، وتحسين العرض على الجوال والحاسب مع الحفاظ على الطابع المهني للموقع.'],
                ],
                'cta' => ['label' => 'تواصل معنا', 'href' => '/contact'],
            ],
            'privacy' => [
                'title' => 'سياسة الخصوصية',
                'description' => 'تعرف على طريقة جمع البيانات واستخدامها وحمايتها داخل الموقع.',
                'eyebrow' => 'الخصوصية والثقة',
                'intro' => 'نلتزم بحماية خصوصية الزوار والمستخدمين، ونتعامل مع البيانات بما يخدم التشغيل والأمان وتحسين الخدمة.',
                'sections' => [
                    ['title' => 'البيانات التي قد نجمعها', 'body' => 'قد يتم تسجيل بيانات تقنية أساسية مثل عنوان IP، نوع المتصفح، ونمط التصفح لتحسين الحماية والأداء وتحليل الأعطال.'],
                    ['title' => 'طريقة استخدام البيانات', 'body' => 'تُستخدم البيانات لتحسين تجربة الاستخدام، قياس الأداء، منع إساءة الاستخدام، والاستجابة للطلبات الواردة من خلال النماذج والخدمات المرتبطة بالموقع.'],
                    ['title' => 'المشاركة والحماية', 'body' => 'لا يتم بيع البيانات الشخصية، ولا تتم مشاركتها إلا للضرورة التشغيلية أو عند وجود التزام قانوني، مع اتخاذ إجراءات مناسبة لحماية البيانات.'],
                ],
                'cta' => ['label' => 'الشروط والأحكام', 'href' => '/page/terms'],
            ],
            'terms' => [
                'title' => 'الشروط والأحكام',
                'description' => 'الشروط المنظمة لاستخدام الموقع والخدمات والمحتوى المنشور.',
                'eyebrow' => 'الاستخدام النظامي',
                'intro' => 'باستخدامك للموقع فإنك تقر بالالتزام بشروط الاستخدام العامة والسياسات المعمول بها داخل المنصة.',
                'sections' => [
                    ['title' => 'الاستخدام المقبول', 'body' => 'يجب استخدام الموقع والخدمات بطريقة مشروعة ومسؤولة، والامتناع عن أي سلوك يضر بالمنصة أو يسيء إلى المحتوى أو المستخدمين.'],
                    ['title' => 'الملكية الفكرية', 'body' => 'جميع المواد المنشورة تخضع للحقوق والسياسات الخاصة بالموقع أو بمالكيها الأصليين، ولا يجوز إعادة استخدامها بما يخالف الأنظمة أو التصاريح الممنوحة.'],
                    ['title' => 'التحديثات والمسؤولية', 'body' => 'يجوز تحديث الشروط أو تعديل بعض الميزات والخدمات عند الحاجة، ويعد استمرارك في استخدام الموقع بعد التحديث قبولاً بالتعديلات الجديدة.'],
                ],
                'cta' => ['label' => 'سياسة الخصوصية', 'href' => '/page/privacy'],
            ],
            'contact' => [
                'title' => 'اتصل بنا',
                'description' => 'أرسل رسالتك إلى فريق الموقع عبر نموذج التواصل الموحد.',
                'eyebrow' => 'قنوات التواصل',
                'intro' => 'يسعدنا استقبال رسائلك واستفساراتك وملاحظاتك. استخدم النموذج التالي وسنعاود التواصل معك في أقرب وقت ممكن.',
                'sections' => [
                    ['title' => 'الدعم والاستفسارات', 'body' => 'يمكنك استخدام صفحة التواصل للاستفسارات العامة، الملاحظات الفنية، واقتراحات التطوير المتعلقة بالموقع والخدمات المرتبطة به.'],
                    ['title' => 'سرعة الاستجابة', 'body' => 'يتم حفظ الرسائل بشكل منظم لتسهيل متابعتها والرد عليها، مع مراعاة التحقق من صحة البيانات المدخلة قبل الإرسال.'],
                    ['title' => 'الروابط المفيدة', 'body' => 'يمكنك الرجوع إلى صفحة من نحن أو صفحات الخصوصية والشروط للحصول على معلومات إضافية عن الموقع وسياساته.'],
                ],
                'cta' => ['label' => 'من نحن', 'href' => '/page/about'],
            ],
        ];
    }
}

if (!function_exists('gdy_static_page_inner')) {
    function gdy_static_page_inner(string $slug, bool $withForm = true): string
    {
        $defs = gdy_static_page_defs();
        $page = $defs[$slug] ?? $defs['about'];
        $siteName = gdy_static_page_site_name();
        $flash = $slug === 'contact' ? gdy_static_page_flash() : null;
        $action = gdy_static_page_url('/contact/send.php');
        ob_start();
        ?>
<section class="gdy-static-wrap">
  <div class="gdy-static-container">
    <div class="gdy-static-header">
      <div class="gdy-static-brand"><span class="gdy-static-mark">G</span><span><?= gdy_static_page_h($siteName) ?></span></div>
      <div class="gdy-static-badge"><?= gdy_static_page_h($page['title']) ?></div>
    </div>
    <div class="gdy-static-hero">
      <div class="gdy-static-panel">
        <span class="gdy-static-eyebrow"><?= gdy_static_page_h($page['eyebrow']) ?></span>
        <h1 class="gdy-static-title"><?= gdy_static_page_h($page['title']) ?></h1>
        <p class="gdy-static-lead"><?= gdy_static_page_h($page['intro']) ?></p>
      </div>
      <div class="gdy-static-side">
        <div class="gdy-static-links">
          <a class="gdy-static-link primary" href="<?= gdy_static_page_h(gdy_static_page_url($page['cta']['href'])) ?>"><?= gdy_static_page_h($page['cta']['label']) ?></a>
          <a class="gdy-static-link" href="<?= gdy_static_page_h(gdy_static_page_url('/page/about')) ?>">من نحن</a>
          <a class="gdy-static-link" href="<?= gdy_static_page_h(gdy_static_page_url('/page/privacy')) ?>">الخصوصية</a>
          <a class="gdy-static-link" href="<?= gdy_static_page_h(gdy_static_page_url('/page/terms')) ?>">الشروط</a>
          <a class="gdy-static-link" href="<?= gdy_static_page_h(gdy_static_page_url('/contact')) ?>">اتصل بنا</a>
        </div>
      </div>
    </div>
    <div class="gdy-static-grid">
      <?php foreach ($page['sections'] as $section): ?>
        <article class="gdy-static-card">
          <h2><?= gdy_static_page_h($section['title']) ?></h2>
          <p><?= gdy_static_page_h($section['body']) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
    <?php if ($slug === 'contact' && $withForm): ?>
      <?php if ($flash): ?>
        <div class="gdy-static-flash <?= gdy_static_page_h((string) ($flash['type'] ?? 'success')) ?>">
          <?= gdy_static_page_h((string) ($flash['message'] ?? '')) ?>
        </div>
      <?php endif; ?>
      <form class="gdy-static-form" method="post" action="<?= gdy_static_page_h($action) ?>">
        <?= function_exists('csrf_field') ? csrf_field() : '' ?>
        <input type="hidden" name="lang" value="ar">
        <div class="gdy-static-form-grid">
          <div class="gdy-static-field">
            <label for="contact_name">الاسم</label>
            <input id="contact_name" type="text" name="name" required>
          </div>
          <div class="gdy-static-field">
            <label for="contact_email">البريد الإلكتروني</label>
            <input id="contact_email" type="email" name="email" required>
          </div>
        </div>
        <div class="gdy-static-field">
          <label for="contact_subject">الموضوع</label>
          <input id="contact_subject" type="text" name="subject">
        </div>
        <div class="gdy-static-field">
          <label for="contact_message">الرسالة</label>
          <textarea id="contact_message" name="message" required></textarea>
        </div>
        <div>
          <button class="gdy-static-submit" type="submit">إرسال الرسالة</button>
        </div>
      </form>
    <?php endif; ?>
  </div>
</section>
<?php
        return (string) ob_get_clean();
    }
}

if (!function_exists('gdy_static_page_render')) {
    function gdy_static_page_render(string $slug): void
    {
        $defs = gdy_static_page_defs();
        $page = $defs[$slug] ?? $defs['about'];
        $siteName = gdy_static_page_site_name();
        $pageTitle = $page['title'] . ' | ' . $siteName;
        $pageDescription = $page['description'];
        $GLOBALS['siteTitle'] = $siteName;
        $GLOBALS['pageTitle'] = $pageTitle;
        $GLOBALS['siteDescription'] = $pageDescription;
        $GLOBALS['metaDescription'] = $pageDescription;
        $GLOBALS['pageCanonical'] = gdy_static_page_url($slug === 'contact' ? '/contact' : '/page/' . $slug);

        $headerIncluded = false;
        foreach ([
            __DIR__ . '/../frontend/templates/header.php',
            __DIR__ . '/../frontend/views/partials/header.php',
        ] as $headerFile) {
            if (is_file($headerFile)) {
                require $headerFile;
                $headerIncluded = true;
                break;
            }
        }

        if (!$headerIncluded) {
            echo '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0"><title>' . gdy_static_page_h($pageTitle) . '</title><meta name="description" content="' . gdy_static_page_h($pageDescription) . '"></head><body>';
        }

        echo gdy_static_page_inner($slug, true);

        $footerIncluded = false;
        foreach ([
            __DIR__ . '/../frontend/templates/footer.php',
            __DIR__ . '/../frontend/views/partials/footer.php',
        ] as $footerFile) {
            if (is_file($footerFile)) {
                require $footerFile;
                $footerIncluded = true;
                break;
            }
        }

        if (!$footerIncluded) {
            echo '</body></html>';
        }
    }
}
