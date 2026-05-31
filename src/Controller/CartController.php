<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\CartService;

#[Route('/cart')]
class CartController extends AbstractController
{
    #[Route('', name: 'app_cart')]
    public function index(CartService $cartService): Response
    {
        return $this->render('cart/index.html.twig', [
            'cart' => $cartService->getCart(),
            'total' => $cartService->getTotal(),
        ]);
    }

    #[Route('/add/{slug}', name: 'app_cart_add', methods: ['POST'])]
    public function add(Book $book, CartService $cartService): Response
    {
        $cartService->add($book);

        $this->addFlash('success', 'Book added to cart successfully!');

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/remove/{slug}', name: 'app_cart_remove')]
    public function remove(Book $book, CartService $cartService): Response
    {
        $cartService->remove($book);

        $this->addFlash('success', 'Book removed from cart successfully!');

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/checkout', name: 'app_cart_checkout')]
    #[IsGranted('ROLE_USER')]
    public function checkout(
        CartService $cartService,
        EntityManagerInterface $entityManager,
        StripeService $stripeService
    ): Response {
        $cart = $cartService->getCart();

        if (empty($cart)) {
            $this->addFlash('warning', 'Your cart is empty');
            return $this->redirectToRoute('app_cart');
        }

        // Pre-load books with their categories
        $bookIds = array_map(fn($item) => $item['book']->getId(), $cart);
        $books = $entityManager->getRepository(Book::class)
            ->createQueryBuilder('b')
            ->select('b', 'c')
            ->leftJoin('b.category', 'c')
            ->where('b.id IN (:ids)')
            ->setParameter('ids', $bookIds)
            ->getQuery()
            ->getResult();

        // Create a lookup array for quick access
        $booksById = [];
        foreach ($books as $book) {
            $booksById[$book->getId()] = $book;
        }

        $order = new Order();
        $order->setUser($this->getUser());
        $order->setStatus('pending');
        $totalPrice = 0;

        foreach ($cart as $item) {
            $book = $booksById[$item['book']->getId()];
            $orderItem = new OrderItem();
            $orderItem->setBook($book);
            $orderItem->setQuantity($item['quantity']);
            $orderItem->setPrice($book->getPrice());
            
            $order->addOrderItem($orderItem);
            $totalPrice += $book->getPrice() * $item['quantity'];
        }

        $order->setTotalPrice($totalPrice);

        $entityManager->persist($order);
        $entityManager->flush();

        // Create Stripe checkout session
        $checkoutSession = $stripeService->createCheckoutSession($order);

        // Store the order ID in the session
        $cartService->clear();

        // Redirect to Stripe Checkout
        return $this->redirect($checkoutSession->url);
    }

    #[Route('/checkout/success', name: 'app_checkout_success')]
    #[IsGranted('ROLE_USER')]
    public function checkoutSuccess(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $orderId = $request->query->get('orderId');
        if ($orderId) {
            $order = $entityManager->getRepository(Order::class)->find($orderId);
            if ($order) {
                $order->setStatus('completed');
                $entityManager->flush();
            }
        }

        $this->addFlash('success', 'Your order has been placed and paid successfully!');

        return $this->redirectToRoute('app_orders');
    }

    #[Route('/checkout/cancel', name: 'app_checkout_cancel')]
    #[IsGranted('ROLE_USER')]
    public function checkoutCancel(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $orderId = $request->query->get('orderId');
        if ($orderId) {
            $order = $entityManager->getRepository(Order::class)->find($orderId);
            if ($order) {
                $order->setStatus('cancelled');
                $entityManager->flush();
            }
        }

        $this->addFlash('warning', 'Your order has been cancelled.');
        return $this->redirectToRoute('app_cart');
    }

    #[Route('/clear', name: 'app_cart_clear')]
    public function clear(CartService $cartService): Response
    {
        $cartService->clear();

        $this->addFlash('success', 'Cart cleared successfully!');

        return $this->redirectToRoute('app_cart');
    }
} 