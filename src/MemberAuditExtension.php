<?php

namespace LeKoala\MemberAudit;

use SilverStripe\ORM\DB;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\FieldList;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\ReadonlyField;

/**
 * @link https://docs.silverstripe.org/en/5/developer_guides/extending/how_tos/track_member_logins/
 * @property \SilverStripe\Security\Member $owner
 * @property string $LastVisited
 * @property int $NumVisit
 * @method \SilverStripe\ORM\DataList<\LeKoala\MemberAudit\MemberAudit> Audits()
 */
class MemberAuditExtension extends Extension
{
    /**
     * @var array<string,string>
     */
    private static $db = [
        'LastVisited' => 'Datetime',
        'NumVisit' => 'Int',
    ];

    /**
     * @var array<string,string>
     */
    private static $has_many = [
        "Audits" => MemberAudit::class . ".Member",
    ];

    /**
     * This is only called if Security::login_recording is set to true
     *
     * @return void
     */
    public function authenticationSucceeded()
    {
        // empty
    }

    /**
     * This is only called if Security::login_recording is set to true
     *
     * @param array<mixed> $data
     * @param HTTPRequest $request
     * @return void
     */
    public function authenticationFailed($data, $request)
    {
        // empty
    }

    /**
     * This is only called if Security::login_recording is set to true
     *
     * @param array<mixed> $data
     * @param HTTPRequest $request
     * @return void
     */
    public function authenticationFailedUnknownUser($data, $request)
    {
        // empty
    }

    /**
     * This extension hook is called every time a member is logged in
     */
    public function afterMemberLoggedIn(): void
    {
        $this->logVisit();
    }

    /**
     * This extension hook is called when a member's session is restored from "remember me" cookies
     */
    public function memberAutoLoggedIn(): void
    {
        $this->logVisit();
    }

    /**
     * @param FieldList $fields
     * @return void
     */
    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldsToTab('Root.Main', [
            ReadonlyField::create('LastVisited', 'Last visited'),
            ReadonlyField::create('NumVisit', 'Number of visits'),
        ]);
    }

    /**
     * @param string $event
     * @param string|array<mixed> $data
     * @return int
     */
    public function audit($event, $data = null)
    {
        $r = new MemberAudit;
        $r->MemberID = $this->owner->ID;
        $r->Event = $event;
        if ($data) {
            if (is_array($data)) {
                $data = json_encode($data);
                if ($data === false) {
                    $data = '';
                }
            }
            $r->AuditData = $data;
        }
        return $r->write();
    }

    /**
     * Updates the LastVisited entry and increment NumVisit
     */
    protected function logVisit(): void
    {
        if (!Security::database_is_ready()) {
            return;
        }

        $lastVisitedTable = DataObject::getSchema()->tableForField(Member::class, 'LastVisited');

        DB::query(sprintf(
            'UPDATE "' . $lastVisitedTable . '" SET "LastVisited" = %s, "NumVisit" = "NumVisit" + 1 WHERE "ID" = %d',
            DB::get_conn()->now(),
            $this->owner->ID
        ));
    }
}
