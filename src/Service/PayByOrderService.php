<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Tourze\PayByPaymentBundle\Entity\PayByAmount;
use Tourze\PayByPaymentBundle\Entity\PayByOrder;
use Tourze\PayByPaymentBundle\Enum\PayByOrderStatus;
use Tourze\PayByPaymentBundle\Enum\PayByPaySceneCode;
use Tourze\PayByPaymentBundle\Exception\PayByApiException;
use Tourze\PayByPaymentBundle\Exception\PayByException;
use Tourze\PayByPaymentBundle\Repository\PayByOrderRepository;
use Tourze\PayByPaymentBundle\Request\CreateOrderRequest;

#[WithMonologChannel(channel: 'pay_by_payment')]
readonly class PayByOrderService
{
    public function __construct(
        private PayByApiClient $apiClient,
        private PayByOrderRepository $orderRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<string, mixed>|null $accessoryContent
     */
    public function createOrder(
        string $merchantOrderNo,
        string $subject,
        PayByAmount $totalAmount,
        PayByPaySceneCode $paySceneCode,
        ?string $notifyUrl = null,
        ?string $returnUrl = null,
        ?array $accessoryContent = null,
    ): PayByOrder {
        $this->logger->info('Creating PayBy order', [
            'merchant_order_no' => $merchantOrderNo,
            'subject' => $subject,
            'amount' => $totalAmount->getAmount(),
            'currency' => $totalAmount->getCurrency(),
            'pay_scene_code' => $paySceneCode->value,
        ]);

        $existingOrder = $this->orderRepository->findByMerchantOrderNo($merchantOrderNo);
        if (null !== $existingOrder) {
            throw new PayByException('Order with merchant order number already exists');
        }

        $request = new CreateOrderRequest($merchantOrderNo, $subject, $totalAmount, $paySceneCode);
        $request->setNotifyUrl($notifyUrl);
        $request->setReturnUrl($returnUrl);
        $request->setAccessoryContent($accessoryContent);

        try {
            $response = $this->apiClient->createOrder($request);
        } catch (PayByApiException $e) {
            $this->logger->error('Failed to create PayBy order', [
                'error' => $e->getMessage(),
                'error_code' => $e->getErrorCode(),
                'merchant_order_no' => $merchantOrderNo,
            ]);
            throw $e;
        }

        $order = new PayByOrder();

        $orderId = $response['orderId'] ?? '';
        if (!is_string($orderId)) {
            throw new PayByException('orderId must be string');
        }
        $order->setOrderId($orderId);
        $order->setMerchantOrderNo($merchantOrderNo);
        $order->setSubject($subject);
        $order->setTotalAmount($totalAmount);
        $order->setPaySceneCode($paySceneCode);
        $order->setStatus(PayByOrderStatus::PENDING);
        $order->setNotifyUrl($notifyUrl);
        $order->setReturnUrl($returnUrl);
        $order->setAccessoryContent($accessoryContent);

        if (isset($response['qrCodeData']) && is_string($response['qrCodeData'])) {
            $order->setQrCodeData($response['qrCodeData']);
        }
        if (isset($response['qrCodeUrl']) && is_string($response['qrCodeUrl'])) {
            $order->setQrCodeUrl($response['qrCodeUrl']);
        }
        if (isset($response['paymentUrl']) && is_string($response['paymentUrl'])) {
            $order->setPaymentUrl($response['paymentUrl']);
        }

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        $this->logger->info('PayBy order created successfully', [
            'order_id' => $order->getOrderId(),
            'merchant_order_no' => $merchantOrderNo,
        ]);

        return $order;
    }

    public function queryOrder(string $orderId): ?PayByOrder
    {
        $order = $this->orderRepository->findByOrderId($orderId);
        if (null === $order) {
            return null;
        }

        try {
            $response = $this->apiClient->queryOrder($orderId);
            $this->updateOrderFromApiResponse($order, $response);
            $this->entityManager->flush();
        } catch (PayByApiException $e) {
            $this->logger->error('Failed to query PayBy order', [
                'error' => $e->getMessage(),
                'error_code' => $e->getErrorCode(),
                'order_id' => $orderId,
            ]);
        }

        return $order;
    }

    public function cancelOrder(string $orderId, string $cancelReason = ''): bool
    {
        $order = $this->orderRepository->findByOrderId($orderId);
        if (null === $order) {
            throw new PayByException('Order not found');
        }

        if (!$order->canBeCancelled()) {
            throw new PayByException('Order cannot be cancelled');
        }

        try {
            $this->apiClient->cancelOrder($orderId, $cancelReason);
            $order->setStatus(PayByOrderStatus::CANCELLED);
            $this->entityManager->flush();

            $this->logger->info('PayBy order cancelled successfully', [
                'order_id' => $orderId,
                'cancel_reason' => $cancelReason,
            ]);

            return true;
        } catch (PayByApiException $e) {
            $this->logger->error('Failed to cancel PayBy order', [
                'error' => $e->getMessage(),
                'error_code' => $e->getErrorCode(),
                'order_id' => $orderId,
            ]);
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $notificationData
     */
    public function handlePaymentNotification(array $notificationData): bool
    {
        $this->logger->info('Handling PayBy payment notification', [
            'notification_data' => $notificationData,
        ]);

        $order = $this->validateAndGetOrderFromNotification($notificationData);
        if (null === $order) {
            return false;
        }

        $status = $this->validateAndGetStatusFromNotification($notificationData, $order->getOrderId());
        if (null === $status) {
            return false;
        }

        $oldStatus = $order->getStatus();
        $order->setStatus($status);

        if (!$this->updateOrderOptionalFields($order, $notificationData)) {
            return false;
        }

        $this->entityManager->flush();

        $this->logger->info('PayBy order status updated from notification', [
            'order_id' => $order->getOrderId(),
            'old_status' => $oldStatus->value,
            'new_status' => $status->value,
        ]);

        return true;
    }

    /**
     * @param array<string, mixed> $response
     */
    private function updateOrderFromApiResponse(PayByOrder $order, array $response): void
    {
        $this->updateOrderStatusFromResponse($order, $response);
        $this->updateOrderPaymentInfoFromResponse($order, $response);
    }

    /**
     * @param array<string, mixed> $response
     */
    private function updateOrderStatusFromResponse(PayByOrder $order, array $response): void
    {
        if (!isset($response['status'])) {
            return;
        }

        $statusValue = $response['status'];
        if (!is_string($statusValue) && !is_int($statusValue)) {
            return;
        }

        $status = PayByOrderStatus::tryFrom($statusValue);
        if (null !== $status) {
            $order->setStatus($status);
        }
    }

    /**
     * @param array<string, mixed> $response
     */
    private function updateOrderPaymentInfoFromResponse(PayByOrder $order, array $response): void
    {
        if (isset($response['paymentMethod']) && is_string($response['paymentMethod'])) {
            $order->setPaymentMethod($response['paymentMethod']);
        }

        if (isset($response['payTime']) && is_string($response['payTime'])) {
            try {
                $payTime = new \DateTimeImmutable($response['payTime']);
                $order->setPayTime($payTime);
            } catch (\Exception $e) {
                $this->logger->error('Invalid payTime format in API response', [
                    'pay_time' => $response['payTime'],
                    'order_id' => $order->getOrderId(),
                ]);
            }
        }
    }

    /**
     * 根据商户订单号获取订单
     */
    public function getOrder(string $merchantOrderNo): ?PayByOrder
    {
        return $this->orderRepository->findOneBy(['merchantOrderNo' => $merchantOrderNo]);
    }

    /**
     * 更新订单状态
     */
    public function updateOrderStatus(string $merchantOrderNo, PayByOrderStatus $status): bool
    {
        $order = $this->getOrder($merchantOrderNo);
        if (null === $order) {
            return false;
        }

        $order->setStatus($status);
        $this->entityManager->flush();

        $this->logger->info('Order status updated', [
            'merchantOrderNo' => $merchantOrderNo,
            'status' => $status->value,
        ]);

        return true;
    }

    /**
     * @param array<string, mixed> $notificationData
     */
    private function validateAndGetOrderFromNotification(array $notificationData): ?PayByOrder
    {
        $orderId = $notificationData['orderId'] ?? null;
        if (!is_string($orderId) || '' === $orderId) {
            $this->logger->error('Missing or invalid orderId in notification');

            return null;
        }

        $order = $this->orderRepository->findByOrderId($orderId);
        if (null === $order) {
            $this->logger->error('Order not found for notification', [
                'order_id' => $orderId,
            ]);

            return null;
        }

        return $order;
    }

    /**
     * @param array<string, mixed> $notificationData
     */
    private function validateAndGetStatusFromNotification(array $notificationData, string $orderId): ?PayByOrderStatus
    {
        $statusValue = $notificationData['status'] ?? '';
        if (!is_string($statusValue) && !is_int($statusValue)) {
            $this->logger->error('Invalid status type in notification', [
                'status' => $statusValue,
                'order_id' => $orderId,
            ]);

            return null;
        }

        $status = PayByOrderStatus::tryFrom($statusValue);
        if (null === $status) {
            $this->logger->error('Invalid status value in notification', [
                'status' => $statusValue,
                'order_id' => $orderId,
            ]);

            return null;
        }

        return $status;
    }

    /**
     * @param array<string, mixed> $notificationData
     */
    private function updateOrderOptionalFields(PayByOrder $order, array $notificationData): bool
    {
        if (isset($notificationData['paymentMethod']) && is_string($notificationData['paymentMethod'])) {
            $order->setPaymentMethod($notificationData['paymentMethod']);
        }

        if (isset($notificationData['payTime']) && is_string($notificationData['payTime'])) {
            try {
                $payTime = new \DateTimeImmutable($notificationData['payTime']);
                $order->setPayTime($payTime);
            } catch (\Exception $e) {
                $this->logger->error('Invalid payTime format', [
                    'pay_time' => $notificationData['payTime'],
                    'order_id' => $order->getOrderId(),
                ]);

                return false;
            }
        }

        return true;
    }
}
