<?php

namespace LeKoala\MemberAudit;

use SilverStripe\Forms\Form;
use SilverStripe\Core\Extension;
use SilverStripe\Security\Member;
use SilverStripe\Admin\SecurityAdmin;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldImportButton;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordViewer;
use SilverStripe\Forms\GridField\GridFieldDataColumns;

/**
 * @property \SilverStripe\Admin\SecurityAdmin $owner
 */
class BaseSecurityAdminExtension extends Extension
{
    /**
     * @var array<string>
     */
    private static $allowed_actions = [
        'members_audit',
    ];

    protected function getSecurityAdmin(): SecurityAdmin
    {
        return $this->owner;
    }

    protected function getRequest(): HTTPRequest
    {
        return $this->owner->getRequest();
    }

    protected function isInMemberAuditTab(): bool
    {
        $url = explode("/", $this->owner->getRequest()->getURL());
        $segment = $url[2] ?? "";
        return $segment == "members_audit";
    }

    /**
     * @param GridFieldConfig $config
     * @return void
     */
    public function updateGridFieldConfig(GridFieldConfig $config)
    {
        if ($this->isInMemberAuditTab()) {
            $config->removeComponentsByType(GridFieldImportButton::class);
        }
    }

    /**
     * @param Form $form
     * @return void
     */
    public function updateEditForm(Form $form)
    {
        if ($this->isInMemberAuditTab()) {
            $this->addMemberAuditTab($form);
        }
    }

    /**
     * @param Form $form
     * @return void
     */
    protected function addMemberAuditTab(Form $form)
    {
        MemberAudit::clearOldRecords();

        $MemberAudit_SNG = MemberAudit::singleton();
        $list = MemberAudit::get();
        if ($list->count()) {
            $auditTab = $form->Fields();
            $auditTab->removeByName('members_audit');

            $gfc = GridFieldConfig_RecordViewer::create();
            $MemberAuditGrid = new GridField('MemberAudit', _t('BaseSecurityAdminExtension.MemberAudit', "Members audit events"), $list, $gfc);
            $MemberAuditGrid->setForm($form);
            /** @var GridFieldDataColumns $GridFieldDataColumns */
            $GridFieldDataColumns = $gfc->getComponentByType(GridFieldDataColumns::class);
            $GridFieldDataColumns->setDisplayFields([
                'Created' => $MemberAudit_SNG->fieldLabel('Created'),
                'Member.Title' => $MemberAudit_SNG->fieldLabel('Member'),
                'Event' => $MemberAudit_SNG->fieldLabel('Event'),
                'AuditDataShort' => $MemberAudit_SNG->fieldLabel('AuditData'),
            ]);
            $auditTab->push($MemberAuditGrid);
        }
    }
}
