<?php

namespace App\Entity;

use App\Repository\ZoneRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ZoneRepository::class)]
class Zone
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    #[ORM\Column]
    private array $coordonees = [];

    #[ORM\Column]
    private ?int $page = null;

    #[ORM\ManyToOne(inversedBy: 'zones')]
    private ?TypeLivrable $typeLivrable = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getCoordonees(): array
    {
        return $this->coordonees;
    }

    public function setCoordonees(array $coordonees): static
    {
        $this->coordonees = $coordonees;

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

    public function getTypeLivrable(): ?TypeLivrable
    {
        return $this->typeLivrable;
    }

    public function setTypeLivrable(?TypeLivrable $typeLivrable): static
    {
        $this->typeLivrable = $typeLivrable;

        return $this;
    }
}
