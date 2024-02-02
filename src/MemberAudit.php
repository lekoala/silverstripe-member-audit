<?php

namespace LeKoala\MemberAudit;

use SilverStripe\ORM\DB;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\TextField;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\Control\Controller;

/**
 * Audit specific events that may require more attention
 *
 * Simply call $member->audit('myevent',$mydata) to create a new audit record
 *
 * Try to keep "myevent" simple and consistent and set variable data in the data parameter
 *
 * @property string $IP
 * @property string $Event
 * @property string $AuditData
 * @property int $MemberID
 * @property int $SourceMemberID
 * @method \SilverStripe\Security\Member Member()
 * @method \SilverStripe\Security\Member SourceMember()
 */
class MemberAudit extends DataObject
{
    /**
     * When using namespace, specify table name
     * @var string
     */
    private static $table_name = 'MemberAudit';

    /**
     * @var array<string,string>
     */
    private static $db = [
        'Event' => 'Varchar(39)',
        'AuditData' => 'Text',
        "IP" => "Varchar(45)",
    ];

    /**
     * @var array<string,class-string>
     */
    private static $has_one = [
        'Member' => Member::class,
        'SourceMember' => Member::class,
    ];

    /**
     * @var array<string>
     */
    private static $summary_fields = [
        'Created', 'Event', 'SourceMember.Title', 'AuditData.LimitCharacters'
    ];

    /**
     * @var string
     */
    private static $default_sort = 'Created DESC';

    /**
     * @config
     * @var string
     */
    private static $keep_duration = "-1 year";

    /**
     * @return void
     */
    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();

        // Store member triggering the audit trail
        $member = Security::getCurrentUser();
        $this->SourceMemberID = $member ? $member->ID : 0;

        // Store ip
        if (Controller::has_curr() && !$this->IP) {
            $this->IP = Controller::curr()->getRequest()->getIP();
        }
    }

    public static function clearOldRecords(): void
    {
        $keep_duration = self::config()->keep_duration;
        if (!$keep_duration) {
            return;
        }
        $date = date('Y-m-d', strtotime($keep_duration));
        DB::query("DELETE FROM MemberAudit WHERE Created < '$date'");
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->replaceField("AuditData", new TextField("AuditData"));
        return $fields;
    }

    /**
     * @return string
     */
    public function forTemplate()
    {
        return $this->Created . ' - ' . $this->Event;
    }

    public function canEdit($member = null)
    {
        return false;
    }

    public function canDelete($member = null)
    {
        return false;
    }

    public function AuditDataShort(): string
    {
        return substr((string)$this->AuditData, 0, 100) . '...';
    }
}
