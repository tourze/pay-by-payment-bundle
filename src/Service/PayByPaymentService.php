<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Service;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\PayByPaymentBundle\Entity\PayByOrder;
use Tourze\PayByPaymentBundle\Entity\PayByRefund;
use Tourze\PayByPaymentBundle\Entity\PayByTransfer;
use Tourze\PayByPaymentBundle\Repository\PayByOrderRepository;
use Tourze\PayByPaymentBundle\Repository\PayByRefundRepository;
use Tourze\PayByPaymentBundle\Repository\PayByTransferRepository;

/**
 * PayBy支付数据服务统一入口
 *
 * 提供订单、转账、退款等数据的统一查询接口
 */
#[Autoconfigure(public: true)]
readonly class PayByPaymentService
{
    public function __construct(
        private PayByOrderRepository $orderRepository,
        private PayByTransferRepository $transferRepository,
        private PayByRefundRepository $refundRepository,
    ) {
    }

    /**
     * 根据商户订单号查找订单
     */
    public function findOrderByMerchantNo(string $merchantOrderNo): ?PayByOrder
    {
        return $this->orderRepository->findOneBy(['merchantOrderNo' => $merchantOrderNo]);
    }

    /**
     * 根据商户转账单号查找转账
     */
    public function findTransferByMerchantNo(string $merchantTransferNo): ?PayByTransfer
    {
        return $this->transferRepository->findOneBy(['merchantTransferNo' => $merchantTransferNo]);
    }

    /**
     * 根据商户退款单号查找退款
     */
    public function findRefundByMerchantNo(string $merchantRefundNo): ?PayByRefund
    {
        return $this->refundRepository->findOneBy(['merchantRefundNo' => $merchantRefundNo]);
    }

    /**
     * 获取指定时间范围内的订单统计
     * @return array<string, int>
     */
    public function getOrderStats(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        $qb = $this->orderRepository->createQueryBuilder('o');

        $result = $qb
            ->select('COUNT(o.id) as totalOrders, SUM(o.totalAmount.amount) as totalAmount')
            ->where('o.createTime BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleResult()
        ;

        if (!is_array($result)) {
            return ['total_orders' => 0, 'total_amount' => 0];
        }

        $totalOrders = $result['totalOrders'] ?? 0;
        $totalAmount = $result['totalAmount'] ?? 0;

        return [
            'total_orders' => is_numeric($totalOrders) ? (int) $totalOrders : 0,
            'total_amount' => is_numeric($totalAmount) ? (int) $totalAmount : 0,
        ];
    }

    /**
     * 获取指定时间范围内的转账统计
     * @return array<string, int>
     */
    public function getTransferStats(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        $qb = $this->transferRepository->createQueryBuilder('t');

        $result = $qb
            ->select('COUNT(t.id) as totalTransfers, SUM(t.transferAmount.amount) as totalAmount')
            ->where('t.createTime BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleResult()
        ;

        if (!is_array($result)) {
            return ['total_transfers' => 0, 'total_amount' => 0];
        }

        $totalTransfers = $result['totalTransfers'] ?? 0;
        $totalAmount = $result['totalAmount'] ?? 0;

        return [
            'total_transfers' => is_numeric($totalTransfers) ? (int) $totalTransfers : 0,
            'total_amount' => is_numeric($totalAmount) ? (int) $totalAmount : 0,
        ];
    }

    /**
     * 获取指定时间范围内的退款统计
     * @return array<string, int>
     */
    public function getRefundStats(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        $qb = $this->refundRepository->createQueryBuilder('r');

        $result = $qb
            ->select('COUNT(r.id) as totalRefunds, SUM(r.refundAmount.amount) as totalAmount')
            ->where('r.createTime BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleResult()
        ;

        if (!is_array($result)) {
            return ['total_refunds' => 0, 'total_amount' => 0];
        }

        $totalRefunds = $result['totalRefunds'] ?? 0;
        $totalAmount = $result['totalAmount'] ?? 0;

        return [
            'total_refunds' => is_numeric($totalRefunds) ? (int) $totalRefunds : 0,
            'total_amount' => is_numeric($totalAmount) ? (int) $totalAmount : 0,
        ];
    }
}
