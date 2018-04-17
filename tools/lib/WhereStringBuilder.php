<?php
namespace Tools;
    
class WhereStringBuilder
{
    /**
     * @var mysqli $Db
     */
    private static $Db;
    /**
     * @var array $config
     */
    private $config = array();

    private $addReferences = false;

    private $innodbTables = array();

    private $isamTables = array();

    private $searchTables = array();

    private $primaryKeys = array();

    /**
     * This will be a mapping of table names to strings formatted to fit after WHERE
     * in MySql statement. e.g. `table.id IN (1,23,45) OR table.oth_id IN (14,28,39)'
     *
     * @var array $whereStrings
     */
    public $whereStrings = array();

    public function __construct(array $where, mysqli $mysqli)
    {
        //maybe just start a new thread?
        if (!(self::$Db instanceof mysqli)) {
            self::$Db = $mysqli;
        }
        $this->config = $where;
    }

    // structure returned by the build function should be able to be read one table at a time
    // with this function.
    public static function GetColumnsInSet(array $table, array $idSets)
    {
        $sep = '';
        $columnsInSet = '';
        $columns = array_keys($table);
        foreach ($columns as $column) {
            if (isset($idSets{$colum})) {
                $idSet = implode(', ', $idSets[$column]);
            }
            $columnsInSet .= $sep . ' ' . $column . ' IN (' . $idSet . ') ';
            $sep = ' OR ';
        }
        return $columnsInSet;
    }

    /**
     * @return array
     */
    public static function MysqlHasErrors() {
        $ret = array('code'=> 0, 'message' => '');
        if (self::$Db->connect_errno) {
            $ret['message'] =  Color::colorize('red', "Database connection failed: " . self::$Db->connect_error, true);
            $ret['code'] = self::$Db->connect_errno;
        } elseif (self::$Db->errno) {
            $ret['message'] =  Color::colorize('red', "Query failed: " . self::$Db->error, true);
            $ret['code'] = self::$Db->errno;
        }
        return $ret;
    }

