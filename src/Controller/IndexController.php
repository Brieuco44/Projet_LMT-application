<?php

namespace App\Controller;

use App\Entity\Document;
use App\Form\DocumentType;
use App\Repository\DocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\TypeLivrableRepository;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class IndexController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager, private TypeLivrableRepository $TypeLivrableRepo, private DocumentRepository $documentRepo, private HttpClientInterface $httpClient) {}

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
            $typeLivrableId = $formDocument->get('TypeLivrable')->getData();

            /** @var UploadedFile[] $files */
            $files = $formDocument->get('files')->getData();
            
            foreach ($files as $file) {
            
                $stream = fopen($file->getPathname(), 'r');
            
                if ($stream === false) {
                    throw new \RuntimeException('Impossible d’ouvrir le fichier : '.$file->getClientOriginalName());
                }

                $response = $client->request('POST', $_SERVER['API_URL'], [
                    'headers' => [
                        'Accept' => 'application/json', // facultatif
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
                            'contents' => $typeLivrableId->getId(),
                        ]
                    ],
                ]);
                if ($response->getStatusCode() === 200) {
                    dd('OK', $response->getStatusCode(), $response->getBody()->getContents());

                    $data = $response->toArray();
                    $document = new Document();
                    $document->setNom($data['nom']);
                    $document->setTypeLivrable($this->TypeLivrableRepo->find($typeLivrableId));
                    $document->setDateAjout(new \DateTime());
                    $this->entityManager->persist($document);
                    $this->entityManager->flush();
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
