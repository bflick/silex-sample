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
    $dormRepo = $app['em']->getRepository(Dormatory::class);
    $brRepo = $app['em']->getRepository(Bedroom::class);
    $dormRequestContent = $app['serializer']->deserialize(
        $request->getContent(),
        'array<\Sample\Housing\Entities\Dormatory>',
        'json'
    );
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
            $app['em']->persist($bedroom);
        }
        $app['em']->persist($dorm);
    }
    $app['em']->flush();

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
    $studentRepo = $app['em']->getRepository(Student::class);
    $students = $studentRepo->findAll();
    return new Response($app['serialzer']->serialize($students, 'json'), 200);
});

// $app->post('/dormatories', function(Request $request) use ($app) {
//     $data = $request->getContent();

//     $dormatories = $app['serializer']->deserialize(
//         $data,
//         'array<Sample/Housing/Entities/Dormatory>',
//         'json'
//     );
//     $returnDorms = array();
//     foreach ($dormatories as $dorm) {
//         // @todo some validation

//         $app['em']->persist($dorm);
//         $returnDorms []= $dorm;
//     }
//     $app['em']->flush();

//     return new Response($app['serializer']->serialize($returnDorms, 'json'), 200);
// });

$app->run();
?>