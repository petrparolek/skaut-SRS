<?php

declare(strict_types=1);

namespace App\AdminModule\MailingModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\Settings\SettingsException;
use App\Model\User\UserRepository;
use App\Services\MailService;
use Kdyby\Events\Event;
use Nette;
use Nette\Application\UI\Form;
use Nette\Mail\SendException;
use Ublaboo\Mailing\Exception\MailingException;
use Ublaboo\Mailing\Exception\MailingMailCreationException;

/**
 * Formulář pro vytvoření e-mailu.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SendForm
{
    use Nette\SmartObject;

    /**
     * Událost po úspěšně odeslaném e-mailu.
     * @var Event
     */
    public $mailSuccess;

    /** @var BaseForm */
    private $baseFormFactory;

    /** @var MailService */
    private $mailService;

    /** @var RoleRepository */
    private $roleRepository;

    /** @var UserRepository */
    private $userRepository;


    public function __construct(
        BaseForm $baseFormFactory,
        MailService $mailService,
        RoleRepository $roleRepository,
        UserRepository $userRepository
    ) {
        $this->baseFormFactory = $baseFormFactory;
        $this->mailService     = $mailService;
        $this->roleRepository  = $roleRepository;
        $this->userRepository  = $userRepository;
    }

    /**
     * Vytvoří formulář.
     */
    public function create() : Form
    {
        $form = $this->baseFormFactory->create();

        $recipientRolesMultiSelect = $form->addMultiSelect(
            'recipientRoles',
            'admin.mailing.send_recipient_roles',
            $this->roleRepository->getRolesWithoutRolesOptionsWithApprovedUsersCount([Role::GUEST, Role::UNAPPROVED, Role::NONREGISTERED])
        );

        $recipientUsersMultiSelect = $form->addMultiSelect(
            'recipientUsers',
            'admin.mailing.send_recipient_users',
            $this->userRepository->getUsersOptions()
        )
            ->setAttribute('data-live-search', 'true');

        $recipientRolesMultiSelect
            ->addConditionOn($recipientUsersMultiSelect, Form::BLANK)
            ->addRule(Form::FILLED, 'admin.mailing.send_recipients_empty');

        $recipientUsersMultiSelect
            ->addConditionOn($recipientRolesMultiSelect, Form::BLANK)
            ->addRule(Form::FILLED, 'admin.mailing.send_recipients_empty');

        $form->addText('copy', 'admin.mailing.send_copy')
            ->addCondition(Form::FILLED)
            ->addRule(Form::EMAIL, 'admin.mailing.send_copy_format');

        $form->addText('subject', 'admin.mailing.send_subject')
            ->addRule(Form::FILLED, 'admin.mailing.send_subject_empty');

        $form->addTextArea('text', 'admin.mailing.send_text')
            ->addRule(Form::FILLED, 'admin.mailing.send_text_empty')
            ->setAttribute('class', 'tinymce-paragraph');

        $form->addSubmit('submit', 'admin.mailing.send_send');

        $form->getElementPrototype()->onsubmit('tinyMCE.triggerSave()');
        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     * @throws SettingsException
     * @throws \Throwable
     * @throws MailingException
     * @throws MailingMailCreationException
     */
    public function processForm(Form $form, \stdClass $values) : void
    {
        try {
            $recipientsRoles = $this->roleRepository->findRolesByIds($values['recipientRoles']);
            $recipientsUsers = $this->userRepository->findUsersByIds($values['recipientUsers']);

            $this->mailService->sendMail($recipientsRoles, $recipientsUsers, $values['copy'], $values['subject'], $values['text']);
            $this->mailSuccess = true;
        } catch (SendException $ex) {
            $this->mailSuccess = false;
        }
    }
}
