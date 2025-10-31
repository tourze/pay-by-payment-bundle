<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Tourze\PayByPaymentBundle\Entity\PayByAmount;
use Tourze\PayByPaymentBundle\Entity\PayByRefund;
use Tourze\PayByPaymentBundle\Enum\PayByRefundStatus;
use Tourze\PayByPaymentBundle\Exception\PayByApiException;
use Tourze\PayByPaymentBundle\Exception\PayByException;
use Tourze\PayByPaymentBundle\Repository\PayByOrderRepository;
use Tourze\PayByPaymentBundle\Repository\PayByRefundRepository;

#[WithMonologChannel(channel: 'pay_by_payment')]
readonly class PayByRefundService
{
    public function __construct(
        private PayByApiClient $apiClient,
        private PayByRefundRepository $refundRepository,
        private PayByOrderRepository $orderRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<string, mixed>|null $accessoryContent
     */
    public function createRefund(
        string $merchantRefundNo,
        string $merchantOrderNo,
        PayByAmount $refundAmount,
        string $refundReason = '',
        ?string $notifyUrl = null,
        ?array $accessoryContent = null,
    ): PayByRefund {
        $this->logger->info('Creating PayBy refund', [
            'merchant_refund_no' => $merchantRefundNo,
            'merchant_order_no' => $merchantOrderNo,
            'amount' => $refundAmount->getAmount(),
            'currency' => $refundAmount->getCurrency(),
        ]);

        $existingRefund = $this->refundRepository->findByMerchantRefundNo($merchantRefundNo);
        if (null !== $existingRefund) {
            throw new PayByException('Refund with merchant refund number already exists');
        }

        $order = $this->orderRepository->findByMerchantOrderNo($merchantOrderNo);
        if (null === $order) {
            throw new PayByException('Order not found');
        }

        if (!$order->isPaid()) {
            throw new PayByException('Order must be paid before refund');
        }

        $refundableAmount = $order->getRefundableAmount();
        /** @var numeric-string $refundAmountValue */
        $refundAmountValue = $refundAmount->getAmount();
        /** @var numeric-string $refundableAmountValue */
        $refundableAmountValue = $refundableAmount;
        if (bccomp($refundAmountValue, $refundableAmountValue, 2) > 0) {
            throw new PayByException('Refund amount exceeds refundable amount');
        }

        $refund = new PayByRefund();
        $refund->setMerchantRefundNo($merchantRefundNo);
        $refund->setOrder($order);
        $refund->setRefundAmount($refundAmount);
        $refund->setRefundReason($refundReason);
        $refund->setNotifyUrl($notifyUrl);
        $refund->setAccessoryContent($accessoryContent);

        try {
            $data = [
                'merchantRefundNo' => $merchantRefundNo,
                'merchantOrderNo' => $merchantOrderNo,
                'refundAmount' => $refundAmount->toArray(),
                'refundReason' => $refundReason,
            ];

            if (null !== $notifyUrl) {
                $data['notifyUrl'] = $notifyUrl;
            }

            if (null !== $accessoryContent) {
                $data['accessoryContent'] = $accessoryContent;
            }

            $response = $this->apiClient->createRefund($data);
            $refundId = $response['refundId'] ?? '';
            if (!is_string($refundId)) {
                throw new PayByApiException('Invalid refundId in response', 'INVALID_RESPONSE');
            }
            $refund->setRefundId($refundId);
        } catch (PayByApiException $e) {
            $this->logger->error('Failed to create PayBy refund', [
                'error' => $e->getMessage(),
                'error_code' => $e->getErrorCode(),
                'merchant_refund_no' => $merchantRefundNo,
            ]);
            throw $e;
        }

        $this->entityManager->persist($refund);
        $this->entityManager->flush();

        $this->logger->info('PayBy refund created successfully', [
            'refund_id' => $refund->getRefundId(),
            'merchant_refund_no' => $merchantRefundNo,
        ]);

        return $refund;
    }

    public function queryRefund(string $refundId): ?PayByRefund
    {
        $refund = $this->refundRepository->findByRefundId($refundId);
        if (null === $refund) {
            return null;
        }

        try {
            $response = $this->apiClient->queryRefund($refundId);
            $this->updateRefundFromApiResponse($refund, $response);
            $this->entityManager->flush();
        } catch (PayByApiException $e) {
            $this->logger->error('Failed to query PayBy refund', [
                'error' => $e->getMessage(),
                'error_code' => $e->getErrorCode(),
                'refund_id' => $refundId,
            ]);
        }

        return $refund;
    }

    /**
     * @param array<string, mixed> $notificationData
     */
    public function handleRefundNotification(array $notificationData): bool
    {
        $this->logger->info('Handling PayBy refund notification', [
            'notification_data' => $notificationData,
        ]);

        $refundId = $notificationData['refundId'] ?? null;
        if (!is_string($refundId) || '' === $refundId) {
            $this->logger->error('Missing or invalid refundId in notification');

            return false;
        }

        $refund = $this->refundRepository->findByRefundId($refundId);
        if (null === $refund) {
            $this->logger->error('Refund not found for notification', [
                'refund_id' => $refundId,
            ]);

            return false;
        }

        $statusValue = $notificationData['status'] ?? '';
        if (!is_string($statusValue) && !is_int($statusValue)) {
            $this->logger->error('Invalid status type in notification', [
                'status' => $notificationData['status'] ?? null,
                'refund_id' => $refundId,
            ]);

            return false;
        }

        $status = PayByRefundStatus::tryFrom($statusValue);
        if (null === $status) {
            $this->logger->error('Invalid status in notification', [
                'status' => $notificationData['status'] ?? null,
                'refund_id' => $refundId,
            ]);

            return false;
        }

        $oldStatus = $refund->getStatus();
        $refund->setStatus($status);

        if (isset($notificationData['refundTime']) && is_string($notificationData['refundTime'])) {
            try {
                $refundTime = new \DateTimeImmutable($notificationData['refundTime']);
                $refund->setRefundTime($refundTime);
            } catch (\Exception $e) {
                $this->logger->error('Invalid refundTime format', [
                    'refund_time' => $notificationData['refundTime'],
                    'refund_id' => $refundId,
                ]);
            }
        }

        $this->entityManager->flush();

        $this->logger->info('PayBy refund status updated from notification', [
            'refund_id' => $refundId,
            'old_status' => $oldStatus->value,
            'new_status' => $status->value,
        ]);

        return true;
    }

    /**
     * @param array<string, mixed> $response
     */
    private function updateRefundFromApiResponse(PayByRefund $refund, array $response): void
    {
        if (isset($response['status'])) {
            $statusValue = $response['status'];
            if (is_string($statusValue) || is_int($statusValue)) {
                $status = PayByRefundStatus::tryFrom($statusValue);
                if (null !== $status) {
                    $refund->setStatus($status);
                }
            }
        }

        if (isset($response['refundTime']) && is_string($response['refundTime'])) {
            try {
                $refundTime = new \DateTimeImmutable($response['refundTime']);
                $refund->setRefundTime($refundTime);
            } catch (\Exception $e) {
                $this->logger->error('Invalid refundTime format in API response', [
                    'refund_time' => $response['refundTime'],
                    'refund_id' => $refund->getRefundId(),
                ]);
            }
        }
    }

    /**
     * 根据商户退款单号获取退款
     */
    public function getRefund(string $merchantRefundNo): ?PayByRefund
    {
        return $this->refundRepository->findOneBy(['merchantRefundNo' => $merchantRefundNo]);
    }

    /**
     * 更新退款状态
     */
    public function updateRefundStatus(string $merchantRefundNo, PayByRefundStatus $status): bool
    {
        $refund = $this->getRefund($merchantRefundNo);
        if (null === $refund) {
            return false;
        }

        $refund->setStatus($status);
        $this->entityManager->flush();

        $this->logger->info('Refund status updated', [
            'merchantRefundNo' => $merchantRefundNo,
            'status' => $status->value,
        ]);

        return true;
    }
}
