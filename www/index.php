<?php
require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../application/bootstrap.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sample\Housing\Entities\Audit;
use Sample\Housing\Entities\Bedroom;
use Sample\Housing\Entities\Dormatory;
use Sample\Housing\Entities\Student;
use Sample\Housing\Services\DormatoryJsonProvider;

$console = null;
$dormatoryJsonProvider = new DormatoryJsonProvider();
$app->register($dormatoryJsonProvider);

$app->get('/', function() use ($app) {
    $stream = function() {
        readfile('./../www/build/index.html');
    };
    return $app->stream($stream, 200);
});

$app->get('/dormatories', function() use ($dormatoryJsonProvider) {
    return new Response($dormatoryJsonProvider->generateAllDormatoryJson(), 200);
});

$app->post('/dormatories', function(Request $request) use ($app) {
    $diffList = array();
    $dormRepo = $app['orm.em']->getRepository(Dormatory::class);
    $brRepo = $app['orm.em']->getRepository(Bedroom::class);
    $content = $request->getContent();

    // there should only be a list of 2 dorm buildings in json
    if (count($content) == 2) {
        $dormRequestContent = $app['serializer']->deserialize(
            $content,
            'ArrayCollection<\Sample\Housing\Entities\Dormatory>',
            'json'
        );
    } else {
        return new Response('{"success": false}', 202);
    }
    // Dispatch an audit on the dorms
    $event = new Audit();
    $auditContent = $sep = '';
    $dorms = $dormRepo->findAll();
    foreach ($dorms  as $dorm) {
        $students = array();
        foreach ($dorm->getBedrooms() as $bedroom) {
            $students [$bedroom->getId()]= $bedroom->getStudent();
        }
        foreach ($dormRequestContent[$dorm->getId()]->getBedrooms() as $bedroomUpdated) {
            if ($bedroomUpdated->getStudent()
                && $bedroomUpdated->getStudent()->getId() != $students[$bedroomUpdated->getId()]->getId()) {
                if (isset($diffList[$bedroomUpdated->getStudent()->getId()])) {
                    throw new \Exception('Student should have only changed location once.');
                }
                $diffList[$bedroomUpdated->getStudent()->getId()] = implode(',', array(
                    $dorm->getNumber(),
                    $bedroomUpdated->getFloor(),
                    $bedroomUpdated->getNumber()
                ));
                $dorm->addAudit($event);
            }
        }
    }

    // Go and update each changed student/bedroom/dorm
    foreach ($event->getDormatories() as $dorm) {
        foreach ($dorm->getBedrooms() as $bedroom) {
            $bedroom->setStudent($dormRequestContent[$dorm->getId()]->getStudent());
            $app['orm.em']->persist($bedroom);
        }
        $app['orm.em']->persist($dorm);
    }
    $app['orm.em']->flush();

    foreach($diffList as $studentId => $floorNumber) {
        $auditContent .= $sep . $studentId . ':' . $floorNumber;
        $sep = ',';
    }

    $event->setContent(implode('|', $auditContent));
    $event->setUpdated(new \Datetime());
    // The title is a list of every area that changed in update.
    $event->setTitle(implode('|', $diffList));
    $app['dispatcher']->dispatch('update', $event);

    return new Response('{"success": true}', 201);
});

$app->get('/students', function() use ($app) {
    $studentRepo = $app['orm.em']->getRepository(Student::class);
    $students = $studentRepo->findAll();
    return new Response($app['serializer']->serialize($students, 'json'), 200);
});

$app->run();
?>