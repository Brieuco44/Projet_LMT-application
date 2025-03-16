<?php

namespace App\Entity;

use App\Repository\ControleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ControleRepository::class)]
class Controle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?bool $resultat = null;

    #[ORM\ManyToOne(inversedBy: 'controles')]
    private ?document $document = null;

    #[ORM\ManyToOne(inversedBy: 'controles')]
    private ?champs $champs = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isResultat(): ?bool
    {
        return $this->resultat;
    }

    public function setResultat(bool $resultat): static
    {
        $this->resultat = $resultat;

        return $this;
    }

    public function getDocument(): ?document
    {
        return $this->document;
    }

    public function setDocument(?document $document): static
    {
        $this->document = $document;

        return $this;
    }

    public function getChamps(): ?champs
    {
        return $this->champs;
    }

    public function setChamps(?champs $champs): static
    {
        $this->champs = $champs;

        return $this;
    }
}
