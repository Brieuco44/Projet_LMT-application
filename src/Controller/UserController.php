<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\UtilisateurType;
use Symfony\UX\Turbo\TurboBundle;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UtilisateurRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/admin/users')]
class UserController extends AbstractController
{
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $hasher;

    public function __construct(EntityManagerInterface $em, UserPasswordHasherInterface $hasher)
    {
        $this->em     = $em;
        $this->hasher = $hasher;
    }

    #[Route('', name: 'admin_gestion_utilisateur', methods: ['GET'])]
    public function gestionUser(UtilisateurRepository $userRepo): Response
    {
        $users = $userRepo->findAll();

        return $this->render('admin/utilisateur/gestionUser.html.twig', [
            'ListUser' => $users,
        ]);
    }

    #[Route('/new', name: 'admin_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = new Utilisateur();
        $form = $this->createForm(UtilisateurType::class, $user, [
            'is_edit' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('admin')->getData()) {
                $user->setRoles(['ROLE_ADMIN']);
            } else {
                $user->setRoles(['ROLE_USER']);
            }
            $hashed = $this->hasher->hashPassword($user, $form->get('password')->getData());
            $user->setPassword($hashed);
            $this->em->persist($user);
            $this->em->flush();
            return $this->redirectToRoute('admin_gestion_utilisateur');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Veuillez corriger les erreurs du formulaire.');
        }

        return $this->render('admin/utilisateur/_user_form.html.twig', [
            'formUser'    => $form,
            'dialogTitle' => 'Ajouter un Utilisateur',
            'is_edit'     => false,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(Utilisateur $user, Request $request): Response
    {
        $form = $this->createForm(UtilisateurType::class, $user, [
            'is_edit' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('admin')->getData()) {
                $user->setRoles(['ROLE_ADMIN']);
            } else {
                $user->setRoles(['ROLE_USER']);
            }
            if ($plain = $form->get('password')->getData()) {
                $user->setPassword($this->hasher->hashPassword($user, $plain));
            }
            $this->em->flush();
            return $this->redirectToRoute('admin_gestion_utilisateur');
        }

        return $this->render('admin/utilisateur/_user_form.html.twig', [
            'formUser'    => $form,
            'dialogTitle' => 'Éditer un Utilisateur',
            'is_edit'     => true,
            'user' => $user,
        ]);
    }

    #[Route('/{id}', name: 'admin_user_delete', methods: ['POST'])]
    public function delete(Utilisateur $user, Request $request)
    {
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            return $this->redirectToRoute('admin_gestion_utilisateur');
        }


        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $id = $user->getId();
            $this->em->remove($user);
            $this->em->flush();
            
            if ($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                return $this->render('admin/utilisateur/_delete_user.stream.html.twig', [
                    'userId' => $id,
                ]);
            }
        }
    }
}
