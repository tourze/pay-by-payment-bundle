<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\CrudFormType;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;
use Tourze\PayByPaymentBundle\Entity\PayByConfig;
use Tourze\PayByPaymentBundle\Entity\PayByOrder;
use Tourze\PayByPaymentBundle\Entity\PayByRefund;
use Tourze\PayByPaymentBundle\Enum\PayByRefundStatus;

#[AdminCrud(routePath: '/pay-by/refund', routeName: 'pay_by_refund')]
final class PayByRefundCrudController extends AbstractPayByCrudController
{
    public static function getEntityFqcn(): string
    {
        return PayByRefund::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->setLabel('ID')
            ->hideOnForm()
        ;

        yield AssociationField::new('order')
            ->setLabel('关联订单')
            ->setRequired(true)
            ->setCrudController(PayByOrderCrudController::class)
            ->setHelp('此退款关联的原订单')
        ;

        yield TextField::new('refundId')
            ->setLabel('PayBy退款ID')
            ->setHelp('PayBy系统生成的唯一退款ID')
            ->hideOnForm()
        ;

        yield TextField::new('merchantRefundNo')
            ->setLabel('商户退款号')
            ->setRequired(true)
            ->setHelp('商户系统的退款号')
        ;

        yield NumberField::new('refundAmount.amount')
            ->setLabel('退款金额')
            ->setRequired(true)
            ->setNumDecimals(2)
            ->setHelp('退款的金额')
        ;

        yield TextField::new('refundAmount.currency')
            ->setLabel('货币类型')
            ->setRequired(true)
            ->setHelp('退款的货币类型')
        ;

        yield ChoiceField::new('status')
            ->setLabel('退款状态')
            ->setChoices([
                '待处理' => PayByRefundStatus::PENDING,
                '处理中' => PayByRefundStatus::PROCESSING,
                '退款成功' => PayByRefundStatus::SUCCESS,
                '退款失败' => PayByRefundStatus::FAILED,
                '已取消' => PayByRefundStatus::CANCELLED,
            ])
            ->setRequired(true)
            ->setHelp('退款的当前状态')
        ;

        yield TextField::new('refundReason')
            ->setLabel('退款原因')
            ->setHelp('退款的原因说明')
        ;

        yield UrlField::new('notifyUrl')
            ->setLabel('通知地址')
            ->setHelp('退款成功后的通知地址')
        ;

        yield CodeEditorField::new('accessoryContent')
            ->setLabel('扩展内容')
            ->setLanguage('javascript')
            ->setHelp('退款的扩展信息（JSON格式）')
            ->hideOnIndex()
        ;

        yield DateTimeField::new('refundTime')
            ->setLabel('退款时间')
            ->setHelp('退款的实际处理时间')
            ->hideOnForm()
        ;

        yield DateTimeField::new('createTime')
            ->setLabel('创建时间')
            ->hideOnForm()
        ;

        yield DateTimeField::new('updateTime')
            ->setLabel('更新时间')
            ->hideOnForm()
        ;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('退款记录')
            ->setEntityLabelInPlural('退款记录列表')
            ->setPageTitle('index', 'PayBy退款管理')
            ->setPageTitle('detail', '退款详情')
            ->setPageTitle('edit', '编辑退款')
            ->setPageTitle('new', '新增退款')
            ->setSearchFields(['refundId', 'merchantRefundNo', 'order.orderId', 'refundReason'])
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setEntityPermission('ROLE_ADMIN')
            ->showEntityActionsInlined()
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, static function (Action $action): Action {
                return $action
                    ->setIcon('fa fa-eye')
                    ->setLabel('查看')
                    ->setCssClass('btn btn-info btn-sm')
                ;
            })
            ->setPermission(Action::DELETE, 'ROLE_ADMIN')
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('refundId')
            ->add('merchantRefundNo')
            ->add('status')
            ->add('refundReason')
            ->add('createTime')
        ;
    }
}
