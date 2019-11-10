<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\CMS\Content\ContentDTO;
use App\Model\Enums\ProgramRegistrationType;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\Settings\SettingsFacade;
use App\Model\User\UserRepository;
use App\Services\ProgramService;
use Nette\Application\UI\Control;

/**
 * Komponenta s výběrem programů.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class ProgramsContentControl extends Control
{
    /** @var UserRepository */
    private $userRepository;

    /** @var RoleRepository */
    private $roleRepository;

    /** @var SettingsFacade */
    private $settingsFacade;

    /** @var ProgramService */
    private $programService;


    public function __construct(
        UserRepository $userRepository,
        RoleRepository $roleRepository,
        SettingsFacade $settingsFacade,
        ProgramService $programService
    ) {
        parent::__construct();

        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
        $this->settingsFacade = $settingsFacade;
        $this->programService = $programService;
    }

    /**
     * @throws SettingsException
     * @throws \Throwable
     */
    public function render(ContentDTO $content) : void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/programs_content.latte');

        $template->heading = $content->getHeading();

        $template->backlink = $this->getPresenter()->getHttpRequest()->getUrl()->getPath();

        $template->registerProgramsAllowed       = $this->programService->isAllowedRegisterPrograms();
        $template->registerProgramsNotAllowed    = $this->settingsFacade->getValue(Settings::REGISTER_PROGRAMS_TYPE) === ProgramRegistrationType::NOT_ALLOWED;
        $template->registerProgramsAllowedFromTo = $this->settingsFacade->getValue(Settings::REGISTER_PROGRAMS_TYPE) === ProgramRegistrationType::ALLOWED_FROM_TO;
        $template->registerProgramsFrom          = $this->settingsFacade->getDateTimeValue(Settings::REGISTER_PROGRAMS_FROM);
        $template->registerProgramsTo            = $this->settingsFacade->getDateTimeValue(Settings::REGISTER_PROGRAMS_TO);

        $user                = $this->getPresenter()->user;
        $template->guestRole = $user->isInRole($this->roleRepository->findBySystemName(Role::GUEST)->getName());

        if ($user->isLoggedIn()) {
            $template->userHasPermission     = $user->isAllowed(Resource::PROGRAM, Permission::CHOOSE_PROGRAMS);
            $template->userWaitingForPayment = ! $this->settingsFacade->getBoolValue(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT)
                && $this->userRepository->findById($user->getId())->getWaitingForPaymentApplications()->count() > 0;
        }

        $template->render();
    }
}
