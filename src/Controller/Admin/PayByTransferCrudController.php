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
use Tourze\PayByPaymentBundle\Entity\PayByTransfer;
use Tourze\PayByPaymentBundle\Enum\PayByTransferStatus;
use Tourze\PayByPaymentBundle\Enum\PayByTransferType;

#[AdminCrud(routePath: '/pay-by/transfer', routeName: 'pay_by_transfer')]
final class PayByTransferCrudController extends AbstractPayByCrudController
{
    public static function getEntityFqcn(): string
    {
        return PayByTransfer::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->setLabel('ID')
            ->hideOnForm()
        ;

        yield TextField::new('transferId')
            ->setLabel('PayBy转账ID')
            ->setRequired(true)
            ->setHelp('PayBy系统生成的唯一转账ID')
        ;

        yield TextField::new('merchantTransferNo')
            ->setLabel('商户转账号')
            ->setRequired(true)
            ->setHelp('商户系统的转账号')
        ;

        yield FormField::addPanel('金额信息')->setIcon('money-bill');

        yield Field::new('amount')
            ->setLabel('转账金额')
            ->setRequired(true)
            ->setHelp('转账的总金额')
            ->setFormType(NumberType::class)
            ->setFormTypeOption('scale', 2)
            ->hideOnIndex()
            ->onlyOnForms()
        ;

        yield Field::new('currency')
            ->setLabel('货币类型')
            ->setRequired(true)
            ->setHelp('转账的货币类型，默认AED')
            ->setFormType(TextType::class)
            ->hideOnIndex()
            ->onlyOnForms()
        ;

        yield TextField::new('amountFormatted')
            ->setLabel('转账金额')
            ->hideOnForm()
        ;

        yield FormField::addPanel('转账信息')->setIcon('exchange-alt');

        yield ChoiceField::new('transferType')
            ->setLabel('转账类型')
            ->setChoices([
                '转账到银行卡' => PayByTransferType::TRANSFER_TO_BANK,
                '转账到余额' => PayByTransferType::TRANSFER_TO_BALANCE,
                '转账到第三方' => PayByTransferType::TRANSFER_TO_THIRD_PARTY,
            ])
            ->setRequired(true)
            ->setHelp('转账的类型')
        ;

        yield TextField::new('fromAccount')
            ->setLabel('转出账户')
            ->setRequired(true)
            ->setHelp('转账的源账户')
        ;

        yield TextField::new('toAccount')
            ->setLabel('转入账户')
            ->setHelp('转账的目标账户')
        ;

        yield ChoiceField::new('status')
            ->setLabel('转账状态')
            ->setChoices([
                '待处理' => PayByTransferStatus::PENDING,
                '处理中' => PayByTransferStatus::PROCESSING,
                '转账成功' => PayByTransferStatus::SUCCESS,
                '转账失败' => PayByTransferStatus::FAILED,
                '已取消' => PayByTransferStatus::CANCELLED,
            ])
            ->setRequired(true)
            ->setHelp('转账的当前状态')
        ;

        yield TextField::new('transferReason')
            ->setLabel('转账原因')
            ->setHelp('转账的原因说明')
        ;

        yield UrlField::new('notifyUrl')
            ->setLabel('通知地址')
            ->setHelp('转账成功后的通知地址')
        ;

        yield CodeEditorField::new('bankTransferInfo')
            ->setLabel('银行转账信息')
            ->setLanguage('javascript')
            ->setHelp('银行转账相关信息（JSON格式）')
            ->hideOnIndex()
        ;

        yield CodeEditorField::new('accessoryContent')
            ->setLabel('扩展内容')
            ->setLanguage('javascript')
            ->setHelp('转账的扩展信息（JSON格式）')
            ->hideOnIndex()
        ;

        yield DateTimeField::new('transferTime')
            ->setLabel('转账时间')
            ->setHelp('转账的实际处理时间')
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
            ->setEntityLabelInSingular('转账记录')
            ->setEntityLabelInPlural('转账记录列表')
            ->setPageTitle('index', 'PayBy转账管理')
            ->setPageTitle('detail', '转账详情')
            ->setPageTitle('edit', '编辑转账')
            ->setPageTitle('new', '新增转账')
            ->setSearchFields(['transferId', 'merchantTransferNo', 'fromAccount', 'toAccount'])
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
            ->add('transferId')
            ->add('merchantTransferNo')
            ->add('fromAccount')
            ->add('toAccount')
            ->add('status')
            ->add('transferType')
            ->add('createTime')
        ;
    }
}
