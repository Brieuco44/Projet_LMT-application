<?php

namespace App\Controller;

use App\Entity\TypeLivrable;
use App\Form\TypeLivrableType;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\TypeLivrableRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin',)]
    #[IsGranted('ROLE_ADMIN')]
    public function index(): Response
    {
        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    #[Route('/admin/ajoutLivrable', name: 'app_ajoutLivrable')]
    #[IsGranted('ROLE_ADMIN')]
    public function ajoutLivrable(Request $request, SluggerInterface $slugger, EntityManagerInterface $entityManager): Response
    {
        $typelivrable = new TypeLivrable();
        $form = $this->createForm(TypeLivrableType::class, $typelivrable);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pdfFile = $form->get('pdf')->getData();
            if ($pdfFile) {
                $filename = $slugger->slug(pathinfo($pdfFile->getClientOriginalName(), PATHINFO_FILENAME))
                    . '-' . uniqid() . '.' . $pdfFile->guessExtension();

                // Déplace le fichier dans le répertoire de destination
                $pdfFile->move($this->getParameter('pdf_directory'), $filename);
                $typelivrable->setPath($filename);
                $typelivrable->setNom($form->get('nom')->getData());
            }

            $entityManager->persist($typelivrable);
            $entityManager->flush();

            return $this->redirectToRoute('admin_typelivrable_show', [
                'id' => $typelivrable->getId(),
            ]);
        }

        return $this->render('admin/livrable/typelivrable_editor.html.twig', [
            'uploadForm' => $form->createView(),
            'typelivrable' => null,
        ]);
    }


    #[Route('/admin/ajoutLivrable/{id}', name: 'admin_typelivrable_show')]
    #[IsGranted('ROLE_ADMIN')]
    public function showEditor(Typelivrable $typelivrable): Response
    {
        $form = $this->createForm(TypelivrableType::class);
        return $this->render('admin/typelivrable_editor.html.twig', [
            'uploadForm' => $form->createView(),
            'typelivrable' => $typelivrable,
        ]);
    }
}
