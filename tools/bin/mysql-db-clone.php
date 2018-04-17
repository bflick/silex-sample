#!/opt/php55/bin/php -qc/home/brianfl/php.ini
<?php
declare(ticks=1);
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once("/opt/dev-tools/library/php/lib/colors.php");
require_once(__DIR__."/../lib/MysqlCloneTool.php");

$processUser = posix_getpwuid(posix_geteuid());

function readline( $prompt = '' )
{
    echo $prompt . Color::colorize('green', '$ ');
    return rtrim( fgets( STDIN ), "\n" );
}

function show_usage($exitCode, $message = null)
{
    if ($message) {
        echo Color::colorize('yellow', $message, true);
    }
    echo "mysql-clone-db -u upgrade_db -c clone_db [ -b (backup_db) ] [ -m ] [ -t 64 ] [ -l 99 ] [ -p ] [ -d ] [ -f 4 ]\n"
       . "\n"
       . "Puts all the tables from clone_db into update_db, optionally\n"
       . "you can make a backup that goes into an existing database\n"
       . "\n"
       . "\t-b|--backup\t(optional) the database schema where upgrade db will be backed up\n"
       . "\t\t\ttimestamped file in your present directory will be used otherwise if not set\n"
       . "\t-u|--upgrade\tdatabase schema to upgrade\n"
       . "\t-c|--clone\tdatabase to clone\n"
       . "\t-t|--timeout\t(optional) default=1000\n"
       . "\t\t\ttime to wait for lingering queries in minutes\n"
       . "\t-l|--limit\t(optional) default=100\n"
       . "\t\t\tthe percent (1-100) of the database tables to select into update_db\n"
       . "\t-p|--permissive\t(optional) default=false\n"
       . "\t\t\tpass this flag if you do not own one or the other database (update & clone)\n"
       . "\t-m|--match\t(optional) default=false\n"
       . "\t\t\tif you want only tables you have in update database cloned\n"
       . "\t-f|--forks\t(optional) the number of child processes started by this one. default=3\n"
       . "\t-d|--dumpall\tpass this flag if you want to use mysqldump to clone innodb tables as well as myisam tables\n"
       . "\t-h|--help\tthis message\n\n";
    exit($exitCode);
}

/**
 * Parse command line arguments
 */
$options = getopt("b::u:c:t:hmpl:r:f:dw:", array(
    'backup::',
    'update:',
    'clone:',
    'timeout:',
    'help',
    'match',
    'permissive',
    'limit:',
    'retries:',
    'nobackup',
    'forks:',
    'dumpall',
    'whereconf:',
));

if (isset($options['h']) || isset($options['help'])) {
    show_usage(0, "Warning: a backup file will be created in your current directory if the backup optional value is not passed");
}

// @todo warn users there can be no spaces after optional arguments
if (isset($options['b'])) {
    if (!is_string($options['b'])) {
        $options['b'] = true;
    }
}
if (isset($options['backup'])) {
    if (!is_string($options['backup'])) {
        $options['backup'] = true;
    }
}

$options = array(
    'update' => $options['update']?:$options['u'],
    'clone' => $options['clone']?:$options['c'],
    'backup' => $options['backup']?:$options['b'],
    'match' => isset($options['match'])?:isset($options['m']),
    'permissive' => isset($options['permissive'])?:isset($options['p']),
    'timeout' => $options['timeout']?:$options['t'],
    'limit' => $options['limit']?:$options['l'],
    'retries' => $options['retries']?:$options['r'],
    'forks' => $options['forks']?:$options['f'],
    'dumpall' => isset($options['dumpall']) ?: isset($options['d']),
    'whereconf' => $options['whereconf']?:$options['w'],
);

if (isset($options['clone']) && isset($options['update'])) {
    if ($options['whereconf'] && $options['limit']) {
        show_usage(1, "Error: Cannot use whereconf with limit option.");
    }
    $processUsername = $processUser['name'];
    $options['username'] = $processUsername;
    echo "Hi " . $processUsername . ",\n";
    if (strpos($options['update'], $processUsername) !== 0) {
        echo Color::colorize('light red', "Why on earth would you be doing this?", true);
        $strikeOne = true;
    }
    if (strpos($options['clone'], $processUsername) !== 0) {
        if ($strikeOne && !$options['permissive']) {
            echo Color::colorize('red', "Pass the --permissive option to do this.", true);
            exit(1);
        }
    }
    echo "Hit enter create a db clone.\n";
    if (readline() === '') {
        MySqlCloneTool::SetTimeout($options['timeout']);
        $tool = new MySqlCloneTool($options);
        if ($options['backup']) {
            $doClone = $tool->backupUpdateDb();
        } else {
            $doClone = true;
        }
        if ($doClone) {
            $tool->executeDatabaseClone();
        }
    }
} else {
    show_usage(1);
}

?>