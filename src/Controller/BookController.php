<?php

namespace App\Controller;

use App\Entity\Book;
use App\Form\BookFilterType;
use App\Repository\BookRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BookController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(BookRepository $bookRepository, CategoryRepository $categoryRepository): Response
    {
        return $this->render('book/index.html.twig', [
            'featured_books' => $bookRepository->findBy([], ['id' => 'DESC'], 6),
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    #[Route('/books', name: 'app_books')]
    public function list(Request $request, BookRepository $bookRepository): Response
    {
        $filterForm = $this->createForm(BookFilterType::class);
        $filterForm->handleRequest($request);

        $queryBuilder = $bookRepository->createQueryBuilder('b')
            ->leftJoin('b.category', 'c');

        // Apply filters
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $filters = $filterForm->getData();

            if ($filters['category']) {
                $queryBuilder->andWhere('b.category = :category')
                    ->setParameter('category', $filters['category']);
            }

            if ($filters['priceMin']) {
                $queryBuilder->andWhere('b.price >= :priceMin')
                    ->setParameter('priceMin', $filters['priceMin']);
            }

            if ($filters['priceMax']) {
                $queryBuilder->andWhere('b.price <= :priceMax')
                    ->setParameter('priceMax', $filters['priceMax']);
            }

            // Apply sorting
            if ($filters['sort']) {
                switch ($filters['sort']) {
                    case 'price_asc':
                        $queryBuilder->orderBy('b.price', 'ASC');
                        break;
                    case 'price_desc':
                        $queryBuilder->orderBy('b.price', 'DESC');
                        break;
                    case 'title_asc':
                        $queryBuilder->orderBy('b.title', 'ASC');
                        break;
                    case 'title_desc':
                        $queryBuilder->orderBy('b.title', 'DESC');
                        break;
                }
            } else {
                $queryBuilder->orderBy('b.title', 'ASC');
            }
        }

        $page = $request->query->getInt('page', 1);
        $limit = 12;

        $paginator = $this->paginate($queryBuilder, $page, $limit);

        return $this->render('book/list.html.twig', [
            'books' => $paginator['items'],
            'current_page' => $page,
            'total_pages' => $paginator['total_pages'],
            'filterForm' => $filterForm->createView(),
        ]);
    }

    #[Route('/book/{slug}', name: 'app_book_show')]
    public function show(Book $book): Response
    {
        return $this->render('book/show.html.twig', [
            'book' => $book,
        ]);
    }

    #[Route('/search', name: 'app_book_search')]
    public function search(Request $request, BookRepository $bookRepository): Response
    {
        $query = $request->query->get('q');
        $books = [];

        if ($query) {
            $books = $bookRepository->search($query);
        }

        return $this->render('book/search.html.twig', [
            'books' => $books,
            'query' => $query,
        ]);
    }

    private function paginate(QueryBuilder $queryBuilder, int $page, int $limit): array
    {
        $queryBuilder->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $paginator = $queryBuilder->getQuery();

        $total = count($queryBuilder->getQuery()->getResult());

        return [
            'items' => $paginator->getResult(),
            'total_pages' => ceil($total / $limit),
        ];
    }
} 