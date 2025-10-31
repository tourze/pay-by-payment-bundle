<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\PayByPaymentBundle\Entity\PayByConfig;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<PayByConfig>
 */
#[AsRepository(entityClass: PayByConfig::class)]
#[Autoconfigure(public: true)]
class PayByConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PayByConfig::class);
    }

    /**
     * @return array<PayByConfig>
     */
    public function findEnabledConfigs(): array
    {
        return $this->findBy(['enabled' => true], ['name' => 'ASC']);
    }

    public function findDefaultConfig(): ?PayByConfig
    {
        return $this->findOneBy(['enabled' => true, 'isDefault' => true]);
    }

    public function findByName(string $name): ?PayByConfig
    {
        return $this->findOneBy(['name' => $name]);
    }

    public function save(PayByConfig $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PayByConfig $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
