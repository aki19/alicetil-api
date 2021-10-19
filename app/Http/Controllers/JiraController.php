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
        $json_list = array();

        $state = "active,future";
        if ($request->input("release")) {
            $state = "closed,active";
        }

        $util        = new JiraApiUtil();
        $sprint_list = $util->get_sprint_list($state);

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

    public function get_target_issue_list(Request $request) {
        $json_list = array();

        $util      = new JiraApiUtil();
        $task_list = $util->get_target_issue_list($request->sprint_id);
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

}
