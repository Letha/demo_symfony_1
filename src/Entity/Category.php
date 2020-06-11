<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CategoryRepository::class)
 * @UniqueEntity("eId")
 */
class Category implements EntityInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * 
     * @Assert\Type(type="string", message="Category title must be of {{ type }} type.")
     * @Assert\Length(
     *     min=3,
     *     max=12,
     *     allowEmptyString=false,
     *     maxMessage="Category title must have {{ limit }} characters or less.",
     *     minMessage="Category title must have {{ limit }} characters or more."
     * )
     * @Assert\NotNull(message="Category title must not be null.")
     */
    private $title;

    /**
     * @ORM\ManyToMany(targetEntity=Product::class, mappedBy="categories")
     */
    private $products;

    /**
     * @ORM\Column(type="integer", unique=true, nullable=true)
     * @Assert\Type(type="int", groups={"Default", "eId"}, message="Category eId must be of {{ type }} type.")
     */
    private $eId;

    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    /**
     * @return Collection|Product[]
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(Product $product): self
    {
        if (!$this->products->contains($product)) {
            $this->products[] = $product;
            $product->addCategory($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): self
    {
        if ($this->products->contains($product)) {
            $this->products->removeElement($product);
            $product->removeCategory($this);
        }

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
