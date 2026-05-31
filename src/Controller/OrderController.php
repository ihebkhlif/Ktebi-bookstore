<?php

namespace App\Controller;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{
    #[Route('/orders', name: 'app_orders')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $orders = $entityManager
            ->getRepository(Order::class)
            ->findBy(
                ['user' => $this->getUser()],
                ['createdAt' => 'DESC']
            );

        return $this->render('order/index.html.twig', [
            'orders' => $orders,
        ]);
    }
} 