<?php
namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use App\Pagination\Paginator;
use function Symfony\Component\String\u;


use App\Entity\Product;

/**
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function find($id, $lockMode = null, $lockVersion = null)
    {
        return $this->createQueryBuilder('p')
            ->where('p.id = :id')
            ->andWhere('p.deletedAt = 0')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneBySlug(string $slug): ?Product
    {
        return $this->createQueryBuilder('p')
            ->where('p.slug = :slug')
            ->andWhere('p.deletedAt = 0')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findDuplicateSlug(?int $id, string $slug): ?Product
    {
        $queryBuilder = $this->createQueryBuilder('p');
        $queryBuilder
            ->andWhere('p.deletedAt = 0');
        if ($id) {
            $queryBuilder
                ->andWhere('p.id != :id')
                ->setParameter('id', $id);
        }
        $queryBuilder->andWhere('p.slug = :slug OR p.slug LIKE :slug_with_suffix')
            ->setParameter('slug', $slug)
            ->setParameter('slug_with_suffix', $slug . '-%');

        return $queryBuilder
            ->orderBy('p.slug', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAll()
    {
        return $this->createQueryBuilder('p')
            ->where('p.deletedAt = 0')
            ->getQuery()
            ->getResult();
    }

    public function findAllWithDeleted()
    {
        return $this->createQueryBuilder('p')
            ->getQuery()
            ->getResult();
    }

    public function findAllById(array $ids)
    {
        return $this->createQueryBuilder('p')
            ->where('p.id IN (:ids)')
            ->andWhere('p.deletedAt = 0')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    public function search(?string $query, int $firstResult = 0, int $maxResults = 10)
    {
        $query = $this->createQueryBuilder('p')
            ->where('p.name LIKE :query')
            ->andWhere('p.deletedAt = 0')
            ->setFirstResult($firstResult)
            ->setMaxResults($maxResults)
            ->setParameter('query', '%'.addcslashes($query, '%_').'%');

        return new Paginator($query);
    }

    public function findLatest(int $page = 1, Tag $tag = null): Paginator
    {
        $qb = $this->createQueryBuilder('p')
            ->addSelect('t')
            ->leftJoin('p.tags', 't')
            ->where('p.dateCreated <= :now')
            ->orderBy('p.dateCreated', 'DESC')
            ->setParameter('now', new \DateTime())
        ;

        if (null !== $tag) {
            $qb->andWhere(':tag MEMBER OF p.tags')
                ->setParameter('tag', $tag);
        }

        return (new Paginator($qb))->paginate($page);
    }

    /**
     * @return Post[]
     */
    public function findBySearchQuery(string $query, int $limit = Product::NUM_ITEMS): array
    {
        $searchTerms = $this->extractSearchTerms($query);

        if (0 === \count($searchTerms)) {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder('p');

        foreach ($searchTerms as $key => $term) {
            $queryBuilder
                ->orWhere('p.title LIKE :t_'.$key)
                ->setParameter('t_'.$key, '%'.$term.'%')
            ;
        }

        return $queryBuilder
            ->orderBy('p.publishedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Transforms the search string into an array of search terms.
     */
    private function extractSearchTerms(string $searchQuery): array
    {
        $searchQuery = u($searchQuery)->replaceMatches('/[[:space:]]+/', ' ')->trim();
        $terms = array_unique(u($searchQuery)->split(' '));

        // ignore the search terms that are too short
        return array_filter($terms, function ($term) {
            return 2 <= u($term)->length();
        });
    }

    public function countCurrentlySelling()
    {
        return $this->createQueryBuilder('p')
            ->select('count(p.id)')
            ->where('p.deletedAt = 0')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findLatestOne(int $maxResults): array
    {
        return $this->createQueryBuilder('p')
            ->select('p')
            ->where('p.deletedAt = 0')
            ->orderBy('p.dateCreated', 'DESC')
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getResult();
    }
}
