<?php return array(
    'root' => array(
        'name' => 'facebook/pixel-for-wordpress',
        'pretty_version' => '5.2.2',
        'version' => '5.2.2.0',
        'reference' => null,
        'type' => 'project',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'dev' => false,
    ),
    'versions' => array(
        'facebook/capi-param-builder-php' => array(
            'pretty_version' => '1.2.1',
            'version' => '1.2.1.0',
            'reference' => '0502e762127deca4fce9b3e2e1b6ff38b392f526',
            'type' => 'library',
            'install_path' => __DIR__ . '/../facebook/capi-param-builder-php',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'facebook/pixel-for-wordpress' => array(
            'pretty_version' => '5.2.2',
            'version' => '5.2.2.0',
            'reference' => null,
            'type' => 'project',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'guzzlehttp/guzzle' => array(
            'dev_requirement' => false,
            'replaced' => array(
                0 => '*',
            ),
        ),
        'techcrunch/wp-async-task' => array(
            'pretty_version' => 'dev-master',
            'version' => 'dev-master',
            'reference' => '9bdbbf9df4ff5179711bb58b9a2451296f6753dc',
            'type' => 'wordpress-plugin',
            'install_path' => __DIR__ . '/../techcrunch/wp-async-task',
            'aliases' => array(
                0 => '9999999-dev',
            ),
            'dev_requirement' => false,
        ),
    ),
);
