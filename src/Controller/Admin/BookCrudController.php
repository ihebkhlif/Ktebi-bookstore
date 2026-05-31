<?php

namespace App\Controller\Admin;

use App\Entity\Book;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class BookCrudController extends AbstractCrudController
{
    private $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    public static function getEntityFqcn(): string
    {
        return Book::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Book')
            ->setEntityLabelInPlural('Books')
            ->setSearchFields(['title', 'author', 'description'])
            ->setDefaultSort(['title' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        $projectDir = $this->params->get('kernel.project_dir');
        
        yield TextField::new('title');
        yield TextField::new('author');
        yield SlugField::new('slug')->setTargetFieldName('title');
        yield TextEditorField::new('description');
        yield MoneyField::new('price')->setCurrency('USD')->setStoredAsCents(false);
        yield TextField::new('isbn');
        yield DateField::new('publicationDate');
        yield TextField::new('publisher');
        yield NumberField::new('pages');
        yield AssociationField::new('category');
        yield ImageField::new('image')
            ->setBasePath('uploads/books')
            ->setUploadDir('/public/uploads/books')
            ->setUploadedFileNamePattern('[randomhash].[extension]')
            ->setRequired(false);
    }
} 