<?php

declare(strict_types=1);

namespace App\Model\CMS\Document;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Kdyby\Doctrine\EntityRepository;
use function array_map;

/**
 * Třída spravující tagy dokumentů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class TagRepository extends EntityRepository
{
    /**
     * Vrátí tag podle id.
     * @param $id
     */
    public function findById(int $id) : ?Tag
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Vrátí tagy podle id.
     * @param $ids
     * @return Collection
     */
    public function findTagsByIds(array $ids) : Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->in('id', $ids))
            ->orderBy(['name' => 'ASC']);
        return $this->matching($criteria);
    }

    /**
     * Vrátí id tagů.
     * @param $tags
     * @return array
     */
    public function findTagsIds(Collection $tags) : array
    {
        return array_map(function ($o) {
            return $o->getId();
        }, $tags->toArray());
    }

    /**
     * Vrátí všechny názvy tagů.
     * @return array
     */
    public function findAllNames() : array
    {
        $names = $this->createQueryBuilder('t')
            ->select('t.name')
            ->getQuery()
            ->getScalarResult();
        return array_map('current', $names);
    }

    /**
     * Vrátí názvy tagů, kromě tagu s id.
     * @param $id
     * @return array
     */
    public function findOthersNames(int $id) : array
    {
        $names = $this->createQueryBuilder('t')
            ->select('t.name')
            ->where('t.id != :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getScalarResult();
        return array_map('current', $names);
    }

    /**
     * Uloží tag.
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Tag $tag) : void
    {
        $this->_em->persist($tag);
        $this->_em->flush();
    }

    /**
     * Odstraní tag.
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Tag $tag) : void
    {
        $this->_em->remove($tag);
        $this->_em->flush();
    }

    /**
     * Vrátí seznam tagů jako možnosti pro select.
     * @return array
     */
    public function getTagsOptions() : array
    {
        $tags = $this->createQueryBuilder('t')
            ->select('t.id, t.name')
            ->orderBy('t.name')
            ->getQuery()
            ->getResult();

        $options = [];
        foreach ($tags as $tag) {
            $options[$tag['id']] = $tag['name'];
        }
        return $options;
    }
}
