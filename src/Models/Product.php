<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Query\Builder;

/**
 * @property int    $id          商品ID
 * @property string $type        类型
 * @property string $name        名称
 * @property float  $price       售价
 * @property string $content     内容
 * @property string $limit       购买限制
 * @property int    $status      销售状态
 * @property int    $create_time 创建时间
 * @property int    $update_time 更新时间
 * @property int    $sale_count  累计销量
 * @property int    $stock       库存
 *
 * @mixin Builder
 */
final class Product extends Model
{
    protected $connection = 'default';
    protected $table = 'product';

    /**
     * 商品状态
     */
    public function status(): string
    {
        return $this->status ? 'Bình thường' : 'Ngừng bán';
    }

    /**
     * 商品类型
     */
    public function type(): string
    {
        return match ($this->type) {
            'tabp' => 'Gói thời gian + lưu lượng',
            'time' => 'Gói thời gian',
            'bandwidth' => 'Gói lưu lượng',
            default => 'Khác',
        };
    }

    /**
     * 商品库存
     */
    public function stock(): string|int
    {
        return $this->stock < 0 ? 'Không giới hạn' : $this->stock;
    }

    /**
     * Normalize duration/price options from product content JSON.
     *
     * @return list<array{days: int, price: float, label: string}>
     */
    public static function normalizeOptions(mixed $content): array
    {
        if (\is_string($content)) {
            $content = json_decode($content);
        }

        if (\is_array($content)) {
            $content = (object) $content;
        }

        if (! \is_object($content) || ! isset($content->options) || ! \is_array($content->options)) {
            return [];
        }

        $options = [];

        foreach ($content->options as $option) {
            if (\is_array($option)) {
                $option = (object) $option;
            }

            if (! \is_object($option)) {
                continue;
            }

            $days = (int) ($option->days ?? 0);
            $price = (float) ($option->price ?? -1);

            if ($days <= 0 || $price < 0) {
                continue;
            }

            $label = trim((string) ($option->label ?? ''));
            if ($label === '') {
                $label = $days . ' ngày';
            }

            $options[] = [
                'days' => $days,
                'price' => $price,
                'label' => $label,
            ];
        }

        return $options;
    }

    /**
     * Parse duration/price options from either JSON string or parallel arrays.
     *
     * @return list<array{days: int, price: float, label: string}>|null
     */
    public static function parseOptionsPayload(mixed $raw): ?array
    {
        if ($raw === null || $raw === '') {
            return [];
        }

        if (\is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (! \is_array($decoded)) {
                return null;
            }
        } elseif (\is_array($raw)) {
            $decoded = $raw;
        } else {
            return null;
        }

        $options = [];

        foreach ($decoded as $row) {
            if (! \is_array($row)) {
                continue;
            }

            $days = (int) ($row['days'] ?? 0);
            $price = (float) ($row['price'] ?? -1);
            $label = trim((string) ($row['label'] ?? ''));

            if ($days <= 0 || $price < 0) {
                continue;
            }

            if ($label === '') {
                $label = $days . ' ngày';
            }

            $options[] = [
                'days' => $days,
                'price' => $price,
                'label' => $label,
            ];
        }

        return $options;
    }

    /**
     * Build options list from parallel form arrays (option_days/prices/labels).
     *
     * @return list<array{days: int, price: float, label: string}>
     */
    public static function parseOptionsFromArrays(mixed $days, mixed $prices, mixed $labels): array
    {
        if (! \is_array($days)) {
            $days = $days === null || $days === '' ? [] : [$days];
        }
        if (! \is_array($prices)) {
            $prices = $prices === null || $prices === '' ? [] : [$prices];
        }
        if (! \is_array($labels)) {
            $labels = $labels === null || $labels === '' ? [] : [$labels];
        }

        $count = max(\count($days), \count($prices), \count($labels));
        $options = [];

        for ($i = 0; $i < $count; $i++) {
            $d = (int) ($days[$i] ?? 0);
            $p = (float) ($prices[$i] ?? -1);
            $l = trim((string) ($labels[$i] ?? ''));

            if ($d <= 0 || $p < 0) {
                continue;
            }

            if ($l === '') {
                $l = $d . ' ngày';
            }

            $options[] = [
                'days' => $d,
                'price' => $p,
                'label' => $l,
            ];
        }

        return $options;
    }

