<?php

namespace Sample\Housing\Services;

use Doctrine\ORM\EntityManager;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Symfony\Component\Serializer\Serializer;

use Sample\Housing\Entities\Bedroom;
use Sample\Housing\Entities\Dormatory;
use Sample\Housing\Entities\Student;

class DormatoryJsonProvider implements ServiceProviderInterface
{
    private static $dormatories = array();

    /** @var EntityManager */
    private $em;

    /** @var Serializer */
    private $serializer;

    public function register(Container $container)
    {
        if (isset($container['orm.em'])) {
            $this->em = $container['orm.em'];
        } else {
            throw new \Exception("No orm.em");
        }

        if (isset($container['serializer'])) {
            $container['serializer'];
            $this->serializer = $container['serializer'];
        } else {
            throw new \Exception("No serializer");
        }

        //        $container['fetch_dormatory_json'] = array($this, 'generateAllDormatoryJson');
    }

    public function generateAllDormatoryJson()
    {
        $dormatoryRepository = $this->em->getRepository(Dormatory::class);
        return $this->serializer->serialize($dormatoryRepository->findAll(), 'json');
    }

    public function generateStudentsDormatory(Student $student)
    {

    }

    public function generateBedroomDormatory(Bedroom $bedroom)
    {

    }
}