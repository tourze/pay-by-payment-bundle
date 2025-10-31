<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
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
use Tourze\PayByPaymentBundle\Enum\PayByPaySceneCode;

#[AdminCrud(routePath: '/pay-by/config', routeName: 'pay_by_config')]
final class PayByConfigCrudController extends AbstractPayByCrudController
{
    public static function getEntityFqcn(): string
    {
        return PayByConfig::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->setLabel('ID')
            ->hideOnForm()
        ;

        yield TextField::new('name')
            ->setLabel('配置名称')
            ->setRequired(true)
            ->setHelp('给这个配置起一个易于识别的名称')
        ;

        yield TextareaField::new('description')
            ->setLabel('配置描述')
            ->setHelp('描述这个配置的用途和适用场景')
        ;

        yield UrlField::new('apiBaseUrl')
            ->setLabel('API基础地址')
            ->setRequired(true)
            ->setHelp('PayBy API的基础URL，例如：https://api.payby.com/v1')
        ;

        yield TextField::new('apiKey')
            ->setLabel('API密钥')
            ->setRequired(true)
            ->setHelp('PayBy分配的API密钥')
            ->hideOnIndex()
        ;

        yield TextField::new('apiSecret')
            ->setLabel('API秘钥')
            ->setRequired(true)
            ->setHelp('PayBy分配的API密钥，注意保密')
            ->hideOnIndex()
        ;

        yield TextField::new('merchantId')
            ->setLabel('商户ID')
            ->setRequired(true)
            ->setHelp('PayBy分配的商户ID')
        ;

        yield UrlField::new('callbackUrl')
            ->setLabel('回调地址')
            ->setHelp('支付结果通知的接收地址')
        ;

        yield IntegerField::new('timeout')
            ->setLabel('超时时间（秒）')
            ->setHelp('API调用的超时时间，最大300秒')
        ;

        yield IntegerField::new('retryAttempts')
            ->setLabel('重试次数')
            ->setHelp('API调用失败时的重试次数，最多10次')
        ;

        yield BooleanField::new('isDefault')
            ->setLabel('是否默认配置')
            ->setHelp('设置为默认使用的PayBy配置')
        ;

        yield CodeEditorField::new('extraConfig')
            ->setLabel('额外配置')
            ->setLanguage('javascript')
            ->setHelp('额外的配置信息（JSON格式）')
            ->hideOnIndex()
        ;

        yield BooleanField::new('enabled')
            ->setLabel('是否启用')
            ->setHelp('是否启用此配置进行支付处理')
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
            ->setEntityLabelInSingular('PayBy配置')
            ->setEntityLabelInPlural('PayBy配置列表')
            ->setPageTitle('index', 'PayBy配置管理')
            ->setPageTitle('detail', 'PayBy配置详情')
            ->setPageTitle('edit', '编辑PayBy配置')
            ->setPageTitle('new', '新增PayBy配置')
            ->setSearchFields(['name', 'merchantId', 'description'])
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
            ->add('name')
            ->add('merchantId')
            ->add('enabled')
            ->add('isDefault')
            ->add('createTime')
        ;
    }
}
