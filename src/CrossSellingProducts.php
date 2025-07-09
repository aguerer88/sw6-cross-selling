<?php declare(strict_types=1);

namespace CrossSelling;

use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;

class CrossSellingProducts extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);
    }
}
