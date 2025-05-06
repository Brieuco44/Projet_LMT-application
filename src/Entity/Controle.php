<?php

namespace App\Entity;

use App\Entity\Champs;
use App\Entity\Statut;
use App\Entity\Document;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\ControleRepository;

#[ORM\Entity(repositoryClass: ControleRepository::class)]
class Controle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'controles')]
    private ?Document $document = null;

    #[ORM\ManyToOne(inversedBy: 'controles')]
    private ?Champs $champs = null;

    #[ORM\ManyToOne(inversedBy: 'controless')]
    private ?Statut $statut = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDocument(): ?Document
    {
        return $this->document;
    }

    public function setDocument(?Document $document): static
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

    public function getStatut(): ?Statut
    {
        return $this->statut;
    }

    public function setStatut(?Statut $statut): static
    {
        $this->statut = $statut;

        return $this;
    }
}
