<?php

namespace Concrete\Package\QuickTabs;

use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Package\Package;

defined('C5_EXECUTE') or die('Access Denied.');

class Controller extends Package
{
    protected $pkgHandle = 'quick_tabs';

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::$appVersionRequired
     */
    protected $appVersionRequired = '8.5.2';

    protected $pkgVersion = '1.2.2';

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::getPackageName()
     */
    public function getPackageName()
    {
        return t('Quick Tabs');
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::getPackageDescription()
     */
    public function getPackageDescription()
    {
        return t('Add Tabs to your site');
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::install()
     */
    public function install()
    {
        parent::install();
        $this->installContentFile('config/install.xml');
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::upgrade()
     */
    public function upgrade()
    {
        $cn = $this->app->make(Connection::class);
        foreach ([
            'openclose',
            'tabTitle',
            'semantic',
            'tabHandle',
        ] as $field) {
            try {
                $cn->executeUpdate("UPDATE btQuickTabs SET {$field} = '' WHERE {$field} IS NULL");
            } catch (\Exception $_) {
            } catch (\Throwable $_) {
            }
        }
        parent::upgrade();
        $this->installContentFile('config/install.xml');
    }
}
