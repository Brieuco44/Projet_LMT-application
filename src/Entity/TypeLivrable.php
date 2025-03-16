<?php

namespace App\Entity;

use App\Repository\TypeLivrableRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TypeLivrableRepository::class)]
class TypeLivrable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $path = null;

    /**
     * @var Collection<int, Champs>
     */
    #[ORM\OneToMany(targetEntity: Champs::class, mappedBy: 'typeLivrable')]
    private Collection $champs;

    /**
     * @var Collection<int, Document>
     */
    #[ORM\OneToMany(targetEntity: Document::class, mappedBy: 'typeLivrable')]
    private Collection $documents;

    public function __construct()
    {
        $this->champs = new ArrayCollection();
        $this->documents = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @return Collection<int, Champs>
     */
    public function getChamps(): Collection
    {
        return $this->champs;
    }

    public function addChamp(Champs $champ): static
    {
        if (!$this->champs->contains($champ)) {
            $this->champs->add($champ);
            $champ->setTypeLivrable($this);
        }

        return $this;
    }

    public function removeChamp(Champs $champ): static
    {
        if ($this->champs->removeElement($champ)) {
            // set the owning side to null (unless already changed)
            if ($champ->getTypeLivrable() === $this) {
                $champ->setTypeLivrable(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Document>
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(Document $document): static
    {
        if (!$this->documents->contains($document)) {
            $this->documents->add($document);
            $document->setTypeLivrable($this);
        }

        return $this;
    }

    public function removeDocument(Document $document): static
    {
        if ($this->documents->removeElement($document)) {
            // set the owning side to null (unless already changed)
            if ($document->getTypeLivrable() === $this) {
                $document->setTypeLivrable(null);
            }
        }

        return $this;
    }
}
