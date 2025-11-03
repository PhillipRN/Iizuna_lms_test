<?php

namespace IizunaLMS\Schools;

class School
{
    public $id;
    public $name;
    public $zip;
    public $pref;
    public $address;
    public $phone;
    public $lms_code_id;
    public $is_paid;
    public $is_juku;
    public $create_date;
    public $update_date;

    function __construct($data) {
        $this->id = $data['id'] ?? null;
        $this->name = $data['school_name'];
        $this->zip = $data['school_zip'];
        $this->pref = $data['school_pref'];
        $this->address = $data['school_address'];
        $this->phone = $data['school_phone'];
        $this->lms_code_id = $data['lms_code_id'] ?? null;
        $this->is_paid = empty($data['is_paid']) ? 0 : 1;
        $this->is_juku = empty($data['is_juku']) ? 0 : 1; // チェックボックスの値はチェックをしないと飛んでこないため、値がない場合は0指定
    }

    public static function ConvertRecordToHtmlParameters($record) {
        return [
            'id' => $record['id'],
            'school_name' => $record['name'],
            'school_zip' => $record['zip'],
            'school_pref' => $record['pref'],
            'school_address' => $record['address'],
            'school_phone' => $record['phone'],
            'lms_code_id' => $record['lms_code_id'],
            'is_paid' => $record['is_paid'],
            'is_juku' => $record['is_juku'],
            'lms_code' => $record['lms_code']
        ];
    }
}