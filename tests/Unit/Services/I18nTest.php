<?php

/**
 * I18n Service tests using Pest
 */

use App\Services\I18n;
use Symfony\Component\Translation\Translator;

require_once __DIR__ . '/../../../app/predefine.php';

describe('I18n::trans', function () {
    it('returns existing translation for valid key and locale', function () {
        $key = 'lang_name';
        $lang = 'vi_VN';
        $expectedTranslation = 'Tiếng Việt';

        $translation = I18n::trans($key, $lang);

        expect($translation)->toBe($expectedTranslation);
    });

    it('returns key when translation does not exist', function () {
        $key = 'non_existent_key';
        $lang = 'vi_VN';

        $translation = I18n::trans($key, $lang);

        expect($translation)->toBe($key);
    });

    it('falls back to Vietnamese for unsupported locale', function () {
        $translation = I18n::trans('lang_name', 'en_US');

        expect($translation)->toBe('Tiếng Việt');
    });
});

describe('I18n::getLocaleList', function () {
    it('returns only Vietnamese locale', function () {
        $locales = I18n::getLocaleList();

        expect($locales)->toBe(['vi_VN']);
    });
});

describe('I18n::getTranslator', function () {
    it('returns translator instance with correct locale', function () {
        $lang = 'vi_VN';

        $translator = I18n::getTranslator($lang);

        expect($translator)
            ->toBeInstanceOf(Translator::class)
            ->and($translator->getLocale())->toBe($lang);
    });
});
