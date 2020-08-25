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

    public function get_sprint_list() {
        $json_list = array();

        $util        = new JiraApiUtil();
        $sprint_list = $util->get_sprint_list();
        foreach ($sprint_list as $key => $val) {
            $json_list[] = array("id" => $key, "name" => $val);
        }
        return response()->json($json_list, 200);
    }

    public function get_issue_list(Request $request) {
        $json_list = array();

        $util      = new JiraApiUtil();
        $task_list = $util->get_task_list($request->sprint_id);
        foreach ($task_list as $task) {
            $json_list[] = $task;
        }
        return response()->json($json_list, 200);
    }

}
