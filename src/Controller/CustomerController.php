<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\User;
use App\Repository\CustomerRepository;
use App\Request\DTO\CustomerDTO;
use App\Request\DTO\PaginationDTO;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Webmozart\Assert\Assert;

/**
 * class CustomerController.
 */
#[Route('/api/customers')]
class CustomerController extends AbstractController
{
    /**
     * Get all Customers.
     */
    #[Route(name: 'app_customers_collection_get', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Return list of customers',

        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Customer::class, groups: ['customerList']))
        )
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
    #[OA\Tag(name: 'Customers')]
    #[IsGranted('ROLE_COMPANY_ADMIN', message: 'You are not allowed to access')]
    public function collection(
        #[MapRequestPayload()] PaginationDTO $paginationDTO,
        #[CurrentUser] User $connectedUser,
        CustomerRepository $customerRepository,
        SerializerInterface $serializer,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        $context = SerializationContext::create()->setGroups(['customerList']);
        if ($connectedUser->getRoles() === ['ROLE_ADMIN']) {
            $idCache = 'getAllCustomers-'.(string) $paginationDTO->page.'-'.(string) $paginationDTO->limit;

            $jsonCustomerList = $cache->get($idCache, function (ItemInterface $item) use ($customerRepository, $paginationDTO, $serializer, $context) {
                $item->tag('customerCache');
                $item->expiresAfter(15);
                $customerList = $customerRepository->findAllWithPagination($paginationDTO->page, $paginationDTO->limit);

                return $serializer->serialize($customerList, 'json', $context);
            });
        } else {
            Assert::notNull($connectedUser->getCustomer());
            $idCache = 'getCustomer-'.$connectedUser->getCustomer()->getId().'-'.(string) $paginationDTO->page.'-'.(string) $paginationDTO->limit;

            $jsonCustomerList = $cache->get($idCache, function (ItemInterface $item) use ($customerRepository, $connectedUser, $serializer, $context) {
                $item->tag('customerCache');
                $item->expiresAfter(15);
                $customerList = $customerRepository->findBy(['id' => $connectedUser->getCustomer()]);

                return $serializer->serialize($customerList, 'json', $context);
            });
        }

        return new JsonResponse(
            $jsonCustomerList,
            JsonResponse::HTTP_OK,
            [],
            true
        );
    }

    /**
     * Get One Customer by Id.
     */
    #[Route('/{id}', name: 'app_customers_item_get', methods: ['GET'])]
    #[IsGranted('ROLE_COMPANY_ADMIN', message: 'You are not allowed to access')]
    #[OA\Response(
        response: 200,
        description: 'Customer details',

        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Customer::class, groups: ['customerDetail']))
        )
    )]
    #[OA\Tag(name: 'Customers')]
    public function item(
        #[CurrentUser] User $connectedUser,
        Customer $customer,
        SerializerInterface $serializer,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        $context = SerializationContext::create()->setGroups(['customerDetail', 'userList']);
        if ($connectedUser->getRoles() === ['ROLE_ADMIN']) {
            $idCache = 'getCustomer-'.$customer->getId();

            $jsonCustomerList = $cache->get($idCache, function (ItemInterface $item) use ($customer, $serializer, $context) {
                $item->tag('customerCache');
                $item->expiresAfter(15);

                return $serializer->serialize($customer, 'json', $context);
            });

            return new JsonResponse(
                $jsonCustomerList,
                JsonResponse::HTTP_OK,
                [],
                true
            );
        }

        if ($connectedUser->getCustomer() !== $customer) {
            return new JsonResponse(
                null,
                JsonResponse::HTTP_UNAUTHORIZED
            );
        }

        $idCache = 'getCustomer-'.$customer->getId();

        $jsonCustomerList = $cache->get($idCache, function (ItemInterface $item) use ($customer, $serializer, $context) {
            $item->tag('customerCache');
            $item->expiresAfter(15);

            return $serializer->serialize($customer, 'json', $context);
        });

        return new JsonResponse(
            $jsonCustomerList,
            JsonResponse::HTTP_OK,
            [],
            true
        );
    }

    /**
     * Create a new Customer.
     */
    #[Route(name: 'app_customers_collection_post', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'You are not allowed to access')]
    #[OA\Response(
        response: 201,
        description: 'Return customer created',

        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Customer::class, groups: ['customerDetail']))
        )
    )]
    #[OA\Tag(name: 'Customers')]
    public function post(
        #[MapRequestPayload()] CustomerDTO $customerDTO,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        $context = SerializationContext::create()->setGroups(['customerDetail']);
        $errors = $validator->validate($customerDTO);
        if ($errors->count() > 0) {
            return new JsonResponse(
                $serializer->serialize($errors, 'json'),
                JsonResponse::HTTP_BAD_REQUEST
            );
        }
        $customer = new Customer();
        $customer->setName($customerDTO->name);

        $em->persist($customer);

        $errors = $validator->validate($customer);
        if ($errors->count() > 0) {
            return new JsonResponse(
                $serializer->serialize($errors, 'json'),
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        $em->flush();
        $cache->invalidateTags(['CustomerCache']);

        return new JsonResponse(
            $serializer->serialize($customer, 'json', $context),
            JsonResponse::HTTP_CREATED,
            ['Location' => $urlGenerator->generate(
                'app_customers_item_get',
                ['id' => $customer->getId()]
            )],
            true
        );
    }

    /**
     * Update Customer.
     */
    #[Route('/{id}', name: 'app_customers_item_put', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'You are not allowed to access')]
    #[OA\Response(
        response: 204,
        description: 'No return',

        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Customer::class, groups: ['customerDetail']))
        )
    )]
    #[OA\Tag(name: 'Customers')]
    public function put(
        #[MapEntity(id: 'id')] Customer $currentCustomer,
        #[MapRequestPayload()] CustomerDTO $customerDTO,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        $currentCustomer->setName($customerDTO->name);

        $errors = $validator->validate($currentCustomer);
        if ($errors->count() > 0) {
            return new JsonResponse(
                $serializer->serialize($errors, 'json'),
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        $em->flush();
        $cache->invalidateTags(['CustomerCache']);

        return new JsonResponse(
            null,
            JsonResponse::HTTP_NO_CONTENT
        );
    }

    /**
     * Delete One Customer by Id.
     */
    #[Route('/{id}', name: 'app_customers_item_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'You are not allowed to access')]
    #[OA\Response(
        response: 204,
        description: 'No Return',

        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Customer::class, groups: ['customerDetail']))
        )
    )]
    #[OA\Tag(name: 'Customers')]
    public function delete(
        #[MapEntity(id: 'id')] Customer $customer,
        EntityManagerInterface $em,
        TagAwareCacheInterface $cache
    ): JsonResponse {
        $em->remove($customer);
        $em->flush();
        $cache->invalidateTags(['UserCache', 'CustomerCache']);

        return new JsonResponse(
            null,
            JsonResponse::HTTP_NO_CONTENT
        );
    }
}
