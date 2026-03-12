<?php
declare(strict_types=1);
namespace GodyarV4\Services;

use GodyarV4\Repositories\ContactMessageRepository;

final class ContactService
{
    public function __construct(private readonly ContactMessageRepository $repository) {}

    public function submit(array $payload): array
    {
        $errors = [];
        $name = trim((string)($payload['name'] ?? ''));
        $email = trim((string)($payload['email'] ?? ''));
        $subject = trim((string)($payload['subject'] ?? ''));
        $message = trim((string)($payload['message'] ?? ''));
        $honeypot = trim((string)($payload['website'] ?? ''));

        if ($honeypot !== '') {
            $errors[] = 'تم رفض الطلب.';
        }
        if ($name === '') {
            $errors[] = 'الاسم مطلوب';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'البريد غير صالح';
        }
        if ($message === '' || mb_strlen($message) < 10) {
            $errors[] = 'الرسالة قصيرة جدًا';
        }
        if (mb_strlen($subject) > 190) {
            $errors[] = 'العنوان طويل جدًا';
        }

        if ($errors) {
            return ['ok' => false, 'messages' => $errors];
        }

        $saved = $this->repository->save([
            'name' => $name,
            'email' => $email,
            'subject' => $subject,
            'message' => $message,
            'locale' => (string)($payload['locale'] ?? 'ar'),
            'ip_address' => (string)($payload['ip_address'] ?? ''),
            'user_agent' => (string)($payload['user_agent'] ?? ''),
            'source_url' => (string)($payload['source_url'] ?? ''),
        ]);

        return [
            'ok' => $saved,
            'messages' => [$saved ? 'تم استلام الرسالة وحفظها بنجاح' : 'تم استلام الرسالة، لكن الحفظ تم عبر fallback آمن'],
        ];
    }

    public function latest(int $limit = 50): array
    {
        return $this->repository->latest($limit);
    }
}
