<?php

namespace App\Controller\Admin;

use App\Entity\Car;
use App\Entity\Computer;
use App\Entity\Phone;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class AdminDashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
         return $this->render('admin/index.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Marketplace Admin');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToCrud('Cars', 'fas fa-car', Car::class);
        yield MenuItem::linkToCrud('Phones', 'fas fa-mobile-alt', Phone::class);
        yield MenuItem::linkToCrud('Computers', 'fas fa-desktop', Computer::class);
        yield MenuItem::linkToCrud('User', 'fas fa-user', User::class);
        yield MenuItem::linkToRoute('Back to Home', 'fas fa-home', 'app_index');
    }
}
