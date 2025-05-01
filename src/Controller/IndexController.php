<?php

namespace App\Controller;

use App\Form\DocumentType;
use App\Repository\DocumentRepository;
use App\Repository\UtilisateurRepository;
use App\Repository\TypeLivrableRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class IndexController extends AbstractController
{
    public function __construct(private TypeLivrableRepository $TypeLivrableRepo, private DocumentRepository $documentRepo)
    {
    }

    #[Route('/', name: 'app_home')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {

        $documents = $this->documentRepo->findAll();
        

        return $this->render('index/index.html.twig', [
            'typeLivrables' => $this->TypeLivrableRepo->findAll(),
            'documents' => $documents,
        ]);
    }


    #[Route('/document/form', name: 'document_form', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function documentForm(): Response
    {
        $formDocument = $this->createForm(DocumentType::class);
        return $this->render('index/_formDocument.html.twig', [
            'formDocument' => $formDocument->createView(),
        ]);
    }
}
