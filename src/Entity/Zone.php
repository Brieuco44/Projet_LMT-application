<?php

namespace App\Entity;

use App\Repository\ZoneRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    #[ORM\Column(type: "json")]
    private array $coordonnees = [];

    #[ORM\Column]
    private ?int $page = null;

    #[ORM\ManyToOne(inversedBy: 'zones')]
    private ?TypeLivrable $typeLivrable = null;

    /**
     * @var Collection<int, Champs>
     */
    #[ORM\OneToMany(targetEntity: Champs::class, mappedBy: 'zone')]
    private Collection $champs;

    public function __construct()
    {
        $this->champs = new ArrayCollection();
    }

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

    public function getCoordonnees(): array
    {
        return $this->coordonnees;
    }

    public function setCoordonnees(array $coordonnees): static
    {
        $this->coordonnees = $coordonnees;
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
            $champ->setZone($this);
        }

        return $this;
    }

    public function removeChamp(Champs $champ): static
    {
        if ($this->champs->removeElement($champ)) {
            // set the owning side to null (unless already changed)
            if ($champ->getZone() === $this) {
                $champ->setZone(null);
            }
        }

        return $this;
    }
}
