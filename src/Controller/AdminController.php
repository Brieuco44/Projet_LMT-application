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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\Turbo\TurboBundle;

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
            if ($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                return $this->render('admin/livrable/_insert_zone.stream.html.twig', [
                    'zone' => $zone,
                ]);
            }
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
    public function champsFormSubmit(Request $request, EntityManagerInterface $em)
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
            if ($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                return $this->render('admin/livrable/_insert_champs.stream.html.twig', [
                    'champs' => $champs,
                    'zoneId' => $zoneId,
                ]);
            }
        }
    }

    #[Route('/admin/livrable/champs/{id}/delete', name: 'admin_typelivrable_champs_delete', methods: ['DELETE'])]
    public function deleteChamps(int $id, EntityManagerInterface $entityManager, Request $request)
    {
        $champs = $entityManager->getRepository(Champs::class)->find($id);

        if (!$champs) {
            throw $this->createNotFoundException('Le champ demandé n\'existe pas.');
        }
        $entityManager->remove($champs);
        $entityManager->flush();
        if ($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT) {
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
            return $this->render('admin/livrable/_delete_champs.stream.html.twig', [
                'champsId' => $id,
            ]);
        }
    }

    #[Route('/admin/livrable/zone/{id}/update', name: 'admin_typelivrable_zone_update', methods: ['POST'])]
    public function updateZone(int $id, Request $request, EntityManagerInterface $entityManager) {
        $zone = $entityManager->getRepository(Zone::class)->find($id);
        if (!$zone) {
            throw $this->createNotFoundException('Zone introuvable.');
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['coords']) || !is_array($data['coords'])) {
            return new JsonResponse(['error' => 'Données invalides'], 400);
        }

        $coords = $data['coords'];
        $zone->setCoordonnees([
            'x1' => (int)$coords['x1'],
            'y1' => (int)$coords['y1'],
            'x2' => (int)$coords['x2'],
            'y2' => (int)$coords['y2'],
        ]);
        if (isset($data['page'])) {
            $zone->setPage((int)$data['page']);
        }

        $entityManager->flush();

        if ($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT) {
            return $this->render('admin/livrable/_zone_updated.stream.html.twig', [
                'zone' => $zone,
            ], new Response('', 200, ['Content-Type' => TurboBundle::STREAM_FORMAT]));
        }

        return new JsonResponse([
            'status' => 'ok',
            'zoneId' => $zone->getId(),
            'coords' => $coords,
        ]);
    }

    #[Route('/admin/livrable/zone/{id}/delete', name: 'admin_typelivrable_zone_delete', methods: ['DELETE'])]
    public function deleteZone(int $id, EntityManagerInterface $entityManager, Request $request)
    {
        $zone = $entityManager->getRepository(Zone::class)->find($id);

        if (!$zone) {
            throw $this->createNotFoundException('La zone demandée n\'existe pas.');
        }
        $entityManager->remove($zone);
        $entityManager->flush();
        if ($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT) {
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
            return $this->render('admin/livrable/_delete_zone.stream.html.twig', [
                'zoneId' => $id,
            ]);
        }
    }

    #[Route('/admin/livrable', name: 'admin_gestion_livrable')]
    public function gestionLivrable(TypeLivrableRepository $repo): Response
    {
        $typeLivrables = $repo->findAll();

        foreach ($typeLivrables as $livrable) {
            $path = $this->getParameter('kernel.project_dir') . '/public/uploads/pdf/' . $livrable->getPath();
            $livrable->hasFile = file_exists($path);
        }

        return $this->render('admin/livrable/gestionLivrable.html.twig', [
            'ListLivrable' => $typeLivrables,
        ]);
    }
}
