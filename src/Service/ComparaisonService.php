<?php

namespace App\Service;

use App\Entity\Statut;
use App\Entity\Controle;
use App\Entity\TypeLivrable;
use App\Repository\StatutRepository;
use App\Repository\DocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\TypeLivrableRepository;

class ComparaisonService
{
    private string $exportFile = 'uploads/export-erp/extract-projet ocr.csv';
    private Statut $statutDoc;
    private Statut $nonConformeStatut;
    private Statut $reverifierStatut;
    private Statut $conformeStatut;

    /**
     * @param TypeLivrableRepository $TypeLivrableRepo
     * @param DocumentRepository $documentRepo
     */
    public function __construct(private TypeLivrableRepository $TypeLivrableRepo, private DocumentRepository $documentRepo, private EntityManagerInterface $entityManager, private StatutRepository $statutRepo) {
        $this->conformeStatut = $this->statutRepo->getStatutConforme();
        $this->nonConformeStatut = $this->statutRepo->getStatutNonConforme();
        $this->reverifierStatut = $this->statutRepo->getStatutReverifier();
    }

    public function compareDocuments(TypeLivrable $typeLivrable, int $idDocument, array $dataOCR): void
    {
        $statutDoc = $this->statutRepo->getStatutConforme();
        $dataERP = $this->getLigneExport($typeLivrable->getId(), $dataOCR);
        $document = $this->documentRepo->find($idDocument);
        $zones = $typeLivrable->getZones();
        foreach ($zones as $key => $zone) {
            foreach ($zone->getChamps() as $champ) {
                $controle = new Controle();
                $controle->setChamps($champ);
                $controle->setDocument($document);
                if (!array_key_exists($champ->getDonneeERP(), $dataERP) ) {
                    $controle->setStatut($this->statutRepo->getStatutPbParametre());
                } else {
                    switch ($champ->getTypeChamps()->getNom()) {
                        case 'Identifiant':
                        case 'Text':
                            $controle->setStatut($this->verifyText($dataERP[$champ->getDonneeERP()], $dataOCR[$champ->getZone()->getLibelle()][$champ->getNom()]));
                            break;
                        case 'Date':
                        case 'Date manuscrite':
                            $controle->setStatut($this->verifyDate($dataERP[$champ->getDonneeERP()], $dataOCR[$champ->getZone()->getLibelle()][$champ->getNom()]));
                            break;
                        case 'Signature':
                        case 'Case cochée':
                            $controle->setStatut($this->conformeStatut);
                            break;
                    }
                }
                $this->entityManager->persist($controle);
                $this->entityManager->flush();
            }
        }
        $document->setStatut($statutDoc);
        $this->entityManager->persist($document);
        $this->entityManager->flush();
    }

    /**
     * @param string $dataOCR
     * @param string $dataERP
     * @return Statut
     */
    public function verifyDate(string $dataOCR, string $dataERP): Statut
    {
        $statut = null;
        $dateOCR = \DateTime::createFromFormat('d/m/Y', $dataOCR);
        $dateERP = \DateTime::createFromFormat('d/m/Y', $dataERP);
        if ($dateOCR === false || $dateERP === false) {
            // Handle the error if the date format is incorrect
            $statut = $this->reverifierStatut;
            $this->statutDoc = $this->statutDoc === $this->conformeStatut ? $this->reverifierStatut : $this->statutDoc;
            return $statut;
        }
        if ($dateERP == $dateOCR) {
            $statut = $this->conformeStatut;
        } elseif ($dateERP != $dateOCR) {
            $statut = $this->nonConformeStatut;
            $this->statutDoc = $this->statutDoc !== $this->nonConformeStatut ? $this->nonConformeStatut : $this->statutDoc;
        }
        return $statut;
    }

    /**
     * @param string $dataOCR
     * @param string $dataERP
     * @return Statut
     */
    public function verifytext(string $dataOCR, string $dataERP): Statut
    {
        if ($dataERP === $dataOCR) {
            return $this->conformeStatut;
        }

        if ($dataERP !== $dataOCR) {
            $this->statutDoc = $this->statutDoc !== $this->nonConformeStatut ? $this->nonConformeStatut : $this->statutDoc;
            return $this->nonConformeStatut;
        }

        $this->statutDoc = $this->statutDoc === $this->conformeStatut ? $this->reverifierStatut : $this->statutDoc;
        return $this->reverifierStatut;
    }

    /**
     * @param string $dataOCR
     * @return Statut
     */
    public function verifyBoolean(string $dataOCR): Statut
    {
        if ($dataOCR) {
            return $this->conformeStatut;
        }

        $this->statutDoc = $this->statutDoc !== $this->nonConformeStatut ? $this->nonConformeStatut : $this->statutDoc;
        return $dataOCR === '' ? $this->reverifierStatut : $this->nonConformeStatut;
    }

    /**
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
     * Load and return the header list from the export file.
     *
     * @return array|null
     */
    public function getExportHeaders(): ?array
    {
        if (!file_exists($this->exportFile)) {
            return null;
        }

        if (($handle = fopen($this->exportFile, 'r')) !== false) {
            $headers = fgetcsv($handle, 1000, ';');
            fclose($handle);
            return $headers ?: null;
        }

        return null;
    }

}
