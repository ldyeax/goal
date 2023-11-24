<?php

chdir(__DIR__);

header("Content-Type: application/json");

define("DATABASE_FILENAME", "db.sqlite3");
$db = new SQLite3(DATABASE_FILENAME);
$db->enableExceptions(true);

$userKey = $_REQUEST["key"] || $_COOKIE["key"];
if (is_null($userKey)) {
	die('{"error": "no key"}');
}
$hashed_key = hash("sha256", $userKey);

// create goals table if it doesn't exist
$db->exec("
	CREATE TABLE IF NOT EXISTS goals (
		id INTEGER NOT NULL,
		hashed_key TEXT NOT NULL,
		modified_time INTEGER,
		name TEXT NOT NULL,
		type INTEGER NOT NULL,
		percentage REAL,
		completed INTEGER,
		children TEXT,
		notes TEXT
	)"
);

define("GOAL_TYPE_PERCENTAGE", 1);
define("GOAL_TYPE_BINARY", 2);
define("GOAL_TYPE_COMPOSITE", 3);

class Goal {
	public int $id;
	public string $hashed_key;
	public int $modified_time;
	public string $name;
	public int $type;
	
	// for percentage
	public float $percentage;
	// for binary
	public int $completed;
	// for composite
	public array $children;

	public string $notes;

	function __construct($goalData) {
		if (is_null($goalData)) {
			return;
		}
		$this->id = $goalData["id"];
		$this->hashed_key = $goalData["hashed_key"];
		$this->modified_time = $goalData["modified_time"];
		$this->name = $goalData["name"];
		$this->type = $goalData["type"];
		switch ($this->type) {
			case GOAL_TYPE_PERCENTAGE:
				$this->percentage = $goalData["percentage"];
				break;
			case GOAL_TYPE_BINARY:
				$this->completed = $goalData["completed"];
				break;
			case GOAL_TYPE_COMPOSITE:
				$this->children = explode(",", $goalData["children"]);
				break;
		}
		$this->notes = $goalData["notes"];
	}

