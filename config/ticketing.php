<?php

return [

    // Manual floor for the ticket control-number sequence, keyed by the
    // 2-digit year used in the "LGICT-{yy}-####" prefix. The next generated
    // number will be floor + 1 even if no ticket with that suffix exists yet.
    // Set here instead of writing a placeholder ticket row so real ticket
    // data (and dashboards/reports built on it) stay untouched.
    'ticket_number_floors' => [
        '26' => 1439,
    ],

];
