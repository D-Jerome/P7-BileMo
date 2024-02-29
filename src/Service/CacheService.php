<?php


namespace App\Service;

// use App\Services\PaginationService;
use App\Request\DTO\FilterDTO;
use App\Request\DTO\PaginationDTO;
use JMS\Serializer\SerializerInterface;
use Psr\Cache\InvalidArgumentException;
use JMS\Serializer\SerializationContext;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;


class CacheService
{
    private SerializerInterface $serializer;
    private PaginationDTO $paginationDTO;
    private FilterDTO $filterDTO;
    private TagAwareCacheInterface $cachePool;

    public function __construct(
            TagAwareCacheInterface $cachePool,
            SerializerInterface $serializer,
            #[MapRequestPayload()] PaginationDTO $paginationDTO,
            #[MapRequestPayload()] FilterDTO $FilterDTO
            )
    {
        $this->serializer = $serializer;
        $this->paginationDTO = $paginationDTO;
        $this->filterDTO = $FilterDTO;
        $this->cachePool = $cachePool;
    }

 
    public function getUniqueData(string $class, $entity, $context)
    {
        $cacheName = $class.$entity->getId();

        return $this->cachePool->get($cacheName, function (ItemInterface $item) use ($entity, $class ,$context) {
            $item->expiresAfter(15);
            $item->tag($class);

            return $this->serializer->serialize($entity, 'json', $context);
        });
    }

    public function getBy(string $class, $entityRepository, $context, $connectedUser)
    {
        $cacheName = 'getAll'.$class.'-'.$connectedUser->getId();

        return $this->cachePool->get($cacheName, function (ItemInterface $item) use ($entityRepository, $class ,$context, $connectedUser) {
            $item->expiresAfter(15);
            $item->tag($class);

            $entityList = $entityRepository->findBy(['id' => $connectedUser->getCustomer()]);
            return $this->serializer->serialize($entityList, 'json', $context);
        });
    }

    public function getAllData(string $class, $entityRepository, $context)
    {
        $idCache = 'getAll'.$class.'-'.(string) $this->paginationDTO->page.'-'.(string) $this->paginationDTO->limit;
        
        return $this->cachePool->get($idCache, function (ItemInterface $item) use ($entityRepository, $class, $context) {
            $item->tag('all'.$class.'UsersCache');
            $item->expiresAfter(15);
            $entityList = $entityRepository->findAllWithPagination($this->paginationDTO->page, $this->paginationDTO->limit);
        
            return $this->serializer->serialize($entityList, 'json', $context);
        });
    }


    public function getAllDataByCustomer(string $class, $entityRepository, $context, $connectedUser)
    {
        
        $idCache = 'getAll'.$class.'-'.(string) $this->paginationDTO->page.'-'.(string) $this->paginationDTO->limit;
        
        return $this->cachePool->get($idCache, function (ItemInterface $item) use ($entityRepository, $class, $context, $connectedUser) {
            $item->tag('all'.$class.'UsersCache');
            $item->expiresAfter(15);
            $entityList = $entityRepository->findByWithPagination(['customer' => $connectedUser->getCustomer()], $this->paginationDTO->page, $this->paginationDTO->limit);
        
            return $this->serializer->serialize($entityList, 'json', $context);
        });
    }

    public function getAllProductBy(string $class, $entityRepository, $context)
    {
      
        $idCache = 'getAll'.$class.'-'.(string)$this->filterDTO->brand .'-'. (string) $this->paginationDTO->page.'-'.(string) $this->paginationDTO->limit;
        
        return $this->cachePool->get($idCache, function (ItemInterface $item) use ($entityRepository, $class, $context) {
            $item->tag('all'.$class.'BrandCache');
            $item->expiresAfter(15);
            $entityList = $entityRepository->findByWithPagination($this->filterDTO->brand, $this->paginationDTO->page, $this->paginationDTO->limit);
        
            return $this->serializer->serialize($entityList, 'json', $context);
        });
    }



    public function invalidate($entity)
    {
        $this->cachePool->invalidateTags([$entity]);
    }
}



// $idCache = 'getAllUsersByCustomer-'.(string) $paginationDTO->page.'-'.(string) $paginationDTO->limit;

// $jsonUserList = $cache->get($idCache, function (ItemInterface $item) use ($userRepository, $paginationDTO, $serializer, $context, $connectedUser) {
//     $item->tag('allUsersByCustomerCache');
//     $item->expiresAfter(15);
//     $userList = $userRepository->findByWithPagination(['customer' => $connectedUser->getCustomer()], $paginationDTO->page, $paginationDTO->limit);

//     return $serializer->serialize($userList, 'json', $context);
// });


// $class = get_class($entityRepository);
// $idCache = 'getAll'.$classEntity.'-'.(string) $paginationDTO->page.'-'.(string) $paginationDTO->limit;

// $jsonUserList = $cache->get($idCache, function (ItemInterface $item) use ($entityRepository, $classEntity, $paginationDTO, $serializer, $context) {
//     $item->tag('all'.$classEntity.'UsersCache');
//     $item->expiresAfter(15);
//     $entityList = $entityRepository->findAllWithPagination($paginationDTO->page, $paginationDTO->limit);

//     return $serializer->serialize($entityList, 'json', $context);
// });




// $idCache = 'getBrandProducts-'.$filterDTO->brand.'-'.(string) $paginationDTO->page.'-'.(string) $paginationDTO->limit;

// $jsonProductList = $cache->get($idCache, function (ItemInterface $item) use ($productRepository, $paginationDTO, $filterDTO, $serializer, $context) {
//     $item->tag('productCache');
//     $item->expiresAfter(15);
//     $productList = $productRepository->findByWithPagination($filterDTO->brand, $paginationDTO->page, $paginationDTO->limit);

//     return $serializer->serialize($productList, 'json', $context);
// });



// $idCache = 'get'.$entity.'-'.$entity->getId();

// $jsonProduct = $cache->get($idCache, function (ItemInterface $item) use ($entity, $serializer, $context) {
//     $item->tag($entity.'Cache');
//     $item->expiresAfter(15);

//     return $serializer->serialize($entity, 'json', $context);
// });