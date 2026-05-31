<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderPlaced extends Notification
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order;

        return (new MailMessage)
            ->subject("Order Confirmed – {$order->order_number}")
            ->greeting("Hello {$order->customer_name},")
            ->line("Thank you for your order! We have received it and it is now being processed.")
            ->line("**Order Number:** {$order->order_number}")
            ->line("**Total:** EGP " . number_format($order->total, 2))
            ->line("**Payment Method:** " . strtoupper($order->payment_method))
            ->line("**Shipping Address:** {$order->shipping_address}")
            ->action('View Order', url("/orders/{$order->order_number}"))
            ->line('Thank you for shopping with Winny Land!');
    }
}
