<?php

namespace App\Entity;

use App\Repository\ChampsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChampsRepository::class)]
class Champs
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column]
    private array $zone = [];

    #[ORM\ManyToOne(inversedBy: 'champs')]
    private ?typeChamps $typeChamps = null;

    #[ORM\ManyToOne(inversedBy: 'champs')]
    private ?typeLivrable $typeLivrable = null;

    /**
     * @var Collection<int, Controle>
     */
    #[ORM\OneToMany(targetEntity: Controle::class, mappedBy: 'champs')]
    private Collection $controles;

    #[ORM\Column]
    private ?int $page = null;

    public function __construct()
    {
        $this->controles = new ArrayCollection();
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

    public function getZone(): array
    {
        return $this->zone;
    }

    public function setZone(array $zone): static
    {
        $this->zone = $zone;

        return $this;
    }

    public function getTypeChamps(): ?typeChamps
    {
        return $this->typeChamps;
    }

    public function setTypeChamps(?typeChamps $typeChamps): static
    {
        $this->typeChamps = $typeChamps;

        return $this;
    }

    public function getTypeLivrable(): ?typeLivrable
    {
        return $this->typeLivrable;
    }

    public function setTypeLivrable(?typeLivrable $typeLivrable): static
    {
        $this->typeLivrable = $typeLivrable;

        return $this;
    }

    /**
     * @return Collection<int, Controle>
     */
    public function getControles(): Collection
    {
        return $this->controles;
    }

    public function addControle(Controle $controle): static
    {
        if (!$this->controles->contains($controle)) {
            $this->controles->add($controle);
            $controle->setChamps($this);
        }

        return $this;
    }

    public function removeControle(Controle $controle): static
    {
        if ($this->controles->removeElement($controle)) {
            // set the owning side to null (unless already changed)
            if ($controle->getChamps() === $this) {
                $controle->setChamps(null);
            }
        }

        return $this;
    }

    public function getPage(): ?int
    {
        return $this->page;
    }

    public function setPage(int $page): static
    {
        $this->page = $page;

        return $this;
    }
}
