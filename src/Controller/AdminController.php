<?php

namespace App\Controller;

use App\Entity\Champs;
use App\Form\ZoneType;
use App\Form\ChampsType;
use App\Entity\TypeLivrable;
use App\Entity\Zone;
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

    #[Route('/_formZone', name: 'admin_typelivrable_zone_form', methods: ['GET', 'POST'])]
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
            'formZone' => $formZone->createView(),
        ]);
    }
    #[Route('/_formChamps', name: 'admin_typelivrable_champs_form')]
    public function formChamps(Request $request, EntityManagerInterface $entityManager): Response
    {
        $formChamps = $this->createForm(ChampsType::class);
        $formChamps->handleRequest($request);
        if ($formChamps->isSubmitted() && $formChamps->isValid()) {
            $champs = $formChamps->getData();
            $entityManager->persist($champs);
            $entityManager->flush();
        }
        return $this->render('admin/livrable/_formChamps.html.twig', [
            'formChamps' => $formChamps->createView(),
        ]);
    }

    // #[Route('/admin/livrable/champs/form', name: 'admin_typelivrable_champs_form')]
    // public function champsForm(Request $request, EntityManagerInterface $em): Response
    // {
    //     $zoneId = $request->query->get('zone_id');
    //     $zone = $em->getRepository(Zone::class)->find($zoneId);
    //     $champs = new Champs();
    //     if ($zone) {
    //         $champs->setZone($zone);
    //     }

    //     $formChamps = $this->createForm(ChampsType::class, $champs);
    //     $formChamps->handleRequest($request);

    //     if ($formChamps->isSubmitted() && $formChamps->isValid()) {
    //         dd($formChamps->getData());
    //         $em->persist($champs);
    //         $em->flush();

    //         // C'est ici qu'on vérifie si c'est une requête Turbo-Stream
    //         if ($request->headers->contains('Turbo-Frame', 'champs_form')) {
    //             return $this->render('admin/typelivrable/_insert_champs.stream.html.twig', [
    //                 'champs' => $champs,
    //                 'zoneId' => $zoneId,
    //             ]);
    //         }

    //         // Sinon faire une redirection normale (par sécurité)
    //         return $this->redirectToRoute('admin_typelivrable_edit', ['id' => $zone->getTypeLivrable()->getId()]);
    //     }

    //     // Soit un GET (affichage vide), soit POST invalide (erreurs)
    //     return $this->render('admin/livrable/_formChamps.html.twig', [
    //         'formChamps' => $formChamps,
    //     ]);
    // }

    #[Route('/admin/livrable/champs/form', name: 'admin_typelivrable_champs_form', methods: ['GET'])]
    public function champsForm(Request $request, EntityManagerInterface $em): Response
    {
        $zoneId = $request->query->get('zone_id');
        $zone = $em->getRepository(Zone::class)->find($zoneId);
        $champs = new Champs();
        if ($zone) {
            $champs->setZone($zone);
        }

        $formChamps = $this->createForm(ChampsType::class, $champs);

        return $this->render('admin/livrable/_formChamps.html.twig', [
            'formChamps' => $formChamps,
        ]);
    }

    #[Route('/admin/livrable/champs/form', name: 'admin_typelivrable_champs_form_submit', methods: ['POST'])]
    public function champsFormSubmit(Request $request, EntityManagerInterface $em): Response
    {
        $zoneId = $request->query->get('zone_id');
        $zone = $em->getRepository(Zone::class)->find($zoneId);
        $champs = new Champs();
        if ($zone) {
            $champs->setZone($zone);
        }

        $formChamps = $this->createForm(ChampsType::class, $champs);
        $formChamps->handleRequest($request);

        if ($formChamps->isSubmitted() && $formChamps->isValid()) {
            $em->persist($champs);
            $em->flush();

            return $this->render('admin/livrable/_insert_champs.stream.html.twig', [
                'champs' => $champs,
                'zoneId' => $zoneId,
            ]);
        }

        // Si le formulaire a des erreurs de validation
        return $this->render('admin/livrable/_formChamps.html.twig', [
            'formChamps' => $formChamps,
        ]);
    }
}