	public function write() {
		global $db;
		$goalData = $db->prepare("
			INSERT INTO goals (
				id,
				hashed_key,
				modified_time,
				name,
				type,
				percentage,
				completed,
				children,
				notes
			) VALUES (
				:id,
				:hashed_key,
				modified_time,
				:name,
				:type,
				:percentage,
				:completed,
				:children,
				:notes
			)
		");
		if (!$goalData) {
			die('{"error":"' . $db->lastErrorMsg() . '"}');
		}
		$goalData->bindValue(":id", $this->id, SQLITE3_INTEGER);
		$goalData->bindValue(":hashed_key", $this->hashed_key, SQLITE3_TEXT);
		$goalData->bindValue(":modified_time", $this->modified_time, SQLITE3_INTEGER);
		$goalData->bindValue(":name", $this->name, SQLITE3_TEXT);
		$goalData->bindValue(":type", $this->type, SQLITE3_INTEGER);
		$goalData->bindValue(":percentage", $this->percentage, SQLITE3_FLOAT);
		$goalData->bindValue(":completed", $this->completed, SQLITE3_INTEGER);
		$goalData->bindValue(":children", implode(",", $this->children), SQLITE3_TEXT);
		$goalData->bindValue(":notes", $this->notes, SQLITE3_TEXT);
		$goalData->execute();
	}
}

function getLatestGoals() {
	global $db;
	global $hashed_key;
	$allGoalsData = $db->prepare('
		WITH latest_goals AS (
			SELECT
				id,
				MAX(modified_time) AS latest_modified_time
			FROM goals
			WHERE hashed_key = :hashed_key
			GROUP BY id
		)
		SELECT *
		FROM goals
		INNER JOIN latest_goals
		ON goals.id = latest_goals.id
		AND goals.modified_time = latest_goals.latest_modified_time
		ORDER BY goals.id ASC
	');
	if (!$allGoalsData) {
		die('{"error":"' . $db->lastErrorMsg() . '"}');
	}
	$allGoalsData->bindValue(":hashed_key", $hashed_key, SQLITE3_TEXT);
	$allGoalsData = $allGoalsData->execute();
	$goals = array();
	while ($goalData = $allGoalsData->fetchArray()) {
		$goal = new Goal($goalData);
		$goals[$goal->id] = $goal;
	}
	return $goals;
}

function getLatestGoal($id) {
	global $db;
	global $hashed_key;
	$goalData = $db->prepare('
		SELECT *
		FROM goals
		WHERE hashed_key = :hashed_key
		AND id = :id
		ORDER BY modified_time DESC
		LIMIT 1
	');
	if (!$goalData) {
		die('{"error":"' . $db->lastErrorMsg() . '"}');
	}
	$goalData->bindValue(":hashed_key", $hashed_key, SQLITE3_TEXT);
	$goalData->bindValue(":id", $id, SQLITE3_INTEGER);
	$goalData = $goalData->execute();
	return new Goal($goalData->fetchArray());
}

function updateGoal() {
	$goal = getLatestGoal(null);

	$vars = get_class_vars(get_class($goal));
	$vars = array_keys($vars);
	$vars = array_filter($vars, function($var) {
		return $var != "id" && $var != "hashed_key";
	});
	foreach ($vars as $var) {
		if (isset($_REQUEST[$var])) {
			$goal->$var = $_REQUEST[$var];
		}
	}

	$goal->modified_time = time();
	$goal->write();
}

function createGoal() {
	global $hashed_key;
	
	$goal = new Goal(null);
	$goal->id = time();
	$goal->hashed_key = $hashed_key;

	$vars = get_class_vars(get_class($goal));
	$vars = array_keys($vars);
	$vars = array_filter($vars, function($var) {
		return $var != "id" && $var != "hashed_key";
	});
	foreach ($vars as $var) {
		if (isset($_REQUEST[$var])) {
			$goal->$var = $_REQUEST[$var];
		}
	}

	$goal->modified_time = time();
	$goal->write();

	if (isset($_REQUEST["parent_id"])) {
		$parentGoal = getLatestGoal(null);
		$parentGoal->children[] = $goal->id;
		$parentGoal->modified_time = time();
		$parentGoal->write();
	}

	return $goal->id;
}

function getAllGoals() {
	global $hashed_key;
	global $db;
	$allGoalsData = $db->prepare(
		"SELECT * FROM goals WHERE hashed_key = :hashed_key ORDER BY id ASC"
	);
	$allGoalsData->bindValue(":hashed_key", $hashed_key, SQLITE3_TEXT);
	$allGoalsData = $allGoalsData->execute();
	$goals = array();
	while ($goalData = $allGoalsData->fetchArray()) {
		$goal = new Goal($goalData);
		$goals[] = $goal;
	}
	return $goals;
}

$function = $_REQUEST["function"];
switch ($function) {
	case "getAllGoals":
		$goals = getAllGoals();
		echo json_encode($goals);
		break;
	case "getLatestGoals":
		$goals = getLatestGoals();
		echo json_encode($goals);
		break;
	case "getLatestGoal":
		$goal = getLatestGoal(null);
		echo json_encode($goal);
		break;
	case "updateGoal":
		updateGoal();
		echo '{"success":true}';
		break;
	case "createGoal":
		$id = createGoal();
		echo '{"id":$id}';
		break;
}

$test1 = new Goal(null);
$test1->id = 1;
$test1->hashed_key = hash("sha256", "test");
$test1->modified_time = time();
$test1->name = "test1";
$test1->type = GOAL_TYPE_PERCENTAGE;
$test1->percentage = 0.5;
$test1->notes = "test1 notes";
$test1->write();

$test2 = new Goal(null);
$test2->id = 2;
$test2->hashed_key = hash("sha256", "test");
$test2->modified_time = time();
$test2->name = "test2";
$test2->type = GOAL_TYPE_BINARY;
$test2->completed = 1;
$test2->notes = "test2 notes";
$test2->write();

$test3 = new Goal(null);
$test3->id = 3;
$test3->hashed_key = hash("sha256", "test");
$test3->modified_time = time();
$test3->name = "test3";
$test3->type = GOAL_TYPE_COMPOSITE;
$test3->children = [1, 2];
$test3->notes = "test3 notes";
$test3->write();

$test4 = new Goal(null);
$test4->id = 4;
$test4->hashed_key = hash("sha256", "test");
$test4->modified_time = time();
$test4->name = "test4";
$test4->type = GOAL_TYPE_PERCENTAGE;
$test4->percentage = 0.2;
$test4->notes = "test4 notes";
$test4->write();

?>