    /**
     *
     * @throw Exception if it doesn't like you
     * @return array associative of arrays the table->column->id_set
     */
    public function build($schema, array $innodbTables, array $isamTables)
    {
        $oldAutocommit = false;
        $result = self::$Db->query('SELECT @@autocommit;');
        $ok = self::$Db->commit();
        if ($ok) {
            $oldAutocommit = $result->fetch_array()[0];
        }
        self::$Db->autocommit(true);
        $this->addReferences = true;
        $this->innodbTables = $innodbTables;
        $this->isamTables = $isamTables;
        $this->searchTables = array();
        $references = array();
        $rootTables = array();
        $searchTree = array('ROOT' => array());
        $tables = array();
        if (!is_string($schema)) {
            throw new Exception("schema must be a string.");
        }
        foreach ($this->config['tables'] as $table => $tableConfig) {
            if (false === array_search($table, $tables)) {
                $this->assertPrimaryKeyForTable($schema, $table);
                $tables []= $table;
            }
            if ($tableConfig['root']) {
                if (false === array_search($table, $rootTables)) {
                    $rootTables []= $table;
                }
            }
            if (!isset($references[$table])) {
                $references[$table] = array();
            }
            if (isset($tableConfig['references'])) {
                foreach ($tableConfig['references'] as $primaryId => $foreignColumn) {
                    if (!isset($references[$table][$primaryId])) {
                        $references[$table][$primaryId] = array();
                    }
                    if (false === array_search($foreignColumn, $references[$table][$primaryId])) {
                        $references[$table][$primaryId] []= $foreignColumn;
                    }
                    list($foreignTable, $column) = explode('.', $foreignColumn);
                    if (false === array_search($foreignTable, $tables)) {
                        $this->assertPrimaryKeyForTable($schema, $foreignTable);
                        $tables []= $foreignTable;
                    }
                    if (false === array_key_exists($foreignTable, $references)) {
                        $references[$foreignTable] = array();
                    }
                }
            }
        }
        $err = self::MysqlHasErrors();
        if ($err['code'] !== 0) {
            echo $err['message'];
            exit($err['code']);
        }
        if (count($rootTables) <= 0) {
            throw new Exception("There must be at least one root table.");
        }
        do {
            $this->searchTablesForReferences($schema, $tables, $references);
        } while ($this->addReferences);
        $err = self::MysqlHasErrors();
        if ($err['code'] !== 0) {
            echo $err['message'];
            exit($err['code']);
        }
        foreach ($rootTables as $rootTable) {
            foreach ($references[$rootTable] as $foreignKey => $foreignColumns) {
                foreach ($foreignColumns as $foreignColumn) {
                    list($foreignTable, $column) = explode('.', $foreignColumn);
                    if (false !== array_search($foreignTable, $rootTables)) {
                        // If two tables are listed as root any relationship should be one to one
                        $this->assertUniqueColumn($schema, $foreignTable, $column);
                        $this->assertUniqueColumn($schema, $rootTable, $foreignKey);
                    }
                }
            }
            $searchTree['ROOT'] = array_merge($searchTree['ROOT'], array($rootTable => array()));
        }
        foreach ($references as $table => &$refColumns) {
            foreach ($refColumns as $primaryId => $foreignColumns) {
                foreach ($foreignColumns as $foreignColumn) {
                    list($foreignTable, $column) = explode('.', $foreignColumn);
                    $searchTree = $this->appendForeignTableToSearchTree($searchTree, $table, $foreignTable);
                }
            }
        }
        foreach ($references as $table => $refColumns) {
            // if the table isn't a root table descendant we need to add it at the root level
            if (!$this->searchTreeContainsReferencedTable($searchTree, $table)) {
                $searchTree['ROOT'] = array_merge($searchTree['ROOT'], array($table => array()));
            }
        }
        $config = $this->config;
        ksort($references);
        sort($tables);
        $trueTree = $searchTree;
        $interacting = true;
        echo "Here are the tables which can be pulled into the clone.\n";
        echo "\n" . implode(', ', $tables) . "\n\n";
        echo "Blank choice means accept remainder of the list.\n";
        while ($interacting) {
            foreach ($tables as $idx => $table) {
                echo "Pick, Decline or Reshelve (p,d,r) " . $table . "\n";
                $foundAs = array();
                foreach ($references[$table] as $ref => $refs) {
                    foreach ($refs as $foreignColumn) {
                        $foundAs []= $table . '.' . $ref . ' <-> ' . $foreignColumn;
                    }
                }
                foreach ($references  as $foreignTable => $group) {
                    if ($table !== $foreignTable) {
                        foreach ($group as $ref => $refs) {
                            foreach ($refs as $foreignColumn) {
                                if (0 === strpos($foreignColumn, $table)
                                    && $table === substr($foreignColumn, 0, strpos($foreignColumn, '.'))) {
                                    $foundAs []=  $foreignTable . '.' . $ref . '<->' . $foreignColumn;
                                }
                            }
                        }
                    }
                }
                if ($foundAs) {
                    echo implode("\n", $foundAs) . "\n";
                }
                $in = array(STDIN);
                if (stream_select($in, $write, $except, 0)) {
                    while ("\n" !== fgetc(STDIN));
                }
                $char = trim(strtolower(fgetc(STDIN)));
                if ('' !== $char) {
                    switch ($char) {
                    case 'd':
                        unset($config['tables'][$table]);
                        $trueTree = $this->cleanSearchTree($table, $trueTree);
                        $references = $this->cleanReferences($table, $references);
                    case 'p':
                        unset($tables[$idx]);
                    case 'r':
                    default:
                        // reshelve if unsupported option provided
                    }
                } else {
                    $confirm = false;
                    while ('' !== ($confirm = strtolower(trim(fgetc(STDIN))))) {
                        echo "Are you sure you want to accept all foreign tables found? (Y/n)\n";
                        if ($confirm === 'y') {
                            $interacting = false;
                            break;
                        }
                    }
                }
            }
            if (count($tables) === 0) {
                break;
            }
        }
        foreach (array_keys($references) as $table) {
            $this->primaryKeys[$table] = $this->getPrimaryKeyForTable($schema, $table);
        }
        $err = self::MysqlHasErrors();
        if ($err['code'] !== 0) {
            echo $err['message'];
            exit($err['code']);
        }
        $this->buildWhereStringsForSearchTree($trueTree, $schema, $config);
        var_dump($this->whereStrings);exit;

        self::$Db->autocommit($oldAutocommit);
        return array($innodbTables, $isamTables, $this->whereStrings);
    }

