<?php

namespace App\Features\Webmail\Services;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class WebmailArchiveSettings
{
    private const SENDER_KEY = 'webmail_zip_sender';

    private const PASSWORD_KEY = 'webmail_zip_password';

    /** @return array{sender: string, password: string} */
    public function formData(): array
    {
        return [
            'sender' => $this->sender() ?? '',
            'password' => '',
        ];
    }

    public function hasPassword(): bool
    {
        return $this->password() !== null;
    }

    public static function normalizeSenderPattern(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $pattern = strtolower(trim($value));

        if (! str_contains($pattern, '*') && filter_var($pattern, FILTER_VALIDATE_EMAIL) !== false) {
            return $pattern;
        }

        if (str_starts_with($pattern, '*@')) {
            $domain = substr($pattern, 2);

            if ($domain !== '' && ! str_contains($domain, '*')
                && filter_var('sender@'.$domain, FILTER_VALIDATE_EMAIL) !== false) {
                return '*@'.$domain;
            }
        }

        return null;
    }

    public function matchesSender(string $email): bool
    {
        $pattern = $this->sender();
        $email = strtolower(trim($email));

        if ($pattern === null || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        if (! str_starts_with($pattern, '*@')) {
            return hash_equals($pattern, $email);
        }

        return hash_equals(substr($pattern, 2), substr(strrchr($email, '@') ?: '', 1));
    }

    /** @return array{sender: string, password: string}|null */
    public function rule(): ?array
    {
        $sender = $this->sender();
        $password = $this->password();

        if ($sender === null || $password === null) {
            return null;
        }

        return ['sender' => $sender, 'password' => $password];
    }

    /** @param array{sender?: mixed, password?: mixed} $data */
    public function save(array $data): void
    {
        $rawSender = is_string($data['sender'] ?? null) ? trim($data['sender']) : '';
        $sender = self::normalizeSenderPattern($rawSender);
        $password = $data['password'] ?? null;

        if ($rawSender !== '' && $sender === null) {
            throw new InvalidArgumentException('Invalid webmail ZIP sender pattern.');
        }

        DB::transaction(function () use ($sender, $password): void {
            if ($sender === null) {
                DB::table('settings')->whereIn('setting_key', [self::SENDER_KEY, self::PASSWORD_KEY])->delete();

                return;
            }

            DB::table('settings')->updateOrInsert(
                ['setting_key' => self::SENDER_KEY],
                ['setting_value' => $sender],
            );

            if (is_string($password) && $password !== '') {
                DB::table('settings')->updateOrInsert(
                    ['setting_key' => self::PASSWORD_KEY],
                    ['setting_value' => Crypt::encryptString($password)],
                );
            }
        });
    }

    private function sender(): ?string
    {
        $sender = trim((string) DB::table('settings')
            ->where('setting_key', self::SENDER_KEY)
            ->value('setting_value'));

        return self::normalizeSenderPattern($sender);
    }

    private function password(): ?string
    {
        $encrypted = DB::table('settings')
            ->where('setting_key', self::PASSWORD_KEY)
            ->value('setting_value');

        if (! is_string($encrypted) || $encrypted === '') {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (DecryptException) {
            return null;
        }
    }
}
