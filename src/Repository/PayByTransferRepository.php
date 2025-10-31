<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\PayByPaymentBundle\Entity\PayByTransfer;
use Tourze\PayByPaymentBundle\Enum\PayByTransferStatus;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<PayByTransfer>
 */
#[AsRepository(entityClass: PayByTransfer::class)]
#[Autoconfigure(public: true)]
class PayByTransferRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PayByTransfer::class);
    }

    public function save(PayByTransfer $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PayByTransfer $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByTransferId(string $transferId): ?PayByTransfer
    {
        return $this->findOneBy(['transferId' => $transferId]);
    }

    public function findByMerchantTransferNo(string $merchantTransferNo): ?PayByTransfer
    {
        return $this->findOneBy(['merchantTransferNo' => $merchantTransferNo]);
    }

    /**
     * @return array<PayByTransfer>
     */
    public function findByFromAccount(string $fromAccount): array
    {
        return $this->findBy(['fromAccount' => $fromAccount]);
    }

    /**
     * @return array<PayByTransfer>
     */
    public function findByToAccount(string $toAccount): array
    {
        return $this->findBy(['toAccount' => $toAccount]);
    }

    /**
     * @return array<PayByTransfer>
     */
    public function findByStatus(PayByTransferStatus $status): array
    {
        return $this->findBy(['status' => $status]);
    }

    /**
     * @return array<PayByTransfer>
     */
    public function findPendingTransfers(): array
    {
        return $this->findBy(['status' => PayByTransferStatus::PENDING]);
    }

    /**
     * @return array<PayByTransfer>
     */
    public function findSuccessfulTransfers(): array
    {
        return $this->findBy(['status' => PayByTransferStatus::SUCCESS]);
    }

    /**
     * @return array<PayByTransfer>
     */
    public function findTransfersByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        /** @var array<PayByTransfer> */
        return $this->createQueryBuilder('t')
            ->where('t.createTime >= :startDate')
            ->andWhere('t.createTime <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('t.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return array<string, mixed>
     */
    public function getTransferStatistics(?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array
    {
        $qb = $this->createQueryBuilder('t');

        $qb->select([
            'COUNT(t.id) as totalCount',
            'SUM(CASE WHEN t.status = :success THEN 1 ELSE 0 END) as successCount',
            'SUM(CASE WHEN t.status = :success THEN t.transferAmount.amount ELSE 0 END) as totalAmount',
            't.transferAmount.currency',
            't.transferType',
        ])
            ->groupBy('t.transferAmount.currency, t.transferType')
            ->setParameter('success', PayByTransferStatus::SUCCESS)
        ;

        if (null !== $startDate) {
            $qb->andWhere('t.createTime >= :startDate')
                ->setParameter('startDate', $startDate)
            ;
        }

        if (null !== $endDate) {
            $qb->andWhere('t.createTime <= :endDate')
                ->setParameter('endDate', $endDate)
            ;
        }

        /** @var array<string, mixed> */
        return $qb->getQuery()->getResult();
    }
}
