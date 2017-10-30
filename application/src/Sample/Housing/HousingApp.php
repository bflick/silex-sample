<?php

namespace Sample\Housing;

use Dflydev\Provider\DoctrineOrm\DoctrineOrmServiceProvider;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Eole\Sandstone\Application;
use Eole\Sandstone\Push\Bridge\ZMQ\ServiceProvider as ZMQProvider;
use Eole\Sandstone\Push\ServiceProvider as  PushProvider;
use Eole\Sandstone\Serializer\ServiceProvider as SerializerProvider;
use Eole\Sandstone\Websocket\ServiceProvider as WebsocketProvider;
use Kurl\Silex\Provider\DoctrineMigrationsProvider;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\SerializerServiceProvider;
use Symfony\Component\Console\Helper\HelperSet;

class HousingApp extends Application
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

        $entityPath = __DIR__.'/Entities';

        $this->register(new DoctrineOrmServiceProvider(), array(
            'orm.proxies_dir' => __DIR__.'/../../../../cache/doctrine/proxies',
            'orm.default_cache' => 'array',
            'orm.em.options' => array(
                'mappings' => array(
                    array(
                        'type' => 'annotation',
                        'path' => $entityPath,
                        'namespace' => 'Sample\\Housing\\Entities',
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

        //       $this->register(new SerializerServiceProvider());

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

        // Register serializer metadata
        $this['serializer.builder']->addMetadataDir(
            __DIR__,
            ''
        );
    }
}