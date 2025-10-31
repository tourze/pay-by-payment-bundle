<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\PayByPaymentBundle\Entity\PayByRefund;
use Tourze\PayByPaymentBundle\Enum\PayByRefundStatus;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<PayByRefund>
 */
#[AsRepository(entityClass: PayByRefund::class)]
#[Autoconfigure(public: true)]
class PayByRefundRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PayByRefund::class);
    }

    public function save(PayByRefund $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PayByRefund $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByRefundId(string $refundId): ?PayByRefund
    {
        return $this->findOneBy(['refundId' => $refundId]);
    }

    public function findByMerchantRefundNo(string $merchantRefundNo): ?PayByRefund
    {
        return $this->findOneBy(['merchantRefundNo' => $merchantRefundNo]);
    }

    /**
     * @return array<PayByRefund>
     */
    public function findByOrderId(string $orderId): array
    {
        /** @var array<PayByRefund> */
        return $this->createQueryBuilder('r')
            ->join('r.order', 'o')
            ->where('o.orderId = :orderId')
            ->setParameter('orderId', $orderId)
            ->orderBy('r.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return array<PayByRefund>
     */
    public function findByStatus(PayByRefundStatus $status): array
    {
        return $this->findBy(['status' => $status]);
    }

    /**
     * @return array<PayByRefund>
     */
    public function findPendingRefunds(): array
    {
        return $this->findBy(['status' => PayByRefundStatus::PENDING]);
    }

    /**
     * @return array<PayByRefund>
     */
    public function findSuccessfulRefunds(): array
    {
        return $this->findBy(['status' => PayByRefundStatus::SUCCESS]);
    }

    /**
     * @return array<PayByRefund>
     */
    public function findRefundsByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        /** @var array<PayByRefund> */
        return $this->createQueryBuilder('r')
            ->where('r.createTime >= :startDate')
            ->andWhere('r.createTime <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('r.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRefundStatistics(?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array
    {
        $qb = $this->createQueryBuilder('r');

        $qb->select([
            'COUNT(r.id) as totalCount',
            'SUM(CASE WHEN r.status = :success THEN 1 ELSE 0 END) as successCount',
            'SUM(CASE WHEN r.status = :success THEN r.refundAmount.amount ELSE 0 END) as totalAmount',
            'r.refundAmount.currency',
        ])
            ->groupBy('r.refundAmount.currency')
            ->setParameter('success', PayByRefundStatus::SUCCESS)
        ;

        if (null !== $startDate) {
            $qb->andWhere('r.createTime >= :startDate')
                ->setParameter('startDate', $startDate)
            ;
        }

        if (null !== $endDate) {
            $qb->andWhere('r.createTime <= :endDate')
                ->setParameter('endDate', $endDate)
            ;
        }

        /** @var array<string, mixed> */
        return $qb->getQuery()->getResult();
    }
}
