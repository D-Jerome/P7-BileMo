<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Request\DTO\PaginationDTO;
use App\Request\DTO\UserDTO;
use App\Service\CacheService;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
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
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Return list of users',

        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: User::class, groups: ['userList']))
        )
    )]
    #[OA\Response(
        response: Response::HTTP_UNAUTHORIZED,
        description: 'JWT Token not found'
    )]
    #[OA\Response(
        response: Response::HTTP_BAD_REQUEST,
        description: 'Error in query'
    )]
    #[OA\Parameter(
        name: 'page',
        in: 'query',
        description: 'Page to reach',
        schema: new OA\Schema(type: 'int')
    )]
    #[OA\Parameter(
        name: 'limit',
        in: 'query',
        description: 'Number of items by page',
        schema: new OA\Schema(type: 'int')
    )]
    #[OA\Tag(name: 'Users')]
    public function collection(
        #[CurrentUser] User $connectedUser,
        UserRepository $userRepository,
        CacheService $cacheService
    ): JsonResponse {
        $context = SerializationContext::create()->setGroups(['userList', 'customerList']);
        if ($connectedUser->getRoles() === ['ROLE_ADMIN']) {
            $jsonUserList = $cacheService->getAllData('user', $userRepository, $context );
        } else {
            Assert::notNull($connectedUser->getCustomer());

            $jsonUserList = $cacheService->getAllDataByCustomer('user', $userRepository, $context, $connectedUser);
        }

        return new JsonResponse($jsonUserList, JsonResponse::HTTP_OK, [], true);
    }

    /**
     * Get One User by Id.
     */
    #[Route('/{id}', name: 'app_users_item_get', methods: ['GET'])]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Return User details',

        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: User::class, groups: ['userDetail']))
        )
    )]
    #[OA\Response(
        response: Response::HTTP_UNAUTHORIZED,
        description: 'JWT Token not found'
    )]
    #[OA\Response(
        response: Response::HTTP_NOT_FOUND,
        description: 'no ressource found.'
    )]
    #[OA\Tag(name: 'Users')]
    public function item(
        #[CurrentUser] User $connectedUser,
        #[MapEntity(id: 'id')] User $user,
        CacheService $cacheService
    ): JsonResponse {
        $context = SerializationContext::create()->setGroups(['userDetail', 'customerList']);
        $jsonUser = $cacheService->getUniqueData('user', $user, $context);

        if ($connectedUser->getRoles() === ['ROLE_ADMIN']) {
            return new JsonResponse(
                $jsonUser,
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
            $jsonUser,
            JsonResponse::HTTP_OK,
            [],
            true
        );
    }

    /**
     * Create a new User.
     */
    #[Route(name: 'app_users_collection_post', methods: ['POST'])]
    #[OA\Response(
        response: Response::HTTP_CREATED,
        description: 'Return user created',

        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: User::class, groups: ['userDetail']))
        )
    )]
    #[OA\Response(
        response: Response::HTTP_UNAUTHORIZED,
        description: 'JWT Token not found'
    )]
    #[OA\Response(
        response: Response::HTTP_BAD_REQUEST,
        description: 'Error in query'
    )]
    #[OA\Tag(name: 'Users')]
    public function post(
        #[MapRequestPayload()] UserDTO $userDTO,
        #[CurrentUser] User $connectedUser,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $userPasswordHasher,
        CacheService $cacheService
    ): JsonResponse {
        $context = SerializationContext::create()->setGroups(['userDetail']);
        // /** @var User $user */
        // $user = $serializer->deserialize($request->getContent(), User::class, 'json');

        $errors = $validator->validate($userDTO);
        if ($errors->count() > 0) {
            return new JsonResponse(
                $serializer->serialize($errors, 'json', $context),
                JsonResponse::HTTP_BAD_REQUEST
            );
        }
        $user = new User();
        if (!$userDTO->username || !$userDTO->email || !$userDTO->password) {
            return new JsonResponse(
                $serializer->serialize($errors, 'json', $context),
                JsonResponse::HTTP_BAD_REQUEST
            );
        }
        $user->setUsername($userDTO->username);
        $user->setEmail($userDTO->email);
        $user->setPassword(
            $userPasswordHasher->hashPassword($user, $userDTO->password)
        );
        $user->setCustomer($connectedUser->getCustomer());

        $em->persist($user);

        $errors = $validator->validate($user);
        if ($errors->count() > 0) {
            return new JsonResponse(
                $serializer->serialize($errors, 'json', $context),
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        $em->flush();
        $cacheService->invalidate(['UserCache']);

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
    #[OA\Response(
        response: Response::HTTP_NO_CONTENT,
        description: 'No Return',

        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: User::class, groups: ['userDetail']))
        )
    )]
    #[OA\Response(
        response: Response::HTTP_UNAUTHORIZED,
        description: 'JWT Token not found'
    )]
    #[OA\Response(
        response: Response::HTTP_BAD_REQUEST,
        description: 'Error in query'
    )]
    #[OA\Tag(name: 'Users')]
    public function put(
        #[CurrentUser] User $connectedUser,
        #[MapEntity(id: 'id')] User $currentUser,
        #[MapRequestPayload()] UserDTO $userDTO,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $userPasswordHasher,
        CacheService $cacheService
    ): JsonResponse {
        if ($connectedUser->getCustomer() !== $currentUser->getCustomer()) {
            return new JsonResponse(
                null,
                JsonResponse::HTTP_UNAUTHORIZED
            );
        }
        // /** @var User $newUser */
        // $newUser = $serializer->deserialize(
        //     $request->getContent(),
        //     User::class,
        //     'json'
        // );
        if (null !== $userDTO->username) {
            $currentUser->setUsername($userDTO->username);
        }
        if (null !== $userDTO->email) {
            $currentUser->setEmail($userDTO->email);
        }
        if (null !== $userDTO->roles) {
            $currentUser->setRoles([$userDTO->roles]);
        }
        $errors = $validator->validate($currentUser);
        if ($errors->count() > 0) {
            return new JsonResponse(
                $serializer->serialize($errors, 'json'),
                JsonResponse::HTTP_BAD_REQUEST
            );
        }
        if (null !== $userDTO->password) {
            $currentUser->setPassword(
                $userPasswordHasher->hashPassword(
                    $currentUser,
                    $userDTO->password
                )
            );
        }
        $em->flush();
        $cacheService->invalidate(['UserCache']);

        return new JsonResponse(
            null,
            JsonResponse::HTTP_NO_CONTENT
        );
    }

    /**
     * Delete One User by Id.
     */
    #[Route('/{id}', name: 'app_users_item_delete', methods: ['DELETE'])]
    #[OA\Response(
        response: Response::HTTP_NO_CONTENT,
        description: 'No Return',

        content: null
    )]
    #[OA\Response(
        response: Response::HTTP_UNAUTHORIZED,
        description: 'JWT Token not found'
    )]
    #[OA\Response(
        response: Response::HTTP_NOT_FOUND,
        description: 'no ressource found.'
    )]
    #[OA\Tag(name: 'Users')]
    public function delete(
        #[CurrentUser] User $connectedUser,
        #[MapEntity(id: 'id')] User $user,
        EntityManagerInterface $em,
        CacheService $cacheService
    ): JsonResponse {
        if ($connectedUser->getCustomer() !== $user->getCustomer()) {
            return new JsonResponse(
                null,
                JsonResponse::HTTP_UNAUTHORIZED
            );
        }

        $em->remove($user);
        $em->flush();
        $cacheService->invalidate(['UserCache']);

        return new JsonResponse(
            null,
            JsonResponse::HTTP_NO_CONTENT
        );
    }
}
