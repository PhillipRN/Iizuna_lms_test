<?php

namespace IizunaLMS\LmsTickets;

use IizunaLMS\Books\BookLoader;

class LmsTicket
{
/**
 * 書籍No
 * | 10052 | 総合英語 Evergreen |
 * | 10086 | 総合英語 be 4th Edition |
 * | 10093 | 総合英語 Harmony New Edition |
 * | 99052 | Sample 総合英語 Evergreen |
 * | 99086 | Sample 総合英語 be 4th Edition |
 * | 99093 | Sample 総合英語 Harmony New Edition |
 * | 900001 | e-ONIGIRI |
 */
    static public array $AvailableTitleNos = [10086, 10052, 900001];
    static public array $AvailableTitleNosForApplication = [10086, 10052];
    const TITLE_NO_ONIGIRI = 900001;
    const STATUS_DISABLE = 0;
    const STATUS_ENABLE = 1;
    const STATUS_DELETE_BY_TEACHER = 2;
    const STATUS_DELETE_BY_ADMINISTRATOR = 3;

    /**
     * @return array
     */
    public function GetAvailableTicketTypes(): array
    {
        return $this->GetTicketTypes(self::$AvailableTitleNos);
    }

    /**
     * @return array
     */
    public function GetAvailableTicketTypesForApplication(): array
    {
        return $this->GetTicketTypes(self::$AvailableTitleNosForApplication);
    }

    private function GetTicketTypes($titleNos): array
    {
        $titles = [];

        $books = (new BookLoader())->GetBookDetails($titleNos);
        $bookMap = [];
        foreach ($books as $book) {
            $bookMap[$book['title_no']] = [
                'title_no' => $book['title_no'],
                'name' => $book['name']
            ];
        }

        foreach ($titleNos as $titleNo) {
            if (!isset($bookMap[$titleNo])) continue;

            $book = $bookMap[$titleNo];

            $titles[$titleNo] = [
                'title_no' => $book['title_no'],
                'name' => $book['name']
            ];
        }

        // e-ONIGIRI 英単語
        if (in_array(self::TITLE_NO_ONIGIRI, $titleNos)) {
            $titles[self::TITLE_NO_ONIGIRI] = [
                'title_no' => self::TITLE_NO_ONIGIRI,
                'name' => 'e-ONIGIRI 英単語'
            ];
        }

        return $titles;
    }

    /**
     * @param $title_no
     * @return mixed|void
     */
    public function GetTicketType($title_no)
    {
        $ticketTypes = $this->GetAvailableTicketTypes();

        foreach ($ticketTypes as $ticketType) {
            if ($ticketType['title_no'] == $title_no) {
                return $ticketType;
            }
        }
    }
}