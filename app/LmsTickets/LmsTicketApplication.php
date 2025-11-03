<?php

namespace IizunaLMS\LmsTickets;

class LmsTicketApplication
{
    const STATUS_APPLICATION = 1;                // 購入申請中
    const STATUS_APPROVED = 2;                   // 購入済み
    const STATUS_CANCELLED_BY_TEACHER = 3;       // 先生による取り消し
    const STATUS_CANCELLED_BY_ADMINISTRATOR = 4; // 管理者による取り消し

    const TYPE_APPLICATION_BY_TEACHER = 1;       // 先生が購入申請
    const TYPE_GRANTED_ADMINISTRATOR = 2;        // 管理者が付与
}