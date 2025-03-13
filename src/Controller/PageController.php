<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class PageController extends AbstractController
{
    #[Route('/', name: 'app_index')]
    public function index(): Response
    {
        return $this->render('page/index.html.twig', [
        ]);
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    #[IsGranted('ROLE_USER')]
    public function dashboard(): Response
    {
        $userProducts = $this->getUser()->getProducts();
        $activeProducts = [];
        $drafts = [];
        $expiredProducts = [];

        if (count($userProducts) > 0) {
            foreach ($userProducts as $userProduct) {
                switch ($userProduct->getStatus()) {
                    case 'draft':
                        $drafts[] = $userProduct;
                        break;
                    case 'expired':
                        $expiredProducts[] = $userProduct;
                        break;
                    default:
                        $activeProducts[] = $userProduct;
                }
            }
        }

        return $this->render('page/dashboard.html.twig', [
            'activeProducts' => $activeProducts,
            'drafts' => $drafts,
            'expiredProducts' => $expiredProducts,
        ]);
    }

    // src/Controller/PageController.php

    #[Route('/profile', name: 'app_profile')]
    #[IsGranted('ROLE_USER')]
    public function edit(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(UserProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();

            if (!empty($plainPassword)) {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('page/profile.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }


}