    private function cleanSearchTree($discard, array $searchTree)
    {
        foreach ($searchTree as $k => &$v) {
            if ($k === $discard) {
                unset($searchTree[$k]);
            } elseif (is_array($v)) {
                $v = $this->cleanSearchTree($discard, $v);
            }
        }
        return $searchTree;
    }

    private function searchTreeContainsReferencedTable(array $searchTree, $table)
    {
        $s = false;
        foreach ($searchTree as $k => &$v) {
            if ($k === $table) {
                return true;
            } elseif (is_array($v)) {
                $s = $this->searchTreeContainsReferencedTable($v, $table);
            }
            if ($s) {
                return true;
            }
        }
        return $s;
    }

    private function appendForeignTableToSearchTree(array $searchTree, $table, $foreignTable)
    {
        $breakRecursion = false;
        $newTable = array($foreignTable => array());
        $arrayIterator = new RecursiveArrayIterator($searchTree);
        $recursiveIterator = new RecursiveIteratorIterator(
            $arrayIterator,
            \RecursiveIteratorIterator::SELF_FIRST
        );
        $prevDepth = -1;
        foreach ($recursiveIterator as $key => $value) {
            $currentDepth = $recursiveIterator->getDepth();
            if ($breakRecursion) {
                if ($currentDepth !== $prevDepth + 1) {
                    $breakRecursion = false;
                }
                continue;
            }
            if ($key === $table) {
                $subDepth = $currentDepth;
                for ($subIterator = $recursiveIterator->getSubIterator($subDepth); $subDepth >= 0; 
                     $subIterator = $recursiveIterator->getSubIterator(--$subDepth)) {
                    if ($subIterator->key() === $table && $subDepth === $currentDepth) {
                        $mArray = $subIterator->current();
                        if (is_array($mArray)) {
                            $newTable = array_merge_recursive($mArray, $newTable);
                        }
                        if (isset($newTable[$table])) {
                            // don't allow recursive iterator to infinitely recurse in this node
                            $breakRecursion = true;
                        }
                        $subIterator->offsetSet($subIterator->key(), $newTable);
                    } else {
                        $mArrayIter = $recursiveIterator->getSubIterator(($subDepth+1));
                        if ($mArrayIter) {
                            $subIterator->offsetSet($subIterator->key(), $mArrayIter->getArrayCopy());
                        }
                    }
                }
            }
            $prevDepth = $currentDepth;
        }
        return $recursiveIterator->getArrayCopy();
    }

    private function searchTablesForReferences($schema, array &$tables, array &$references)
    {
        if (count($this->searchTables) ===  0) {
            $this->searchTables = $tables;
        }
        $searchRemoveI = array();
        $this->addReferences = false;
        foreach ($this->searchTables as $i => $table) {
            if (false === array_search($table, $this->isamTables)) {
                // MyIsam tables do not support foreign keys
                $refSql = <<<SQL
SELECT
 k.TABLE_NAME AS TABLE_NAME,
 k.COLUMN_NAME as COLUMN_NAME,
 k.REFERENCED_TABLE_NAME AS REFERENCED_TABLE_NAME,
 k.REFERENCED_COLUMN_NAME AS REFERENCED_COLUMN_NAME,
 k.REFERENCED_TABLE_SCHEMA AS REFERENCED_TABLE_SCHEMA
 FROM
  INFORMATION_SCHEMA.TABLE_CONSTRAINTS i
 INNER JOIN
  INFORMATION_SCHEMA.KEY_COLUMN_USAGE k
   ON
    i.CONSTRAINT_NAME = k.CONSTRAINT_NAME
   AND
    i.TABLE_SCHEMA = k.TABLE_SCHEMA
   AND
    i.TABLE_SCHEMA = k.REFERENCED_TABLE_SCHEMA
WHERE
 i.CONSTRAINT_TYPE = 'FOREIGN KEY'
AND
 k.REFERENCED_TABLE_SCHEMA = '$schema'
AND
 k.TABLE_SCHEMA = '$schema'
AND
 k.TABLE_NAME = '$table';
SQL;
                $result = self::$Db->query($refSql);
                while ($row = $result->fetch_object())  {
                    $newRef = $row->REFERENCED_TABLE_NAME . '.' . $row->REFERENCED_COLUMN_NAME;
                    $refColumn = $row->COLUMN_NAME;
                    if (!isset($references[$row->TABLE_NAME])) {
                        $references[$row->TABLE_NAME] = array($refColumn => array($newRef));
                    } else {
                        if (false === array_search($newRef, $references[$row->TABLE_NAME])) {
                            if (array_key_exists($refColumn, $references[$row->TABLE_NAME])) {
                                $references[$row->TABLE_NAME][$refColumn] []= $newRef;
                            } else {
                                $references[$row->TABLE_NAME][$refColumn] = array($newRef);
                            }
                        }
                    }
                    if (isset($this->config['tables'][$table]) && !$this->config['tables'][$table]['foreign']) {
                        continue;
                    }
                    if (false === array_search($row->REFERENCED_TABLE_NAME, $tables, true)) {
                        $tables []= $row->REFERENCED_TABLE_NAME;
                        $this->searchTables []= $row->REFERENCED_TABLE_NAME;
                        $this->addReferences = true;
                        $references[$row->REFERENCED_TABLE_NAME] = array();
                        $this->assertPrimaryKeyForTable($row->REFERENCED_TABLE_SCHEMA, $row->REFERENCED_TABLE_NAME);
                    }
                }
            }
            $searchRemoveI []= $i;
        }
        foreach ($searchRemoveI as $i) {
            unset($this->searchTables[$i]);
        }
    }

