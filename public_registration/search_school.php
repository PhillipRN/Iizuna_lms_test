<?php
require_once (__DIR__ . '/../app/bootstrap.php');

use IizunaLMS\Errors\Error;
use IizunaLMS\Helpers\DisplayJsonHelper;
use IizunaLMS\Helpers\RequestHelper;
use IizunaLMS\Models\SchoolModel;

$params = RequestHelper::GetPostParams();

if (empty($params['sck']) || $params['sck'] != $_SESSION[SESS_TEACHER_REGISTER_CHECK_KEY]) {
    DisplayJsonHelper::ShowErrorAndExit(Error::ERROR_TEACHER_REGISTER_INVALID_PARAMETERS);
}

$zip = str_replace('-', '', $params['school_zip']);

$records = (new SchoolModel())->GetsByKeyValue('zip', $zip);

$schools = [];

foreach ($records as $record) {
    $schools[] = [
        'id' => $record['id'],
        'name' => $record['name'],
        'pref' => $record['pref'],
        'address' => $record['address']
    ];
}

$result = [
    'schools' => $schools
];

DisplayJsonHelper::ShowAndExit($result);