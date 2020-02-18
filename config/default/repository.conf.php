<?php

/**
 * Default config header created during install
 */

return new oat\taoRevision\model\RepositoryService([
    'storage' => new oat\taoRevision\model\storage\RdsStorage([
        'persistence' => 'default'
    ]),
    'filesystem' => 'revisions',
]);
