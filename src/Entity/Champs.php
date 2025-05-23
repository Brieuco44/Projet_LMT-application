<?php

namespace App\Entity;

use App\Entity\Zone;
use App\Entity\Controle;
use App\Entity\TypeChamps;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\ChampsRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity(repositoryClass: ChampsRepository::class)]
class Champs
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\ManyToOne(inversedBy: 'champs')]
    private ?TypeChamps $typeChamps = null;

    /**
     * @var Collection<int, Controle>
     */
    #[ORM\OneToMany(targetEntity: Controle::class, mappedBy: 'champs', cascade: ['remove'], orphanRemoval: true)]
    private Collection $controles;

    #[ORM\Column(length: 255)]
    private ?string $question = null;

    #[ORM\ManyToOne(inversedBy: 'champs')]
    private ?Zone $zone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $donneeERP = null;

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

    public function getTypeChamps(): ?TypeChamps
    {
        return $this->typeChamps;
    }

    public function setTypeChamps(?TypeChamps $typeChamps): static
    {
        $this->typeChamps = $typeChamps;

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

    public function getQuestion(): ?string
    {
        return $this->question;
    }

    public function setQuestion(string $question): static
    {
        $this->question = $question;

        return $this;
    }

    public function getZone(): ?Zone
    {
        return $this->zone;
    }

    public function setZone(?Zone $zone): static
    {
        $this->zone = $zone;

        return $this;
    }

    public function getDonneeERP(): ?string
    {
        return $this->donneeERP;
    }

    public function setDonneeERP(?string $donneeERP): static
    {
        $this->donneeERP = $donneeERP;

        return $this;
    }
}
