<?php

namespace App\Controller;

use GuzzleHttp\Client;
use App\Entity\Document;
use App\Form\DocumentType;
use App\Repository\ChampsRepository;
use App\Service\ComparaisonService;
use App\Repository\DocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\TypeLivrableRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\Turbo\Helper\TurboStream;
use Symfony\UX\Turbo\TurboBundle;

final class IndexController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager, private TypeLivrableRepository $TypeLivrableRepo, private DocumentRepository $documentRepo, private HttpClientInterface $httpClient, private ComparaisonService $comparaisonService) {}

    #[Route('/', name: 'app_home')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        return $this->render('index/index.html.twig', [
            'typeLivrables' => $this->TypeLivrableRepo->findAll(),
            'documents' => $this->documentRepo->findAll(),
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
                    $this->entityManager->persist($document);
                    $this->entityManager->flush();
                    $this->comparaisonService->compareDocuments($typeLivrable, $document->getId(), $dataOCR);
                    if ($request->getPreferredFormat()=== TurboBundle::STREAM_FORMAT) {
                        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
                        return $this->render('index/_insert_document.stream.html.twig', [
                            'document' => $document,
                        ]);
                    }

                } else {
                    // Handle error
                    dd((string)$response->getStatusCode(), $response->getBody()->getContents());
                    $this->addFlash('error', 'Erreur lors de l\'upload du document.');
                }
                
            }
        }
        return $this->render('index/_formDocument.html.twig', [
            'formDocument' => $formDocument->createView(),
        ]);
    }
}
