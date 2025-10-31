<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Controller;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\PayByPaymentBundle\Exception\PayByException;
use Tourze\PayByPaymentBundle\Service\PayByOrderService;
use Tourze\PayByPaymentBundle\Service\PayByRefundService;
use Tourze\PayByPaymentBundle\Service\PayBySignatureService;
use Tourze\PayByPaymentBundle\Service\PayByTransferService;

#[WithMonologChannel(channel: 'pay_by_payment')]
final class PayByNotificationController extends AbstractController
{
    public function __construct(
        private PayByOrderService $orderService,
        private PayByRefundService $refundService,
        private PayByTransferService $transferService,
        private PayBySignatureService $signatureService,
        private LoggerInterface $logger,
    ) {
    }

    #[Route(path: '/api/pay-by/notification', name: 'pay_by_notification', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $this->logger->info('Received PayBy notification', [
            'content' => $request->getContent(),
            'headers' => $request->headers->all(),
        ]);

        try {
            $data = json_decode($request->getContent(), true);
            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new PayByException('Invalid JSON data');
            }

            if (!is_array($data)) {
                throw new PayByException('Invalid notification data format');
            }

            $signature = $request->headers->get('X-PayBy-Signature');
            if (null === $signature || '' === $signature) {
                throw new PayByException('Missing signature header');
            }

            /** @var array<string, mixed> $validatedData */
            $validatedData = $data;
            if (!$this->signatureService->verifySignature($validatedData, $signature)) {
                throw new PayByException('Invalid signature');
            }

            $result = $this->processNotification($validatedData);

            if ($result) {
                return new JsonResponse(['status' => 'success']);
            }

            return new JsonResponse(['status' => 'error', 'message' => 'Failed to process notification'], 400);
        } catch (PayByException $e) {
            $this->logger->error('Invalid PayBy notification', [
                'error' => $e->getMessage(),
                'content' => $request->getContent(),
            ]);

            return new JsonResponse(['status' => 'error', 'message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            $this->logger->error('Error processing PayBy notification', [
                'error' => $e->getMessage(),
                'content' => $request->getContent(),
            ]);

            return new JsonResponse(['status' => 'error', 'message' => 'Internal server error'], 500);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function processNotification(array $data): bool
    {
        if (isset($data['orderId'])) {
            return $this->orderService->handlePaymentNotification($data);
        }

        if (isset($data['refundId'])) {
            return $this->refundService->handleRefundNotification($data);
        }

        if (isset($data['transferId'])) {
            return $this->transferService->handleTransferNotification($data);
        }

        $this->logger->warning('Unknown notification type', [
            'data' => $data,
        ]);

        return false;
    }
}
