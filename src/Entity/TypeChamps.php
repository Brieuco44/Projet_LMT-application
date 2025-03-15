<?php

namespace App\Entity;

use App\Repository\TypeChampsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TypeChampsRepository::class)]
class TypeChamps
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    /**
     * @var Collection<int, Champs>
     */
    #[ORM\OneToMany(targetEntity: Champs::class, mappedBy: 'typeChamps')]
    private Collection $champs;

    public function __construct()
    {
        $this->champs = new ArrayCollection();
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
            $champ->setTypeChamps($this);
        }

        return $this;
    }

    public function removeChamp(Champs $champ): static
    {
        if ($this->champs->removeElement($champ)) {
            // set the owning side to null (unless already changed)
            if ($champ->getTypeChamps() === $this) {
                $champ->setTypeChamps(null);
            }
        }

        return $this;
    }
}
