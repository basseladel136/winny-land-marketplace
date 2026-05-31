<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusUpdated extends Notification
{
    use Queueable;

    private const STATUS_LABELS = [
        'pending'    => 'Pending',
        'processing' => 'Processing',
        'shipped'    => 'Shipped',
        'delivered'  => 'Delivered',
        'cancelled'  => 'Cancelled',
    ];

    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order  = $this->order;
        $status = self::STATUS_LABELS[$order->status] ?? ucfirst($order->status);

        $mail = (new MailMessage)
            ->subject("Order Update – {$order->order_number}")
            ->greeting("Hello {$order->customer_name},")
            ->line("Your order status has been updated.")
            ->line("**Order Number:** {$order->order_number}")
            ->line("**New Status:** {$status}");

        if ($order->status === 'shipped') {
            $mail->line('Your order is on its way! You will receive it shortly.');
        } elseif ($order->status === 'delivered') {
            $mail->line('Your order has been delivered. We hope you enjoy your purchase!');
        } elseif ($order->status === 'cancelled') {
            $mail->line('Your order has been cancelled. If you have any questions, please contact us.');
        }

        return $mail
            ->action('View Order', url("/orders/{$order->order_number}"))
            ->line('Thank you for shopping with Winny Land!');
    }
}
