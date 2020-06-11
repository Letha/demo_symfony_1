<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ProductRepository::class)
 * @UniqueEntity("eId")
 */
class Product implements EntityInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * 
     * @Assert\Type(
     *     groups={"id"},
     *     type="int",
     *     message="Product id must be of {{ type }} type."
     * )
     * @Assert\NotNull(
     *     groups={"id"}, 
     *     message="Product id must not be null."
     * )
     */
    private $id;

    /**
     * @ORM\ManyToMany(targetEntity=Category::class, inversedBy="products")
     */
    private $categories;

    /**
     * @ORM\Column(type="string", length=255)
     * 
     * @Assert\Type(
     *     groups={"Default", "title"},
     *     type="string", 
     *     message="Product title must be of {{ type }} type."
     * )
     * @Assert\Length(
     *     groups={"Default", "title"},
     *     min=3,
     *     max=12,
     *     allowEmptyString=false,
     *     maxMessage="Product title must have {{ limit }} characters or less.",
     *     minMessage="Product title must have {{ limit }} characters or more."
     * )
     * @Assert\NotNull(
     *     groups={"Default", "title"}, 
     *     message="Product title must not be null."
     * )
     */
    private $title;

    /**
     * @ORM\Column(type="float")
     * 
     * @Assert\Type(
     *     groups={"Default", "price"},
     *     type="float",
     *     message="Product price must be of {{ type }} type."
     * )
     * @Assert\Range(
     *     groups={"Default", "price"},
     *     min=0,
     *     max=200,
     *     notInRangeMessage="Product price must be between {{ min }} and {{ max }}.",
     *     invalidMessage="Product price must be a valid number."
     * )
     * @Assert\NotNull(
     *     groups={"Default", "price"},
     *     message="Product price must not be null."
     * )
     */
    private $price;

    /**
     * @ORM\Column(type="integer", unique=true, nullable=true)
     * @Assert\Type(
     *     groups={"Default", "eId"},
     *     type="int",
     *     message="Product eId must be of {{ type }} type."
     * )
     */
    private $eId;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId($id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return Collection|Category[]
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): self
    {
        if (!$this->categories->contains($category)) {
            $this->categories[] = $category;
        }

        return $this;
    }

    public function removeCategory(Category $category): self
    {
        if ($this->categories->contains($category)) {
            $this->categories->removeElement($category);
        }

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle($title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice($price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getEId(): ?int
    {
        return $this->eId;
    }

    public function setEId($eId): self
    {
        $this->eId = $eId;

        return $this;
    }
}
