<?php

namespace App\Controllers\Shared;

use App\Controllers\BaseController;
use App\Models\NotificationModel;

class Notifications extends BaseController
{
    public function markRead(int $id)
    {
        $user = currentUser();

        (new NotificationModel())->markRead($id, (int) $user['id']);

        return $this->ajaxSuccess('OK');
    }
}
