<?php

namespace App\Models;

use CodeIgniter\Model;

class AnnouncementModel extends Model
{
    protected $table = 'announcements';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['type', 'title', 'content', 'image', 'date', 'status', 'publish_at', 'expires_at', 'notified', 'created_by'];

    /**
     * Limits the next query to announcements everyone can see right now:
     * active, already published (or never scheduled) and not yet expired.
     */
    public function live(): static
    {
        $now = date('Y-m-d H:i:s');

        $this->where('announcements.status', 'active')
            ->groupStart()->where('announcements.publish_at', null)->orWhere('announcements.publish_at <=', $now)->groupEnd()
            ->groupStart()->where('announcements.expires_at', null)->orWhere('announcements.expires_at >', $now)->groupEnd();

        return $this;
    }

    /**
     * Tells every active user (except the poster) about an announcement and
     * marks it notified. Called when it's posted, or — for a scheduled one —
     * when it goes live (App\Libraries\Automation).
     */
    public function notifyEveryone(array $a): int
    {
        $notifications = new NotificationModel();
        $sent          = 0;
        foreach ((new UserModel())->active()->where('id !=', (int) ($a['created_by'] ?? 0))->findAll() as $recipient) {
            $notifications->insert([
                'user_id'  => $recipient['id'],
                'type'     => 'announcement',
                'title'    => $a['title'],
                'sub'      => $a['type'] . ' · ' . date('M d', strtotime($a['date'])),
                'url'      => base_url('announcements') . '?id=' . $a['id'],
                'ref_type' => 'announcement',
                'ref_id'   => $a['id'],
                'is_read'  => 0,
            ]);
            $sent++;
        }
        $this->update($a['id'], ['notified' => 1]);

        return $sent;
    }

    /** Where an announcement stands: 'live', 'scheduled' (not yet published) or 'archived'. */
    public static function stateOf(array $a): string
    {
        $now = date('Y-m-d H:i:s');
        if (($a['status'] ?? 'active') !== 'active' || (! empty($a['expires_at']) && $a['expires_at'] <= $now)) {
            return 'archived';
        }

        return ! empty($a['publish_at']) && $a['publish_at'] > $now ? 'scheduled' : 'live';
    }
}
