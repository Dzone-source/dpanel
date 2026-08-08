<?php

declare(strict_types=1);

use App\Models\Product;

describe('Product::resolveCarriersLabel', function () {
    it('parses SoftBank / LINEMO / Ymobile from product name', function () {
        expect(Product::resolveCarriersLabel('LINEMO/SOFBANK/YMOBILE', (object) []))
            ->toBe('SoftBank · LINEMO · Y!mobile');
    });

    it('prefers explicit carriers from content', function () {
        expect(Product::resolveCarriersLabel('LINEMO', (object) ['carriers' => 'au · docomo']))
            ->toBe('au · docomo');
    });

    it('maps Vietnam VPN style names', function () {
        expect(Product::resolveCarriersLabel('VIET NAM VPN', (object) []))
            ->toBe('VPN Việt Nam');
    });
});

describe('Product::buildShopHighlights', function () {
    it('lists unlock speed, device limit, carriers and bandwidth', function () {
        $content = (object) [
            'bandwidth' => 1000,
            'speed_limit' => 1000,
            'ip_limit' => 2,
            'class_time' => 30,
        ];

        $features = Product::buildShopHighlights('LINEMO/SOFTBANK/YMOBILE', $content, 'tabp', true);
        $labels = array_column($features, 'label');

        expect($labels)->toContain('Hỗ trợ nhà mạng')
            ->and($labels)->toContain('Mở khóa tốc độ cao')
            ->and($labels)->toContain('Tốc độ')
            ->and($labels)->toContain('Giới hạn thiết bị')
            ->and($labels)->toContain('Lưu lượng')
            ->and($labels)->toContain('Thời hạn');

        $byLabel = [];
        foreach ($features as $feature) {
            $byLabel[$feature['label']] = $feature['value'];
        }

        expect($byLabel['Hỗ trợ nhà mạng'])->toBe('SoftBank · LINEMO · Y!mobile')
            ->and($byLabel['Mở khóa tốc độ cao'])->toBe('Đến 1000 Mbps')
            ->and($byLabel['Tốc độ'])->toBe('1000 Mbps')
            ->and($byLabel['Giới hạn thiết bị'])->toBe('2 thiết bị đồng thời')
            ->and($byLabel['Lưu lượng'])->toBe('1000 GB')
            ->and($byLabel['Thời hạn'])->toBe('Tùy chọn khi mua');
    });

    it('shows unlimited speed unlock copy when speed_limit is 0', function () {
        $features = Product::buildShopHighlights(
            'VIET NAM VPN',
            (object) ['speed_limit' => 0, 'ip_limit' => 0, 'bandwidth' => 1000],
            'tabp',
            false
        );
        $byLabel = [];
        foreach ($features as $feature) {
            $byLabel[$feature['label']] = $feature['value'];
        }

        expect($byLabel['Hỗ trợ nhà mạng'])->toBe('VPN Việt Nam')
            ->and($byLabel['Mở khóa tốc độ cao'])->toBe('Không giới hạn')
            ->and($byLabel['Giới hạn thiết bị'])->toBe('Không giới hạn');
    });
});
