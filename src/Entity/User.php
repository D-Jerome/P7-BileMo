<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Hateoas\Configuration\Annotation as Hateoas;
use JMS\Serializer\Annotation\Groups;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Hateoas\Relation(
 *      "UsersList",
 *      href = @Hateoas\Route(
 *          "app_users_item_get",
 *          parameters = { "id" = "expr(object.getId())" }
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups="get")
 * )
 * @Hateoas\Relation(
 *      "delete",
 *      href = @Hateoas\Route(
 *          "app_users_item_delete",
 *          parameters = { "id" = "expr(object.getId())" },
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups="get", excludeIf = "expr(not is_granted('ROLE_COMPANY_ADMIN'))"),
 * )
 * @Hateoas\Relation(
 *      "update",
 *      href = @Hateoas\Route(
 *          "app_users_item_put",
 *          parameters = { "id" = "expr(object.getId())" },
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups="get", excludeIf = "expr(not is_granted('ROLE_COMPANY_ADMIN'))"),
 * )
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(
    fields: 'username',
    message: 'cet utilisateur existe déjà, merci de changer de username',
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * [Description for $id].
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue()]
    private ?int $id = null;

    /**
     * [Description for $username].
     */
    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Assert\NotBlank()]
    #[Assert\Length(min: 5, minMessage: 'Username {{ value }} est trop court, minimum {{ limit }} caractères requis')]
    private string $username;

    /**
     * [Description for $email].
     */
    #[Groups(['get'])]
    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\Email()]
    private ?string $email = null;

    /**
     * [Description for $password].
     */
    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\PasswordStrength()]
    #[Assert\NotNull()]
    private ?string $password = null;

    /**
     * [Description for $createdAt].
     */
    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['get'])]
    private ?\DateTimeInterface $createdAt = null;

    /**
     * [Description for $roles].
     *
     * @var array<int,string>
     */
    #[ORM\Column(length: 255)]
    private array $roles = ['ROLE_USER'];

    /**
     * [Description for $customer].
     */
    #[ORM\ManyToOne(inversedBy: 'users')]
    #[ORM\JoinColumn()]
    #[Groups(['get'])]
    private ?Customer $customer = null;

    /**
     * [Description for __construct].
     */
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
     * Get the value of email.
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Set the value of email.
     */
    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get the value of password.
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Set the value of password.
     */
    public function setPassword(?string $password): self
    {
        $this->password = $password;

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
    public function setCreatedAt(?\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get the value of customer.
     */
    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * Returns the roles granted to the user.
     *
     *     public function getRoles()
     *     {
     *         return ['ROLE_USER'];
     *     }
     *
     * Alternatively, the roles might be stored in a ``roles`` property,
     * and populated in any number of different ways when the user object
     * is created.
     *
     * @return array<int,string>
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * [Description for setRoles].
     *
     * @param array<int,string> $roles
     */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials(): void
    {
    }

    /**
     * Returns the identifier for this user (e.g. username or email address).
     */
    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    /**
     * Get the value of username.
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Set the value of username.
     */
    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }
}
