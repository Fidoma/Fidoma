<?php

if (! defined('FIDOMA_ROUNDCUBE_GATEWAY')) {
    http_response_code(403);
    exit;
}

/** Preserve CI's strict session-ID validation with Roundcube's separate DB store. */
class rcube_session_fidoma extends rcube_session_db implements SessionUpdateTimestampHandlerInterface
{
    public function start()
    {
        // PHP has already consumed the Fidoma cookie during its first session_start().
        // Select the second cookie explicitly, after Roundcube registers its handler.
        $id = rcube_utils::get_input_string(session_name(), rcube_utils::INPUT_COOKIE);
        if (is_string($id) && preg_match('/^[a-zA-Z0-9,-]{16,256}$/D', $id)) {
            session_id($id);
        }
        parent::start();
    }

    public function validateId(string $id): bool
    {
        $data = $this->read($id);

        return is_string($data) && $data !== '';
    }

    public function updateTimestamp(string $id, string $data): bool
    {
        return (bool) $this->write($id, $data);
    }
}
