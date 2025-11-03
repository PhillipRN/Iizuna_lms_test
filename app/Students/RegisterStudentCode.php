<?php
namespace IizunaLMS\Students;

use IizunaLMS\Models\StudentLmsCodeModel;
use IizunaLMS\Models\LmsCodeModel;
use IizunaLMS\Helpers\PDOHelper;

class RegisterStudentCode
{
    /**
     * 複数の生徒に指定されたLMSコードを登録する
     *
     * @param array $studentIds 生徒ID配列
     * @param int $lmsCodeId LMSコードID
     * @return array 処理結果（登録数、スキップ数、エラー情報）
     */
    public function Register($studentIds, $lmsCodeId)
    {
        // 無効な入力値をチェック
        if (empty($studentIds) || !is_array($studentIds) || empty($lmsCodeId)) {
            return [
                'success' => false,
                'error' => 'Invalid parameters'
            ];
        }

        // LMSコードの存在確認
        $lmsCodeModel = new LmsCodeModel();
        $lmsCode = $lmsCodeModel->GetById($lmsCodeId);

        if (empty($lmsCode)) {
            return [
                'success' => false,
                'error' => 'Invalid LMS code'
            ];
        }

        // トランザクション開始
        PDOHelper::GetPDO()->beginTransaction();

        try {
            $studentLmsCodeModel = new StudentLmsCodeModel();
            $registeredCount = 0;
            $skippedCount = 0;

            foreach ($studentIds as $studentId) {
                // 既に関連付けが存在するかチェック
                if ($studentLmsCodeModel->Exists($studentId, $lmsCodeId)) {
                    $skippedCount++;
                    continue;
                }

                // 関連付けを追加
                $result = $studentLmsCodeModel->AddRelation($studentId, $lmsCodeId);

                if ($result) {
                    $registeredCount++;
                } else {
                    // エラーが発生した場合はトランザクションをロールバック
                    PDOHelper::GetPDO()->rollBack();
                    return [
                        'success' => false,
                        'error' => 'Failed to register code'
                    ];
                }
            }

            // 全て成功したらコミット
            PDOHelper::GetPDO()->commit();

            return [
                'success' => true,
                'registeredCount' => $registeredCount,
                'skippedCount' => $skippedCount
            ];
        } catch (\Exception $e) {
            // 例外が発生した場合はロールバック
            PDOHelper::GetPDO()->rollBack();

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}