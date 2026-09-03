<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use function file_get_contents;
use function is_array;
use function is_readable;
use function json_decode;
use function json_encode;
use const BASE_PATH;

/**
 * @property int    $id
 * @property string $item
 * @property string $value
 * @property string $class
 * @property string $is_public
 * @property string $type
 * @property string $default
 * @property string $mark
 *
 * @mixin Builder
 */
final class Config extends Model
{
    protected $connection = 'default';
    protected $table = 'config';

    public static function obtain($item): bool|int|array|string
    {
        $config = (new Config())->where('item', $item)->first();

        if ($config === null) {
            return '';
        }

        return match ($config->type) {
            'bool' => (bool) $config->value,
            'int' => (int) $config->value,
            'array' => json_decode((string) $config->value, true) ?? [],
            default => (string) $config->value,
        };
    }

    public static function getClass($class): array
    {
        $configs = [];
        $all_configs = (new Config())->where('class', $class)->get();

        foreach ($all_configs as $config) {
            $configs[$config->item] = match ($config->type) {
                'bool' => (bool) $config->value,
                'int' => (int) $config->value,
                'array' => json_decode((string) $config->value, true) ?? [],
                default => (string) $config->value,
            };
        }

        return $configs;
    }

    public static function getItemListByClass($class): array
    {
        $items = [];
        $all_configs = (new Config())->where('class', $class)->get();

        foreach ($all_configs as $config) {
            $items[] = $config->item;
        }

        return $items;
    }

    public static function getPublicConfig(): array
    {
        $configs = [];
        $all_configs = (new Config())->where('is_public', 1)->get();

        foreach ($all_configs as $config) {
            $configs[$config->item] = match ($config->type) {
                'bool' => (bool) $config->value,
                'int' => (int) $config->value,
                'array' => json_decode((string) $config->value, true) ?? [],
                default => (string) $config->value,
            };
        }

        // Defaults so auth/register pages never hit undefined array keys
        $defaults = [
            'reg_mode' => 'open',
            'reg_email_verify' => false,
            'enable_reg_captcha' => false,
            'enable_login_captcha' => false,
            'enable_checkin_captcha' => false,
            'display_docs' => false,
            'display_docs_only_for_paid_user' => false,
            'enable_ticket' => false,
            'display_detect_log' => false,
            'live_chat' => '',
            'manual_qr_bank_bin' => '',
            'manual_qr_bank_name' => '',
            'manual_qr_account_number' => '',
            'manual_qr_account_name' => '',
            'manual_qr_image_url' => '',
        ];

        foreach ($defaults as $key => $value) {
            if (! array_key_exists($key, $configs)) {
                $configs[$key] = $value;
            }
        }

        return $configs;
    }

    public static function set(string $item, mixed $value): bool
    {
        $value = is_array($value) ? json_encode($value) : $value;

        try {
            $config = (new Config())->where('item', $item)->first();
            if ($config === null) {
                return false;
            }

            $config->value = $value;
            $config->save();
        } catch (QueryException $e) {
            return false;
        }

        return true;
    }

    /**
     * Insert missing settings defined in config/settings.json.
     *
     * @return int number of newly inserted rows
     */
    public static function importMissingFromFile(?string $path = null): int
    {
        $path ??= BASE_PATH . '/config/settings.json';
        if (! is_readable($path)) {
            return 0;
        }

        $settings = json_decode((string) file_get_contents($path), true);
        if (! is_array($settings)) {
            return 0;
        }

        $added = 0;

        foreach ($settings as $item) {
            if (! is_array($item) || ! isset($item['item'])) {
                continue;
            }

            $exists = (new Config())->where('item', $item['item'])->first();
            if ($exists !== null) {
                continue;
            }

            $new_item = new Config();
            $new_item->item = (string) $item['item'];
            $new_item->value = (string) ($item['value'] ?? '');
            $new_item->class = (string) ($item['class'] ?? '');
            $new_item->is_public = (string) ($item['is_public'] ?? '0');
            $new_item->type = (string) ($item['type'] ?? 'string');
            $new_item->default = (string) ($item['default'] ?? '');
            $new_item->mark = (string) ($item['mark'] ?? '');
            $new_item->save();
            $added++;
        }

        return $added;
    }
}
