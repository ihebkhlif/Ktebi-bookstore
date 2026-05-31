<?php

namespace App\Controller;

use App\Entity\Order;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/payment')]
class PaymentController extends AbstractController
{
    public function __construct(
        private string $stripeSecretKey,
        private EmailService $emailService
    ) {
        Stripe::setApiKey($this->stripeSecretKey);
    }

    #[Route('/create-checkout-session/{id}', name: 'app_payment_checkout')]
    public function createCheckoutSession(Order $order): Response
    {
        $lineItems = [];
        foreach ($order->getOrderItems() as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $item->getBook()->getTitle(),
                        'description' => sprintf('By %s', $item->getBook()->getAuthor()),
                    ],
                    'unit_amount' => $item->getPrice() * 100, // Convert to cents
                ],
                'quantity' => $item->getQuantity(),
            ];
        }

        $checkoutSession = Session::create([
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => $this->generateUrl('app_payment_success', ['id' => $order->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->generateUrl('app_payment_cancel', ['id' => $order->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        return $this->redirect($checkoutSession->url);
    }

    #[Route('/success/{id}', name: 'app_payment_success')]
    public function success(Order $order, EntityManagerInterface $entityManager): Response
    {
        $order->setStatus('paid');
        $entityManager->flush();

        // Send order confirmation email
        $this->emailService->sendOrderConfirmation($order);

        // Check stock levels and send alerts if necessary
        foreach ($order->getOrderItems() as $item) {
            $book = $item->getBook();
            if ($book->getStock() <= 5) {
                $this->emailService->sendLowStockAlert($book->getTitle(), $book->getStock());
            }
        }

        $this->addFlash('success', 'Payment successful! Your order has been confirmed.');

        return $this->redirectToRoute('app_orders');
    }

    #[Route('/cancel/{id}', name: 'app_payment_cancel')]
    public function cancel(Order $order): Response
    {
        $this->addFlash('warning', 'Payment cancelled. Please try again.');

        return $this->redirectToRoute('app_cart');
    }
} 