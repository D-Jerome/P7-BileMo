<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Hateoas\Configuration\Annotation as Hateoas;
use JMS\Serializer\Annotation\Groups;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Hateoas\Relation(
 *      name= "ProductsDetail",
 *      href = @Hateoas\Route(
 *          "app_products_item_get",
 *          parameters = { "id" = "expr(object.getId())" },
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups="productList"),
 * )
 * @Hateoas\Relation(
 *      "ProductsList",
 *      href = @Hateoas\Route(
 *          "app_products_collection_get",
 *          parameters = {  }
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups="productDetail"),
 * )
 * @Hateoas\Relation(
 *      "delete",
 *      href = @Hateoas\Route(
 *          "app_products_item_delete",
 *          parameters = { "id" = "expr(object.getId())" },
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups="productDetail", excludeIf = "expr(not is_granted('ROLE_ADMIN'))"),
 * )
 * @Hateoas\Relation(
 *      "update",
 *      href = @Hateoas\Route(
 *          "app_products_item_put",
 *          parameters = { "id" = "expr(object.getId())" },
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups="productDetail", excludeIf = "expr(not is_granted('ROLE_ADMIN'))"),
 * )
 */
#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[UniqueEntity(
    fields: ['reference'],
    message: 'ce produit existe déjà',
)]
class Product
{
    /**
     * [Description for $id].
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    #[Groups(['productList', 'productDetail'])]
    private ?int $id = null;

    /**
     * [Description for $brand].
     */
    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['productList', 'productDetail'])]
    #[Assert\NotBlank()]
    #[Assert\Length(max: 255)]
    private ?string $brand = null;

    /**
     * [Description for $name].
     */
    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['productList', 'productDetail'])]
    #[Assert\NotBlank()]
    #[Assert\Length(max: 255)]
    private ?string $name = null;

    /**
     * [Description for $description].
     */
    #[Groups(['productDetail'])]
    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank()]
    private ?string $description = null;

    /**
     * [Description for $reference].
     */
    #[Groups(['productDetail'])]
    #[ORM\Column(type: 'string', unique: true)]
    #[Assert\NotBlank()]
    #[Assert\Length(max: 255)]
    private ?string $reference = null;

    /**
     * [Description for $createdAt].
     */
    #[Groups(['productDetail'])]
    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    /**
     * Get the value of id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the value of brand.
     */
    public function getBrand(): ?string
    {
        return $this->brand;
    }

    /**
     * Set the value of brand.
     */
    public function setBrand(?string $brand): self
    {
        $this->brand = $brand;

        return $this;
    }

    /**
     * Get the value of name.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set the value of name.
     */
    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the value of description.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set the value of description.
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get the value of reference.
     */
    public function getReference(): ?string
    {
        return $this->reference;
    }

    /**
     * Set the value of reference.
     */
    public function setReference(?string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }

    /**
     * Get the value of createdAt.
     */
    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * Set the value of createdAt.
     */
    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
