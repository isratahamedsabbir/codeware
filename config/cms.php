<?php

return [
    'editor_base_url' => env('CMS_EDITOR_BASE_URL', 'http://localhost:3000'),

    // How long a minted Puck editor token stays valid, in minutes. Lives in
    // .env (not the settings table) and is edited from the Settings button on
    // the admin Pages screen — see PuckEditor::sessionMinutes().
    'puck_session_minutes' => (int) env('PUCK_SESSION', 30),
];
