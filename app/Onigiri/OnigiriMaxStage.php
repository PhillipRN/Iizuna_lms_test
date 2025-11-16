<?php

namespace IizunaLMS\Onigiri;

/**
 * Call the Onigiri API to obtain the max stage definition list.
 */
class OnigiriMaxStage extends OnigiriCurlBase
{
    /**
     * @return array<int, array<string, string>>
     */
    public function GetMaxStageList(): array
    {
        $url = ONIGIRI_API . '?m=max_stage_list';

        $result = $this->CurlExecAndGetDecodedResult($url, []);

        if (!is_array($result)) {
            throw new \RuntimeException('Failed to fetch max stage list from Onigiri API.');
        }

        return $result;
    }
}
