<?php

namespace App\Service;

use App\Entity\Statut;
use App\Entity\Controle;
use App\Entity\TypeLivrable;
use App\Repository\StatutRepository;
use App\Repository\DocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\TypeLivrableRepository;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class ComparaisonService
{
    private string $exportFile = 'uploads/export-erp/extract-projet-ocr.csv';
    private ?Statut $statutDoc = null;
    private Statut $nonConformeStatut;
    private Statut $reverifierStatut;
    private Statut $conformeStatut;

    /**
     * @param TypeLivrableRepository $TypeLivrableRepo
     * @param DocumentRepository $documentRepo
     */
    public function __construct(private LoggerInterface $logger, private TypeLivrableRepository $TypeLivrableRepo, private DocumentRepository $documentRepo, private EntityManagerInterface $entityManager, private StatutRepository $statutRepo)
    {
        $this->conformeStatut = $this->statutRepo->getStatutConforme();
        $this->nonConformeStatut = $this->statutRepo->getStatutNonConforme();
        $this->reverifierStatut = $this->statutRepo->getStatutReverifier();
        $this->statutDoc = $this->conformeStatut;
    }

    public function compareDocuments(TypeLivrable $typeLivrable, int $idDocument, array $dataOCR): void
    {
        $this->statutDoc = $this->conformeStatut;
        $dataERP = $this->getLigneExport($typeLivrable->getId(), $dataOCR);
        $document = $this->documentRepo->find($idDocument);
        $zones = $typeLivrable->getZones();
        if ($dataERP === null) {
            $this->statutDoc = $this->statutRepo->getStatutIdentifiantIntrouvable();
            $document->setStatut($this->statutDoc);
        } else {
            foreach ($zones as $zone) {
                foreach ($zone->getChamps() as $champ) {
                    $controle = new Controle();
                    $controle->setChamps($champ);
                    $controle->setDocument($document);
                    if ($dataOCR[$champ->getZone()->getLibelle()][$champ->getNom()] === null) {
                        $controle->setStatut($this->statutRepo->getStatutChampsInexistant());
                    } else {
                        switch ($champ->getTypeChamps()->getNom()) {
                            case 'Identifiant':
                            case 'Text':
                            case 'Num commande':
                                $controle->setStatut($this->verifyText($dataERP[$champ->getDonneeERP()], $dataOCR[$champ->getZone()->getLibelle()][$champ->getNom()]));
                                break;
                            case 'Date':
                            case 'Date manuscrite':
                                $controle->setStatut($this->verifyDate($dataERP[$champ->getDonneeERP()], $dataOCR[$champ->getZone()->getLibelle()][$champ->getNom()]));
                                break;
                            case 'Signature':
                            case 'Case cochée':
                                $controle->setStatut($this->verifyBoolean($dataOCR[$champ->getZone()->getLibelle()][$champ->getNom()]));
                                break;
                            default:
                                $controle->setStatut($this->nonConformeStatut);
                                break;
                        }
                    }
                    $this->entityManager->persist($controle);
                    $this->entityManager->flush();

                    if ($controle->getStatut() === $this->nonConformeStatut) {
                        $this->statutDoc = $this->nonConformeStatut;
                    } elseif ($this->statutDoc === $this->conformeStatut && $controle->getStatut() === $this->reverifierStatut) {
                        $this->statutDoc = $this->reverifierStatut;
                    }
                }
            }
        }
        $document->setStatut($this->statutDoc);

        $this->entityManager->persist($document);
        $this->entityManager->flush();
    }

    /**
     * @param string $dataOCR
     * @param string $dataERP
     * @return Statut
     */
    public function verifyDate(string $dataERP, string $dataOCR): Statut
    {
        $statut = null;
        if ($dataOCR === 'Aucune date trouvée ou date non reconnue') {
            return $this->reverifierStatut;
        }
        $dateOCR = \DateTime::createFromFormat('d/m/Y', $dataOCR);
        $dateERP = \DateTime::createFromFormat('Y-m-d', $dataERP);
        if ($dateOCR === false || $dateERP === false) {
            $statut = $this->reverifierStatut;
            return $statut;
        }
        $dateOCR = $dateOCR->format('d/m/Y');
        $dateERP = $dateERP->format('d/m/Y');
        if ($dateERP == $dateOCR) {
            $statut = $this->conformeStatut;
        } elseif ($dateERP != $dateOCR) {
            $statut = $this->nonConformeStatut;
        }
        return $statut;
    }

    /**
     * @param string $dataOCR
     * @param string $dataERP
     * @return Statut
     */
    public function verifytext(string $dataERP, string $dataOCR): Statut
    {
        if ($dataERP === $dataOCR) {
            return $this->conformeStatut;
        }

        if ($dataERP !== $dataOCR) {
            return $this->nonConformeStatut;
        }
        return $this->reverifierStatut;
    }

    /**
     * @param string $dataOCR
     * @return Statut
     */
    public function verifyBoolean(bool $dataOCR): Statut
    {
        $this->logger->info('Donnée Signature OCR : ' . $dataOCR);
        if ($dataOCR) {
            return $this->conformeStatut;
        }
        return $dataOCR === false ? $this->nonConformeStatut : $this->reverifierStatut;
    }

    /**
     * Récupère la ligne d'export ERP correspondant à l'identifiant OCR
     * 
     * @param int $typeLivrableId
     * @param array $data
     * @return array|null
     */
    public function getLigneExport(int $typeLivrableId, array $data): ?array
    {
        $pathIdentifiant = $this->TypeLivrableRepo->findIdentifiant($typeLivrableId);
        $identifiantOCR = $data[$pathIdentifiant['Zone']][$pathIdentifiant['Champ']];

        if (($handle = fopen($this->exportFile, 'r')) !== false) {
            $headers = fgetcsv($handle, 1000, ';');

            while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                $row = array_combine($headers, $data);
                if ($row[$pathIdentifiant['DonneeERP']] == $identifiantOCR) {
                    fclose($handle);
                    return $row;
                }
            }
        }
        return null;
    }

    /**
     * Charge le fichier d'export ERP et retourne les en-têtes
     *
     * @return array|null
     */
    public function getExportHeaders(): ?array
    {
        if (!file_exists($this->exportFile)) {
            return [];
        }

        if (($handle = fopen($this->exportFile, 'r')) !== false) {
            $headers = fgetcsv($handle, 1000, ';');
            fclose($handle);
            return $headers ?: [];
        }

        return [];
    }
}