    private function assertPrimaryKeyForTable($schema, $table)
    {
        $found = false;
        //    unique keys should be validated for existence in key_column_usage
        $priKeySql = <<<SQL
SELECT
 1
 FROM
  INFORMATION_SCHEMA.COLUMNS
 WHERE
  TABLE_SCHEMA = '$schema'
 AND
  TABLE_NAME='$table'
 AND
  COLUMN_KEY = 'PRI';
SQL;
        $result = self::$Db->query($priKeySql);
        while ($row = $result->fetch_object())  {
            $found = true;
        }
        if (is_object($result) && $result->num_rows) {
            $result->free();
        }
        if (!$found) {
            throw new Exception($schema . '.'  . $table . " has no primary key");
        }
    }

    private function assertUniqueColumn($schema, $table, $column)
    {
        // We will only allow two root tables to have a relationship if it is one to one.
        // the Reference must be listed as a UNIQUE or PRIMARY key
        $uniqueColumnSql = <<<SQL
SELECT
 1
 FROM
  INFORMATION_SCHEMA.COLUMNS
 WHERE
  COLUMN_NAME = '$column'
 AND
  TABLE_NAME = '$table'
 AND
  TABLE_SCHEMA = '$schema'
 AND
  COLUMN_KEY IN ('PRI', 'UNI');
SQL;
        $result = self::$Db->query($uniqueColumnSql);
        if (!$result->num_rows) {
            throw new Exception("Non-unique column found " . $schema . '.' . $table . '.' . $column);
        }
    }

    private function getPrimaryKeyForTable($schema, $table)
    {
        $tableIdSql = <<<SQL
SELECT
 COLUMN_NAME
 FROM
  INFORMATION_SCHEMA.COLUMNS
 WHERE
  TABLE_SCHEMA = '$schema'
 AND
  TABLE_NAME='$table'
 AND
  COLUMN_KEY = 'PRI';
SQL;
        $result = self::$Db->query($tableIdSql);
        if ($row = $result->fetch_object()) {
            return $row->COLUMN_NAME;
        } else {
            throw new Exception("No primary id column found for " . $schema . '.' . $table);
        }
    }

    private function cleanReferences($delTable, array $references)
    {
        foreach ($references as $table => &$refColumns) {
            foreach ($refColumns as $priKey => &$foreignColumns) {
                foreach ($foreignColumns as $i => $foreignColumn) {
                    if (0 === strpos($foreignColumn, $delTable)
                        && $delTable === substr($foreignColumn, 0, strpos($foreignColumn, '.'))) {
                        unset($foreignColumns[$i]);
                    }
                }
            }
            if ($delTable === $table) {
                unset($references[$table]);
            }
        }
        return $references;
    }

