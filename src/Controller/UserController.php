<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user')]
final class UserController extends AbstractController
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[IsGranted('ROLE_USER')]
    #[Route('/{id}/delete-self', name: 'app_user_delete_self', methods: ['POST'])]
    public function deleteSelf(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->getUser()->getId() !== $user->getId()) {
            throw new AccessDeniedException('You can only delete your own account.');
        }

        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();

            $request->getSession()->invalidate(); // Clear session
            $this->container->get('security.token_storage')->setToken(null); // Remove security token

            return $this->redirectToRoute('app_index');
        }

        return $this->redirectToRoute('app_index', [], Response::HTTP_SEE_OTHER);
    }


}
