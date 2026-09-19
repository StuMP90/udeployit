<?php

return [

    /*
    | Where the persistent bare git mirror for each project is kept
    | (one subdirectory per project id). Tests point this at a temp dir.
    */
    'repos_path' => env('UDEPLOYIT_REPOS_PATH', storage_path('app/repos')),

];
