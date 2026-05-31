<?php

namespace App\Service;

use App\Entity\Order;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class StripeService
{
    public function __construct(
        private ParameterBagInterface $params,
        private UrlGeneratorInterface $urlGenerator
    ) {
        Stripe::setApiKey($this->params->get('stripe_secret_key'));
    }

    public function createCheckoutSession(Order $order): Session
    {
        $lineItems = [];
        foreach ($order->getOrderItems() as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $item->getBook()->getTitle(),
                        'description' => sprintf('Author: %s', $item->getBook()->getAuthor()),
                    ],
                    'unit_amount' => (int)($item->getPrice() * 100), // Convert to cents
                ],
                'quantity' => $item->getQuantity(),
            ];
        }

        return Session::create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => $this->urlGenerator->generate('app_checkout_success', [
                'orderId' => $order->getId()
            ], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->urlGenerator->generate('app_checkout_cancel', [
                'orderId' => $order->getId()
            ], UrlGeneratorInterface::ABSOLUTE_URL),
            'metadata' => [
                'order_id' => $order->getId()
            ],
            'client_reference_id' => (string)$order->getId(),
            'customer_email' => $order->getUser()->getEmail(),
        ]);
    }
} 