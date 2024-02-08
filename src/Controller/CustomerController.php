<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\User;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
            items: new OA\Items(ref: new Model(type: Customer::class, groups: ['get']))
        )
    )
    ]
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
        Request $request,
        CustomerRepository $customerRepository,
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
            $repo = $customerRepository->findAllWithPagination($page, $limit);
        } else {
            $repo = $customerRepository->findBy(['id' => $connectedUser->getCustomer()]);
        }
        $context = SerializationContext::create()->setGroups(['get']);

        return new JsonResponse(
            $serializer->serialize($repo, 'json', $context),
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
            items: new OA\Items(ref: new Model(type: Customer::class, groups: ['get']))
        )
    )]
    #[OA\Tag(name: 'Customers')]
    public function item(
        Customer $customer,
        SerializerInterface $serializer
    ): JsonResponse {
        /**
         * @var User $connectedUser
         */
        $connectedUser = $this->getUser();
        $context = SerializationContext::create()->setGroups(['get']);
        if ($connectedUser->getRoles() === ['ROLE_ADMIN']) {
            return new JsonResponse(
                $serializer->serialize($customer, 'json', $context),
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

        return new JsonResponse(
            $serializer->serialize($customer, 'json', $context),
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
            items: new OA\Items(ref: new Model(type: Customer::class, groups: ['get']))
        )
    )]
    #[OA\Tag(name: 'Customers')]
    public function post(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator
    ): JsonResponse {
        /** @var Customer $customer */
        $customer = $serializer->deserialize($request->getContent(), Customer::class, 'json');
        $context = SerializationContext::create()->setGroups(['get']);
        $errors = $validator->validate($customer);
        if ($errors->count() > 0) {
            return new JsonResponse(
                $serializer->serialize($errors, 'json'),
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        $em->persist($customer);

        $errors = $validator->validate($customer);
        if ($errors->count() > 0) {
            return new JsonResponse(
                $serializer->serialize($errors, 'json'),
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        $em->flush();

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
            items: new OA\Items(ref: new Model(type: Customer::class, groups: ['get']))
        )
    )]
    #[OA\Tag(name: 'Customers')]
    public function put(
        Customer $currentCustomer,
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse {
        /** @var Customer $newCustomer */
        $newCustomer = $serializer->deserialize(
            $request->getContent(),
            Customer::class,
            'json'
        );

        $currentCustomer->setName($newCustomer->getName());

        $errors = $validator->validate($currentCustomer);
        if ($errors->count() > 0) {
            return new JsonResponse(
                $serializer->serialize($errors, 'json'),
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        $em->flush();

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
            items: new OA\Items(ref: new Model(type: Customer::class, groups: ['get']))
        )
    )]
    #[OA\Tag(name: 'Customers')]
    public function delete(
        Customer $customer,
        EntityManagerInterface $em
    ): JsonResponse {
        $em->remove($customer);
        $em->flush();

        return new JsonResponse(
            null,
            JsonResponse::HTTP_NO_CONTENT
        );
    }
}
