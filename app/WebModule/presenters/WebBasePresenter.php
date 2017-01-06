<?php

namespace App\WebModule\Presenters;

use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\User\User;
use App\Presenters\BasePresenter;
use App\Services\Authorizator;
use Skautis\Skautis;

abstract class WebBasePresenter extends BasePresenter
{
    /**
     * @var \App\Model\ACL\ResourceRepository
     * @inject
     */
    public $resourceRepository;

    /**
     * @var \App\Model\ACL\RoleRepository
     * @inject
     */
    public $roleRepository;

    /**
     * @var \App\Model\CMS\PageRepository
     * @inject
     */
    public $pageRepository;

    /**
     * @var \App\Model\Settings\SettingsRepository
     * @inject
     */
    public $settingsRepository;

    /**
     * @var \App\Model\User\UserRepository
     * @inject
     */
    public $userRepository;

    /**
     * @var Skautis
     * @inject
     */
    public $skautIS;

    /**
     * @var User
     */
    protected $dbuser;

    /**
     * @return CssLoader
     */
    protected function createComponentCss()
    {
        return $this->webLoader->createCssLoader('web');
    }

    /**
     * @return JavaScriptLoader
     */
    protected function createComponentJs()
    {
        return $this->webLoader->createJavaScriptLoader('web');
    }

    public function startup()
    {
        parent::startup();

        $this->checkInstallation();

        if ($this->user->isLoggedIn() && !$this->skautIS->getUser()->isLoggedIn())
            $this->user->logout(true);

        $this->user->setAuthorizator(new Authorizator($this->roleRepository, $this->resourceRepository));

        $this->dbuser = $this->user->isLoggedIn() ? $this->userRepository->findUserById($this->user->id) : null;
    }

    public function beforeRender()
    {
        parent::beforeRender();

        $this->template->dbuser = $this->dbuser;

        $this->template->backlink = $this->getHttpRequest()->getUrl()->getPath();

        $this->template->logo = $this->settingsRepository->getValue('logo');
        $this->template->footer = $this->settingsRepository->getValue('footer');
        $this->template->seminarName = $this->settingsRepository->getValue('seminar_name');

        $this->template->unregisteredRole = $this->roleRepository->findRoleByUntranslatedName(\App\Model\ACL\Role::UNREGISTERED);
        $this->template->unapprovedRole = $this->roleRepository->findRoleByUntranslatedName(\App\Model\ACL\Role::UNAPPROVED);

        $this->template->adminAccess = $this->user->isAllowed(Resource::ADMIN, Permission::ACCESS);
        $this->template->displayUsersRoles = $this->settingsRepository->getValue('display_users_roles');

        $this->template->pages = $this->pageRepository->findPublishedPagesOrderedByPosition();
        $this->template->sidebarVisibility = false;

        $this->template->settings = $this->settingsRepository;
    }

    private function checkInstallation() {
        try {
            if (!filter_var($this->settingsRepository->getValue('admin_created'), FILTER_VALIDATE_BOOLEAN))
                $this->redirect(':Install:Install:default');
        } catch (\Doctrine\DBAL\Exception\TableNotFoundException $ex) {
            $this->redirect(':Install:Install:default');
        } catch (\App\Model\Settings\SettingsException $ex) {
            $this->redirect(':Install:Install:default');
        }
    }
}