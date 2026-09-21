<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
$pdo = Database::getInstance()->getConnection();
$pdo->exec(file_get_contents(__DIR__ . '/migrations/20260913_explorer.sql'));
$pdo->exec("ALTER TABLE explorer_nodes MODIFY kind ENUM('project','program','component_group','activity_group','component','activity','folder','file') NOT NULL");
$pdo->beginTransaction();
try {
    $placeGroup = function ($parent, $kind, $name, $createdBy) use ($pdo) {
        $s = $pdo->prepare('SELECT id FROM explorer_nodes WHERE kind=? AND parent_id <=> ? AND deleted_at IS NULL LIMIT 1');
        $s->execute([$kind, $parent]);
        if ($id = $s->fetchColumn()) return $id;
        $s = $pdo->prepare('INSERT INTO explorer_nodes (parent_id,name,kind,created_by) VALUES (?,?,?,?)');
        $s->execute([$parent,$name,$kind,$createdBy]);
        return $pdo->lastInsertId();
    };
    $place = function ($kind, $row, $parent) use ($pdo) {
        $s = $pdo->prepare('SELECT id FROM explorer_nodes WHERE kind=? AND entity_id=? AND parent_id <=> ?');
        $s->execute([$kind, $row['id'], $parent]);
        if ($id = $s->fetchColumn()) return $id;
        $s = $pdo->prepare('INSERT INTO explorer_nodes (parent_id,name,kind,entity_id,created_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?)');
        $s->execute([$parent,$row['title'],$kind,$row['id'],$row['created_by'],$row['created_at'],$row['updated_at']]);
        return $pdo->lastInsertId();
    };
    if (!$pdo->query("SELECT version FROM explorer_migrations WHERE version='20260913_initial_placement'")->fetchColumn()) {
      foreach ($pdo->query('SELECT * FROM projects WHERE deleted_at IS NULL')->fetchAll() as $project) {
        $projectNode = $place('project', $project, null);
        $s = $pdo->prepare('SELECT * FROM programs WHERE id=? AND deleted_at IS NULL');
        $s->execute([$project['program_id']]);
        $program = $s->fetch();
        $programNode = $program ? $place('program', $program, $projectNode) : null;
        $componentsGroup = $programNode ? $placeGroup($programNode, 'component_group', 'Components', $program['created_by']) : null;
        $activitiesGroup = $programNode ? $placeGroup($programNode, 'activity_group', 'Activities', $program['created_by']) : null;
        $s = $pdo->prepare('SELECT * FROM components WHERE project_id=? AND deleted_at IS NULL');
        $s->execute([$project['id']]);
        foreach ($s->fetchAll() as $component) {
            if (!$program) throw new RuntimeException('Project '.$project['id'].' has components but no active program. Assign its program before migrating. No records were moved.');
            $componentNode = $place('component', $component, $componentsGroup);
            $a = $pdo->prepare('SELECT * FROM extension_activities WHERE component_id=? AND deleted_at IS NULL');
            $a->execute([$component['id']]);
            foreach ($a->fetchAll() as $activity) $place('activity', $activity, $activitiesGroup);
        }
      }
      $pdo->exec("INSERT INTO explorer_migrations(version) VALUES('20260913_initial_placement')");
    }
    if (!$pdo->query("SELECT version FROM explorer_migrations WHERE version='20260913_program_groups'")->fetchColumn()) {
        $programs = $pdo->query("SELECT * FROM explorer_nodes WHERE kind='program' AND deleted_at IS NULL")->fetchAll();
        foreach ($programs as $programNode) {
            $componentsGroup = $placeGroup($programNode['id'], 'component_group', 'Components', $programNode['created_by']);
            $activitiesGroup = $placeGroup($programNode['id'], 'activity_group', 'Activities', $programNode['created_by']);
            $s = $pdo->prepare("UPDATE explorer_nodes SET parent_id=? WHERE parent_id=? AND kind='component' AND deleted_at IS NULL");
            $s->execute([$componentsGroup, $programNode['id']]);
            $components = $pdo->prepare("SELECT id FROM explorer_nodes WHERE parent_id=? AND kind='component' AND deleted_at IS NULL");
            $components->execute([$componentsGroup]);
            foreach ($components->fetchAll(PDO::FETCH_COLUMN) as $componentNodeId) {
                $moveActivities = $pdo->prepare("UPDATE explorer_nodes SET parent_id=? WHERE parent_id=? AND kind='activity' AND deleted_at IS NULL");
                $moveActivities->execute([$activitiesGroup, $componentNodeId]);
            }
        }
        $pdo->exec("INSERT INTO explorer_migrations(version) VALUES('20260913_program_groups')");
    }
    $pdo->commit();
    echo "Explorer migration complete. Existing business tables, folders, files and uploads were preserved.\n";
} catch (Throwable $e) { $pdo->rollBack(); throw $e; }
