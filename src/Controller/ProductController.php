<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Product;
use App\Service\CacheService;
use OpenApi\Attributes as OA;
use App\Request\DTO\FilterDTO;
use App\Request\DTO\ProductDTO;
use App\Request\DTO\PaginationDTO;
use App\Repository\ProductRepository;
use JMS\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

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
        response: Response::HTTP_OK,
        description: 'Return list of products',

        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Product::class, groups: ['productList']))
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
    #[OA\Parameter(
        name: 'brand',
        in: 'query',
        description: 'Filter by Brand',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Tag(name: 'Products')]
    public function collection(
        #[MapRequestPayload()] FilterDTO $filterDTO,
        ProductRepository $productRepository,
        CacheService $cacheService
    ): JsonResponse {
        $context = SerializationContext::create()->setGroups(['productList']);

        if (null === $filterDTO->brand) {
            $jsonProductList = $cacheService->getAllData('product', $productRepository, $context );
        } else {
            $jsonProductList = $cacheService->getAllProductBy('product', $productRepository, $context );
        }
        if (2 == strlen($jsonProductList)) {
            return new JsonResponse(
                [],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        return new JsonResponse(
            $jsonProductList,
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
        response: Response::HTTP_OK,
        description: 'Return product details',

        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Product::class, groups: ['ProductDetail']))
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
    #[OA\Tag(name: 'Products')]
    public function item(
        #[MapEntity(id: 'id')] Product $product,
        CacheService $cacheService
    ): JsonResponse {
        $context = SerializationContext::create()->setGroups(['productDetail']);
        $jsonProduct = $cacheService->getUniqueData('product', $product, $context);

        return new JsonResponse(
            $jsonProduct,
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
        response: Response::HTTP_CREATED,
        description: 'Return product created',

        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Product::class, groups: ['productDetail']))
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
    #[OA\Tag(name: 'Products')]
    public function post(
        #[MapRequestPayload()] ProductDTO $productDTO,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator,
        CacheService $cacheService
    ): JsonResponse {
        $context = SerializationContext::create()->setGroups(['productDetail']);

        $product = new Product();
        $product->setBrand($productDTO->brand);
        $product->setDescription($productDTO->description);
        $product->setName($productDTO->name);
        $product->setReference($productDTO->reference);
        $em->persist($product);

        $cacheService->invalidate(['productCache']);
        $errors = $validator->validate($product);
        if ($errors->count() > 0) {
            return new JsonResponse(
                $serializer->serialize($errors, 'json', $context),
                JsonResponse::HTTP_BAD_REQUEST
            );
        }
        $em->flush();

        return new JsonResponse(
            $serializer->serialize($product, 'json', $context),
            JsonResponse::HTTP_CREATED,
            ['Location' => $urlGenerator->generate(
                'app_products_item_get',
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
        response: Response::HTTP_NO_CONTENT,
        description: 'No Return',

        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Product::class, groups: ['productDetail']))
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
    #[OA\Tag(name: 'Products')]
    public function put(
        #[MapEntity(id: 'id')] Product $currentProduct,
        #[MapRequestPayload()] ProductDTO $productDTO,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        CacheService $cacheService
    ): JsonResponse {
        $context = SerializationContext::create()->setGroups(['productDetail']);
        if (null !== $productDTO->name) {
            $currentProduct->setName($productDTO->name);
        }
        if (null !== $productDTO->brand) {
            $currentProduct->setBrand($productDTO->brand);
        }
        if (null !== $productDTO->description) {
            $currentProduct->setDescription($productDTO->description);
        }
        if (null !== $productDTO->reference) {
            $currentProduct->setReference($productDTO->reference);
        }

        $errors = $validator->validate($currentProduct);
        if ($errors->count() > 0) {
            return new JsonResponse(
                $serializer->serialize($errors, 'json', $context),
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        $em->flush();
        $cacheService->invalidate(['productCache']);

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
    #[OA\Tag(name: 'Products')]
    public function delete(
        #[MapEntity(id: 'id')] Product $product,
        EntityManagerInterface $em,
        CacheService $cacheService
    ): JsonResponse {
        $em->remove($product);
        $em->flush();
        $cacheService->invalidate(['productCache']);

        return new JsonResponse(
            null,
            JsonResponse::HTTP_NO_CONTENT
        );
    }
}