    private function buildWhereStringsForSearchTree(array &$searchTree, $schema, array $config)
    {
        foreach ($config['tables'] as $table => $tableConfig) {
            $keys = array();
            if (is_array($tableConfig['set'])) {
                foreach ($tableConfig['set'] as $col => $vals) {
                    $many = false;
                    foreach ($vals as $limit) {
                        if (strtolower($limit) !== 'infinity') {
                            $many = true;
                        }
                    }
                    $first = true;
                    foreach ($vals as $entry => $limit) {
                        if ($first || $many) {
                            $first = false;
                            $sql = 'SELECT ' . $this->primaryKeys[$table]
                                 . ' FROM ' . $schema . '.'
                                 . $table . ' WHERE ';
                        } elseif (!$many) {
                            $sql .= ' OR ';
                        }
                        $sql .=  $col . ' = \'' . $entry . '\''
                             . (!$many||!intval($limit) ? '' : ' LIMIT ' . $limit);
                        if ($many) {
                            $result = self::$Db->query($sql);

                            if (is_object($result) && $keySet = $result->fetch_all()) {
                                $keys += array_column($keySet, 0);
                            }
                        }
                    }
                    if (!$many) {
                        $result = self::$Db->query($sql);
                        if (is_object($result) && $keySet = $result->fetch_all()) {
                            $keys += array_column($keySet, 0);
                        }
                    }
                }
                $searchTree = $this->appendSetToSearchTree(
                    $searchTree,
                    $table,
                    array($this->primaryKeys[$table] => $keys)
                );
                $searchTree = $this->appendTableChildSets($schema, $table, $searchTree);
            }
        }
        $searchTree = $this->traverseTree($searchTree);
        // @todo still need to dedupe and build where Strings
    }

