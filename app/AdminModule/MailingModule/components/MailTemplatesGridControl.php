<?php

declare(strict_types=1);

namespace App\AdminModule\MailingModule\Components;

use App\Model\Mailing\TemplateRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Kdyby\Translation\Translator;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridColumnStatusException;
use Ublaboo\DataGrid\Exception\DataGridException;

/**
 * Komponenta pro správu automatických e-mailů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class MailTemplatesGridControl extends Control
{
    /** @var Translator */
    private $translator;

    /** @var TemplateRepository */
    private $templateRepository;


    public function __construct(Translator $translator, TemplateRepository $templateRepository)
    {
        parent::__construct();

        $this->translator         = $translator;
        $this->templateRepository = $templateRepository;
    }

    /**
     * Vykreslí komponentu.
     */
    public function render() : void
    {
        $this->template->render(__DIR__ . '/templates/mail_templates_grid.latte');
    }

    /**
     * Vytvoří komponentu.
     * @param $name
     * @throws DataGridColumnStatusException
     * @throws DataGridException
     */
    public function createComponentMailTemplatesGrid($name) : void
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->templateRepository->createQueryBuilder('t')
            ->where('t.system = FALSE'));
        $grid->setDefaultSort(['type' => 'ASC']);
        $grid->setPagination(false);

        $grid->addColumnText('type', 'admin.mailing.templates_type')
            ->setRenderer(function ($row) {
                return $this->translator->translate('common.mailing.template_type.' . $row->getType());
            });

        $grid->addColumnStatus('active', 'admin.mailing.templates_active')
            ->addOption(false, 'admin.mailing.templates_active_inactive')
            ->setClass('btn-danger')
            ->endOption()
            ->addOption(true, 'admin.mailing.templates_active_active')
            ->setClass('btn-success')
            ->endOption()
            ->onChange[] = [$this, 'changeActive'];

        $grid->addColumnText('sendToUser', 'admin.mailing.templates_send_to_user')
            ->setReplacement([
                '0' => $this->translator->translate('admin.common.no'),
                '1' => $this->translator->translate('admin.common.yes'),
            ]);

        $grid->addColumnText('sendToOrganizer', 'admin.mailing.templates_send_to_organizer')
            ->setReplacement([
                '0' => $this->translator->translate('admin.common.no'),
                '1' => $this->translator->translate('admin.common.yes'),
            ]);

        $grid->addAction('edit', 'admin.common.edit', 'Templates:edit');
    }

    /**
     * Aktivuje/deaktivuje automatický e-mail.
     * @param $id
     * @param $active
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws AbortException
     */
    public function changeActive($id, $active) : void
    {
        $p = $this->getPresenter();

        $template = $this->templateRepository->findById($id);

        if ($template->isSystem() && ! $active) {
            $p->flashMessage('admin.mailing.templates_change_active_denied', 'danger');
        } else {
            $template->setActive($active);
            $this->templateRepository->save($template);

            $p->flashMessage('admin.mailing.templates_changed_active', 'success');
        }

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['mailTemplatesGrid']->redrawItem($id);
        } else {
            $this->redirect('this');
        }
    }
}
