<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Tourze\PayByPaymentBundle\Entity\PayByAmount;
use Tourze\PayByPaymentBundle\Entity\PayByTransfer;
use Tourze\PayByPaymentBundle\Enum\PayByTransferStatus;
use Tourze\PayByPaymentBundle\Enum\PayByTransferType;
use Tourze\PayByPaymentBundle\Exception\PayByApiException;
use Tourze\PayByPaymentBundle\Exception\PayByException;
use Tourze\PayByPaymentBundle\Repository\PayByTransferRepository;

#[WithMonologChannel(channel: 'pay_by_payment')]
class PayByTransferService
{
    public function __construct(
        private PayByApiClient $apiClient,
        private PayByTransferRepository $transferRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<string, mixed>|null $accessoryContent
     */
    public function createInternalTransfer(
        string $merchantTransferNo,
        string $fromAccount,
        string $toAccount,
        PayByAmount $transferAmount,
        string $transferReason = '',
        ?string $notifyUrl = null,
        ?array $accessoryContent = null,
    ): PayByTransfer {
        $this->logger->info('Creating PayBy internal transfer', [
            'merchant_transfer_no' => $merchantTransferNo,
            'from_account' => $fromAccount,
            'to_account' => $toAccount,
            'amount' => $transferAmount->getAmount(),
            'currency' => $transferAmount->getCurrency(),
        ]);

        $existingTransfer = $this->transferRepository->findByMerchantTransferNo($merchantTransferNo);
        if (null !== $existingTransfer) {
            throw new PayByException('Transfer with merchant transfer number already exists');
        }

        $transfer = new PayByTransfer();
        $transfer->setMerchantTransferNo($merchantTransferNo);
        $transfer->setTransferType(PayByTransferType::INTERNAL);
        $transfer->setFromAccount($fromAccount);
        $transfer->setToAccount($toAccount);
        $transfer->setTransferAmount($transferAmount);
        $transfer->setTransferReason($transferReason);
        $transfer->setNotifyUrl($notifyUrl);
        $transfer->setAccessoryContent($accessoryContent);

        try {
            $data = [
                'merchantTransferNo' => $merchantTransferNo,
                'fromAccount' => $fromAccount,
                'toAccount' => $toAccount,
                'transferAmount' => $transferAmount->toArray(),
                'transferReason' => $transferReason,
            ];

            if (null !== $notifyUrl) {
                $data['notifyUrl'] = $notifyUrl;
            }

            if (null !== $accessoryContent) {
                $data['accessoryContent'] = $accessoryContent;
            }

            $response = $this->apiClient->createTransfer($data);
            $transferId = $response['transferId'] ?? '';
            if (!is_string($transferId)) {
                throw new PayByApiException('Invalid transferId in response', 'INVALID_RESPONSE');
            }
            $transfer->setTransferId($transferId);
        } catch (PayByApiException $e) {
            $this->logger->error('Failed to create PayBy internal transfer', [
                'error' => $e->getMessage(),
                'error_code' => $e->getErrorCode(),
                'merchant_transfer_no' => $merchantTransferNo,
            ]);
            throw $e;
        }

        $this->entityManager->persist($transfer);
        $this->entityManager->flush();

        $this->logger->info('PayBy internal transfer created successfully', [
            'transfer_id' => $transfer->getTransferId(),
            'merchant_transfer_no' => $merchantTransferNo,
        ]);

        return $transfer;
    }

    /**
     * @param array<string, mixed> $bankTransferInfo
     * @param array<string, mixed>|null $accessoryContent
     */
    public function createBankTransfer(
        string $merchantTransferNo,
        string $fromAccount,
        array $bankTransferInfo,
        PayByAmount $transferAmount,
        string $transferReason = '',
        ?string $notifyUrl = null,
        ?array $accessoryContent = null,
    ): PayByTransfer {
        $this->logger->info('Creating PayBy bank transfer', [
            'merchant_transfer_no' => $merchantTransferNo,
            'from_account' => $fromAccount,
            'bank_info' => $bankTransferInfo,
            'amount' => $transferAmount->getAmount(),
            'currency' => $transferAmount->getCurrency(),
        ]);

        $existingTransfer = $this->transferRepository->findByMerchantTransferNo($merchantTransferNo);
        if (null !== $existingTransfer) {
            throw new PayByException('Transfer with merchant transfer number already exists');
        }

        $transfer = new PayByTransfer();
        $transfer->setMerchantTransferNo($merchantTransferNo);
        $transfer->setTransferType(PayByTransferType::BANK_TRANSFER);
        $transfer->setFromAccount($fromAccount);
        $transfer->setTransferAmount($transferAmount);
        $transfer->setTransferReason($transferReason);
        $transfer->setNotifyUrl($notifyUrl);
        $transfer->setBankTransferInfo($bankTransferInfo);
        $transfer->setAccessoryContent($accessoryContent);

        try {
            $data = [
                'merchantTransferNo' => $merchantTransferNo,
                'fromAccount' => $fromAccount,
                'bankTransferInfo' => $bankTransferInfo,
                'transferAmount' => $transferAmount->toArray(),
                'transferReason' => $transferReason,
            ];

            if (null !== $notifyUrl) {
                $data['notifyUrl'] = $notifyUrl;
            }

            if (null !== $accessoryContent) {
                $data['accessoryContent'] = $accessoryContent;
            }

            $response = $this->apiClient->createBankTransfer($data);
            $transferId = $response['transferId'] ?? '';
            if (!is_string($transferId)) {
                throw new PayByApiException('Invalid transferId in response', 'INVALID_RESPONSE');
            }
            $transfer->setTransferId($transferId);
        } catch (PayByApiException $e) {
            $this->logger->error('Failed to create PayBy bank transfer', [
                'error' => $e->getMessage(),
                'error_code' => $e->getErrorCode(),
                'merchant_transfer_no' => $merchantTransferNo,
            ]);
            throw $e;
        }

        $this->entityManager->persist($transfer);
        $this->entityManager->flush();

        $this->logger->info('PayBy bank transfer created successfully', [
            'transfer_id' => $transfer->getTransferId(),
            'merchant_transfer_no' => $merchantTransferNo,
        ]);

        return $transfer;
    }

    public function queryTransfer(string $transferId): ?PayByTransfer
    {
        $transfer = $this->transferRepository->findByTransferId($transferId);
        if (null === $transfer) {
            return null;
        }

        try {
            $response = $this->apiClient->queryTransfer($transferId);
            $this->updateTransferFromApiResponse($transfer, $response);
            $this->entityManager->flush();
        } catch (PayByApiException $e) {
            $this->logger->error('Failed to query PayBy transfer', [
                'error' => $e->getMessage(),
                'error_code' => $e->getErrorCode(),
                'transfer_id' => $transferId,
            ]);
        }

        return $transfer;
    }

    /**
     * @param array<string, mixed> $notificationData
     */
    public function handleTransferNotification(array $notificationData): bool
    {
        $this->logger->info('Handling PayBy transfer notification', [
            'notification_data' => $notificationData,
        ]);

        $transferId = $notificationData['transferId'] ?? null;
        if (!is_string($transferId) || '' === $transferId) {
            $this->logger->error('Missing or invalid transferId in notification');

            return false;
        }

        $transfer = $this->transferRepository->findByTransferId($transferId);
        if (null === $transfer) {
            $this->logger->error('Transfer not found for notification', [
                'transfer_id' => $transferId,
            ]);

            return false;
        }

        $statusValue = $notificationData['status'] ?? '';
        if (!is_string($statusValue) && !is_int($statusValue)) {
            $this->logger->error('Invalid status type in notification', [
                'status' => $notificationData['status'] ?? null,
                'transfer_id' => $transferId,
            ]);

            return false;
        }

        $status = PayByTransferStatus::tryFrom($statusValue);
        if (null === $status) {
            $this->logger->error('Invalid status in notification', [
                'status' => $notificationData['status'] ?? null,
                'transfer_id' => $transferId,
            ]);

            return false;
        }

        $oldStatus = $transfer->getStatus();
        $transfer->setStatus($status);

        if (isset($notificationData['transferTime']) && is_string($notificationData['transferTime'])) {
            try {
                $transferTime = new \DateTimeImmutable($notificationData['transferTime']);
                $transfer->setTransferTime($transferTime);
            } catch (\Exception $e) {
                $this->logger->error('Invalid transferTime format', [
                    'transfer_time' => $notificationData['transferTime'],
                    'transfer_id' => $transferId,
                ]);
            }
        }

        $this->entityManager->flush();

        $this->logger->info('PayBy transfer status updated from notification', [
            'transfer_id' => $transferId,
            'old_status' => $oldStatus->value,
            'new_status' => $status->value,
        ]);

        return true;
    }

    /**
     * @param array<string, mixed> $response
     */
    private function updateTransferFromApiResponse(PayByTransfer $transfer, array $response): void
    {
        if (isset($response['status'])) {
            $statusValue = $response['status'];
            if (is_string($statusValue) || is_int($statusValue)) {
                $status = PayByTransferStatus::tryFrom($statusValue);
                if (null !== $status) {
                    $transfer->setStatus($status);
                }
            }
        }

        if (isset($response['transferTime']) && is_string($response['transferTime'])) {
            try {
                $transferTime = new \DateTimeImmutable($response['transferTime']);
                $transfer->setTransferTime($transferTime);
            } catch (\Exception $e) {
                $this->logger->error('Invalid transferTime format in API response', [
                    'transfer_time' => $response['transferTime'],
                    'transfer_id' => $transfer->getTransferId(),
                ]);
            }
        }
    }

    /**
     * 根据商户转账单号获取转账
     */
    public function getTransfer(string $merchantTransferNo): ?PayByTransfer
    {
        return $this->transferRepository->findOneBy(['merchantTransferNo' => $merchantTransferNo]);
    }
}
