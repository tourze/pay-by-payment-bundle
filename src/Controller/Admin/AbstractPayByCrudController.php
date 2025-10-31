<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use Symfony\Contracts\Service\Attribute\Required;
use Tourze\PayByPaymentBundle\Entity\PayByAmount;
use Tourze\PayByPaymentBundle\Entity\PayByConfig;
use Tourze\PayByPaymentBundle\Repository\PayByConfigRepository;

/**
 * @phpstan-ignore missingType.generics
 */
abstract class AbstractPayByCrudController extends AbstractCrudController
{
    #[Required]
    public PayByConfigRepository $payByConfigRepository;

    public function createEntity(string $entityFqcn): object
    {
        // 特殊处理 PayByAmount，因为它需要构造函数参数
        if (PayByAmount::class === $entityFqcn) {
            /** @var PayByAmount $entity */
            $entity = new PayByAmount('0.00', 'AED');
        } else {
            $entity = parent::createEntity($entityFqcn);
        }

        // 如果实体有PayByConfig关联，设置默认配置
        if (method_exists($entity, 'setPayByConfig')) {
            $config = $this->payByConfigRepository->findOneBy(['enabled' => true]);

            if (null !== $config) {
                $entity->setPayByConfig($config);
            }
        }

        return $entity;
    }
}
