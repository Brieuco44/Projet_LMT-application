<?php

namespace App\Controller;

use App\Entity\Champs;
use App\Form\ZoneType;
use App\Form\ChampsType;
use App\Entity\TypeLivrable;
use App\Entity\Zone;
use App\Form\TypeLivrableType;
use App\Repository\UtilisateurRepository;
use App\Service\ComparaisonService;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\TypeLivrableRepository;
use App\Repository\ZoneRepository;
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
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TypeLivrableRepository $typeLivrableRepo,
        private ComparaisonService $comparaisonService,
        private ZoneRepository $zoneRepo,
        private UtilisateurRepository $utilisateurRepo
    ) {
    }
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
    public function ajoutLivrable(Request $request, SluggerInterface $slugger): Response
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

                $this->entityManager->persist($typelivrable);
                $this->entityManager->flush();

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
    public function typelivrableParametrage(int $id, TypeLivrableRepository $repo, Request $request): Response
    {
        $typeLivrable = $repo->find($id);
        if (!$typeLivrable) {
            throw $this->createNotFoundException('livrable non trouvé.');
        }

        $formZone = $this->createForm(ZoneType::class);
        $formZone->handleRequest($request);

        if ($formZone->isSubmitted() && $formZone->isValid()) {
            $zone = $formZone->getData(); // coordonnees n'est pas mappé automatiquement

            $raw = $formZone->get('coordonnees')->getData();
            $decoded = json_decode($raw, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                throw new \RuntimeException('JSON invalide pour les coordonnées.');
            }

            $zone->setCoordonnees($decoded);
            $zone->setTypeLivrable($typeLivrable);

            $this->entityManager->persist($zone);
            $this->entityManager->flush();

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
    public function formZone(Request $request): Response
    {
        $formZone = $this->createForm(ZoneType::class);
        $formZone->handleRequest($request);
        if ($formZone->isSubmitted() && $formZone->isValid()) {
            $zone = $formZone->getData();
            $this->entityManager->persist($zone);
            $this->entityManager->flush();
        }
        return $this->render('admin/livrable/_formZone.html.twig', [
            'formZone' => $formZone->createView(),
        ]);
    }
    #[Route('/_formChamps', name: 'admin_typelivrable_champs_form')]
    public function formChamps(Request $request): Response
    {
        $headers = $this->comparaisonService->getExportHeaders() ?? [];

        $formChamps = $this->createForm(ChampsType::class, null, [
            'headers' => $headers,
            'hasIdentifiant' => $this->typeLivrableRepo->hasIdentifiant($request->query->get('id')) ?? null,
            'zone' => null,
        ]);
        $formChamps->handleRequest($request);
        if ($formChamps->isSubmitted() && $formChamps->isValid()) {
            $champs = $formChamps->getData();
            $this->entityManager->persist($champs);
            $this->entityManager->flush();
        }


        return $this->render('admin/livrable/_formChamps.html.twig', [
            'formChamps' => $formChamps->createView(),
        ]);
    }

    #[Route('/admin/livrable/champs/form', name: 'admin_typelivrable_champs_form', methods: ['GET'])]
    public function champsForm(Request $request): Response
    {
        $zoneId = $request->query->get('zone_id');
        $zone = $this->zoneRepo->find($zoneId);

        $champs = new Champs();
        if ($zone) {
            $champs->setZone($zone);
            $hasIdentifiant = $this->typeLivrableRepo->hasIdentifiant($zone->getTypeLivrable()->getId());
        }


        $headers = $this->comparaisonService->getExportHeaders() ?? [];
        $formChamps = $this->createForm(ChampsType::class, null, [
            'hasIdentifiant' => $hasIdentifiant ?? null,
            'headers' => $headers,
            'zone' => $zone,
        ]);

        return $this->render('admin/livrable/_formChamps.html.twig', [
            'formChamps' => $formChamps
        ]);
    }

    #[Route('/admin/livrable/champs/form', name: 'admin_typelivrable_champs_form_submit', methods: ['POST'])]
    public function champsFormSubmit(Request $request)
    {
        $zoneId = $request->query->get('zone_id');
        $zone = $this->zoneRepo->find($zoneId);

        $champs = new Champs();

        $headers = $this->comparaisonService->getExportHeaders() ?? [];
        $formChamps = $this->createForm(ChampsType::class, $champs,[
            'headers' => $headers,
            'hasIdentifiant' => $this->typeLivrableRepo->hasIdentifiant($zone->getTypeLivrable()->getId()),
            'zone' => $zone,
        ]);

        $formChamps->handleRequest($request);

        if ($formChamps->isSubmitted() && $formChamps->isValid()) {
            // si c'est Signature, on force question et donneeERP à ''
            if ($champs->getTypeChamps()->getNom() === 'Signature' || $champs->getTypeChamps()->getNom() === 'Case cochée') {
                $champs->setQuestion('');
                $champs->setDonneeERP('');
            }

            $champs->setZone($zone); // Deplacé ici car champs écrasé sinon
            $this->entityManager->persist($champs);
            $this->entityManager->flush();

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
    public function deleteChamps(int $id, Request $request)
    {
        $champs = $this->entityManager->getRepository(Champs::class)->find($id);

        if (!$champs) {
            throw $this->createNotFoundException('Le champ demandé n\'existe pas.');
        }
        $this->entityManager->remove($champs);
        $this->entityManager->flush();
        if ($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT) {
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
            return $this->render('admin/livrable/_delete_champs.stream.html.twig', [
                'champsId' => $id,
            ]);
        }
    }

    #[Route('/admin/livrable/zone/{id}/update', name: 'admin_typelivrable_zone_update', methods: ['POST'])]
    public function updateZone(int $id, Request $request) {
        $zone = $this->zoneRepo->find($id);
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

        $this->entityManager->flush();

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
    public function deleteZone(int $id, Request $request)
    {
        $zone = $this->zoneRepo->find($id);

        if (!$zone) {
            throw $this->createNotFoundException('La zone demandée n\'existe pas.');
        }
        $this->entityManager->remove($zone);
        $this->entityManager->flush();
        if ($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT) {
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
            return $this->render('admin/livrable/_delete_zone.stream.html.twig', [
                'zoneId' => $id,
            ]);
        }
    }

    #[Route('/admin/livrable', name: 'admin_gestion_livrable', methods: ["GET"])]
    #[IsGranted('ROLE_ADMIN')]
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

    #[Route('/admin/livrable/{id}/delete', name: 'admin_livrable_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(
        int $id,
        Request $request,
        TypeLivrableRepository $repo,
        EntityManagerInterface $em
    ): Response {
        $livrable = $repo->find($id);
        if (!$livrable) {
            throw $this->createNotFoundException('Livrable introuvable.');
        }

        if (!$this->isCsrfTokenValid('delete'.$livrable->getId(), $request->request->get('_token'))) {
            $this->addFlash('warning', 'Token CSRF invalide, suppression annulée.');
            return $this->redirectToRoute('admin_gestion_livrable');
        }

        $em->remove($livrable);
        $em->flush();

        $this->addFlash('success', 'Livrable supprimé avec succès.');
        return $this->redirectToRoute('admin_gestion_livrable');
    }

}
