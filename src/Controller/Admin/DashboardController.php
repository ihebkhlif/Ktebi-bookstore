<?php

namespace App\Controller\Admin;

use App\Entity\Book;
use App\Entity\Category;
use App\Entity\Order;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/admin', name: 'admin_dashboard')]
    public function index(): Response
    {
        $countBooks = $this->entityManager->getRepository(Book::class)->count([]);
        $countCategories = $this->entityManager->getRepository(Category::class)->count([]);
        $countOrders = $this->entityManager->getRepository(Order::class)->count([]);
        $countUsers = $this->entityManager->getRepository(User::class)->count([]);

        // Calculate total revenue
        $orders = $this->entityManager->getRepository(Order::class)->findAll();
        $totalRevenue = array_reduce($orders, function($carry, $order) {
            return $carry + $order->getTotalPrice();
        }, 0);

        return $this->render('admin/dashboard.html.twig', [
            'count_books' => $countBooks,
            'count_categories' => $countCategories,
            'count_orders' => $countOrders,
            'count_users' => $countUsers,
            'total_revenue' => $totalRevenue,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Ktebi Admin')
            ->setFaviconPath('data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 128 128%22><text y=%221.2em%22 font-size=%2296%22>📚</text></svg>')
            ->renderContentMaximized();
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addCssFile('css/admin.css');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Books', 'fas fa-book', Book::class);
        yield MenuItem::linkToCrud('Categories', 'fas fa-list', Category::class);
        yield MenuItem::linkToCrud('Orders', 'fas fa-shopping-cart', Order::class);
        yield MenuItem::linkToCrud('Users', 'fas fa-users', User::class);
        yield MenuItem::linkToRoute('Back to Site', 'fas fa-arrow-left', 'app_home');
    }
} 