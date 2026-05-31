<?php

namespace App\Service;

use App\Entity\Book;
use Symfony\Component\HttpFoundation\RequestStack;

class CartService
{
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function getSession()
    {
        return $this->requestStack->getSession();
    }

    public function add(Book $book): void
    {
        $cart = $this->getCart();
        $bookId = $book->getId();

        if (!isset($cart[$bookId])) {
            $cart[$bookId] = [
                'book' => $book,
                'quantity' => 0
            ];
        }

        $cart[$bookId]['quantity']++;

        $this->getSession()->set('cart', $cart);
        $this->updateCartCount();
    }

    public function remove(Book $book): void
    {
        $cart = $this->getCart();
        $bookId = $book->getId();

        if (isset($cart[$bookId])) {
            unset($cart[$bookId]);
            $this->getSession()->set('cart', $cart);
            $this->updateCartCount();
        }
    }

    public function clear(): void
    {
        $this->getSession()->remove('cart');
        $this->getSession()->remove('cart_count');
    }

    public function getCart(): array
    {
        return $this->getSession()->get('cart', []);
    }

    public function getTotal(): float
    {
        $total = 0;
        foreach ($this->getCart() as $item) {
            $total += $item['book']->getPrice() * $item['quantity'];
        }
        return $total;
    }

    private function updateCartCount(): void
    {
        $cart = $this->getCart();
        $count = 0;
        foreach ($cart as $item) {
            $count += $item['quantity'];
        }
        $this->getSession()->set('cart_count', $count);
    }
} 