    private function appendTableChildSets($schema, $table, array $searchTree)
    {
        $searchTree = json_decode(json_encode($searchTree), true);
        $arrayIterator = new RecursiveArrayIterator($searchTree);
        $recursiveIterator = new RecursiveIteratorIterator(
            $arrayIterator,
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($recursiveIterator as $key => $value) {
            $currentDepth = $recursiveIterator->getDepth();
            if ($currentDepth > 0 && $key === $table) {
                foreach ($value as $foreignTable => $tree) {
                    if ($foreignTable !== 'SET') {
                        $sqlSet = $this->getSqlSetFromParentSet($schema, $table, $value['SET'], $foreignTable);
                        $innerIterator = $recursiveIterator->getInnerIterator();
                        $innerIterator->offsetSet($innerIterator->key(), $sqlSet);
                        /*
                        for ($subDepth = $currentDepth; $subDepth >= 0;
                             $subIterator = $recursiveIterator->getSubIterator(--$subDepth)) {
                            if ($subDepth === $currentDepth) {
                                $mArray = array_merge_recursive(array($foreignTable => array('SET' => $sqlSet)), $tree);
                                $subIterator->offsetSet('SET', $mArray);
                            } else {
                                $mArrayParent = $recursiveIterator->getSubIterator($subDepth + 1);
                                if ($mArrayParent) {
                                    $subIterator->offsetSet($subIterator->key(), $mArrayParent->getArrayCopy());
                                }
                            }
                        }
                        */
                    }
                }
            }
        }
        return $recursiveIterator->getArrayCopy();
    }

    private function getSqlSetFromParentSet($schema, $parentTable, array $parentSet, $table)
    {
        $parentPrimaryKey = $this->primaryKeys[$parentTable];
        $primaryKey = $this->primaryKeys[$table];
        $sqlString = 'SELECT ' . $schema . '.' . $table . '.' . $primaryKey
                   . ' FROM ' . $schema . '.' . $parentTable
                   . ' INNER JOIN ' . $schema . '.' . $table
                   . ' ON ' . $schema . '.' . $parentTable . '.' . $parentPrimaryKey
                   . ' = ' . $schema . '.' . $table . '.' . $primaryKey;
        $sep = ' WHERE ';    
        foreach ($parentSet as $foreignKey => $idSet) {
            $sqlString .= $sep . $schema . '.' . $parentTable . '.' . $foreignKey
                       . ' IN (\'' . implode('\',\'', $idSet) . '\') ';
            $sep = ' OR ';
        }
        $result = self::$Db->query($sqlString);
        if (is_object($result) && $result->num_rows) {
            $rows = $result->fetch_all();
            if (isset($rows[0][0]))  {
                return array($primaryKey => array_column($rows,0));
            }
        }
        return array($primaryKey => array());
    }

    private function traverseTree(array $searchTree)
    {
        $keySets = array();
        $arrayIterator = new RecursiveArrayIterator($searchTree);
        // self first we want to look all the way down the tree adding id sets
        // from parent relationships to children primary keys
        $recursiveIterator = new RecursiveIteratorIterator(
            $arrayIterator,
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($recursiveIterator as $key => $value) {
            $parentKeys = array();
            $currentDepth = $recursiveIterator->getDepth();
            if ($currentDepth > 1) {
                // start at 1 because tree has ROOT node
                // and we will look for parents each recursive iteration
                $subIterator = $recursiveIterator->getSubIterator($currentDepth);
                if ($subIterator->key() === 'SET') {
                    $subIter2 = $recursiveIterator->getSubIterator($currentDepth-1);
                    // for each table in the current level look at key(table) and
                    foreach ($subIter2 as $table => $tree) {
                        if ($table !== 'SET') {
                            $arraySet = $subIterator->getArrayCopy()['SET'];
                            if (!isset($arraySet[$this->primaryKeys[$table]])) {
                                throw new Exception("Dirty searchTree " . $table
                                                    . " not found in:\n"
                                                    . print_r($searchTree, true));
                            }
                        } else {
                            // This is the parent SET.
                            $parentSet = $tree;
                        }
                    }
                    if (!$parentSet) {
                        continue;
                    }
                    foreach ($subIter2 as $table => $tree) {
                        if ($table !== 'SET') {
                            foreach ($tree as $innerTable => $innerTree) {
                                if ($innerTable !== 'SET') {
                                    $tablePrimaryKey = array_keys($parentSet)[0];
                                    $innerSet = $subIter2->getOffset('SET');
                                    if ($innerSet) {
                                        $innerPrimaryKey = array_keys($innerSet->getArrayCopy())[0];
                                    } else {
                                        continue;
                                    }
                                    $sqlString = 'SELECT ' . $innerTable . '.' . $innerPrimaryKey
                                               . ' FROM '. $table . ' INNER JOIN ' . $innerTable
                                               . ' ON ' . $table . '.' . $tablePrimaryKey
                                               . ' = ' . $innerTable . '.' . $innerPrimaryKey;
                                    $sep = ' WHERE ';           
                                    foreach ($parentKeys as $foreignKey => $idSet) {
                                        $sqlString .= $sep . $foreignKey . ' IN (\'' . implode('\',\'', $idSet) . '\') ';
                                        $sep = ' OR ';
                                    }
                                }
                            }
                            if ($sqlString) {
                                $result = self::$Db->query($sqlString);
                                if (is_object($result) && $result->num_rows) {
                                    $mergeArray = $tree['SET'];
                                    $rows = $result->fetch_all();
                                    if (isset($rows[0][0]))  {
                                        $subIter2->offsetSet('SET', array_merge($mergeArray, array_column($rows, 0)));
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $recursiveIterator->getArrayCopy();
    }

    private function appendSetToSearchTree(array $searchTree, $table, array $sqlSet)
    {
        $arrayIterator = new RecursiveArrayIterator($searchTree);
        $recursiveIterator = new RecursiveIteratorIterator(
            $arrayIterator,
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($recursiveIterator as $key => $value) {
            if ($key === $table) {
                $tablePrimaryKey = $this->primaryKeys[$table];
                if(isset($value['SET'])) {
                    $newSet = array_merge_recursive($sqlSet, $value['SET']);
                } else {
                    $newSet = $sqlSet;
                }
                // sort the newSet so that primaryKey is always in front
                uksort($newSet, function($a, $b) use ($tablePrimaryKey) {
                    if ($a === $tablePrimaryKey) {
                        return 0;
                    }
                    if ($b === $tablePrimaryKey) {
                        return -1;
                    }
                    return 1;
                });
                $value['SET'] = $newSet;
                $currentDepth = $subDepth = $recursiveIterator->getDepth();
                for ($subIterator = $recursiveIterator->getSubIterator($subDepth);
                     $subDepth >= 0;
                     $subIterator = $recursiveIterator->getSubIterator(--$subDepth)) {

                    if ($subIterator->key() === $table && $subDepth === $currentDepth) {
                        $subIterator->offsetSet($table, $value);
                    } else {
                        $mArrayIter = $recursiveIterator->getSubIterator($subDepth + 1);
                        if ($mArrayIter) {
                            $subIterator->offsetSet($subIterator->key(), $mArrayIter->getArrayCopy());
                        }
                    }
                }
            }
        }
        return $recursiveIterator->getArrayCopy();
    }
}
