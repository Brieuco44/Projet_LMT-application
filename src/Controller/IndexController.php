<?php

namespace App\Controller;

use GuzzleHttp\Client;
use App\Entity\Document;
use App\Form\DocumentType;
use Symfony\UX\Turbo\TurboBundle;
use App\Service\ComparaisonService;
use App\Repository\ChampsRepository;
use App\Repository\ControleRepository;
use App\Repository\DocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\UX\Turbo\Helper\TurboStream;
use App\Repository\TypeLivrableRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class IndexController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager, private TypeLivrableRepository $TypeLivrableRepo, private DocumentRepository $documentRepo, private HttpClientInterface $httpClient, private ComparaisonService $comparaisonService, private ControleRepository $controleRepo) {}

    #[Route('/', name: 'app_home')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        $currentUser = $this->getUser();
        $documents = $this->documentRepo->findByUser($currentUser);
        return $this->render('index/index.html.twig', [
            'typeLivrables' => $this->TypeLivrableRepo->findAll(),
            'documents' => $documents,
        ]);
    }


    #[Route('/document/form/upload', name: 'document_form', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function documentForm(): Response
    {
        $formDocument = $this->createForm(DocumentType::class);
        return $this->render('index/_formDocument.html.twig', [
            'formDocument' => $formDocument->createView(),
        ]);
    }

    #[Route('/document/form/upload', name: 'document_upload', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function uploadDocumentForm(Request $request): Response
    {
        $client = new Client();
        $formDocument = $this->createForm(DocumentType::class);
        $formDocument->handleRequest($request);
        if ($formDocument->isSubmitted() && $formDocument->isValid()) {
            $typeLivrable = $formDocument->get('TypeLivrable')->getData();
            $documents = [];
            /** @var UploadedFile[] $files */
            $files = $formDocument->get('files')->getData();
            foreach ($files as $file) {

                $stream = fopen($file->getPathname(), 'r');

                if ($stream === false) {
                    throw new \RuntimeException('Impossible d’ouvrir le fichier : ' . $file->getClientOriginalName());
                }

                $response = $client->request('POST', $_SERVER['API_URL'], [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                    'multipart' => [
                        [
                            'name'     => 'pdffile',
                            'contents' => $stream,
                            'filename' => $file->getClientOriginalName(),
                            'headers'  => [
                                'Content-Type' => $file->getMimeType(),
                            ],
                        ],
                        [
                            'name' => 'typelivrable',
                            'contents' => $typeLivrable->getId(),
                        ]
                    ],
                ]);
                if ($response->getStatusCode() === 200) {
                    $dataOCR = json_decode($response->getBody()->getContents(), true);
                    $document = new Document();
                    $document->setDate(new \DateTime());
                    $document->setTypeLivrable($typeLivrable);
                    $document->setNom($file->getClientOriginalName());
                    $document->setUser($this->getUser());
                    $this->entityManager->persist($document);
                    $this->entityManager->flush();
                    $this->comparaisonService->compareDocuments($typeLivrable, $document->getId(), $dataOCR);
                    $documents[] = $document;
                } else {
                    // Handle error
                  if ($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT) {
                    $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                      return $this->render('index/_error.stream.html.twig', [
                          'message' => 'Erreur lors de l\'envoi du fichier : ' . $file->getClientOriginalName(),
                      ]);
                    }
                }
            }
            if ($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT) {
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                $formDocument = $this->createForm(DocumentType::class);

                return $this->render('index/_insert_document.stream.html.twig', [
                    'documents' => $documents,
                    'formDocument' => $formDocument->createView(),
                ]);
            }
        }
        return $this->render('index/_formDocument.html.twig', [
            'formDocument' => $formDocument->createView(),
        ]);
    }

    #[Route('/document/controles', name: 'affichage_controle')]
    #[IsGranted('ROLE_USER')]
    public function afficherControles(Request $request, ChampsRepository $champsRepository, ?int $id): Response
    {
        // $controles = null;
        $id = $request->query->get('id');
        if ($id) {
            $document = $this->documentRepo->find($id);
            if (!$document) {
                throw $this->createNotFoundException('Document non trouvé');
            }
            $controles = $this->controleRepo->findBy(['document' => $document]);
        }

        return $this->render('index/_controles.html.twig', [
            'controles' => $controles ?? null,
            'document' => $document ?? null,
        ]);

    }

    #[Route('/document/delete/{id}', name: 'delete_document', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(int $id, Request $request, DocumentRepository $documentRepository): Response
    {
        $document = $documentRepository->find($id);

        if (
            $document
            && $this->isCsrfTokenValid('delete_document_' . $document->getId(), $request->request->get('_token'))
        ) {
            // On garde l'ID en variable avant le flush
            $docId = $document->getId();

            $this->entityManager->remove($document);
            $this->entityManager->flush();

            return $this->render('index/_delete_stream.html.twig', [
                'id' => $docId,
            ], new Response('', 200, ['Content-Type' => 'text/vnd.turbo-stream.html']));
        }

        return new Response("Erreur lors de la suppression", 400);
    }
}