    /**
     * Build shop-card highlight rows (icon/label/value) for user product listing.
     *
     * @return list<array{icon: string, label: string, value: string}>
     */
    public static function buildShopHighlights(string $name, mixed $content, string $type, bool $hasOptions = false): array
    {
        if (\is_string($content)) {
            $content = json_decode($content);
        }
        if (\is_array($content)) {
            $content = (object) $content;
        }
        if (! \is_object($content)) {
            $content = (object) [];
        }

        $features = [];
        $carriers = self::resolveCarriersLabel($name, $content);
        if ($carriers !== '') {
            $features[] = [
                'icon' => 'ti-antenna-bars-5',
                'label' => 'Hỗ trợ nhà mạng',
                'value' => $carriers,
            ];
        }

        if ($type !== 'bandwidth') {
            $speedLimit = isset($content->speed_limit) ? (string) $content->speed_limit : '';
            if ($speedLimit !== '') {
                $unlimitedSpeed = $speedLimit === '0';
                $features[] = [
                    'icon' => 'ti-rocket',
                    'label' => 'Mở khóa tốc độ cao',
                    'value' => $unlimitedSpeed ? 'Không giới hạn' : ('Đến ' . $speedLimit . ' Mbps'),
                ];
                $features[] = [
                    'icon' => 'ti-bolt',
                    'label' => 'Tốc độ',
                    'value' => $unlimitedSpeed ? 'Không giới hạn' : ($speedLimit . ' Mbps'),
                ];
            }

            $ipLimit = isset($content->ip_limit) ? (string) $content->ip_limit : '';
            if ($ipLimit !== '') {
                $features[] = [
                    'icon' => 'ti-devices',
                    'label' => 'Giới hạn thiết bị',
                    'value' => $ipLimit === '0'
                        ? 'Không giới hạn'
                        : ($ipLimit . ' thiết bị đồng thời'),
                ];
            }
        }

        if (($type === 'tabp' || $type === 'bandwidth') && isset($content->bandwidth) && $content->bandwidth !== '' && $content->bandwidth !== null) {
            $features[] = [
                'icon' => 'ti-database',
                'label' => 'Lưu lượng',
                'value' => $content->bandwidth . ' GB',
            ];
        }

        if ($type === 'tabp' || $type === 'time') {
            if ($hasOptions) {
                $features[] = [
                    'icon' => 'ti-calendar',
                    'label' => 'Thời hạn',
                    'value' => 'Tùy chọn khi mua',
                ];
            } elseif (isset($content->class_time) && $content->class_time !== '' && $content->class_time !== null) {
                $features[] = [
                    'icon' => 'ti-calendar',
                    'label' => 'Thời hạn',
                    'value' => $content->class_time . ' ngày',
                ];
            }
        }

        return $features;
    }

    /**
     * Resolve carrier / network support text for shop descriptions.
     */
    public static function resolveCarriersLabel(string $name, mixed $content): string
    {
        if (\is_string($content)) {
            $content = json_decode($content);
        }
        if (\is_array($content)) {
            $content = (object) $content;
        }

        if (\is_object($content)) {
            $explicit = trim((string) ($content->carriers ?? ''));
            if ($explicit !== '') {
                return $explicit;
            }
        }

        $upper = mb_strtoupper($name);
        $found = [];
        $aliases = [
            'SOFTBANK' => 'SoftBank',
            'SOFBANK' => 'SoftBank',
            'LINEMO' => 'LINEMO',
            'Y!MOBILE' => 'Y!mobile',
            'YMOBILE' => 'Y!mobile',
            'Y-MOBILE' => 'Y!mobile',
            'AU' => 'au',
            'DOCOMO' => 'docomo',
            'RAKUTEN' => 'Rakuten',
        ];

        foreach ($aliases as $needle => $label) {
            if (str_contains($upper, $needle) && ! \in_array($label, $found, true)) {
                $found[] = $label;
            }
        }

        if ($found !== []) {
            return implode(' · ', $found);
        }

        if (str_contains($upper, 'VIET') || str_contains($upper, 'VIỆT') || str_contains($upper, 'VN ')) {
            return 'VPN Việt Nam';
        }

        return '';
    }

    /**
     * Optional short product summary stored in content JSON.
     */
    public static function resolveSummary(mixed $content): string
    {
        if (\is_string($content)) {
            $content = json_decode($content);
        }
        if (\is_array($content)) {
            $content = (object) $content;
        }
        if (! \is_object($content)) {
            return '';
        }

        return trim((string) ($content->summary ?? $content->description ?? ''));
    }

    /**
     * Resolve purchase price + flat content snapshot for a selected option index.
     *
     * @return array{price: float, content: array<string, mixed>, option: ?array{days: int, price: float, label: string}}|null
     */
    public function resolvePurchaseOption(?int $optionIndex): ?array
    {
        $base = json_decode($this->content, true);
        if (! \is_array($base)) {
            $base = [];
        }

        $options = self::normalizeOptions($this->content);

        if ($options === []) {
            return [
                'price' => (float) $this->price,
                'content' => $base,
                'option' => null,
            ];
        }

        if ($optionIndex === null) {
            $optionIndex = 0;
        }

        if (! isset($options[$optionIndex])) {
            return null;
        }

        $option = $options[$optionIndex];
        $content = $base;
        unset($content['options']);

        if ($this->type === 'tabp' || $this->type === 'time') {
            $content['time'] = $option['days'];
            $content['class_time'] = $option['days'];
        }

        $content['option_label'] = $option['label'];
        $content['option_days'] = $option['days'];

        return [
            'price' => (float) $option['price'],
            'content' => $content,
            'option' => $option,
        ];
    }
}
