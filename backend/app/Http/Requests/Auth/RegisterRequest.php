<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => [
                'required',
                'email:rfc,dns',   // RFC + DNS check — rejects non-existent domains
                'max:255',
                'unique:users,email',
                function ($attribute, $value, $fail) {
                    $this->blockDisposableEmail($value, $fail);
                },
            ],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->uncompromised(),   // checks HaveIBeenPwned
            ],
            'phone'    => ['nullable', 'string', 'max:20'],
            'locale'   => ['nullable', 'string', 'in:en,ar'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.min'           => 'Password must be at least 8 characters.',
            'password.letters'       => 'Password must contain at least one letter.',
            'password.mixed_case'    => 'Password must contain uppercase and lowercase letters.',
            'password.numbers'       => 'Password must contain at least one number.',
            'password.uncompromised' => 'This password has appeared in a data breach. Please choose a different one.',
        ];
    }

    // ── Disposable / throwaway email domain block ─────────────────────────────

    private function blockDisposableEmail(string $email, callable $fail): void
    {
        $domain = strtolower(substr(strrchr($email, '@'), 1));

        $blocked = [
            // Common disposable/temp email providers
            'mailinator.com', 'guerrillamail.com', 'guerrillamail.net',
            'guerrillamail.de', 'guerrillamail.info', 'guerrillamail.org',
            'yopmail.com', 'yopmail.fr', 'sharklasers.com', 'guerrillamailblock.com',
            'grr.la', 'guerrillamail.biz', 'spam4.me', 'trashmail.com',
            'trashmail.me', 'trashmail.net', 'trashmail.at', 'trashmail.io',
            'tempmail.com', 'temp-mail.org', 'throwam.com', 'throwam.io',
            'fakeinbox.com', 'maildrop.cc', 'mailnull.com', 'spamgourmet.com',
            'spamgourmet.net', 'spamgourmet.org', 'dispostable.com',
            'mintemail.com', 'spambox.us', 'spambox.info', 'spambox.me',
            'getonemail.net', 'mailseal.de', 'spamevader.net', 'inoutmail.de',
            '0-mail.com', '0815.ru', '0815.su', '0clickemail.com',
            '10minutemail.com', '10minutemail.net', '10minutemail.org',
            '20minutemail.com', 'filzmail.com', 'gawab.com',
            'privacy.net', 'spamfree24.org', 'discardmail.com',
            'binkmail.com', 'bobmail.info', 'dayrep.com', 'deadaddress.com',
            'jetable.fr.nf', 'kasmail.com', 'kurzepost.de', 'lifebyfood.com',
            'linktrackr.com', 'mailme.lv', 'mindless.com', 'mt2015.com',
            'mt2016.com', 'mytrashmail.com', 'nobulk.com', 'nospamfor.us',
            'objectmail.com', 'obobbo.com', 'odaymail.com', 'owlpic.com',
            'pecinan.com', 'pecinan.net', 'pecinan.org',
            'qq.com',   // often abused — remove if you want to allow QQ
            'recursor.net', 'redchan.it', 'safetymail.info', 'sendspamhere.com',
            'sharklasers.com', 'shieldedmail.com', 'spamavert.com',
            'spamcorners.com', 'spameater.org', 'spamfree.eu', 'spamgob.com',
            'spamherelots.com', 'spamhereplease.com', 'spamoff.de',
            'spamslicer.com', 'spamspot.com', 'spamthis.co.uk',
            'suremail.info', 'sweetxxx.de', 'teleworm.us',
            'trbvm.com', 'trym.com', 'uggsrock.com', 'uroid.com',
            'veryrealemail.com', 'viditag.com', 'webuser.in', 'wetrainbayarea.com',
            'wh4f.org', 'whyspam.me', 'willhackforfood.biz', 'wuzupmail.net',
            'xn--9kq967cs3f.com', 'xn--ixa.com', 'xyz.am',
            'yepmail.net', 'zehnminuten.de', 'zehnminutenmail.de',
        ];

        if (in_array($domain, $blocked, true)) {
            $fail('Disposable or temporary email addresses are not allowed.');
        }
    }
}
