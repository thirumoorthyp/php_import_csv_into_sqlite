<?php
// Starting clock time in seconds
$start_time = microtime(true);

// Import data into SQLite
import_csv_to_sqlite(new PDO('sqlite:sqlite_employees.sqlite'),'employees.csv', $options=array());

// End clock time in seconds
$end_time = microtime(true);

// Calculate script execution time
$execution_time = ($end_time - $start_time);
  
echo " Execution time of script = ".$execution_time." sec";
function import_csv_to_sqlite($pdo, $csv_path)
{
    if (($csv_handle = fopen($csv_path, "r")) === FALSE)
        throw new Exception('Cannot open CSV file');
	
    $delimiter = ',';
    $table = preg_replace("/[^A-Z0-9]/i", '', basename($csv_path));
    $fields = array_map(function ($field){
        return strtolower(preg_replace("/[^A-Z0-9]/i", '', $field));
    }, fgetcsv($csv_handle, 0, $delimiter));

    $create_fields_str = join(', ', array_map(function ($field){
        return "$field TEXT NULL";
    }, $fields));

    $pdo->beginTransaction();

    $insert_fields_str = join(', ', $fields);
    $insert_values_str = join(', ', array_fill(0, count($fields),  '?'));
    $insert_sql = "INSERT INTO $table ($insert_fields_str) VALUES ($insert_values_str)";
    $insert_sth = $pdo->prepare($insert_sql);

    $inserted_rows = 0;
    while (($data = fgetcsv($csv_handle, 0, $delimiter)) !== FALSE) {
        $insert_sth->execute($data);
        $inserted_rows++;
    }

    $pdo->commit();

    fclose($csv_handle);

    return array(
            'table' => $table,
            'fields' => $fields,
            'insert' => $insert_sth,
            'inserted_rows' => $inserted_rows
        );

}