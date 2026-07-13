<?php

declare(strict_types=1);

namespace App\Services;

use Symfony\Component\Translation\Loader\PhpFileLoader;
use Symfony\Component\Translation\Translator;
use const BASE_PATH;

final class I18n
{
    public const DEFAULT_LOCALE = 'vi_VN';

    public static function trans(string $key, ?string $lang = null): string
    {
        return self::getTranslator($lang)->trans($key);
    }

    public static function getLocaleList(): array
    {
        return [self::DEFAULT_LOCALE];
    }

    public static function getTranslator(?string $lang = null): Translator
    {
        $locale = self::DEFAULT_LOCALE;
        $localeFile = BASE_PATH . '/resources/locale/' . $locale . '.php';

        $translator = new Translator($locale);
        $translator->addLoader('php', new PhpFileLoader());
        $translator->addResource('php', $localeFile, $locale);

        return $translator;
    }
}
