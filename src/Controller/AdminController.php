<?php

namespace App\Controller;

use App\Form\ZoneType;
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

    #[Route('/admin/livrable/upload', name: 'app_ajoutLivrable')]
    #[IsGranted('ROLE_ADMIN')]
    public function ajoutLivrable(Request $request, SluggerInterface $slugger, EntityManagerInterface $entityManager): Response
    {
        $typelivrable = new TypeLivrable();
        $form = $this->createForm(TypeLivrableType::class, $typelivrable);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
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

                return $this->redirectToRoute('admin_typelivrable_parametrage', [
                    'id' => $typelivrable->getId(),
                ]);
            } catch (\Throwable $th) {
                $this->addFlash('error', 'Une erreur est survenue lors de l\'ajout du livrable.');
                return $this->redirectToRoute('app_ajoutLivrable');
            }
        }

        return $this->render('admin/livrable/ajoutLivrable.html.twig', [
            'uploadForm' => $form,
        ]);
    }

    #[Route('/admin/livrable/{id}/parametrage', name: 'admin_typelivrable_parametrage')]
    #[IsGranted('ROLE_ADMIN')]
    public function typelivrableParametrage(int $id, TypeLivrableRepository $repo, Request $request, EntityManagerInterface $entityManager): Response
    {
        $typeLivrable = $repo->find($id);
        if (!$typeLivrable) {
            throw $this->createNotFoundException('livrable non trouvé.');
        }
        $formZone = $this->createForm(ZoneType::class);
        $formZone->handleRequest($request);
        if ($formZone->isSubmitted() && $formZone->isValid()) {
            $zone = $formZone->getData();
            $zone->setTypeLivrable($typeLivrable);
            $entityManager->persist($zone);
            $entityManager->flush();
        }

        return $this->render('admin/livrable/parametrage.html.twig', [
            'typeLivrable' => $typeLivrable,
            'formZone' => $formZone->createView(),
            
        ]);
    }

    #[Route('/admin/livrable/formZone', name: 'admin_typelivrable_zone_form', methods: ['GET', 'POST'])]
    public function formZone(Request $request, EntityManagerInterface $entityManager): Response
    {
        $formZone = $this->createForm(ZoneType::class);
        $formZone->handleRequest($request);
        if ($formZone->isSubmitted() && $formZone->isValid()) {
            $zone = $formZone->getData();
            $entityManager->persist($zone);
            $entityManager->flush();
        }
        return $this->render('admin/livrable/_formZone.html.twig', [

        ]);
    }
}
