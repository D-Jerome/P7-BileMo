<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Customer;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * [Description AppFixtures].
 */
class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    /**
     * [Description for load].
     */
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        for ($j = 0; $j <= 5; ++$j) {
            $customer = new Customer();
            $customer->setName($faker->company());
            $customerList[] = $customer;
            $manager->persist($customer);
        }

        $user = new User();
        $user->setUsername($faker->userName());
        $user->setPassword($this->userPasswordHasher->hashPassword($user, 'password'));
        $user->setEmail($faker->companyEmail());
        $user->setRoles(['ROLE_ADMIN']);
        $manager->persist($user);

        foreach ($customerList as $customer) {
            $user = new User();
            $user->setUsername($faker->userName());
            $user->setPassword($this->userPasswordHasher->hashPassword($user, 'password'));
            $user->setEmail($faker->companyEmail());
            $user->setRoles(['ROLE_COMPANY_ADMIN']);
            $user->setCustomer($customerList[array_rand($customerList)]);
            $manager->persist($user);
        }

        for ($i = 0; $i <= 10; ++$i) {
            $user = new User();
            $user->setUsername($faker->userName());
            $user->setPassword($this->userPasswordHasher->hashPassword($user, 'password'));
            $user->setEmail($faker->companyEmail());
            $user->setCustomer($customerList[array_rand($customerList)]);
            $manager->persist($user);
        }

        for ($i = 0; $i <= 20; ++$i) {
            $product = new Product();
            $product->setBrand($faker->company());
            $product->setName($faker->text(20));
            $product->setReference($faker->uuid());
            $product->setDescription($faker->realText($faker->numberBetween(200, 300)));
            $manager->persist($product);
        }
        $manager->flush();
    }
}
