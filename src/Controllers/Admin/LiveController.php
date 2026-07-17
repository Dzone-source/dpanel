<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\User;
use App\Utils\Tools;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

/**
 * Lightweight polling endpoint for admin live status updates.
 */
final class LiveController extends BaseController
{
    public function status(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $latestUser = (new User())->orderBy('id', 'desc')->first(['id', 'email', 'user_name', 'reg_date']);

        $ticketCount = (new Ticket())->where('status', 'open_wait_admin')->count();
        $latestTicket = (new Ticket())->where('status', 'open_wait_admin')
            ->orderBy('id', 'desc')
            ->first(['id', 'title', 'type', 'datetime', 'userid']);

        $orderCount = (new Order())->whereIn('status', ['pending_payment', 'pending_activation'])->count();
        $latestOrder = (new Order())->whereIn('status', ['pending_payment', 'pending_activation'])
            ->orderBy('id', 'desc')
            ->first(['id', 'status', 'product_name', 'user_id', 'update_time']);

        $invoiceCount = (new Invoice())->whereIn('status', ['unpaid', 'partially_paid'])->count();
        $latestInvoice = (new Invoice())->whereIn('status', ['unpaid', 'partially_paid'])
            ->orderBy('update_time', 'desc')
            ->first(['id', 'status', 'price', 'user_id', 'update_time']);

        $latestUserId = (int) ($latestUser->id ?? 0);
        $latestTicketId = (int) ($latestTicket->id ?? 0);
        $latestOrderId = (int) ($latestOrder->id ?? 0);
        $latestInvoiceId = (int) ($latestInvoice->id ?? 0);
        $latestInvoiceUpdate = (int) ($latestInvoice->update_time ?? 0);

        $fingerprint = implode(':', [
            $latestUserId,
            $latestTicketId,
            $ticketCount,
            $latestOrderId,
            $orderCount,
            $latestInvoiceId,
            $latestInvoiceUpdate,
            $invoiceCount,
        ]);

        return $response->withJson([
            'ret' => 1,
            'fingerprint' => $fingerprint,
            'server_time' => time(),
            'users' => [
                'latest_id' => $latestUserId,
                'latest_email' => (string) ($latestUser->email ?? ''),
                'latest_name' => (string) ($latestUser->user_name ?? ''),
                'latest_time' => $latestUser !== null ? (string) $latestUser->reg_date : '',
            ],
            'tickets' => [
                'wait_admin' => $ticketCount,
                'latest_id' => $latestTicketId,
                'latest_title' => (string) ($latestTicket->title ?? ''),
                'latest_type' => (string) ($latestTicket->type ?? ''),
                'latest_time' => $latestTicket !== null ? Tools::toDateTime((int) $latestTicket->datetime) : '',
                'url' => $latestTicketId > 0 ? '/admin/ticket/' . $latestTicketId . '/view' : '/admin/ticket',
            ],
            'orders' => [
                'pending' => $orderCount,
                'latest_id' => $latestOrderId,
                'latest_name' => (string) ($latestOrder->product_name ?? ''),
                'latest_status' => (string) ($latestOrder->status ?? ''),
                'url' => $latestOrderId > 0 ? '/admin/order/' . $latestOrderId . '/view' : '/admin/order',
            ],
            'invoices' => [
                'open' => $invoiceCount,
                'latest_id' => $latestInvoiceId,
                'latest_status' => (string) ($latestInvoice->status ?? ''),
                'latest_price' => $latestInvoice !== null ? Tools::formatVnd((float) $latestInvoice->price, 0) : '',
                'url' => $latestInvoiceId > 0 ? '/admin/invoice/' . $latestInvoiceId . '/view' : '/admin/invoice',
            ],
        ]);
    }
}
