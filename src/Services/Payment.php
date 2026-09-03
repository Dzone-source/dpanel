<?php

declare(strict_types=1);

namespace App\Services;

use Psr\Http\Message\ResponseInterface;
use function basename;
use function class_exists;
use function get_parent_class;
use function glob;

final class Payment
{
    public static function getAllPaymentMap(): array
    {
        $payments = [];
        $files = glob(__DIR__ . '/Gateway/*.php') ?: [];

        foreach ($files as $file) {
            $class_name = basename($file, '.php');
            if ($class_name === 'Base') {
                continue;
            }

            $class = '\\App\\Services\\Gateway\\' . $class_name;
            if (! class_exists($class)) {
                continue;
            }

            if (get_parent_class($class) === 'App\\Services\\Gateway\\Base') {
                $payments[] = $class;
            }
        }

        return $payments;
    }

    public static function getPaymentsEnabled(): array
    {
        return array_values(array_filter(Payment::getAllPaymentMap(), static function ($payment) {
            return $payment::_enable();
        }));
    }

    public static function getPaymentMap(): array
    {
        $result = [];

        foreach (self::getPaymentsEnabled() as $payment) {
            $result[$payment::_name()] = $payment;
        }

        return $result;
    }

    public static function getPaymentByName($name): ?string
    {
        $all = self::getPaymentMap();

        return $all[$name];
    }

    public static function notify($request, $response, $args): ResponseInterface
    {
        $payment = self::getPaymentByName($args['type']);

        if ($payment !== null) {
            $instance = new $payment();
            return $instance->notify($request, $response, $args);
        }

        return $response->withStatus(404);
    }

    public static function returnHTML($request, $response, $args): ResponseInterface
    {
        $payment = self::getPaymentByName($args['type']);

        if ($payment !== null) {
            $instance = new $payment();
            return $instance->getReturnHTML($request, $response, $args);
        }

        return $response->withStatus(404);
    }

    public static function purchase($request, $response, $args): ResponseInterface
    {
        $payment = self::getPaymentByName($args['type']);

        if ($payment !== null) {
            $instance = new $payment();
            return $instance->purchase($request, $response, $args);
        }

        return $response->withStatus(404);
    }
}
