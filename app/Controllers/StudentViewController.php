<?php
namespace IizunaLMS\Controllers;

use IizunaLMS\Datas\TeacherLoginData;
use IizunaLMS\Helpers\SessionHelper;
use IizunaLMS\Schools\SchoolGroupLoader;
use IizunaLMS\Students\Datas\StudentPageParameters;
use IizunaLMS\Students\StudentLoader;

/**
 * 生徒一覧画面のコントローラークラス
 */
class StudentViewController
{
    const PAGE_LIMIT = 100;

    /**
     * @var TeacherLoginData 教師データ
     */
    private TeacherLoginData $teacher;

    /**
     * @var array リクエストパラメータ
     */
    private $params;

    /**
     * @var StudentLoader 生徒データローダー
     */
    private $studentLoader;

    /**
     * コンストラクタ
     *
     * @param TeacherLoginData $teacher 教師データ
     * @param array $params リクエストパラメータ
     */
    public function __construct(TeacherLoginData $teacher, $params)
    {
        $this->teacher = $teacher;
        $this->params = $params;
        $this->studentLoader = new StudentLoader();
    }

    /**
     * ビュー表示に必要なデータを取得する
     *
     * @return array ビューデータ
     */
    public function getViewData($limit=null)
    {
        $limit = $limit ?? static::PAGE_LIMIT;

        // リクエストパラメータの取得
        $currentPage = $this->params['page'] ?? 1;
        $sortStudentNumber = $this->params['sn'] ?? 1;
        $changeLmsCodeId = $this->params['lcid'] ?? null;

        // 学校・グループ情報の取得
        $schoolGroups = $this->getSchoolGroups();

        // パラメータ初期化が必要な場合
        if ($changeLmsCodeId == null) {
            $changeLmsCodeId = $this->initializeParameters($schoolGroups, $changeLmsCodeId);
        }

        // 生徒ページパラメータの取得
        $studentPageParameters = $this->getStudentPageParameters($changeLmsCodeId);

        // 生徒レコードと最大ページ数の取得
        $records = [];
        $maxPageNumber = 0;
        $currentLmsCodeId = null;
        $currentSchoolGroup = null;

        if (!empty($studentPageParameters)) {
            $currentLmsCodeId = $studentPageParameters->current_lms_code_id;
            $records = $this->getStudentRecords($studentPageParameters, $currentPage, $limit, $sortStudentNumber);
            $maxPageNumber = $this->studentLoader->getMaxPageNumber($currentLmsCodeId, $limit);

            // 生徒レコードに学校情報を追加
            $records = $this->enrichStudentRecordsWithSchoolInfo($records, $schoolGroups);

            foreach ($schoolGroups as $schoolGroup) {
                if ($currentLmsCodeId == $schoolGroup['lms_code_id'])
                {
                    $currentSchoolGroup = $schoolGroup;
                    break;
                }
            }
        }

        return [
            'teacher' => $this->teacher,
            'records' => $records,
            'schoolGroups' => $schoolGroups,
            'currentSchoolGroup' => $currentSchoolGroup,
            'currentPage' => $currentPage,
            'maxPageNumber' => $maxPageNumber,
            'currentLmsCodeId' => $currentLmsCodeId,
            'sortStudentNumber' => $sortStudentNumber
        ];
    }

    /**
     * 学校・グループ情報を取得する
     *
     * @return array 学校・グループ情報
     */
    private function getSchoolGroups()
    {
        $schoolId = $this->teacher->school_id;
        return SchoolGroupLoader::GetSchoolAndGroups($schoolId);
    }

    /**
     * パラメータを初期化する
     *
     * @param array $schoolGroups 学校グループデータ
     * @param mixed $changeLmsCodeId 変更対象のLMSコードID
     * @return mixed 初期化後のLMSコードID
     */
    private function initializeParameters($schoolGroups, $changeLmsCodeId)
    {
        SessionHelper::UnsetStudentPageParameters();

        if (empty($changeLmsCodeId)) {
            foreach ($schoolGroups as $schoolGroup) {
                if (!empty($schoolGroup['is_school'])) {
                    $changeLmsCodeId = $schoolGroup['lms_code_id'];
                    break;
                }
            }
        }

        return $changeLmsCodeId;
    }

