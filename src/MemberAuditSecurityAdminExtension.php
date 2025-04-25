<?php

namespace LeKoala\MemberAudit;

use SilverStripe\Forms\Form;
use SilverStripe\Core\Extension;
use SilverStripe\Admin\SecurityAdmin;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldImportButton;

/**
 * MemberAuditSecurityAdminExtension
 * @property \SilverStripe\Admin\SecurityAdmin $owner
 */
class MemberAuditSecurityAdminExtension extends Extension
{
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
        return $segment == "LeKoala-MemberAudit-MemberAudit";
    }

    /**
     * @param GridFieldConfig $config
     * @return void
     */
    public function updateGridFieldConfig(GridFieldConfig $config)
    {
        if ($this->isInMemberAuditTab()) {
            $config->removeComponentsByType(GridFieldAddNewButton::class);
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
            MemberAudit::clearOldRecords();
        }
    }
}
