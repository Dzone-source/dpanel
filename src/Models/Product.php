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
        return $this->status ? '正常' : '下架';
    }

    /**
     * 商品类型
     */
    public function type(): string
    {
        return match ($this->type) {
            'tabp' => '时间流量包',
            'time' => '时间包',
            'bandwidth' => '流量包',
            default => '其他',
        };
    }

    /**
     * 商品库存
     */
    public function stock(): string|int
    {
        return $this->stock < 0 ? '无限制' : $this->stock;
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
     * Parse options JSON from admin form into a validated list.
     *
     * @return list<array{days: int, price: float, label: string}>|null null on invalid JSON
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
