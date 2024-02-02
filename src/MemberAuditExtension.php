<?php

namespace LeKoala\MemberAudit;

use SilverStripe\ORM\DB;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\FieldList;
use SilverStripe\Security\Member;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Security;
use SilverStripe\Forms\ReadonlyField;

/**
 * A lot of base functionalities for your members
 *
 * Most group of functions are grouped within traits when possible
 *
 * @link https://docs.silverstripe.org/en/5/developer_guides/extending/how_tos/track_member_logins/
 * @property \SilverStripe\Security\Member $owner
 * @property string $LastVisited
 * @property int $NumVisit
 * @method \SilverStripe\ORM\DataList|\LeKoala\MemberAudit\MemberAudit[] Audits()
 */
class MemberAuditExtension extends DataExtension
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
    }

    /**
     * This is only called if Security::login_recording is set to true
     *
     * @param array $data
     * @param HTTPRequest $request
     * @return void
     */
    public function authenticationFailed($data, $request)
    {
    }

    /**
     * This is only called if Security::login_recording is set to true
     *
     * @param array $data
     * @param HTTPRequest $request
     * @return void
     */
    public function authenticationFailedUnknownUser($data, $request)
    {
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
