<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Webmozart\Assert\Assert;

/**
 * class UserController.
 */
#[Route('/api/users')]
class UserController extends AbstractController
{
    /**
     * Get all Users.
     */
    #[Route(name: 'app_users_collection_get', methods: ['GET'])]
    public function collection(
        Request $request,
        UserRepository $userRepository,
        SerializerInterface $serializer
    ): JsonResponse {
        /**
         * @var User $connectedUser
         */
        $connectedUser = $this->getUser();
        /** @var int $page */
        $page = $request->get('page', 1);
        /** @var int $limit */
        $limit = $request->get('limit', 4);

        if ($connectedUser->getRoles() === ['ROLE_ADMIN']) {
            $repo = $userRepository->findAllWithPagination($page, $limit);
        } else {
            $repo = $userRepository->findByWithPagination(['customer' => $connectedUser->getCustomer()], $page, $limit);
        }
        $context = SerializationContext::create()->setGroups(['get']);

        return new JsonResponse(
            $serializer->serialize(
                $repo,
                'json',
                $context
            ),
            JsonResponse::HTTP_OK,
            [],
            true
        );
    }

    /**
     * Get One User by Id.
     */
    #[Route('/{id}', name: 'app_users_item_get', methods: ['GET'])]
    public function item(
        User $user,
        SerializerInterface $serializer
    ): JsonResponse {
        /**
         * @var User $connectedUser
         */
        $connectedUser = $this->getUser();
        $context = SerializationContext::create()->setGroups(['get']);
        if ($connectedUser->getRoles() === ['ROLE_ADMIN']) {
            return new JsonResponse(
                $serializer->serialize($user, 'json', $context),
                JsonResponse::HTTP_OK,
                [],
                true
            );
        }
        if ($connectedUser->getCustomer() !== $user->getCustomer()) {
            return new JsonResponse(
                null,
                JsonResponse::HTTP_UNAUTHORIZED
            );
        }

        return new JsonResponse(
            $serializer->serialize($user, 'json', $context),
            JsonResponse::HTTP_OK,
            [],
            true
        );
    }

    /**
     * Create a new User.
     */
    #[Route(name: 'app_users_collection_post', methods: ['POST'])]
    public function post(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $userPasswordHasher
    ): JsonResponse {
        /**
         * @var User $connectedUser
         */
        $connectedUser = $this->getUser();
        $context = SerializationContext::create()->setGroups(['get']);
        /** @var User $user */
        $user = $serializer->deserialize($request->getContent(), User::class, 'json');

        $errors = $validator->validate($user);
        if ($errors->count() > 0) {
            return new JsonResponse(
                $serializer->serialize($errors, 'json'),
                JsonResponse::HTTP_BAD_REQUEST
            );
        }
        Assert::notNull($user->getPassword());
        $user->setPassword(
            $userPasswordHasher->hashPassword(
                $user,
                $user->getPassword()
            )
        );
        $user->setCustomer($connectedUser->getCustomer());
        $em->persist($user);

        $errors = $validator->validate($user);
        if ($errors->count() > 0) {
            return new JsonResponse(
                $serializer->serialize($errors, 'json'),
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        $em->flush();

        return new JsonResponse(
            $serializer->serialize(
                $user,
                'json',
                $context
            ),
            JsonResponse::HTTP_CREATED,
            ['Location' => $urlGenerator->generate('app_users_item_get', ['id' => $user->getId()])],
            true
        );
    }

    /**
     * Update User.
     */
    #[Route('/{id}', name: 'app_users_item_put', methods: ['PUT'])]
    public function put(
        User $currentUser,
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $userPasswordHasher
    ): JsonResponse {
        /**
         * @var User $connectedUser
         */
        $connectedUser = $this->getUser();

        if ($connectedUser->getCustomer() !== $currentUser->getCustomer()) {
            return new JsonResponse(
                null,
                JsonResponse::HTTP_UNAUTHORIZED
            );
        }
        /** @var User $newUser */
        $newUser = $serializer->deserialize(
            $request->getContent(),
            User::class,
            'json'
        );

        $currentUser->setUsername($newUser->getUsername());
        $currentUser->setEmail($newUser->getEmail());
        $currentUser->setPassword($newUser->getPassword());
        $currentUser->setRoles($newUser->getRoles());

        $errors = $validator->validate($currentUser);
        if ($errors->count() > 0) {
            return new JsonResponse(
                $serializer->serialize($errors, 'json'),
                JsonResponse::HTTP_BAD_REQUEST
            );
        }
        if ($newUser->getPassword()) {
            $currentUser->setPassword(
                $userPasswordHasher->hashPassword(
                    $currentUser,
                    $newUser->getPassword()
                )
            );
        }
        $em->flush();

        return new JsonResponse(
            null,
            JsonResponse::HTTP_NO_CONTENT
        );
    }

    /**
     * Delete One User by Id.
     */
    #[Route('/{id}', name: 'app_users_item_delete', methods: ['DELETE'])]
    public function delete(
        User $user,
        EntityManagerInterface $em
    ): JsonResponse {
        /**
         * @var User $connectedUser
         */
        $connectedUser = $this->getUser();

        if ($connectedUser->getCustomer() !== $user->getCustomer()) {
            return new JsonResponse(
                null,
                JsonResponse::HTTP_UNAUTHORIZED
            );
        }

        $em->remove($user);
        $em->flush();

        return new JsonResponse(
            null,
            JsonResponse::HTTP_NO_CONTENT
        );
    }
}
