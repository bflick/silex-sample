<?php

namespace Sample\Process;

use CorsHelper\CorsServiceProvider;
use Dflydev\Provider\DoctrineOrm\DoctrineOrmServiceProvider;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Eole\Sandstone\Application;
use Eole\Sandstone\OAuth2\Silex\OAuth2ServiceProvider;
use Eole\Sandstone\Push\Bridge\ZMQ\ServiceProvider as ZMQProvider;
use Eole\Sandstone\Push\ServiceProvider as  PushProvider;
use Eole\Sandstone\Serializer\ServiceProvider as SerializerProvider;
use Eole\Sandstone\Websocket\ServiceProvider as WebsocketProvider;
use Kurl\Silex\Provider\DoctrineMigrationsProvider;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\SecurityServiceProvider;
use Silex\Provider\SerializerServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Security\Core\User\InMemoryUserProvider;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
class ProcessApp extends Application
{
    private $console;
    
    public function __construct($params, $console)
    {
        $this->console = $console;
        parent::__construct($params);

        $this->register(new DoctrineServiceProvider(), array(
            'db.options' => array(
                'driver'   => 'pdo_mysql',
                'host'     => getenv('DB_HOST'),
                'user'     => 'root',
                'password' => getenv('DB_PASS'),
                'dbname' => 'sample',
            ),
        ));

        $this->register(
            new DoctrineMigrationsProvider($console),
            array(
                'migrations.directory' => __DIR__.'/../../../../mysql/migrations',
                'migrations.name' => 'Migrations',
                'migrations.namespace' => 'Sample\Migrations',
                'migrations.table_name' => 'migrations',
            )
        );

        $entityPath = __DIR__ . '/Events';
        $this->register(new DoctrineOrmServiceProvider(), array(
            'orm.proxies_dir' => __DIR__.'/../../../../cache/doctrine/proxies',
            'orm.default_cache' => 'array',
            'orm.em.options' => array(
                'mappings' => array(
                    array(
                        'type' => 'annotation',
                        'path' => $entityPath,
                        'namespace' => 'Sample\\Process\\Events',
                    )
            ))
        ));

        $newDefaultAnnotationDrivers = array(
            $entityPath,
        );

        $config = Setup::createAnnotationMetadataConfiguration(
            $newDefaultAnnotationDrivers,
            false
        );

        $em = EntityManager::create($this['db.options'], $config);

        $helpers = new HelperSet(array(
            'db' => new ConnectionHelper($em->getConnection()),
            'em' => new EntityManagerHelper($em),
        ));

        // Sandstone requires JMS serializer
        $this->register(new SerializerProvider());

        // Register and configure your websocket server
        $this->register(new WebsocketProvider(), [
            'sandstone.websocket.server' => [
                'bind' => '0.0.0.0',
                'port' => '25569',
            ],
        ]);

        // Register Push Server and ZMQ bridge extension
        $this->register(new PushProvider());
        $this->register(new ZMQProvider(), [
            'sandstone.push.server' => [
                'bind' => '127.0.0.1',
                'host' => '127.0.0.1',
                'port' => 5555,
            ],
        ]);
        
        $this->register(new CorsServiceProvider(), [
            "cors.maxAge" => 150,
            "cors.allowOrigin" => "http://sample",
            "cors.allowMethods" => 'POST, GET, OPTIONS'
        ]);

        $this['app.user_provider'] = function () {
            return new InMemoryUserProvider([
                // username: admin / password: foo
                'admin' => [
                    'roles' => ['ROLE_ADMIN'],
                    'password' => '$2y$10$3i9/lVd8UOFIJ6PAMFt8gu3/r5g0qeCJvoSlLCsvMTythye19F77a',
                 ],
            ]);
        };

        $this->register(new SecurityServiceProvider(), [
            'security.firewalls' => [
                'cors-preflight' => [
                    'anonymous' => true,
                    'pattern' => $this['cors.preflightRequestMatcher'],
                ],
                'authentication' => [
                    'anonymous' => true,
                    'pattern' => '^/oauth',
                ],
                'api' => [
                    'pattern' => '^/api',
                    'http' => true,
                    'users' => $this['app.user_provider'],
                    'oauth' => true,
                    'stateless' => true,
                    'anonymous' => false,
                 ],
             ],
        ]);

       // Send POST for /oauth/access-token
       // grant_type=password&client_id=brianflick-sample&client_secret=DS*u2gdv(UCAfnn350831rfDNg429iAWFASm-25nvAc9xjA3:D>S/?s351rs&username=admin&password=foo
        $this->register(new OAuth2ServiceProvider(), [
            'oauth.firewall_name' => 'api',
            'oauth.security.user_provider' => 'app.user_provider',
            'oauth.tokens_dir' => getenv('TMP_DIR') . '/oauthtokens',
            'oauth.scope' => [
                'id' => 'sandstone-scope',
                'description' => 'Websocket scope',
             ],
             'oauth.clients' => [
                 'sample' => [
                     'name' => 'sample',
                     'id' => 'brianflick-sample',
                     'secret' => 'secert',
                 ],
              ],
         ]);

        $this->register(new MonologServiceProvider(), [
            "monolog.logfile" =>  "/tmp/brianflick-sample.log"
        ]);
    }
}