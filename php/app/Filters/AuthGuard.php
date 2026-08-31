<?php

namespace App\Filters;

use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthGuard implements FilterInterface
{
    // How often (seconds) to write users.last_active_at — every authenticated
    // request would otherwise mean a DB write per request; the "online now"
    // read (Chat::ONLINE_WINDOW_SECONDS, 120s) is far coarser than this, so a
    // ~20s staleness here is imperceptible while cutting write volume ~20x.
    private const PRESENCE_PING_INTERVAL = 20;

    public function before(RequestInterface $request, $arguments = null)
    {
        $user = session()->get('user');

        if ($user) {
            $this->pingPresence((int) $user['id']);

            return;
        }

        if (str_starts_with($request->getPath(), 'api/')) {
            return service('response')
                ->setJSON(['error' => 'Unauthenticated.'])
                ->setStatusCode(401);
        }

        return redirect()->to('/login');
    }

    private function pingPresence(int $userId): void
    {
        $lastPing = session()->get('last_active_ping');

        if ($lastPing && (time() - $lastPing) < self::PRESENCE_PING_INTERVAL) {
            return;
        }

        session()->set('last_active_ping', time());
        (new UserModel())->update($userId, ['last_active_at' => date('Y-m-d H:i:s')]);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No post-processing needed.
    }
}
