<?php

return [
    'navigation_group' => 'External services',
    'navigation_label' => 'AI templates',
    'template' => 'Template',
    'templates' => 'Templates',
    'create_template' => 'Template',
    'form' => [
        'section' => 'AI template',
    ],
    'fields' => [
        'title' => 'Title',
        'area' => 'Area',
        'action' => 'Action',
        'prompt' => 'Prompt',
        'active' => 'Active',
        'sort_order' => 'Sort order',
        'arrival_date' => 'Arrival',
        'departure_date' => 'Departure',
        'product' => 'Product',
    ],
    'actions' => [
        'create_client' => 'Create client',
        'create_reservation' => 'Create reservation',
    ],
    'simulation' => [
        'badge' => 'Simulation – no real AI is used.',
        'create_client_heading' => 'Create client from email',
        'create_reservation_heading' => 'Create reservation from email',
        'client_created' => 'Client was created.',
        'reservation_created' => 'Reservation was created.',
        'duplicate_client' => 'A client with this email address already exists.',
    ],
    'areas' => [
        'email_message' => 'Email message',
    ],
    'roundcube' => [
        'actions' => 'AI actions',
        'selection_prepared' => 'AI action selected',
        'selection_prepared_body' => '“:template” is prepared for the selected message.',
    ],
];
