<?php
namespace App\Entity;

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ProductRepository")
 * @ORM\Table(
 *     name="symfony_products",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="uniq_slug_softdelete", columns={"slug", "deleted_at"})
 *     }
 * )
 */
class Product
{
    public const NUM_ITEMS = 8;
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     */
    private $name;

    /**
     * @ORM\Column(type="text")
     */
    private $description;

    /**
     * @ORM\Column(type="integer")
     */
    private $stock;

    /**
     * @ORM\Column(type="decimal", scale=2)
     */
    private $price;


    /**
     * @ORM\Column(type="decimal", scale=3)
     */
    private $weight;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Image", mappedBy="product", cascade={"persist"})
     */
    private $images;


    private $quantity;

    /**
     * @ORM\Column(type="datetime")
     */
    private $dateCreated;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $deletedAt;

    /**
     * @ORM\Column(type="string", length=191)
     */
    private $slug;

    /**
     * @var Comment[]|ArrayCollection
     *
     * @ORM\OneToMany(
     *      targetEntity="Comment",
     *      mappedBy="product",
     *      orphanRemoval=true,
     *      cascade={"persist"}
     * )
     * @ORM\OrderBy({"publishedAt": "DESC"})
     */
    private $comments;

    /**
     * @var Tag[]|ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\Tag", cascade={"persist"})
     * @ORM\JoinTable(name="symfony_product_tag")
     * @ORM\OrderBy({"name": "ASC"})
     * @Assert\Count(max="4", maxMessage="product.too_many_tags")
     */
    private $tags;

    /**
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="products")
     */
    private $category;


    public function __construct()
    {
        $this->images = new ArrayCollection();
        $this->dateCreated = new \DateTime();
        $this->comments = new ArrayCollection();
        $this->tags = new ArrayCollection();
        // Null values are ignored by unique constraints
        $this->deletedAt = date_create('0000-00-00 00:00:00');
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('name', new Assert\Type('string'));
        $metadata->addPropertyConstraint('name', new Assert\NotNull());

        $metadata->addPropertyConstraint('description', new Assert\Type('string'));
        $metadata->addPropertyConstraint('description', new Assert\NotNull());

        $metadata->addPropertyConstraint('stock', new Assert\Type('int'));
        $metadata->addPropertyConstraint('stock', new Assert\NotNull());

        $metadata->addPropertyConstraint('price', new Assert\NotNull());

        $metadata->addPropertyConstraint('weight', new Assert\NotNull());

        $metadata->addPropertyConstraint('images', new Assert\Count([
            'min' => 1,
            'max' => 3,
            'minMessage' => 'Chaque produit doit avoir au moins une image',
            'maxMessage' => 'Un produit ne peut pas avoir plus de trois images'
        ]));
        $metadata->addPropertyConstraint('images', new Assert\Valid());
    }

    public function setId(int $id)
    {
        $this->id = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }


    public function getWeight(): ?float
    {
        return $this->weight;
    }

    public function getImages()
    {
        return $this->images;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }


    public function setStock($stock)
    {
        $this->stock = $stock;
    }

    public function setPrice($price)
    {
        $this->price = $price;
    }

    public function setWeight($weight)
    {
        $this->weight = $weight;
    }

    public function setImages($images)
    {
        $this->images = $images;
    }

    public function hasStock(): bool
    {
        return $this->stock > 0;
    }

    public function calcTotalPrice(): float
    {
        return $this->quantity * $this->price;
    }

    public function addImage(Image $image)
    {
        $image->setProduct($this);

        $this->images->add($image);
    }

    public function removeImage(Image $image)
    {
        $this->images->remove($image);
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getDateCreated(): ?\DateTimeInterface
    {
        return $this->dateCreated;
    }

    public function setDateCreated(\DateTimeInterface $dateCreated): self
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeInterface $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): void
    {
        $comment->setProduct($this);
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
        }
    }

    public function removeComment(Comment $comment): void
    {
        $this->comments->removeElement($comment);
    }

    public function addTag(Tag ...$tags): void
    {
        foreach ($tags as $tag) {
            if (!$this->tags->contains($tag)) {
                $this->tags->add($tag);
            }
        }
    }

    public function removeTag(Tag $tag): void
    {
        $this->tags->removeElement($tag);
    }

    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function getProduct(): ?Product
      {
          return $this->product;
      }

    public function setProduct(Product $product): void
      {
            $this->product = $product;
      }

      /**
       * Get category
       *
       */
      public function getCategory()
      {
          return $this->category;
      }

      /**
       * Set category
       *
       * @param Category $category
       */
      public function setCategory($category)
      {
          $this->category = $category;
      }

}
