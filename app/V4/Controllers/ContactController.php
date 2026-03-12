<?php
declare(strict_types=1);
namespace GodyarV4\Controllers;

use GodyarV4\Bootstrap\Request;
use GodyarV4\Bootstrap\Response;

final class ContactController extends Controller
{
    public function index(Request $request, string $locale): Response
    {
        return $this->theme()->render('pages/contact', [
            'locale' => $locale,
            'flash' => $_SESSION['gdy_v4_contact_flash'] ?? null,
            'seo' => $this->seo()->defaults([
                'title' => 'Contact',
                'canonical' => godyar_v4_url($locale, 'contact'),
            ]),
        ]);
    }

    public function send(Request $request, string $locale): Response
    {
        $name = trim((string) $request->input('name', ''));
        $email = trim((string) $request->input('email', ''));
        $message = trim((string) $request->input('message', ''));
        $errors = [];
        if ($name === '') { $errors[] = 'الاسم مطلوب'; }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'البريد غير صالح'; }
        if ($message === '') { $errors[] = 'الرسالة مطلوبة'; }
        $_SESSION['gdy_v4_contact_flash'] = $errors ? ['ok' => false, 'messages' => $errors] : ['ok' => true, 'messages' => ['تم استلام الرسالة بنجاح']];
        return Response::redirect(godyar_v4_url($locale, 'contact'));
    }
}
