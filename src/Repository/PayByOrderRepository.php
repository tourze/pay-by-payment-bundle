<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\PayByPaymentBundle\Entity\PayByOrder;
use Tourze\PayByPaymentBundle\Enum\PayByOrderStatus;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<PayByOrder>
 */
#[AsRepository(entityClass: PayByOrder::class)]
#[Autoconfigure(public: true)]
class PayByOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PayByOrder::class);
    }

    public function save(PayByOrder $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PayByOrder $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByOrderId(string $orderId): ?PayByOrder
    {
        return $this->findOneBy(['orderId' => $orderId]);
    }

    public function findByMerchantOrderNo(string $merchantOrderNo): ?PayByOrder
    {
        return $this->findOneBy(['merchantOrderNo' => $merchantOrderNo]);
    }

    /**
     * @return array<PayByOrder>
     */
    public function findByStatus(PayByOrderStatus $status): array
    {
        return $this->findBy(['status' => $status]);
    }

    /**
     * @return array<PayByOrder>
     */
    public function findPendingOrders(): array
    {
        return $this->findBy(['status' => PayByOrderStatus::PENDING]);
    }

    /**
     * @return array<PayByOrder>
     */
    public function findPaidOrders(): array
    {
        return $this->findBy(['status' => PayByOrderStatus::SUCCESS]);
    }

    /**
     * @return array<PayByOrder>
     */
    public function findOrdersByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        /** @var array<PayByOrder> */
        return $this->createQueryBuilder('o')
            ->where('o.createTime >= :startDate')
            ->andWhere('o.createTime <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('o.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOrderStatistics(?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array
    {
        $qb = $this->createQueryBuilder('o');

        $qb->select([
            'COUNT(o.id) as totalCount',
            'SUM(CASE WHEN o.status = :success THEN 1 ELSE 0 END) as successCount',
            'SUM(CASE WHEN o.status = :success THEN o.totalAmount.amount ELSE 0 END) as totalAmount',
            'o.totalAmount.currency',
        ])
            ->groupBy('o.totalAmount.currency')
            ->setParameter('success', PayByOrderStatus::SUCCESS)
        ;

        if (null !== $startDate) {
            $qb->andWhere('o.createTime >= :startDate')
                ->setParameter('startDate', $startDate)
            ;
        }

        if (null !== $endDate) {
            $qb->andWhere('o.createTime <= :endDate')
                ->setParameter('endDate', $endDate)
            ;
        }

        /** @var array<string, mixed> */
        return $qb->getQuery()->getResult();
    }
}
