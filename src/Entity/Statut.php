<?php

namespace App\Entity;

use App\Repository\StatutRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StatutRepository::class)]
class Statut
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $Libelle = null;

    /**
     * @var Collection<int, Document>
     */
    #[ORM\OneToMany(targetEntity: Document::class, mappedBy: 'statut')]
    private Collection $document;

    /**
     * @var Collection<int, Controle>
     */
    #[ORM\OneToMany(targetEntity: Controle::class, mappedBy: 'statut')]
    private Collection $controle;

    public function __construct()
    {
        $this->document = new ArrayCollection();
        $this->controle = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->Libelle;
    }

    public function setLibelle(string $Libelle): static
    {
        $this->Libelle = $Libelle;

        return $this;
    }

    /**
     * @return Collection<int, Document>
     */
    public function getDocument(): Collection
    {
        return $this->document;
    }

    public function addDocument(Document $document): static
    {
        if (!$this->document->contains($document)) {
            $this->document->add($document);
            $document->setStatut($this);
        }

        return $this;
    }

    public function removeDocument(Document $document): static
    {
        if ($this->document->removeElement($document)) {
            // set the owning side to null (unless already changed)
            if ($document->getStatut() === $this) {
                $document->setStatut(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Controle>
     */
    public function getControle(): Collection
    {
        return $this->controle;
    }

    public function addControle(Controle $controle): static
    {
        if (!$this->controle->contains($controle)) {
            $this->controle->add($controle);
            $controle->setStatut($this);
        }

        return $this;
    }

    public function removeControle(Controle $controle): static
    {
        if ($this->controle->removeElement($controle)) {
            // set the owning side to null (unless already changed)
            if ($controle->getStatut() === $this) {
                $controle->setStatut(null);
            }
        }

        return $this;
    }
}
