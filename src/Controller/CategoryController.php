<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/categories')]
class CategoryController extends AbstractController
{
    #[Route('', name: 'app_categories')]
    public function index(CategoryRepository $categoryRepository): Response
    {
        return $this->render('category/index.html.twig', [
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    #[Route('/{slug}', name: 'app_category_show')]
    public function show(Category $category, Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 12;

        $books = $category->getBooks();
        $total = count($books);

        return $this->render('category/show.html.twig', [
            'category' => $category,
            'books' => array_slice($books->toArray(), ($page - 1) * $limit, $limit),
            'current_page' => $page,
            'total_pages' => ceil($total / $limit),
        ]);
    }
} 