    /**
     * 生徒ページパラメータを取得する
     *
     * @param mixed $changeLmsCodeId 変更対象のLMSコードID
     * @return StudentPageParameters|null
     */
    private function getStudentPageParameters($changeLmsCodeId)
    {
        if (!empty($changeLmsCodeId)) {
            $studentPageParameters = new StudentPageParameters([
                'current_lms_code_id' => $changeLmsCodeId
            ]);
            SessionHelper::SetStudentPageParameters($studentPageParameters);
            return $studentPageParameters;
        } else if (SessionHelper::IssetStudentPageParameters()) {
            return SessionHelper::GetStudentPageParameters();
        }

        return null;
    }

    /**
     * 生徒レコードを取得する
     *
     * @param StudentPageParameters $studentPageParameters 生徒ページパラメータ
     * @param int $currentPage 現在のページ番号
     * @param int $limit ページ最大表示数
     * @param int $sortStudentNumber 学籍番号のソート順
     * @return array 生徒レコード
     */
    private function getStudentRecords($studentPageParameters, $currentPage, $limit, $sortStudentNumber)
    {
        $sortParams = $this->getSortParameters($sortStudentNumber);

        return $this->studentLoader->getStudentsByLmsCodeIdAndPage(
            $studentPageParameters->current_lms_code_id,
            $currentPage,
            $limit,
            $sortParams
        );
    }

    /**
     * ソートパラメータを取得する
     *
     * @param int $sortStudentNumber 学籍番号のソート順
     * @return array ソートパラメータ
     */
    private function getSortParameters($sortStudentNumber)
    {
        $sortParams = [];

        if ($sortStudentNumber == 1) {
            $sortParams['student_number'] = 'ASC';
        } else if ($sortStudentNumber == 2) {
            $sortParams['student_number'] = 'DESC';
        }

        return $sortParams;
    }

    /**
     * 生徒レコードに学校情報を追加する
     *
     * @param array $records 生徒レコード
     * @param array $schoolGroups 学校グループデータ
     * @return array 学校情報が追加された生徒レコード
     */
    private function enrichStudentRecordsWithSchoolInfo($records, $schoolGroups)
    {
        if (empty($records)) {
            return $records;
        }

        // 学校マップの作成
        $schoolMap = $this->createSchoolMap($schoolGroups);

        // 各生徒レコードに学校情報を追加
        foreach ($records as $key => $record) {
            $schoolNames = $this->getSchoolNamesForStudent($record, $schoolMap);
            $record['lms_code_names'] = implode('||', $schoolNames);
            $records[$key] = $record;
        }

        return $records;
    }

    /**
     * 学校マップを作成する
     *
     * @param array $schoolGroups 学校グループデータ
     * @return array 学校マップ
     */
    private function createSchoolMap($schoolGroups)
    {
        $schoolMap = [];
        foreach ($schoolGroups as $schoolGroup) {
            if ($schoolGroup['is_school']) {
                $schoolMap[$schoolGroup['lms_code_id']] = $schoolGroup;
            }
        }
        return $schoolMap;
    }

    /**
     * 生徒の所属学校名リストを取得する
     *
     * @param array $record 生徒レコード
     * @param array $schoolMap 学校マップ
     * @return array 学校名リスト
     */
    private function getSchoolNamesForStudent($record, $schoolMap)
    {
        $lmsCodeIds = explode(',', $record['lms_code_ids']);
        $schoolNames = [];

        foreach ($lmsCodeIds as $lmsCodeId) {
            if (isset($schoolMap[$lmsCodeId])) {
                $schoolNames[] = $schoolMap[$lmsCodeId]['name'];
            }
        }

        if (!empty($record['lms_code_names'])) {
            $schoolNames[] = $record['lms_code_names'];
        }

        return $schoolNames;
    }
}