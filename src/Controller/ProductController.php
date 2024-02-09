<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
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
 * class ProductController.
 */
#[Route('/api/products')]
class ProductController extends AbstractController
{
    /**
     * Get all Products.
     */
    #[Route(name: 'app_products_collection_get', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Return list of all customers',

        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Product::class, groups: ['get']))
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
    #[OA\Parameter(
        name: 'brand',
        in: 'query',
        description: 'Filter by Brand',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Tag(name: 'Products')]
    public function collection(
        Request $request,
        ProductRepository $productRepository,
        SerializerInterface $serializer
    ): JsonResponse {
        /** @var int $page */
        $page = $request->get('page', 1);
        /** @var int $limit */
        $limit = $request->get('limit', 4);
        /** @var string $brand */
        $brand = $request->get('brand', '');

        if ('' === $brand) {
            $repo = $productRepository->findAllWithPagination($page, $limit);
        } else {
            $repo = $productRepository->findByWithPagination($brand, $page, $limit);
        }
        if ([] === $repo) {
            return new JsonResponse(
                $repo,
                JsonResponse::HTTP_BAD_REQUEST
            );
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
     * Get One Product by Id.
     */
    #[Route('/{id}', name: 'app_products_item_get', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Return product details',

        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Product::class, groups: ['get']))
        )
    )]
    #[OA\Tag(name: 'Products')]
    public function item(Product $product, SerializerInterface $serializer): JsonResponse
    {
        $context = SerializationContext::create()->setGroups(['get']);

        return new JsonResponse(
            $serializer->serialize($product, 'json', $context),
            JsonResponse::HTTP_OK,
            [],
            true
        );
    }

    /**
     * Create a new Product.
     */
    #[Route(name: 'app_products_collection_post', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'You are not allowed to access')]
    #[OA\Response(
        response: 201,
        description: 'Return product created',

        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Product::class, groups: ['get']))
        )
    )]
    #[OA\Tag(name: 'Products')]
    public function post(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator
    ): JsonResponse {
        /** @var Product $product */
        $product = $serializer->deserialize($request->getContent(), Product::class, 'json');
        $context = SerializationContext::create()->setGroups(['get']);
        $errors = $validator->validate($product);
        if ($errors->count() > 0) {
            return new JsonResponse(
                $serializer->serialize($errors, 'json'),
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        $em->persist($product);

        $errors = $validator->validate($product);
        if ($errors->count() > 0) {
            return new JsonResponse(
                $serializer->serialize($errors, 'json'),
                JsonResponse::HTTP_BAD_REQUEST
            );
        }
        $em->flush();

        return new JsonResponse(
            $serializer->serialize($product, 'json', $context),
            JsonResponse::HTTP_CREATED,
            ['Location' => $urlGenerator->generate(
                'app_Products_item_get',
                ['id' => $product->getId()]
            ),
            ],
            true
        );
    }

    /**
     * Update Product.
     */
    #[Route('/{id}', name: 'app_products_item_put', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'You are not allowed to access')]
    #[OA\Response(
        response: 204,
        description: 'No Return',

        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Product::class, groups: ['get']))
        )
    )]
    #[OA\Tag(name: 'Products')]
    public function put(
        Product $currentProduct,
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse {
        /** @var Product $newProduct */
        $newProduct = $serializer->deserialize(
            $request->getContent(),
            Product::class,
            'json'
        );

        $currentProduct->setName($newProduct->getName());
        $currentProduct->setBrand($newProduct->getBrand());
        $currentProduct->setDescription($newProduct->getDescription());
        $currentProduct->setReference($newProduct->getReference());

        $errors = $validator->validate($currentProduct);
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
     * Delete One Product by Id.
     */
    #[Route('/{id}', name: 'app_products_item_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'You are not allowed to access')]
    #[OA\Response(
        response: 204,
        description: 'No Return',

        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Product::class, groups: ['get']))
        )
    )]
    #[OA\Tag(name: 'Products')]
    public function delete(Product $product, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($product);
        $em->flush();

        return new JsonResponse(
            null,
            JsonResponse::HTTP_NO_CONTENT
        );
    }
}
