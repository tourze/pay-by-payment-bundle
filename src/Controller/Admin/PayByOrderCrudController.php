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
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Tourze\PayByPaymentBundle\Entity\PayByConfig;
use Tourze\PayByPaymentBundle\Entity\PayByOrder;
use Tourze\PayByPaymentBundle\Enum\PayByOrderStatus;
use Tourze\PayByPaymentBundle\Enum\PayByPaySceneCode;

#[AdminCrud(routePath: '/pay-by/order', routeName: 'pay_by_order')]
final class PayByOrderCrudController extends AbstractPayByCrudController
{
    public static function getEntityFqcn(): string
    {
        return PayByOrder::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->setLabel('ID')
            ->hideOnForm()
        ;

        yield AssociationField::new('config')
            ->setLabel('支付配置')
            ->setRequired(true)
            ->setCrudController(PayByConfigCrudController::class)
            ->setHelp('此订单使用的PayBy支付配置')
        ;

        yield TextField::new('orderId')
            ->setLabel('PayBy订单ID')
            ->setRequired(true)
            ->setHelp('PayBy系统生成的唯一订单ID')
        ;

        yield TextField::new('merchantOrderNo')
            ->setLabel('商户订单号')
            ->setRequired(true)
            ->setHelp('商户系统的订单号')
        ;

        yield FormField::addPanel('金额信息')->setIcon('money-bill');

        yield Field::new('amount')
            ->setLabel('订单金额')
            ->setRequired(true)
            ->setHelp('订单的总金额')
            ->setFormType(NumberType::class)
            ->setFormTypeOption('scale', 2)
            ->hideOnIndex()
            ->onlyOnForms()
        ;

        yield Field::new('currency')
            ->setLabel('货币类型')
            ->setRequired(true)
            ->setHelp('订单的货币类型，默认AED')
            ->setFormType(TextType::class)
            ->hideOnIndex()
            ->onlyOnForms()
        ;

        yield TextField::new('amountFormatted')
            ->setLabel('订单金额')
            ->hideOnForm()
        ;

        yield FormField::addPanel('订单信息')->setIcon('shopping-cart');

        yield TextField::new('subject')
            ->setLabel('订单标题')
            ->setRequired(true)
            ->setHelp('订单的标题或商品名称')
        ;

        yield TextareaField::new('body')
            ->setLabel('订单描述')
            ->setHelp('订单的详细描述')
        ;

        yield TextField::new('paymentMethod')
            ->setLabel('支付方式')
            ->setHelp('使用的支付方式')
        ;

        yield ChoiceField::new('paySceneCode')
            ->setLabel('支付场景')
            ->setChoices([
                '网页支付' => PayByPaySceneCode::WEB,
                '小程序支付' => PayByPaySceneCode::MINI_PROGRAM,
                'APP支付' => PayByPaySceneCode::APP,
                'H5支付' => PayByPaySceneCode::H5,
            ])
            ->setRequired(true)
            ->setHelp('订单的支付场景')
        ;

        yield ChoiceField::new('status')
            ->setLabel('订单状态')
            ->setChoices([
                '待支付' => PayByOrderStatus::PENDING,
                '支付中' => PayByOrderStatus::PROCESSING,
                '支付成功' => PayByOrderStatus::SUCCESS,
                '支付失败' => PayByOrderStatus::FAILED,
                '已取消' => PayByOrderStatus::CANCELLED,
                '已退款' => PayByOrderStatus::REFUNDED,
            ])
            ->setRequired(true)
            ->setHelp('订单的当前状态')
        ;

        yield TextField::new('qrCodeData')
            ->setLabel('二维码数据')
            ->setHelp('支付二维码的数据')
            ->hideOnIndex()
        ;

        yield UrlField::new('qrCodeUrl')
            ->setLabel('二维码链接')
            ->setHelp('支付二维码的链接')
        ;

        yield UrlField::new('paymentUrl')
            ->setLabel('支付链接')
            ->setHelp('生成的支付链接')
        ;

        yield UrlField::new('notifyUrl')
            ->setLabel('通知地址')
            ->setHelp('支付成功后的通知地址')
        ;

        yield UrlField::new('returnUrl')
            ->setLabel('返回地址')
            ->setHelp('支付完成后的返回地址')
        ;

        yield CodeEditorField::new('accessoryContent')
            ->setLabel('扩展内容')
            ->setLanguage('javascript')
            ->setHelp('订单的扩展信息（JSON格式）')
            ->hideOnIndex()
        ;

        yield DateTimeField::new('payTime')
            ->setLabel('支付时间')
            ->setHelp('订单的支付时间')
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
            ->setEntityLabelInSingular('支付订单')
            ->setEntityLabelInPlural('支付订单列表')
            ->setPageTitle('index', 'PayBy订单管理')
            ->setPageTitle('detail', '订单详情')
            ->setPageTitle('edit', '编辑订单')
            ->setPageTitle('new', '新增订单')
            ->setSearchFields(['orderId', 'merchantOrderNo', 'subject', 'paymentMethod'])
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
            ->add('orderId')
            ->add('merchantOrderNo')
            ->add('subject')
            ->add('status')
            ->add('paySceneCode')
            ->add('config')
            ->add('createTime')
            ->add('paymentMethod')
        ;
    }
}
