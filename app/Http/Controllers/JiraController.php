<?php

namespace App\Http\Controllers;

use App\Libraries\JiraApiUtil;
use Illuminate\Http\Request;

class JiraController extends Controller {

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        //
    }

    public function get_sprint_list(Request $request) {
        echo "get_sprint_list".PHP_EOL;

        $json_list = array();

        $state = "active,future";
        if ($request->input("release")) {
            $state = "closed,active";
        }

        $util        = new JiraApiUtil();
        $sprint_list = $util->get_sprint_list(__JIRA_CBTS_BOARD_ID, $state);

        uasort($sprint_list, function ($x, $y) {
            $key = 'endDate';
            if ($x[$key] == $y[$key]) {
                return 0;
            } else if ($x[$key] < $y[$key]) {
                return -1;
            } else {
                return 1;
            }
        });

        if ($request->input("release")) {
            foreach ($sprint_list as $key => $val) {
                $json_list[] = array("id" => $key, "name" => $val["endDate"], "state" => $val["state"]);
            }
        } else {
            foreach ($sprint_list as $key => $val) {
                $json_list[] = array("id" => $key, "name" => $val["name"]);
            }
        }
        return response()->json($json_list, 200);
    }

    public function get_sprint_issue_list(Request $request) {
        $json_list = array();

        $util      = new JiraApiUtil();
        $task_list = $util->get_sprint_issue_list($request->sprint_id);
        foreach ($task_list as $task) {
            $json_list[] = $task;
            if (isset($task["subtasks"])) {
                foreach ($task["subtasks"] as $subtask) {
                    $subtask["name"] = "→ " . $subtask["name"];
                    $json_list[]     = $subtask;
                }
            }
        }

        $sort = array();
        foreach ($json_list as $key => $value) {
            $sort[$key] = isset($value['parent_key']) ? $value['parent_key'] : 0;
        }
        array_multisort($sort, SORT_ASC, $json_list);
        return response()->json($json_list, 200);
    }

    public function get_child_issue_list(Request $request) {
        $json_list = array();

        $util      = new JiraApiUtil();
        $task_list = $util->get_child_issue_list($request->parent_key);
        foreach ($task_list as $task) {
            $json_list[] = $task;
        }
        return response()->json($json_list, 200);
    }

    public function get_active_issue_list(Request $request) {
        $json_list = array();

        $util      = new JiraApiUtil();
        $task_list = $util->get_active_issue_list($request->assignee);
        if ($request->json_decode) {
            foreach ($task_list as $task) {
                $json_list[] = $task;
            }
            return response()->json($json_list, 200);
        } else {
            foreach ($task_list as $task) {
                $json_list[] = $task["key"] . "：" . $task["name"];
            }
            return response(implode(PHP_EOL, $json_list));
        }

    }

    public function get_timeline_list() {
        $util      = new JiraApiUtil();
        $json_list = array_merge($this->get_timeline_list_by_board(__JIRA_CBTS_BOARD_ID), $this->get_timeline_list_by_board(__JIRA_CBTS_OLD_BOARD_ID));
        return response()->json($json_list, 200);
    }

    private function get_timeline_list_by_board($board_id) {
        $util        = new JiraApiUtil();
        $sprint_list = $util->get_sprint_list($board_id, "closed,active");

        uasort($sprint_list, function ($x, $y) {
            $key = 'endDate';
            if ($x[$key] == $y[$key]) {
                return 0;
            } else if ($x[$key] > $y[$key]) {
                return -1;
            } else {
                return 1;
            }
        });

        $sprint_task_list = array();
        foreach ($util->get_target_issue_list($board_id, array_keys($sprint_list)) as $key => $val) {
            $sprint_task_list[$val["sprint_id"]][$key] = $val;
        }

        $timeline_list = array();
        foreach ($sprint_list as $key => $val) {
            $task_list = array();
            if (isset($sprint_task_list[$key])) {
                $task_list = $sprint_task_list[$key];
            }
            $timeline_list[] = array("id" => $key, "name" => $val["endDate"], "state" => $val["state"], "issues" => $task_list);
        }
        return $timeline_list;
    }

}
