<?php

namespace App\Entity;

use App\Repository\TeamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeamRepository::class)]
#[ORM\Table(name: 'team')]
class Team
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 3)]
    private ?string $code = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $flag = null;

    #[ORM\Column(length: 1)]
    private ?string $groupName = null;

    #[ORM\OneToMany(mappedBy: 'homeTeam', targetEntity: Game::class)]
    private Collection $homeGames;

    #[ORM\OneToMany(mappedBy: 'awayTeam', targetEntity: Game::class)]
    private Collection $awayGames;

    public function __construct()
    {
        $this->homeGames = new ArrayCollection();
        $this->awayGames = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
        return $this;
    }

    public function getFlag(): ?string
    {
        return $this->flag;
    }

    public function setFlag(?string $flag): static
    {
        $this->flag = $flag;
        return $this;
    }

    public function getGroupName(): ?string
    {
        return $this->groupName;
    }

    public function setGroupName(string $groupName): static
    {
        $this->groupName = $groupName;
        return $this;
    }

    /**
     * @return Collection<int, Game>
     */
    public function getHomeGames(): Collection
    {
        return $this->homeGames;
    }

    /**
     * @return Collection<int, Game>
     */
    public function getAwayGames(): Collection
    {
        return $this->awayGames;
    }
}
